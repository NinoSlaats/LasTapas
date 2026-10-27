<?php
/*
 * Las Tapas - reservering_functies.php
 * Gedeelde functies voor reserveringen: gegevens netjes maken en e-mails sturen
 * (bevestiging, wijziging, annulering) in de taal van de gast.
 */
require_once __DIR__ . '/gedeeld.php';
require_once __DIR__ . '/mailer.php';

const RES_TEKSTEN = [
    'nl' => [
        'dagen' => ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        'maanden' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        'onderwerp_bevestigd' => 'Je reservering bij Las Tapas is bevestigd',
        'onderwerp_gewijzigd' => 'Je reservering bij Las Tapas is gewijzigd',
        'onderwerp_geannuleerd' => 'Je reservering bij Las Tapas is geannuleerd',
        'kop_bevestigd' => '¡Hasta pronto! Je reservering is bevestigd',
        'kop_gewijzigd' => 'Je reservering is gewijzigd',
        'kop_geannuleerd' => 'Je reservering is geannuleerd',
        'hallo' => 'Hallo %s,',
        'tekst_bevestigd' => 'Bedankt voor je reservering. We kijken ernaar uit je te verwelkomen!',
        'tekst_gewijzigd' => 'Je reservering is aangepast. Hieronder staan de nieuwe gegevens.',
        'tekst_geannuleerd' => 'Je reservering is geannuleerd. Jammer dat je niet kunt komen; je bent altijd welkom!',
        'nummer' => 'Reserveringsnummer', 'datum' => 'Datum', 'tijd' => 'Tijd', 'personen' => 'Personen',
        'tafel' => 'Tafel', 'etage' => [1 => 'begane grond', 2 => 'eerste verdieping'], 'opmerking' => 'Opmerking',
        'knop' => 'Bekijken, verplaatsen of annuleren',
        'aankomst' => 'Noem bij aankomst je naam of je reserveringsnummer.',
        'opnieuw' => 'Opnieuw reserveren',
        'voet' => 'Las Tapas · Taberna española',
    ],
    'en' => [
        'dagen' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'maanden' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'onderwerp_bevestigd' => 'Your reservation at Las Tapas is confirmed',
        'onderwerp_gewijzigd' => 'Your reservation at Las Tapas has been changed',
        'onderwerp_geannuleerd' => 'Your reservation at Las Tapas has been cancelled',
        'kop_bevestigd' => '¡Hasta pronto! Your reservation is confirmed',
        'kop_gewijzigd' => 'Your reservation has been changed',
        'kop_geannuleerd' => 'Your reservation has been cancelled',
        'hallo' => 'Hello %s,',
        'tekst_bevestigd' => 'Thank you for your reservation. We look forward to welcoming you!',
        'tekst_gewijzigd' => 'Your reservation has been changed. The new details are below.',
        'tekst_geannuleerd' => 'Your reservation has been cancelled. Sorry you cannot make it; you are always welcome!',
        'nummer' => 'Reservation number', 'datum' => 'Date', 'tijd' => 'Time', 'personen' => 'Guests',
        'tafel' => 'Table', 'etage' => [1 => 'ground floor', 2 => 'first floor'], 'opmerking' => 'Note',
        'knop' => 'View, change or cancel',
        'aankomst' => 'When you arrive, just give your name or reservation number.',
        'opnieuw' => 'Book again',
        'voet' => 'Las Tapas · Taberna española',
    ],
    'es' => [
        'dagen' => ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
        'maanden' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
        'onderwerp_bevestigd' => 'Tu reserva en Las Tapas está confirmada',
        'onderwerp_gewijzigd' => 'Tu reserva en Las Tapas ha cambiado',
        'onderwerp_geannuleerd' => 'Tu reserva en Las Tapas ha sido cancelada',
        'kop_bevestigd' => '¡Hasta pronto! Tu reserva está confirmada',
        'kop_gewijzigd' => 'Tu reserva ha cambiado',
        'kop_geannuleerd' => 'Tu reserva ha sido cancelada',
        'hallo' => 'Hola %s:',
        'tekst_bevestigd' => 'Gracias por tu reserva. ¡Tenemos muchas ganas de recibirte!',
        'tekst_gewijzigd' => 'Tu reserva se ha modificado. Abajo tienes los nuevos datos.',
        'tekst_geannuleerd' => 'Tu reserva ha sido cancelada. Qué pena que no puedas venir; ¡siempre serás bienvenido/a!',
        'nummer' => 'Número de reserva', 'datum' => 'Fecha', 'tijd' => 'Hora', 'personen' => 'Personas',
        'tafel' => 'Mesa', 'etage' => [1 => 'planta baja', 2 => 'primera planta'], 'opmerking' => 'Nota',
        'knop' => 'Ver, cambiar o cancelar',
        'aankomst' => 'Al llegar, di tu nombre o tu número de reserva.',
        'opnieuw' => 'Reservar de nuevo',
        'voet' => 'Las Tapas · Taberna española',
    ],
];

