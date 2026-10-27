<?php
/*
 * Las Tapas - reservering_api.php
 * JSON-API voor reserveringen.
 *  Gasten:  config, beschikbaar, reserveer, mijn, wijzig, annuleer (met de geheime code uit de e-mail)
 *  Personeel (ingelogd): lijst, vandaag, status, staf_wijzig, staf_annuleer
 */
session_start();
require __DIR__ . '/reservering_functies.php';
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');

function resStuur($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}
function resFout($melding) {
    resStuur(['succes' => false, 'melding' => $melding]);
}

$rol = $_SESSION['rol'] ?? '';
$isPersoneel = in_array($rol, ['chef', 'bar', 'serveerster', 'baas'], true);
$medewerker = $_SESSION['gebruiker'] ?? 'Personeel';
function vereisPersoneel() {
    global $isPersoneel;
    if (!$isPersoneel) {
        http_response_code(403);
        resFout('Geen toegang: log in als medewerker.');
    }
}
// Eerste dag (vanaf vandaag) waarop nog een starttijd vrij te kiezen is, bijv. vandaag na sluitingstijd: de volgende open dag
function eersteReserveerDag($inst) {
    for ($i = 0; $i <= $inst['dagen']; $i++) {
        $d = date('Y-m-d', strtotime("+$i days"));
        foreach (reserveerTijden($d) as $t) if (controleerReserveerMoment($d, $t) === '') return $d;
    }
    return date('Y-m-d');
}

function zoekOpCode($code) {
    $code = preg_replace('/[^a-f0-9]/', '', strtolower((string)$code));
    if (strlen($code) < 20) return null;
    return q("SELECT * FROM reserveringen WHERE code = ?", "s", [$code])->fetch_assoc() ?: null;
}

