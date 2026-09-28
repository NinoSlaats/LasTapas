<?php
/*
 * Las Tapas - menu_beheer.php
 * De baas beheert hier het menu (met allergenen en drankprijzen), de arrangementen
 * en de algemene instellingen. Alles wordt direct gebruikt door de klantpagina.
 */
session_start();
require __DIR__ . '/gedeeld.php';
require_once __DIR__ . '/mailer.php';
vereisBaas();
set_exception_handler('toonFoutPagina');
db();

// Unieke, korte code voor een nieuw menu-item (bijv. "Patatas Bravas" -> "patatas-bravas")
function maakMenuId($naam) {
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $naam);
    $s = strtolower($s !== false ? $s : $naam);
    $s = trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    $s = substr($s !== '' ? $s : 'item', 0, 30);
    $basis = $s;
    $i = 2;
    while (q("SELECT 1 FROM menu WHERE id = ?", "s", [$s])->fetch_assoc()) {
        $s = $basis . '-' . $i++;
    }
    return $s;
}

// Na opslaan terug naar hetzelfde tabblad en hetzelfde gerecht/arrangement
function terug($ok, $sectie, $kies = '') {
    header('Location: menu_beheer.php?ok=' . urlencode($ok) . '&sectie=' . urlencode($sectie) . '&kies=' . urlencode($kies));
    exit();
}

$fout = null;