function resTaal($taal) {
    return isset(RES_TEKSTEN[$taal]) ? $taal : 'nl';
}

// "vrijdag 3 oktober" / "Friday 3 October" / "viernes 3 de octubre"
function resDatumTekst($datumTijd, $taal) {
    $tk = RES_TEKSTEN[resTaal($taal)];
    $ts = strtotime($datumTijd);
    $dag = $tk['dagen'][intval(date('w', $ts))];
    $maand = $tk['maanden'][intval(date('n', $ts)) - 1];
    return $taal === 'es' ? "$dag " . date('j', $ts) . " de $maand" : "$dag " . date('j', $ts) . " $maand";
}

function resNummer($r) {
    return 'R-' . str_pad((string)intval($r['id']), 4, '0', STR_PAD_LEFT);
}

// Reservering zoals de pagina's hem krijgen (zonder interne velden)
function resVoorScherm($r, $metContact = false) {
    $tafel = intval($r['tafel']);
    $uit = [
        'id' => intval($r['id']),
        'nummer' => resNummer($r),
        'datum' => date('Y-m-d', strtotime($r['start_tijd'])),
        'tijd' => date('H:i', strtotime($r['start_tijd'])),
        'tot' => date('H:i', strtotime($r['eind_tijd'])),
        'personen' => intval($r['personen']),
        'tafel' => $tafel,
        'etage' => TAFEL_INDELING[$tafel]['etage'] ?? 1,
        'plaatsen' => array_sum(array_map(fn($t) => TAFEL_INDELING[$t]['plaatsen'] ?? 0, resTafels($r))),
        'plek' => TAFEL_INDELING[$tafel]['plek'] ?? '',
        'tafels' => resTafels($r),
        'tafel_tekst' => implode(' + ', resTafels($r)),
        'status' => $r['status'],
        'naam' => $r['naam'],
        'opmerking' => $r['opmerking'],
        'voorbij' => strtotime($r['start_tijd']) < time(),
    ];
    if ($metContact) {
        $uit += ['email' => $r['email'], 'telefoon' => $r['telefoon'], 'gewijzigd_door' => $r['gewijzigd_door'],
                 'aangekomen_op' => $r['aangekomen_op'] ? date('H:i', strtotime($r['aangekomen_op'])) : null];
    }
    return $uit;
}