try {
    db();
    $methode = $_SERVER['REQUEST_METHOD'];
    $input = $methode === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_GET;
    $actie = $input['actie'] ?? '';
    $inst = reserveerInstellingen();
    ruimGeannuleerdeReserveringenOp();

    // ---------- Instellingen en plattegrond ----------
    if ($actie === 'config') {
        $tafels = [];
        foreach (TAFEL_INDELING as $nr => $t) $tafels[] = ['nr' => $nr] + $t;
        resStuur([
            'aan' => $inst['aan'],
            'tijden' => reserveerTijden(),
            'openingstijden' => openingstijden(),
            'eerste_dag' => eersteReserveerDag($inst),
            'duur' => $inst['duur'],
            'vandaag' => date('Y-m-d'),
            'tot' => date('Y-m-d', strtotime('+' . $inst['dagen'] . ' days')),
            'tafels' => $tafels,
            'max_personen' => $inst['max_personen'],
            'max_aan_tafel' => max(array_column(TAFEL_INDELING, 'plaatsen')),
        ]);
    }

    // ---------- Welke tijden en tafels zijn vrij? ----------
    if ($actie === 'beschikbaar') {
        $datum = (string)($input['datum'] ?? '');
        $personen = max(1, intval($input['personen'] ?? 2));
        $negeer = 0;
        if (!empty($input['wijzig'])) {
            $r = zoekOpCode($input['wijzig']);
            if ($r) $negeer = intval($r['id']);
        }
        if ($isPersoneel && !empty($input['negeer_id'])) $negeer = intval($input['negeer_id']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) resFout('Kies een datum.');

        // Per tijd: hoeveel passende tafels zijn er nog vrij?
        $tijden = [];
        foreach (reserveerTijden($datum) as $tijd) {
            $voorbij = controleerReserveerMoment($datum, $tijd) !== '';
            $vrij = 0;
            $past = false;
            if (!$voorbij) {
                $st = tafelStatus($datum, $tijd, $personen, $negeer);
                foreach ($st as $x) if ($x === 'vrij') $vrij++;
                $past = groepPastErgens($st, $personen);
            }
            // 'vrij' = aantal vrije tafels; is er geen plek voor de hele groep naast elkaar, dan telt het als vol
            $tijden[] = ['tijd' => $tijd, 'vrij' => $past ? $vrij : 0, 'voorbij' => $voorbij];
        }
        $uit = ['succes' => true, 'tijden' => $tijden];
        $tijd = (string)($input['tijd'] ?? '');
        $uit['gesloten'] = !reserveerTijden($datum);
        if ($tijd !== '' && in_array($tijd, reserveerTijden($datum), true)) {
            $uit['tafels'] = tafelStatus($datum, $tijd, $personen, $negeer);
        }
        resStuur($uit);
    }

    // ---------- Nieuwe reservering (gast) ----------
    if ($actie === 'reserveer') {
        if ($methode !== 'POST') resFout('Ongeldige aanvraag.');
        if (!$inst['aan']) resFout('Online reserveren is op dit moment niet mogelijk. Bel ons gerust.');
        // Tegen misbruik: maximaal 5 reserveringen per uur vanaf hetzelfde apparaat
        $sleutel = 'reserveer-' . ($_SERVER['REMOTE_ADDR'] ?? '');
        $n = intval(q("SELECT COUNT(*) AS n FROM login_pogingen WHERE gebruikersnaam = ? AND tijd > (NOW() - INTERVAL 1 HOUR)", "s", [$sleutel])->fetch_assoc()['n']);
        if ($n >= 5 && !$isPersoneel) resFout('Je hebt al een paar reserveringen gemaakt. Probeer het later opnieuw of bel ons.');

        [$gast, $fout] = controleerGastGegevens($input);
        if (!$gast) resFout($fout);
        $personen = intval($input['personen'] ?? 0);
        $tafels = tafelsUitInvoer($input);
        [$start, $eind, $fout] = controleerReserveerKeuze((string)($input['datum'] ?? ''), (string)($input['tijd'] ?? ''), $personen, $tafels, 0, $isPersoneel);
        if ($fout) resFout($fout);
        $tafel = $tafels[0];
        $extra = implode(',', array_slice($tafels, 1));

        $code = bin2hex(random_bytes(16));
        $taal = resTaal((string)($input['taal'] ?? 'nl'));
        q("INSERT INTO reserveringen (code, naam, email, telefoon, personen, tafel, extra_tafels, start_tijd, eind_tijd, opmerking, status, taal, gemaakt_op)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'gepland', ?, NOW())",
          "ssssiisssss", [$code, $gast['naam'], $gast['email'], $gast['telefoon'], $personen, $tafel, $extra, $start, $eind, $gast['opmerking'], $taal]);
        $r = q("SELECT * FROM reserveringen WHERE id = ?", "i", [db()->insert_id])->fetch_assoc();
        q("INSERT INTO login_pogingen (gebruikersnaam, ip, tijd) VALUES (?, ?, NOW())", "ss", [$sleutel, $_SERVER['REMOTE_ADDR'] ?? '']);

        [$gelukt, $melding] = stuurReserveringMail($r, 'bevestigd');
        resStuur(['succes' => true, 'code' => $code, 'reservering' => resVoorScherm($r),
                  'mail' => ['gelukt' => $gelukt, 'melding' => $melding]]);
    }

    // ---------- Eigen reservering bekijken (gast, via de link) ----------
    if ($actie === 'mijn') {
        $r = zoekOpCode($input['code'] ?? '');
        if (!$r) resFout('Deze reservering kunnen we niet vinden. Controleer de link uit je e-mail. Heb je geannuleerd? Geannuleerde reserveringen worden na 15 minuten verwijderd.');
        $scherm = resVoorScherm($r);
        $scherm['email'] = $r['email'];
        $scherm['telefoon'] = $r['telefoon'];
        resStuur(['succes' => true, 'reservering' => $scherm]);
    }

    // ---------- Verplaatsen of annuleren (gast) ----------
    if ($actie === 'wijzig' || $actie === 'annuleer') {
        if ($methode !== 'POST') resFout('Ongeldige aanvraag.');
        $r = zoekOpCode($input['code'] ?? '');
        if (!$r) resFout('Deze reservering kunnen we niet vinden.');
        if ($r['status'] !== 'gepland') resFout('Deze reservering kan niet meer worden aangepast.');
        if (strtotime($r['start_tijd']) < time()) resFout('Deze reservering is al begonnen of voorbij. Bel ons als je iets wilt veranderen.');

        if ($actie === 'annuleer') {
            q("UPDATE reserveringen SET status = 'geannuleerd', gewijzigd_op = NOW(), gewijzigd_door = 'Gast' WHERE id = ?", "i", [intval($r['id'])]);
            $r['status'] = 'geannuleerd';
            [$gelukt, $melding] = stuurReserveringMail($r, 'geannuleerd');
            resStuur(['succes' => true, 'mail' => ['gelukt' => $gelukt, 'melding' => $melding]]);
        }

        $personen = intval($input['personen'] ?? $r['personen']);
        $tafels = tafelsUitInvoer($input);
        [$start, $eind, $fout] = controleerReserveerKeuze((string)($input['datum'] ?? ''), (string)($input['tijd'] ?? ''), $personen, $tafels, intval($r['id']));
        if ($fout) resFout($fout);
        q("UPDATE reserveringen SET start_tijd = ?, eind_tijd = ?, personen = ?, tafel = ?, extra_tafels = ?, gewijzigd_op = NOW(), gewijzigd_door = 'Gast' WHERE id = ?",
          "ssiisi", [$start, $eind, $personen, $tafels[0], implode(',', array_slice($tafels, 1)), intval($r['id'])]);
        $r = q("SELECT * FROM reserveringen WHERE id = ?", "i", [intval($r['id'])])->fetch_assoc();
        [$gelukt, $melding] = stuurReserveringMail($r, 'gewijzigd');
        resStuur(['succes' => true, 'code' => $r['code'], 'reservering' => resVoorScherm($r), 'mail' => ['gelukt' => $gelukt, 'melding' => $melding]]);
    }

    // ===================== PERSONEEL =====================

    // Alle reserveringen van een dag
    if ($actie === 'lijst') {
        vereisPersoneel();
        $datum = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($input['datum'] ?? '')) ? $input['datum'] : date('Y-m-d');
        $rijen = alleRijen(q("SELECT * FROM reserveringen WHERE start_tijd >= ? AND start_tijd < ? ORDER BY start_tijd, tafel",
                             "ss", ["$datum 00:00:00", date('Y-m-d', strtotime("$datum +1 day")) . ' 00:00:00']));
        resStuur(['succes' => true, 'datum' => $datum, 'reserveringen' => array_map(fn($r) => resVoorScherm($r, true), $rijen),
                  'tijden' => reserveerTijden($datum), 'duur' => $inst['duur']]);
    }

    // Zoeken op naam, telefoonnummer of reserveringsnummer (vanaf 7 dagen terug)
    if ($actie === 'zoek') {
        vereisPersoneel();
        $term = trim((string)($input['q'] ?? ''));
        if (mb_strlen($term) < 2) resStuur(['succes' => true, 'reserveringen' => []]);
        $cijfers = preg_replace('/\D/', '', $term);
        $nummer = preg_match('/^r?-?0*(\d+)$/i', $term, $m) ? intval($m[1]) : 0;
        $rijen = alleRijen(q("SELECT * FROM reserveringen WHERE start_tijd >= ? AND (naam LIKE ? OR email LIKE ? OR (? <> '' AND REPLACE(REPLACE(REPLACE(telefoon, ' ', ''), '-', ''), '+', '') LIKE ?) OR id = ?)
                              ORDER BY start_tijd LIMIT 50",
                             "sssssi", [date('Y-m-d', strtotime('-7 days')) . ' 00:00:00', "%$term%", "%$term%", $cijfers, '%' . $cijfers . '%', $nummer]));
        resStuur(['succes' => true, 'reserveringen' => array_map(fn($r) => resVoorScherm($r, true), $rijen)]);
    }

    // Komende reserveringen: per dag hoeveel er zijn (vanaf de dag na de bekeken datum, 14 dagen vooruit)
    if ($actie === 'aankomend') {
        vereisPersoneel();
        $vanaf = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($input['datum'] ?? '')) ? $input['datum'] : date('Y-m-d');
        $begin = date('Y-m-d', strtotime("$vanaf +1 day"));
        // Nooit reserveringen uit het verleden: bekijk je een oude dag, dan begint het lijstje bij vandaag
        if ($begin < date('Y-m-d')) $begin = date('Y-m-d');
        $eind = date('Y-m-d', strtotime("$begin +14 days"));
        $dagen = [];
        foreach (alleRijen(q("SELECT start_tijd, personen FROM reserveringen
                              WHERE status IN ('gepland', 'aangekomen') AND start_tijd >= ? AND start_tijd < ? ORDER BY start_tijd",
                             "ss", ["$begin 00:00:00", "$eind 00:00:00"])) as $r) {
            $dag = substr($r['start_tijd'], 0, 10);
            if (!isset($dagen[$dag])) $dagen[$dag] = ['datum' => $dag, 'aantal' => 0, 'gasten' => 0, 'eerste' => date('H:i', strtotime($r['start_tijd']))];
            $dagen[$dag]['aantal']++;
            $dagen[$dag]['gasten'] += intval($r['personen']);
        }
        resStuur(['succes' => true, 'van' => $begin, 'tot' => date('Y-m-d', strtotime("$eind -1 day")), 'dagen' => array_values($dagen)]);
    }

    // Reserveringen van vandaag die nog komen of bezig zijn (voor het serveerscherm)
    if ($actie === 'vandaag') {
        vereisPersoneel();
        $rijen = alleRijen(q("SELECT * FROM reserveringen WHERE status IN ('gepland', 'aangekomen')
                              AND start_tijd >= ? AND start_tijd < ? AND eind_tijd > NOW() ORDER BY start_tijd",
                             "ss", [date('Y-m-d') . ' 00:00:00', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00']));
        resStuur(['succes' => true, 'reserveringen' => array_map(fn($r) => resVoorScherm($r, true), $rijen)]);
    }

    if (in_array($actie, ['status', 'staf_wijzig', 'staf_annuleer'], true)) {
        vereisPersoneel();
        if ($methode !== 'POST') resFout('Ongeldige aanvraag.');
        $r = q("SELECT * FROM reserveringen WHERE id = ?", "i", [intval($input['id'] ?? 0)])->fetch_assoc();
        if (!$r) resFout('Reservering niet gevonden.');

        if ($actie === 'status') {
            $nieuw = (string)($input['status'] ?? '');
            if (!in_array($nieuw, ['gepland', 'aangekomen', 'niet_gekomen'], true)) resFout('Ongeldige status.');
            q("UPDATE reserveringen SET status = ?, aangekomen_op = IF(? = 'aangekomen', NOW(), NULL), gewijzigd_op = NOW(), gewijzigd_door = ? WHERE id = ?",
              "sssi", [$nieuw, $nieuw, $medewerker, intval($r['id'])]);
            resStuur(['succes' => true]);
        }

        if ($actie === 'staf_annuleer') {
            q("UPDATE reserveringen SET status = 'geannuleerd', gewijzigd_op = NOW(), gewijzigd_door = ? WHERE id = ?", "si", [$medewerker, intval($r['id'])]);
            $r['status'] = 'geannuleerd';
            $mail = ['gelukt' => false, 'melding' => ''];
            if (!empty($input['mail'])) {
                [$gelukt, $melding] = stuurReserveringMail($r, 'geannuleerd');
                $mail = ['gelukt' => $gelukt, 'melding' => $melding];
            }
            resStuur(['succes' => true, 'mail' => $mail]);
        }

        // Verplaatsen door personeel: de gast krijgt een e-mail met de nieuwe gegevens
        // (personeel mag ook een tijdstip van vandaag kiezen dat al bijna begint, en grotere groepen invoeren)
        $personen = intval($input['personen'] ?? $r['personen']);
        $tafels = tafelsUitInvoer($input);
        [$start, $eind, $fout] = controleerReserveerKeuze((string)($input['datum'] ?? ''), (string)($input['tijd'] ?? ''), $personen, $tafels, intval($r['id']), true);
        if ($fout) resFout($fout);
        q("UPDATE reserveringen SET start_tijd = ?, eind_tijd = ?, personen = ?, tafel = ?, extra_tafels = ?, status = 'gepland', gewijzigd_op = NOW(), gewijzigd_door = ? WHERE id = ?",
          "ssiissi", [$start, $eind, $personen, $tafels[0], implode(',', array_slice($tafels, 1)), $medewerker, intval($r['id'])]);
        $r = q("SELECT * FROM reserveringen WHERE id = ?", "i", [intval($r['id'])])->fetch_assoc();
        [$gelukt, $melding] = stuurReserveringMail($r, 'gewijzigd');
        resStuur(['succes' => true, 'mail' => ['gelukt' => $gelukt, 'melding' => $melding]]);
    }

    resFout('Onbekende actie.');
} catch (Throwable $e) {
    http_response_code(500);
    resFout('Er ging iets mis op de server. Probeer het opnieuw.');
}