// Antwoord voor de foto-knoppen (die werken zonder de pagina te herladen)
function fotoAntwoord($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}
$isFotoAanvraag = ($_SERVER['HTTP_X_FOTO'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Is de upload groter dan PHP toestaat? Dan komt er helemaal niets binnen.
    if (empty($_POST) && empty($_FILES) && intval($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $melding = 'De foto is te groot om te uploaden. Kies een kleinere foto.';
        if ($isFotoAanvraag) fotoAntwoord(['succes' => false, 'melding' => $melding]);
        $_SESSION['flash'] = ['fout', $melding];
        terug('', 'nieuw');
    }
    if ($isFotoAanvraag && !(isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']))) {
        fotoAntwoord(['succes' => false, 'melding' => 'Je sessie is verlopen. Ververs de pagina en probeer het opnieuw.']);
    }
    csrfControle();
    $actie = $_POST['actie'] ?? '';

    // ----- Foto van een gerecht toevoegen of vervangen -----
    if ($actie === 'foto_upload') {
        $id = $_POST['id'] ?? '';
        $gerecht = q("SELECT id, foto FROM menu WHERE id = ?", "s", [$id])->fetch_assoc();
        if (!$gerecht) fotoAntwoord(['succes' => false, 'melding' => 'Dit gerecht bestaat niet meer.']);
        [$gelukt, $resultaat] = verwerkFotoUpload($_FILES['foto'] ?? null, $id);
        if (!$gelukt) fotoAntwoord(['succes' => false, 'melding' => $resultaat]);
        q("UPDATE menu SET foto = ? WHERE id = ?", "ss", [$resultaat, $id]);
        verwijderFotoBestand($gerecht['foto']);   // oude foto opruimen
        fotoAntwoord(['succes' => true, 'foto' => $resultaat]);
    }

    // ----- Foto van een gerecht verwijderen -----
    if ($actie === 'foto_verwijder') {
        $id = $_POST['id'] ?? '';
        $gerecht = q("SELECT foto FROM menu WHERE id = ?", "s", [$id])->fetch_assoc();
        if ($gerecht) {
            q("UPDATE menu SET foto = '' WHERE id = ?", "s", [$id]);
            verwijderFotoBestand($gerecht['foto']);
        }
        fotoAntwoord(['succes' => true]);
    }

    // ----- Menu-item opslaan of toevoegen -----
    if ($actie === 'menu_opslaan' || $actie === 'menu_toevoegen') {
        $naam = trim($_POST['naam'] ?? '');
        $omschrijving = mb_substr(trim($_POST['omschrijving'] ?? ''), 0, 255);
        $categorie = array_key_exists($_POST['categorie'] ?? '', CATEGORIEEN) ? $_POST['categorie'] : 'hoofdgerecht';
        $prijs = max(0, round(floatval(str_replace(',', '.', $_POST['prijs'] ?? '0')), 2));
        if ($categorie !== 'drankje') $prijs = 0;   // gerechten zitten in het arrangement: geen losse prijs
        $allergenen = array_values(array_intersect(array_keys(ALLERGENEN), (array)($_POST['allergenen'] ?? [])));
        $volgorde = intval($_POST['volgorde'] ?? 0);
        $actief = !empty($_POST['actief']) ? 1 : 0;
        $id = $_POST['id'] ?? '';

        if ($naam === '' || mb_strlen($naam) > 100) {
            $fout = 'Geef het gerecht een naam (maximaal 100 tekens).';
        } elseif (strpos($naam, ',') !== false || preg_match('/^\d+\s*x\s/i', $naam)) {
            $fout = 'Een naam mag geen komma bevatten en niet beginnen met bijvoorbeeld "2x ".';
        } elseif (q("SELECT 1 FROM menu WHERE naam = ? AND id <> ?", "ss", [$naam, $id])->fetch_assoc()) {
            $fout = "Er staat al een gerecht met de naam '$naam' op het menu.";
        } elseif ($actie === 'menu_toevoegen') {
            $nieuwId = maakMenuId($naam);
            q("INSERT INTO menu (id, naam, omschrijving, categorie, prijs, allergenen, actief, volgorde) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
              "ssssdsii", [$nieuwId, $naam, $omschrijving, $categorie, $prijs, implode(',', $allergenen), $actief, $volgorde]);
            // Voorraadregel aanmaken (koppeling op naam)
            if (!q("SELECT 1 FROM voorraad WHERE naam = ?", "s", [$naam])->fetch_assoc()) {
                q("INSERT INTO voorraad (naam, categorie, aantal) VALUES (?, ?, 25)", "ss", [$naam, $categorie]);
            }
            // Foto meegestuurd? Dan meteen opslaan
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                [$gelukt, $resultaat] = verwerkFotoUpload($_FILES['foto'], $nieuwId);
                if ($gelukt) {
                    q("UPDATE menu SET foto = ? WHERE id = ?", "ss", [$resultaat, $nieuwId]);
                } else {
                    $_SESSION['flash'] = ['fout', "Het gerecht is toegevoegd, maar de foto niet: $resultaat Je kunt hem hieronder opnieuw toevoegen."];
                }
            }
            terug('toegevoegd', 'menu', $nieuwId);
        } else {
            $oud = q("SELECT naam FROM menu WHERE id = ?", "s", [$id])->fetch_assoc();
            if (!$oud) {
                $fout = 'Dit gerecht bestaat niet meer.';
            } else {
                q("UPDATE menu SET naam=?, omschrijving=?, categorie=?, prijs=?, allergenen=?, actief=?, volgorde=? WHERE id=?",
                  "sssdsiis", [$naam, $omschrijving, $categorie, $prijs, implode(',', $allergenen), $actief, $volgorde, $id]);
                // Naam gewijzigd? Dan ook de voorraadregel hernoemen
                if ($oud['naam'] !== $naam) {
                    q("UPDATE voorraad SET naam = ? WHERE naam = ?", "ss", [$naam, $oud['naam']]);
                }
                terug('opgeslagen', 'menu', $id);
            }
        }
    }

    // ----- Menu-item verwijderen -----
    if ($actie === 'menu_verwijderen') {
        $id = $_POST['id'] ?? '';
        $oud = q("SELECT naam, foto FROM menu WHERE id = ?", "s", [$id])->fetch_assoc();
        if ($oud) {
            q("DELETE FROM menu WHERE id = ?", "s", [$id]);
            q("DELETE FROM voorraad WHERE naam = ?", "s", [$oud['naam']]);
            verwijderFotoBestand($oud['foto']);
        }
        terug('verwijderd', 'menu');
    }

    // ----- Arrangement opslaan of toevoegen -----
    if ($actie === 'pakket_opslaan' || $actie === 'pakket_toevoegen') {
        $naam = trim($_POST['naam'] ?? '');
        $omschrijving = mb_substr(trim($_POST['omschrijving'] ?? ''), 0, 255);
        $duur = max(15, min(600, intval($_POST['duur'] ?? 120)));
        $drank = !empty($_POST['drank']) ? 1 : 0;
        $pv = max(0, round(floatval(str_replace(',', '.', $_POST['prijs_volw'] ?? '0')), 2));
        $ps = max(0, round(floatval(str_replace(',', '.', $_POST['prijs_sen'] ?? '0')), 2));
        $pk = max(0, round(floatval(str_replace(',', '.', $_POST['prijs_kind'] ?? '0')), 2));
        $volgorde = intval($_POST['volgorde'] ?? 0);

        if ($naam === '' || mb_strlen($naam) > 100) {
            $fout = 'Geef het arrangement een naam (maximaal 100 tekens).';
        } elseif ($actie === 'pakket_toevoegen') {
            if (q("SELECT 1 FROM pakketten WHERE naam = ?", "s", [$naam])->fetch_assoc()) {
                $fout = "Er bestaat al een arrangement met de naam '$naam'.";
            } else {
                q("INSERT INTO pakketten (naam, omschrijving, duur, drank, prijs_volw, prijs_sen, prijs_kind, volgorde) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                  "ssiidddi", [$naam, $omschrijving, $duur, $drank, $pv, $ps, $pk, $volgorde]);
                terug('toegevoegd', 'arrangementen', $naam);
            }
        } else {
            // De naam van een bestaand arrangement blijft vast (tafels verwijzen ernaar)
            q("UPDATE pakketten SET omschrijving=?, duur=?, drank=?, prijs_volw=?, prijs_sen=?, prijs_kind=?, volgorde=? WHERE naam=?",
              "siidddis", [$omschrijving, $duur, $drank, $pv, $ps, $pk, $volgorde, $naam]);
            terug('opgeslagen', 'arrangementen', $naam);
        }
    }

    // ----- Arrangement verwijderen -----
    if ($actie === 'pakket_verwijderen') {
        $naam = $_POST['naam'] ?? '';
        if (q("SELECT 1 FROM tafels WHERE pakket = ?", "s", [$naam])->fetch_assoc()) {
            $fout = "Het arrangement '$naam' is nu in gebruik bij een tafel en kan daarom niet worden verwijderd.";
        } else {
            q("DELETE FROM pakketten WHERE naam = ?", "s", [$naam]);
            terug('verwijderd', 'arrangementen');
        }
    }

    // ----- E-mail (voor de bon) -----
    if ($actie === 'mail_opslaan' || $actie === 'mail_test') {
        $mail = [
            'smtp_host'          => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
            'smtp_poort'         => (string)max(1, min(65535, intval($_POST['smtp_poort'] ?? 587))),
            'smtp_beveiliging'   => ($_POST['smtp_beveiliging'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls',
            'smtp_gebruiker'     => trim($_POST['smtp_gebruiker'] ?? ''),
            'smtp_controle_uit'  => !empty($_POST['smtp_controle_uit']) ? '1' : '0',
            'mail_afzender_naam' => mb_substr(trim($_POST['mail_afzender_naam'] ?? 'Las Tapas'), 0, 60) ?: 'Las Tapas',
        ];
        // Wachtwoord alleen wijzigen als er iets is ingevuld
        if (trim($_POST['smtp_wachtwoord'] ?? '') !== '') $mail['smtp_wachtwoord'] = trim($_POST['smtp_wachtwoord']);
        foreach ($mail as $sleutel => $waarde) {
            q("INSERT INTO instellingen (sleutel, waarde) VALUES (?, ?) ON DUPLICATE KEY UPDATE waarde = ?", "sss", [$sleutel, $waarde, $waarde]);
        }
        instellingen(true);

        if ($actie === 'mail_opslaan') terug('opgeslagen', 'instellingen');

        // Testmail naar het opgegeven adres (of naar het eigen adres)
        $naar = trim($_POST['test_adres'] ?? '') ?: $mail['smtp_gebruiker'];
        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;padding:20px;"><h2 style="color:#a8232b;">🍷 Las Tapas</h2>'
              . '<p>¡Hola! Dit is een testmail. Het versturen van bonnen per e-mail werkt.</p></div>';
        [$gelukt, $mailMelding] = stuurMail($naar, 'Testmail van Las Tapas', $html, "Las Tapas\n\nDit is een testmail. Het versturen van bonnen per e-mail werkt.");
        if ($gelukt) $mailOk = "✅ Testmail verstuurd naar $naar. Kijk in de inbox (en eventueel in de map spam).";
        else $fout = $mailMelding;
        $mailPoging = true;
    }

    // ----- Instellingen -----
    // ----- Privacy: bewaartermijnen -----
    if ($actie === 'privacy_opslaan') {
        $privacy = [
            'bewaar_namen_maanden' => (string)max(1, min(24, intval($_POST['bewaar_namen_maanden'] ?? 3))),
            'bewaar_archief_jaren' => (string)max(7, min(10, intval($_POST['bewaar_archief_jaren'] ?? 7))),   // wettelijk minimaal 7
            'laatste_opschoning'   => '',   // nieuwe termijnen meteen toepassen
        ];
        foreach ($privacy as $sleutel => $waarde) {
            q("INSERT INTO instellingen (sleutel, waarde) VALUES (?, ?) ON DUPLICATE KEY UPDATE waarde = ?", "sss", [$sleutel, $waarde, $waarde]);
        }
        terug('opgeslagen', 'instellingen');
    }

    if ($actie === 'instellingen_opslaan') {
        $nieuw = [
            'verleng_minuten'  => (string)max(5, min(240, intval($_POST['verleng_minuten'] ?? 30))),
            'verleng_prijs_pp' => number_format(max(0, floatval(str_replace(',', '.', $_POST['verleng_prijs_pp'] ?? '6'))), 2, '.', ''),
            'ronde_max_pp'     => (string)max(0, min(20, intval($_POST['ronde_max_pp'] ?? 3))),
            'ronde_minuten'    => (string)max(1, min(120, intval($_POST['ronde_minuten'] ?? 10))),
            'opruimen_na_uren' => (string)max(1, min(24, intval($_POST['opruimen_na_uren'] ?? 4))),
        ];
        foreach ($nieuw as $sleutel => $waarde) {
            q("INSERT INTO instellingen (sleutel, waarde) VALUES (?, ?) ON DUPLICATE KEY UPDATE waarde = ?", "sss", [$sleutel, $waarde, $waarde]);
        }
        terug('opgeslagen', 'instellingen');
    }
}

$okTeksten = ['toegevoegd' => 'Toegevoegd.', 'opgeslagen' => 'Opgeslagen.', 'verwijderd' => 'Verwijderd.'];
$melding = $fout ? ['fout', $fout] : (isset($_GET['ok'], $okTeksten[$_GET['ok']]) ? ['ok', $okTeksten[$_GET['ok']]] : null);
if (!empty($mailOk)) $melding = ['ok', $mailOk];
if (!empty($_SESSION['flash'])) {
    $melding = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$menu = menuItems(false);

$openSectie = in_array($_GET['sectie'] ?? '', ['menu', 'nieuw', 'arrangementen', 'instellingen'], true) ? $_GET['sectie'] : 'menu';
$openKies = $_GET['kies'] ?? '';
if (!empty($mailPoging)) $openSectie = 'instellingen';
if ($fout) {
    $a = $_POST['actie'] ?? '';
    if (strpos($a, 'menu_') === 0) { $openSectie = $a === 'menu_toevoegen' ? 'nieuw' : 'menu'; $openKies = $_POST['id'] ?? ''; }
    if (strpos($a, 'pakket_') === 0) { $openSectie = 'arrangementen'; $openKies = $a === 'pakket_toevoegen' ? 'nieuw' : ($_POST['naam'] ?? ''); }
}
$alleInstellingen = instellingen(true);

// Eén formulier voor een menu-item (bestaand of nieuw)
function menuFormulier($m, $nieuw = false) {
    $id = $nieuw ? 'nieuw' : $m['id'];
    ob_start(); ?>
    <?php if ($nieuw): ?>
    <div class="item-kaart" id="item-nieuw">
    <?php else: ?>
    <div class="item-kaart menu-paneel <?php echo !$m['actief'] ? 'inactief' : ''; ?>" id="item-<?php echo esc($id); ?>"
         data-id="<?php echo esc($id); ?>" data-cat="<?php echo esc($m['categorie']); ?>"
         data-naam="<?php echo esc($m['naam'] . ($m['actief'] ? '' : ' (verborgen)')); ?>" hidden>
    <?php endif; ?>
        <?php $foto = $m['foto'] ?? ''; ?>
        <?php if (!$nieuw): ?>
            <!-- Foto: wordt direct opgeslagen, los van de knop Opslaan -->
            <div class="foto-blok" data-id="<?php echo esc($m['id']); ?>" data-naam="<?php echo esc($m['naam']); ?>">
                <div class="foto-voorbeeld">
                    <img src="<?php echo esc($foto); ?>" alt="Foto van <?php echo esc($m['naam']); ?>" <?php echo $foto ? '' : 'hidden'; ?>>
                    <div class="foto-leeg" <?php echo $foto ? 'hidden' : ''; ?>><span>📷</span>Nog geen foto</div>
                    <div class="foto-laden" hidden><span class="draaier"></span>Foto uploaden...</div>
                </div>
                <div class="foto-knoppen">
                    <label class="foto-kies">
                        <span class="met-foto" <?php echo $foto ? '' : 'hidden'; ?>>🔄 Foto wijzigen</span>
                        <span class="zonder-foto" <?php echo $foto ? 'hidden' : ''; ?>>📷 Foto toevoegen</span>
                        <input type="file" accept="image/*" onchange="uploadFoto(this)">
                    </label>
                    <button type="button" class="foto-weg" onclick="verwijderFoto(this)" <?php echo $foto ? '' : 'hidden'; ?>>🗑️ Foto verwijderen</button>
                    <p class="foto-melding" role="status"></p>
                </div>
            </div>
        <?php endif; ?>
        <form method="POST" <?php echo $nieuw ? 'enctype="multipart/form-data" onsubmit="return verstuurNieuwGerecht(this)"' : ''; ?>>
            <?php echo csrfVeld(); ?>
            <input type="hidden" name="actie" value="<?php echo $nieuw ? 'menu_toevoegen' : 'menu_opslaan'; ?>">
            <?php if ($nieuw): ?>
                <!-- Foto kiezen voor een nieuw gerecht (wordt opgeslagen met "Toevoegen aan menu") -->
                <div class="foto-blok nieuw-foto">
                    <div class="foto-voorbeeld">
                        <img alt="Voorbeeld van de foto" hidden>
                        <div class="foto-leeg"><span>📷</span>Nog geen foto</div>
                    </div>
                    <div class="foto-knoppen">
                        <label class="foto-kies">
                            <span class="met-foto" hidden>🔄 Andere foto kiezen</span>
                            <span class="zonder-foto">📷 Foto toevoegen <small>(optioneel)</small></span>
                            <input type="file" name="foto" accept="image/*" onchange="toonVoorbeeld(this)">
                        </label>
                        <button type="button" class="foto-weg" onclick="wisVoorbeeld(this)" hidden>🗑️ Foto weghalen</button>
                        <p class="foto-melding" role="status"></p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!$nieuw): ?><input type="hidden" name="id" value="<?php echo esc($m['id']); ?>"><?php endif; ?>
            <div class="form-grid">
                <div><label>Naam</label><input type="text" name="naam" required maxlength="100" value="<?php echo esc($m['naam']); ?>"></div>
                <div><label>Categorie</label>
                    <select name="categorie" onchange="toonPrijsVeld(this)">
                        <?php foreach (CATEGORIEEN as $k => $label): ?>
                            <option value="<?php echo $k; ?>" <?php echo $m['categorie'] === $k ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="breed"><label>Omschrijving</label><input type="text" name="omschrijving" maxlength="255" value="<?php echo esc($m['omschrijving']); ?>"></div>
                <div class="prijs-veld" <?php echo $m['categorie'] === 'drankje' ? '' : 'hidden'; ?>>
                    <label>Prijs (€) <small>- betalen gasten met een arrangement zonder drank</small></label>
                    <input type="number" name="prijs" step="0.01" min="0" value="<?php echo number_format((float)$m['prijs'], 2, '.', ''); ?>"></div>
                <div><label>Volgorde <small>(lager = hoger in de lijst)</small></label>
                    <input type="number" name="volgorde" value="<?php echo intval($m['volgorde']); ?>"></div>
            </div>
            <p class="subkop">Allergenen</p>
            <div class="allergenen-grid">
                <?php foreach (ALLERGENEN as $k => $a): ?>
                    <label class="vinkje"><input type="checkbox" name="allergenen[]" value="<?php echo $k; ?>" <?php echo in_array($k, $m['allergenen'], true) ? 'checked' : ''; ?>> <?php echo $a['icoon'] . ' ' . esc($a['label']); ?></label>
                <?php endforeach; ?>
            </div>
            <label class="vinkje actief-vinkje"><input type="checkbox" name="actief" value="1" <?php echo $m['actief'] ? 'checked' : ''; ?>> Zichtbaar op het menu</label>
            <div class="item-knoppen">
                <button type="submit"><?php echo $nieuw ? 'Toevoegen aan menu' : 'Opslaan'; ?></button>
            </div>
        </form>
        <?php if (!$nieuw): ?>
            <form method="POST" class="verwijder-form" onsubmit="return confirm('<?php echo esc(addslashes($m['naam'])); ?> definitief verwijderen? Tip: haal liever het vinkje Zichtbaar weg als het tijdelijk op is.');">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="menu_verwijderen">
                <input type="hidden" name="id" value="<?php echo esc($m['id']); ?>">
                <button type="submit" class="btn-verwijder">Verwijderen</button>
            </form>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}

$paginaTitel = 'Menu & prijzen';
$actievePagina = 'menu_beheer.php';
$extraStijl = '
    /* Foto van een gerecht */
    .foto-blok { display: flex; gap: 16px; align-items: center; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--lijn); }
    .foto-voorbeeld { position: relative; width: 200px; aspect-ratio: 4 / 3; flex-shrink: 0; border-radius: 10px; overflow: hidden; background: #f3ece3; border: 1px solid var(--lijn); }
    .foto-voorbeeld img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .foto-leeg { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: var(--grijs); font-size: 0.85rem; font-weight: bold; }
    .foto-leeg span { font-size: 2rem; opacity: 0.6; }
    .foto-laden { position: absolute; inset: 0; background: rgba(255,255,255,0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; font-weight: bold; font-size: 0.85rem; color: var(--inkt); }
    .foto-leeg[hidden], .foto-laden[hidden], .foto-voorbeeld img[hidden] { display: none; }
    .draaier { width: 26px; height: 26px; border: 3px solid #e6d8c8; border-top-color: var(--rood); border-radius: 50%; animation: draai 0.8s linear infinite; }
    @keyframes draai { to { transform: rotate(360deg); } }
    .foto-knoppen { display: flex; flex-direction: column; gap: 8px; min-width: 0; flex: 1; max-width: 260px; }
    .foto-kies { position: relative; display: block; background: var(--rood); color: white; text-align: center; padding: 12px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.95rem; }
    .foto-kies:hover { background: var(--donkerrood); }
    .foto-kies span[hidden] { display: none; }
    .foto-kies small { font-weight: normal; opacity: 0.85; }
    .foto-kies input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; margin: 0; padding: 0; }
    .foto-kies:focus-within { outline: 3px solid #1f4e8c; outline-offset: 2px; }
    .foto-weg { margin: 0; background: white; color: #c0392b; border: 2px solid #c0392b; padding: 10px 16px; font-size: 0.9rem; }
    .foto-weg:hover { background: #fdecea; }
    .foto-weg[hidden] { display: none; }
    .foto-melding { margin: 0; font-size: 0.85rem; font-weight: bold; min-height: 1em; }
    .foto-melding.fout { color: #c0392b; }
    .foto-melding.goed { color: #1e8449; }
    .foto-blok.bezig .foto-kies, .foto-blok.bezig .foto-weg { opacity: 0.5; pointer-events: none; }
    @media (max-width: 700px) {
        .foto-blok { flex-direction: column; align-items: stretch; }
        .foto-voorbeeld { width: 100%; max-width: 420px; margin: 0 auto; }
        .foto-knoppen { max-width: none; }
        .foto-kies, .foto-weg { padding: 14px; font-size: 1rem; }
    }

    .item-kaart { background: var(--vlak); border: 1px solid var(--lijn); border-radius: 10px; padding: 16px; margin-bottom: 16px; position: relative; }
    .item-kaart.inactief { opacity: 0.6; }
    .item-kaart .breed { grid-column: 1 / -1; }
    .subkop { font-weight: bold; font-size: 0.9rem; margin: 14px 0 6px 0; }
    .allergenen-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 6px; }
    .vinkje { font-weight: normal; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; }
    .vinkje input { width: 18px; height: 18px; margin: 0; }
    .actief-vinkje { margin-top: 12px; font-weight: bold; }
    .item-knoppen { display: flex; gap: 10px; }
    .verwijder-form { position: absolute; right: 16px; bottom: 16px; margin: 0; }
    .sectie-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--lijn); margin-bottom: 20px; flex-wrap: wrap; }
    .sectie-tabs button { background: none; color: var(--grijs); border-radius: 0; margin: 0; padding: 12px 18px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
    .sectie-tabs button:hover { background: var(--vlak); }
    .sectie-tabs button.actief { color: var(--rood); border-bottom-color: var(--rood); }
    .cat-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
    .cat-tabs button { margin: 0; background: #f0eae1; color: var(--grijs); border-radius: 20px; padding: 9px 16px; }
    .cat-tabs button.actief { background: var(--rood); color: white; }
    .kies-rij { margin-bottom: 15px; max-width: 420px; }
    .kies-rij select { font-weight: bold; }
    @media (max-width: 700px) {
        .verwijder-form { position: static; margin-top: 10px; }
        .verwijder-form .btn-verwijder, .item-knoppen button { width: 100%; padding: 12px; }
    }
';
include __DIR__ . '/beheer_kop.php';
?>

    <?php if ($melding): ?>
        <div class="melding <?php echo $melding[0]; ?>"><?php echo esc($melding[1]); ?></div>
    <?php endif; ?>

    <!-- Hoofdtabbladen -->
    <div class="sectie-tabs">
        <button type="button" data-sectie="menu" onclick="toonSectie('menu')">🍽️ Menu</button>
        <button type="button" data-sectie="nieuw" onclick="toonSectie('nieuw')">➕ Nieuw gerecht</button>
        <button type="button" data-sectie="arrangementen" onclick="toonSectie('arrangementen')">📦 Arrangementen</button>
        <button type="button" data-sectie="instellingen" onclick="toonSectie('instellingen')">⚙️ Instellingen</button>
    </div>

    <!-- ===== MENU ===== -->
    <section class="sectie" id="sectie-menu" hidden>
        <div class="cat-tabs">
            <?php foreach (CATEGORIEEN as $cat => $catLabel): ?>
                <button type="button" data-cat="<?php echo $cat; ?>" onclick="toonCategorie('<?php echo $cat; ?>')"><?php echo esc($catLabel); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="kies-rij">
            <label for="gerecht-kies">Kies een gerecht</label>
            <select id="gerecht-kies" onchange="toonGerecht(this.value)"></select>
        </div>
        <div id="menu-panelen">
            <?php foreach ($menu as $m) echo menuFormulier($m); ?>
        </div>
        <p class="uitleg">Wijzigingen zijn direct zichtbaar voor klanten. Is iets tijdelijk op? Haal dan het vinkje "Zichtbaar op het menu" weg.</p>
    </section>

    <!-- ===== NIEUW GERECHT ===== -->
    <section class="sectie" id="sectie-nieuw" hidden>
        <p class="uitleg">Vul de gegevens in en kies een categorie. Een prijs vul je alleen in bij drankjes.
            Na het toevoegen staat het gerecht meteen op het menu (en krijgt het een voorraad van 25).</p>
        <?php
            $nieuwGerecht = ['naam' => '', 'omschrijving' => '', 'categorie' => 'voorgerecht', 'prijs' => 0, 'allergenen' => [], 'actief' => 1, 'volgorde' => 100];
            if ($fout && ($_POST['actie'] ?? '') === 'menu_toevoegen') {
                // Wat al was ingevuld niet kwijtraken bij een foutmelding
                $nieuwGerecht = [
                    'naam' => $_POST['naam'] ?? '',
                    'omschrijving' => $_POST['omschrijving'] ?? '',
                    'categorie' => array_key_exists($_POST['categorie'] ?? '', CATEGORIEEN) ? $_POST['categorie'] : 'voorgerecht',
                    'prijs' => floatval($_POST['prijs'] ?? 0),
                    'allergenen' => array_values(array_intersect(array_keys(ALLERGENEN), (array)($_POST['allergenen'] ?? []))),
                    'actief' => !empty($_POST['actief']) ? 1 : 0,
                    'volgorde' => intval($_POST['volgorde'] ?? 100),
                ];
            }
            echo menuFormulier($nieuwGerecht, true);
        ?>
    </section>

    <!-- ===== ARRANGEMENTEN ===== -->
    <section class="sectie" id="sectie-arrangementen" hidden>
        <div class="kies-rij">
            <label for="pakket-kies">Kies een arrangement</label>
            <select id="pakket-kies" onchange="toonPakket(this.value)">
                <?php foreach (pakketten() as $naam => $p): ?>
                    <option value="<?php echo esc($naam); ?>"><?php echo esc($naam); ?></option>
                <?php endforeach; ?>
                <option value="nieuw">➕ Nieuw arrangement toevoegen</option>
            </select>
        </div>

        <?php foreach (pakketten() as $naam => $p): ?>
        <div class="item-kaart pakket-paneel" data-id="<?php echo esc($naam); ?>" hidden>
            <form method="POST">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="pakket_opslaan">
                <input type="hidden" name="naam" value="<?php echo esc($naam); ?>">
                <h3 style="margin-top: 0;"><?php echo esc($naam); ?></h3>
                <div class="form-grid">
                    <div class="breed"><label>Omschrijving</label><input type="text" name="omschrijving" value="<?php echo esc($p['omschrijving']); ?>"></div>
                    <div><label>Duur (minuten)</label><input type="number" name="duur" min="15" max="600" value="<?php echo $p['duur']; ?>"></div>
                    <div><label>Volgorde</label><input type="number" name="volgorde" value="<?php echo $p['volgorde']; ?>"></div>
                    <div><label>Prijs volwassene (€)</label><input type="number" step="0.01" min="0" name="prijs_volw" value="<?php echo number_format($p['prijs']['volw'], 2, '.', ''); ?>"></div>
                    <div><label>Prijs senior 65+ (€)</label><input type="number" step="0.01" min="0" name="prijs_sen" value="<?php echo number_format($p['prijs']['sen'], 2, '.', ''); ?>"></div>
                    <div><label>Prijs kind (€)</label><input type="number" step="0.01" min="0" name="prijs_kind" value="<?php echo number_format($p['prijs']['kind'], 2, '.', ''); ?>"></div>
                </div>
                <label class="vinkje actief-vinkje"><input type="checkbox" name="drank" value="1" <?php echo $p['drank'] ? 'checked' : ''; ?>> Drankjes inbegrepen</label>
                <div class="item-knoppen"><button type="submit">Opslaan</button></div>
            </form>
            <form method="POST" class="verwijder-form" onsubmit="return confirm('Arrangement definitief verwijderen?');">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="pakket_verwijderen">
                <input type="hidden" name="naam" value="<?php echo esc($naam); ?>">
                <button type="submit" class="btn-verwijder">Verwijderen</button>
            </form>
        </div>
        <?php endforeach; ?>

        <div class="item-kaart pakket-paneel" data-id="nieuw" hidden>
            <form method="POST">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="pakket_toevoegen">
                <h3 style="margin-top: 0;">Nieuw arrangement</h3>
                <div class="form-grid">
                    <div><label>Naam</label><input type="text" name="naam" required maxlength="100" placeholder="Bijv. Lunch Tapas (1u)"></div>
                    <div><label>Duur (minuten)</label><input type="number" name="duur" min="15" max="600" value="120"></div>
                    <div class="breed"><label>Omschrijving</label><input type="text" name="omschrijving"></div>
                    <div><label>Prijs volwassene (€)</label><input type="number" step="0.01" min="0" name="prijs_volw" value="0.00"></div>
                    <div><label>Prijs senior 65+ (€)</label><input type="number" step="0.01" min="0" name="prijs_sen" value="0.00"></div>
                    <div><label>Prijs kind (€)</label><input type="number" step="0.01" min="0" name="prijs_kind" value="0.00"></div>
                    <div><label>Volgorde</label><input type="number" name="volgorde" value="10"></div>
                </div>
                <label class="vinkje actief-vinkje"><input type="checkbox" name="drank" value="1"> Drankjes inbegrepen</label>
                <div class="item-knoppen"><button type="submit">Arrangement toevoegen</button></div>
            </form>
        </div>
        <p class="uitleg">De naam van een bestaand arrangement kan niet worden gewijzigd, omdat tafels ernaar verwijzen.</p>
    </section>

    <!-- ===== INSTELLINGEN ===== -->
    <section class="sectie" id="sectie-instellingen" hidden>
        <div class="item-kaart">
            <form method="POST">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="instellingen_opslaan">
                <h3 style="margin-top: 0;">Tijd verlengen</h3>
                <div class="form-grid">
                    <div><label>Aantal minuten per keer</label>
                        <input type="number" name="verleng_minuten" min="5" max="240" value="<?php echo esc($alleInstellingen['verleng_minuten'] ?? 30); ?>"></div>
                    <div><label>Prijs per persoon (€)</label>
                        <input type="number" name="verleng_prijs_pp" step="0.01" min="0" value="<?php echo esc($alleInstellingen['verleng_prijs_pp'] ?? '6.00'); ?>"></div>
                </div>
                <h3>Ronde-limiet</h3>
                <div class="form-grid">
                    <div><label>Max. gerechten per persoon <small>(0 = geen limiet)</small></label>
                        <input type="number" name="ronde_max_pp" min="0" max="20" value="<?php echo esc($alleInstellingen['ronde_max_pp'] ?? 3); ?>"></div>
                    <div><label>Duur van een ronde (minuten)</label>
                        <input type="number" name="ronde_minuten" min="1" max="120" value="<?php echo esc($alleInstellingen['ronde_minuten'] ?? 10); ?>"></div>
                </div>
                <p class="uitleg">Drankjes tellen niet mee voor de ronde-limiet.</p>
                <h3>Achtergelaten tafels</h3>
                <div class="form-grid">
                    <div><label>Automatisch opruimen na ... uur zonder activiteit</label>
                        <input type="number" name="opruimen_na_uren" min="1" max="24" value="<?php echo esc($alleInstellingen['opruimen_na_uren'] ?? 4); ?>"></div>
                </div>
                <div class="item-knoppen"><button type="submit">Instellingen opslaan</button></div>
            </form>
        </div>
        <div class="item-kaart">
            <form method="POST">
                <?php echo csrfVeld(); ?>
                <input type="hidden" name="actie" value="privacy_opslaan">
                <h3 style="margin-top: 0;">🔒 Privacy: bewaartermijnen</h3>
                <p class="uitleg">Gegevens worden één keer per dag automatisch opgeruimd. Namen van gasten en de tekst van beoordelingen
                    worden na de gekozen periode gewist; de sterren en bedragen blijven anoniem bewaard voor de statistieken.
                    Financiële gegevens moet je wettelijk minimaal 7 jaar bewaren (fiscale bewaarplicht). Zie ook de
                    <a href="privacy.php" target="_blank" rel="noopener">privacyverklaring</a>.</p>
                <div class="form-grid">
                    <div><label>Namen en beoordelingsteksten wissen na ... maanden</label>
                        <input type="number" name="bewaar_namen_maanden" min="1" max="24" value="<?php echo esc($alleInstellingen['bewaar_namen_maanden'] ?? 3); ?>"></div>
                    <div><label>Financiële gegevens verwijderen na ... jaar <small>(minimaal 7)</small></label>
                        <input type="number" name="bewaar_archief_jaren" min="7" max="10" value="<?php echo esc($alleInstellingen['bewaar_archief_jaren'] ?? 7); ?>"></div>
                </div>
                <div class="item-knoppen"><button type="submit">Bewaartermijnen opslaan</button></div>
            </form>
        </div>
        <div class="item-kaart">
            <form method="POST">
                <?php echo csrfVeld(); ?>
                <h3 style="margin-top: 0;">📧 E-mail voor de bon</h3>
                <p class="uitleg">Gasten kunnen hun bon per e-mail krijgen. Daarvoor is een e-mailaccount nodig dat mag versturen.
                    <strong>Gmail:</strong> zet tweestapsverificatie aan in je Google-account, maak een <strong>app-wachtwoord</strong> aan
                    (Google-account → Beveiliging → App-wachtwoorden) en vul dat hieronder in, niet je gewone wachtwoord.</p>
                <div class="form-grid">
                    <div><label>E-mailadres (afzender)</label>
                        <input type="text" name="smtp_gebruiker" placeholder="bijv. lastapas.bonnen@gmail.com" value="<?php echo esc($alleInstellingen['smtp_gebruiker'] ?? ''); ?>"></div>
                    <div><label>App-wachtwoord <small>(leeg laten om niet te wijzigen)</small></label>
                        <input type="password" name="smtp_wachtwoord" autocomplete="new-password"
                               placeholder="<?php echo ($alleInstellingen['smtp_wachtwoord'] ?? '') !== '' ? '•••••••• (ingesteld)' : 'nog niet ingesteld'; ?>"></div>
                    <div><label>Naam afzender</label>
                        <input type="text" name="mail_afzender_naam" value="<?php echo esc($alleInstellingen['mail_afzender_naam'] ?? 'Las Tapas'); ?>"></div>
                    <div><label>Mailserver</label>
                        <input type="text" name="smtp_host" value="<?php echo esc($alleInstellingen['smtp_host'] ?? 'smtp.gmail.com'); ?>"></div>
                    <div><label>Poort</label>
                        <input type="number" name="smtp_poort" value="<?php echo esc($alleInstellingen['smtp_poort'] ?? 587); ?>"></div>
                    <div><label>Beveiliging</label>
                        <select name="smtp_beveiliging">
                            <option value="tls" <?php echo ($alleInstellingen['smtp_beveiliging'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (poort 587)</option>
                            <option value="ssl" <?php echo ($alleInstellingen['smtp_beveiliging'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (poort 465)</option>
                        </select></div>
                </div>
                <label class="vinkje actief-vinkje"><input type="checkbox" name="smtp_controle_uit" value="1" <?php echo ($alleInstellingen['smtp_controle_uit'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    Certificaat niet controleren <small style="font-weight: normal;">(alleen aanzetten als de testmail op WampServer anders mislukt)</small></label>
                <div class="form-grid" style="margin-top: 12px;">
                    <div><label>Testmail sturen naar <small>(leeg = naar het afzenderadres)</small></label>
                        <input type="text" name="test_adres" placeholder="jouw@email.nl"></div>
                </div>
                <div class="item-knoppen">
                    <button type="submit" name="actie" value="mail_opslaan">Opslaan</button>
                    <button type="submit" name="actie" value="mail_test" style="background: #1f4e8c;">Opslaan & testmail sturen</button>
                </div>
            </form>
        </div>
    </section>

</div>

<script>
    // Welk tabblad en welk gerecht/arrangement moet open staan? (na opslaan: hetzelfde als net)
    const START = <?php echo json_encode(['sectie' => $openSectie, 'kies' => (string)$openKies], JSON_UNESCAPED_UNICODE); ?>;
    let huidigeCategorie = 'voorgerecht';

    function toonSectie(sectie) {
        document.querySelectorAll('.sectie').forEach(el => el.hidden = el.id !== 'sectie-' + sectie);
        document.querySelectorAll('.sectie-tabs button').forEach(b => b.classList.toggle('actief', b.dataset.sectie === sectie));
    }

    // Categorie kiezen: keuzelijst vullen met de gerechten uit die categorie
    function toonCategorie(cat, kiesId) {
        huidigeCategorie = cat;
        document.querySelectorAll('.cat-tabs button').forEach(b => b.classList.toggle('actief', b.dataset.cat === cat));

        const select = document.getElementById('gerecht-kies');
        select.innerHTML = '';
        document.querySelectorAll('.menu-paneel').forEach(p => {
            if (p.dataset.cat === cat) select.add(new Option(p.dataset.naam, p.dataset.id));
        });

        if (select.options.length === 0) {
            select.add(new Option('(nog geen gerechten in deze categorie)', ''));
        }
        const bestaat = [...select.options].some(o => o.value === kiesId);
        select.value = bestaat ? kiesId : select.options[0].value;
        toonGerecht(select.value);
    }

    function toonGerecht(id) {
        document.querySelectorAll('.menu-paneel').forEach(p => p.hidden = p.dataset.id !== id);
    }

    // ===== Foto's van gerechten =====
    const CSRF = <?php echo json_encode(csrfToken()); ?>;

    // Maakt een grote telefoonfoto kleiner vóór het uploaden (sneller, en geen problemen met de maximale grootte)
    async function verkleinFoto(bestand) {
        try {
            if (!bestand.type.startsWith('image/') || bestand.type === 'image/gif') return bestand;
            let beeld;
            if (window.createImageBitmap) {
                beeld = await createImageBitmap(bestand, { imageOrientation: 'from-image' });
            } else {
                beeld = await new Promise((ok, fout) => {
                    const img = new Image();
                    img.onload = () => ok(img);
                    img.onerror = fout;
                    img.src = URL.createObjectURL(bestand);
                });
            }
            const max = 1600;
            const schaal = Math.min(1, max / Math.max(beeld.width, beeld.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(beeld.width * schaal);
            canvas.height = Math.round(beeld.height * schaal);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(beeld, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(ok => canvas.toBlob(ok, 'image/jpeg', 0.88));
            return blob || bestand;
        } catch (e) {
            return bestand;   // lukt verkleinen niet, dan het origineel sturen (de server verkleint ook)
        }
    }

    function fotoMelding(blok, tekst, soort) {
        const el = blok.querySelector('.foto-melding');
        el.textContent = tekst;
        el.className = 'foto-melding' + (soort ? ' ' + soort : '');
    }

    function zetFotoWeergave(blok, src) {
        const img = blok.querySelector('.foto-voorbeeld img');
        const heeftFoto = !!src;
        if (heeftFoto) img.src = src; else img.removeAttribute('src');
        img.hidden = !heeftFoto;
        blok.querySelector('.foto-leeg').hidden = heeftFoto;
        blok.querySelector('.met-foto').hidden = !heeftFoto;
        blok.querySelector('.zonder-foto').hidden = heeftFoto;
        blok.querySelector('.foto-weg').hidden = !heeftFoto;
    }

    async function stuurFotoAanvraag(gegevens) {
        const fd = new FormData();
        fd.append('csrf', CSRF);
        Object.entries(gegevens).forEach(([k, w]) => w instanceof Blob ? fd.append(k, w, 'foto.jpg') : fd.append(k, w));
        const res = await fetch('menu_beheer.php', { method: 'POST', body: fd, headers: { 'X-Foto': '1' } });
        try {
            return await res.json();
        } catch (e) {
            return { succes: false, melding: 'Er ging iets mis op de server. Ververs de pagina en probeer het opnieuw.' };
        }
    }

    // Bestaand gerecht: foto meteen uploaden
    async function uploadFoto(input) {
        const blok = input.closest('.foto-blok');
        const bestand = input.files[0];
        input.value = '';
        if (!bestand) return;
        if (!bestand.type.startsWith('image/')) {
            fotoMelding(blok, 'Dit bestand is geen foto.', 'fout');
            return;
        }
        blok.classList.add('bezig');
        blok.querySelector('.foto-laden').hidden = false;
        fotoMelding(blok, '');
        try {
            const foto = await verkleinFoto(bestand);
            const data = await stuurFotoAanvraag({ actie: 'foto_upload', id: blok.dataset.id, foto: foto });
            if (data.succes) {
                zetFotoWeergave(blok, data.foto);
                fotoMelding(blok, '✅ Foto opgeslagen. Gasten zien hem meteen op het menu.', 'goed');
            } else {
                fotoMelding(blok, data.melding || 'Uploaden is mislukt.', 'fout');
            }
        } catch (e) {
            fotoMelding(blok, 'Kan de server niet bereiken. Probeer het opnieuw.', 'fout');
        } finally {
            blok.classList.remove('bezig');
            blok.querySelector('.foto-laden').hidden = true;
        }
    }

    // Bestaand gerecht: foto verwijderen
    async function verwijderFoto(knop) {
        const blok = knop.closest('.foto-blok');
        if (!confirm(`Foto van ${blok.dataset.naam} verwijderen?`)) return;
        blok.classList.add('bezig');
        try {
            const data = await stuurFotoAanvraag({ actie: 'foto_verwijder', id: blok.dataset.id });
            if (data.succes) {
                zetFotoWeergave(blok, '');
                fotoMelding(blok, 'Foto verwijderd.', 'goed');
            } else {
                fotoMelding(blok, data.melding || 'Verwijderen is mislukt.', 'fout');
            }
        } catch (e) {
            fotoMelding(blok, 'Kan de server niet bereiken. Probeer het opnieuw.', 'fout');
        } finally {
            blok.classList.remove('bezig');
        }
    }

    // Nieuw gerecht: alleen een voorbeeld tonen; de foto gaat mee met "Toevoegen aan menu"
    function toonVoorbeeld(input) {
        const blok = input.closest('.foto-blok');
        const bestand = input.files[0];
        if (!bestand) return wisVoorbeeld(input);
        if (!bestand.type.startsWith('image/')) {
            input.value = '';
            fotoMelding(blok, 'Dit bestand is geen foto.', 'fout');
            return;
        }
        fotoMelding(blok, '');
        zetFotoWeergave(blok, URL.createObjectURL(bestand));
    }

    function wisVoorbeeld(el) {
        const blok = el.closest('.foto-blok');
        blok.querySelector('input[type=file]').value = '';
        zetFotoWeergave(blok, '');
        fotoMelding(blok, '');
    }

    // Nieuw gerecht versturen: foto eerst verkleinen, daarna het hele formulier sturen
    function verstuurNieuwGerecht(form) {
        const input = form.querySelector('input[type=file][name=foto]');
        const bestand = input && input.files[0];
        if (!bestand || !window.fetch || !window.FormData) return true;   // geen foto: gewoon versturen
        const knop = form.querySelector('button[type=submit]');
        knop.disabled = true;
        knop.textContent = 'Bezig met opslaan...';
        (async () => {
            try {
                const fd = new FormData(form);
                fd.set('foto', await verkleinFoto(bestand), 'foto.jpg');
                const res = await fetch('menu_beheer.php', { method: 'POST', body: fd });
                if (res.redirected) {
                    window.location.href = res.url;   // gelukt: naar het nieuwe gerecht
                } else {
                    // Er is iets niet goed ingevuld: de pagina met de foutmelding tonen
                    const html = await res.text();
                    document.open();
                    document.write(html);
                    document.close();
                }
            } catch (e) {
                knop.disabled = false;
                knop.textContent = 'Toevoegen aan menu';
                fotoMelding(form.querySelector('.foto-blok'), 'Kan de server niet bereiken. Probeer het opnieuw.', 'fout');
            }
        })();
        return false;
    }

    // Prijs alleen tonen als de categorie "Drankjes" is
    function toonPrijsVeld(select) {
        const veld = select.closest('form').querySelector('.prijs-veld');
        if (veld) veld.hidden = select.value !== 'drankje';
    }

    function toonPakket(id) {
        document.querySelectorAll('.pakket-paneel').forEach(p => p.hidden = p.dataset.id !== id);
    }

    // Startpositie
    toonSectie(START.sectie);
    let startCat = 'voorgerecht';
    if (START.sectie === 'menu' && START.kies) {
        const paneel = document.querySelector(`.menu-paneel[data-id="${CSS.escape(START.kies)}"]`);
        if (paneel) startCat = paneel.dataset.cat;
    }
    toonCategorie(startCat, START.sectie === 'menu' ? START.kies : '');

    const pakketKies = document.getElementById('pakket-kies');
    if (START.sectie === 'arrangementen' && [...pakketKies.options].some(o => o.value === START.kies)) {
        pakketKies.value = START.kies;
    }
    toonPakket(pakketKies.value);
</script>
</body>
</html>