// E-mail naar de gast: $soort = 'bevestigd', 'gewijzigd' of 'geannuleerd'. Geeft [gelukt, melding] terug.
function stuurReserveringMail($r, $soort) {
    $taal = resTaal($r['taal'] ?? 'nl');
    $tk = RES_TEKSTEN[$taal];
    $link = siteAdres() . 'mijn_reservering.php?code=' . urlencode($r['code']);
    $opnieuw = siteAdres() . 'reserveren.php';
    $tafel = intval($r['tafel']);
    $etage = $tk['etage'][TAFEL_INDELING[$tafel]['etage'] ?? 1];

    $rijen = [
        [$tk['nummer'], resNummer($r)],
        [$tk['datum'], resDatumTekst($r['start_tijd'], $taal)],
        [$tk['tijd'], date('H:i', strtotime($r['start_tijd'])) . ' – ' . date('H:i', strtotime($r['eind_tijd']))],
        [$tk['personen'], (string)intval($r['personen'])],
        [$tk['tafel'], implode(' + ', resTafels($r)) . " ($etage)"],
    ];
    if ($r['opmerking'] !== '') $rijen[] = [$tk['opmerking'], $r['opmerking']];

    $html = '<div style="background:#fbf5ea;padding:20px 10px;font-family:Segoe UI,Arial,sans-serif;color:#2b1d19;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:460px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;">'
        . '<tr><td style="background:#a8232b;color:#fff;text-align:center;padding:22px 16px 16px;">'
        . '<div style="font-family:Georgia,serif;font-size:26px;font-weight:bold;">🍷 Las Tapas</div>'
        . '<div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;opacity:.85;margin-top:4px;">Taberna española</div></td></tr>'
        . '<tr><td style="height:8px;background:#1f4e8c;background-image:repeating-linear-gradient(90deg,#1f4e8c 0 8px,#fdf6e3 8px 16px);"></td></tr>'
        . '<tr><td style="padding:20px 22px 6px;">'
        . '<div style="font-family:Georgia,serif;font-size:20px;color:#a8232b;font-weight:bold;margin-bottom:10px;">' . esc($tk['kop_' . $soort]) . '</div>'
        . '<p style="margin:0 0 6px;">' . esc(sprintf($tk['hallo'], $r['naam'])) . '</p>'
        . '<p style="margin:0 0 14px;">' . esc($tk['tekst_' . $soort]) . '</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;' . ($soort === 'geannuleerd' ? 'text-decoration:line-through;color:#786a63;' : '') . '">';
    foreach ($rijen as [$label, $waarde]) {
        $html .= '<tr><td style="padding:5px 0;color:#786a63;border-bottom:1px solid #f0e6da;">' . esc($label) . '</td>'
               . '<td style="padding:5px 0;text-align:right;font-weight:bold;border-bottom:1px solid #f0e6da;">' . esc($waarde) . '</td></tr>';
    }
    $html .= '</table></td></tr><tr><td style="padding:18px 22px 6px;text-align:center;">';
    if ($soort === 'geannuleerd') {
        $html .= '<a href="' . esc($opnieuw) . '" style="display:inline-block;background:#5f6f2f;color:#fff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">' . esc($tk['opnieuw']) . '</a>';
    } else {
        $html .= '<a href="' . esc($link) . '" style="display:inline-block;background:#a8232b;color:#fff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">' . esc($tk['knop']) . '</a>'
               . '<p style="font-size:12px;color:#786a63;margin:12px 0 0;">' . esc($tk['aankomst']) . '</p>';
    }
    $html .= '</td></tr><tr><td style="padding:16px 22px 22px;text-align:center;font-size:12px;color:#786a63;">' . esc($tk['voet']) . '</td></tr></table></div>';

    $tekst = $tk['kop_' . $soort] . "\n\n" . sprintf($tk['hallo'], $r['naam']) . "\n" . $tk['tekst_' . $soort] . "\n\n";
    foreach ($rijen as [$label, $waarde]) $tekst .= "$label: $waarde\n";
    $tekst .= "\n" . ($soort === 'geannuleerd' ? $tk['opnieuw'] . ": $opnieuw" : $tk['knop'] . ": $link\n" . $tk['aankomst']) . "\n";

    return stuurMail($r['email'], $tk['onderwerp_' . $soort] . ' (' . resNummer($r) . ')', $html, $tekst);
}

// Gegevens van de gast controleren. Geeft [gegevens, ''] of [null, foutmelding]
function controleerGastGegevens($input) {
    $naam = mb_substr(trim((string)($input['naam'] ?? '')), 0, 100);
    $email = trim((string)($input['email'] ?? ''));
    $telefoon = trim((string)($input['telefoon'] ?? ''));
    if (mb_strlen($naam) < 2) return [null, 'Vul je naam in.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) return [null, 'Vul een geldig e-mailadres in.'];
    $cijfers = preg_replace('/\D/', '', $telefoon);
    if (!preg_match('/^[0-9+\-\s()]{8,30}$/', $telefoon) || strlen($cijfers) < 8 || strlen($cijfers) > 15) {
        return [null, 'Vul een geldig telefoonnummer in (bijv. 06 12345678).'];
    }
    return [['naam' => $naam, 'email' => $email, 'telefoon' => $telefoon,
             'opmerking' => mb_substr(trim((string)($input['opmerking'] ?? '')), 0, 255)], ''];
}

// Tafels uit de invoer halen: 'tafels' => [5, 6] (groep) of 'tafel' => 5
function tafelsUitInvoer($input) {
    $lijst = isset($input['tafels']) && is_array($input['tafels']) ? $input['tafels'] : [$input['tafel'] ?? 0];
    $lijst = array_values(array_unique(array_filter(array_map('intval', $lijst), fn($t) => $t > 0)));
    sort($lijst);
    return $lijst;
}

// Klopt deze set tafels voor dit aantal personen? Geeft '' of een foutmelding
function controleerTafelKeuze(array $tafels, $personen) {
    if (!$tafels) return 'Kies een tafel op de plattegrond.';
    foreach ($tafels as $t) if (!isset(TAFEL_INDELING[$t])) return 'Kies een tafel op de plattegrond.';
    $plaatsen = array_sum(array_map(fn($t) => TAFEL_INDELING[$t]['plaatsen'], $tafels));
    $nodig = tafelsNodig($personen);
    if ($plaatsen < $personen) {
        return count($tafels) === 1 && $nodig === 1
            ? 'Deze tafel is te klein voor jullie gezelschap. Kies een andere tafel.'
            : "Met $personen personen hebben jullie $nodig tafels nodig. Kies er nog " . ($nodig - count($tafels)) . " naast de gekozen tafel.";
    }
    if (count($tafels) > $nodig) return "Met $personen personen zijn $nodig tafels genoeg. Haal een tafel weg.";
    if (!tafelsAaneengesloten($tafels)) return 'Kies tafels die direct naast of achter elkaar staan, dan kunnen we ze aanschuiven.';
    return '';
}

// Datum, tijd, personen en tafel(s) controleren (ook of ze vrij zijn). Geeft [start, eind, ''] of [null, null, fout]
// Personeel mag ($personeel = true) ook vlak voor het begin reserveren en grotere groepen invoeren.
function controleerReserveerKeuze($datum, $tijd, $personen, $tafels, $negeerId = 0, $personeel = false) {
    $tafels = is_array($tafels) ? $tafels : [intval($tafels)];
    if ($personeel) {
        $fout = (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) || !in_array($tijd, reserveerTijden($datum), true)) ? 'Kies een geldige datum en tijd (binnen de openingstijden).' : '';
    } else {
        $fout = controleerReserveerMoment($datum, $tijd);
    }
    if ($fout) return [null, null, $fout];
    $max = $personeel ? array_sum(array_column(TAFEL_INDELING, 'plaatsen')) : reserveerInstellingen()['max_personen'];
    if ($personen < 1 || $personen > $max) return [null, null, "Online reserveren kan voor 1 tot $max personen. Bel ons voor een grotere groep."];
    $fout = controleerTafelKeuze($tafels, $personen);
    if ($fout) return [null, null, $fout];
    $status = tafelStatus($datum, $tijd, $personen, $negeerId);
    foreach ($tafels as $t) {
        if ($status[$t] !== 'vrij') return [null, null, count($tafels) > 1
            ? "Tafel $t is net gereserveerd door iemand anders. Kies andere tafels of een andere tijd."
            : 'Deze tafel is net gereserveerd door iemand anders. Kies een andere tafel of tijd.'];
    }
    $start = "$datum $tijd:00";
    $eind = date('Y-m-d H:i:s', strtotime($start) + reserveerInstellingen()['duur'] * 60);
    return [$start, $eind, ''];
}
