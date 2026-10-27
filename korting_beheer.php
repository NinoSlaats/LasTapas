<?php
/*
 * Las Tapas - korting_beheer.php
 * De baas beheert hier kortingscodes (procent of vast bedrag) en cadeaubonnen.
 * Gasten vullen een code in bij de rekening; cadeaubonnen kopen ze zelf aan tafel
 * (de code wordt geldig zodra de rekening betaald is) of de baas maakt er hier een aan.
 */
session_start();
require __DIR__ . '/gedeeld.php';
vereisBaas();
set_exception_handler('toonFoutPagina');
db();

function terugNaar($soort, $tekst, $extra = '') {
    $_SESSION['flash'] = [$soort, $tekst];
    header('Location: korting_beheer.php' . $extra);
    exit();
}

function euroK($bedrag) {
    return '€ ' . number_format((float)$bedrag, 2, ',', '.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfControle();
    $actie = $_POST['actie'] ?? '';

    // ----- De hele functie aan- of uitzetten -----
    if ($actie === 'functie_wissel') {
        $aan = kortingFunctieAan() ? '0' : '1';
        q("INSERT INTO instellingen (sleutel, waarde) VALUES ('korting_aan', ?) ON DUPLICATE KEY UPDATE waarde = ?", "ss", [$aan, $aan]);
        instellingen(true);
        terugNaar('ok', $aan === '1'
            ? 'Kortingscodes en cadeaubonnen staan AAN. Gasten zien de opties nu bij de rekening.'
            : 'Kortingscodes en cadeaubonnen staan UIT. Gasten zien de opties niet meer.');
    }

    // ----- Kortingscodes -----
    if ($actie === 'korting_nieuw') {
        $code = normaliseerCode($_POST['code'] ?? '');
        $soort = ($_POST['soort'] ?? '') === 'bedrag' ? 'bedrag' : 'procent';
        $waarde = round(floatval(str_replace(',', '.', $_POST['waarde'] ?? '0')), 2);
        $omschrijving = mb_substr(trim($_POST['omschrijving'] ?? ''), 0, 100);
        $geldigTot = trim($_POST['geldig_tot'] ?? '');
        $max = max(0, intval($_POST['max_gebruik'] ?? 0));

        if (strlen($code) < 3 || strlen($code) > 30) terugNaar('fout', 'Een kortingscode heeft 3 tot 30 letters of cijfers.');
        if (strlen($code) === 12 || (strlen($code) === 14 && str_starts_with($code, 'LT'))) {
            terugNaar('fout', 'Kies een andere code: deze lijkt te veel op een cadeauboncode.');
        }
        if ($soort === 'procent' && ($waarde <= 0 || $waarde > 100)) terugNaar('fout', 'Een percentage ligt tussen 1 en 100.');
        if ($soort === 'bedrag' && ($waarde <= 0 || $waarde > 1000)) terugNaar('fout', 'Kies een kortingsbedrag tussen € 0,01 en € 1000.');
        if ($geldigTot !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $geldigTot)) terugNaar('fout', 'Ongeldige datum.');
        if (q("SELECT id FROM kortingscodes WHERE code = ?", "s", [$code])->fetch_assoc()) terugNaar('fout', "De code $code bestaat al.");

        q("INSERT INTO kortingscodes (code, soort, waarde, omschrijving, geldig_tot, max_gebruik, gebruikt, actief, gemaakt_op)
           VALUES (?, ?, ?, ?, ?, ?, 0, 1, NOW())",
          "ssdssi", [$code, $soort, $waarde, $omschrijving, $geldigTot !== '' ? $geldigTot : null, $max]);
        terugNaar('ok', "Kortingscode $code is aangemaakt.");
    }

    if ($actie === 'korting_wissel') {
        q("UPDATE kortingscodes SET actief = 1 - actief WHERE id = ?", "i", [intval($_POST['id'] ?? 0)]);
        terugNaar('ok', 'De kortingscode is aangepast.');
    }

    if ($actie === 'korting_verwijder') {
        q("DELETE FROM kortingscodes WHERE id = ?", "i", [intval($_POST['id'] ?? 0)]);
        terugNaar('ok', 'De kortingscode is verwijderd.');
    }

    // ----- Cadeaubonnen -----
    if ($actie === 'cadeau_nieuw') {
        $bedrag = round(floatval(str_replace(',', '.', $_POST['bedrag'] ?? '0')), 2);
        if ($bedrag < 5 || $bedrag > 500) terugNaar('fout', 'Kies een bedrag tussen € 5 en € 500.', '#cadeaubonnen');
        $code = maakCadeauCode();
        q("INSERT INTO cadeaubonnen (code, bedrag, saldo, status, voor_naam, boodschap, tafel, tafel_sessie, gemaakt_door, gekocht_op, geactiveerd_op)
           VALUES (?, ?, ?, 'actief', ?, ?, 0, '', ?, NOW(), NOW())",
          "sddsss", [$code, $bedrag, $bedrag, mb_substr(trim($_POST['voor'] ?? ''), 0, 60),
                     mb_substr(trim($_POST['boodschap'] ?? ''), 0, 200), $_SESSION['gebruiker'] ?? 'Baas']);
        terugNaar('ok', 'Cadeaubon ' . toonCadeauCode($code) . ' is aangemaakt. Klik op "Bekijken" om hem te printen.', '?zoek=' . urlencode($code) . '#cadeaubonnen');
    }

    if ($actie === 'cadeau_blokkeer') {
        $id = intval($_POST['id'] ?? 0);
        q("UPDATE cadeaubonnen SET status = IF(status = 'geblokkeerd', 'actief', 'geblokkeerd') WHERE id = ? AND status <> 'open'", "i", [$id]);
        terugNaar('ok', 'De cadeaubon is aangepast.', '#cadeaubonnen');
    }

    if ($actie === 'cadeau_verwijder') {
        $id = intval($_POST['id'] ?? 0);
        $bon = q("SELECT code FROM cadeaubonnen WHERE id = ?", "i", [$id])->fetch_assoc();
        if ($bon) {
            // Staat de bon nu op de rekening van een tafel? Dan daar ook weghalen.
            q("UPDATE tafels SET cadeaubon_code = '' WHERE cadeaubon_code = ?", "s", [$bon['code']]);
            q("DELETE FROM cadeaubonnen WHERE id = ?", "i", [$id]);
            terugNaar('ok', 'Cadeaubon ' . toonCadeauCode($bon['code']) . ' is verwijderd.', '#cadeaubonnen');
        }
        terugNaar('fout', 'Deze cadeaubon bestaat niet (meer).', '#cadeaubonnen');
    }
}

$melding = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$codes = alleRijen(q("SELECT * FROM kortingscodes ORDER BY actief DESC, gemaakt_op DESC"));

// Zoeken op (een deel van) de code, met of zonder "LT-" en streepjes: "LT-V", "vbp2", "3GFT-5VZ"
function codePastBij($code, $zoek) {
    return $zoek === '' || str_contains('LT' . $code, $zoek) || str_contains($code, $zoek);
}
$zoek = normaliseerCode($_GET['zoek'] ?? '');
// Alle bonnen laden (nieuwste eerst); het zoekvak filtert ze live in de browser
$bonnen = alleRijen(q("SELECT * FROM cadeaubonnen ORDER BY id DESC LIMIT 500"));
$aantalGevonden = count(array_filter($bonnen, fn($b) => codePastBij($b['code'], $zoek)));
$totaalTegoed = floatval(q("SELECT COALESCE(SUM(saldo), 0) AS s FROM cadeaubonnen WHERE status = 'actief'")->fetch_assoc()['s']);

$paginaTitel = 'Korting & cadeaubonnen';
$actievePagina = 'korting_beheer.php';
$extraStijl = '
    input[type=date] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 0.95rem; }
    .form-grid.drie { grid-template-columns: 1fr 1fr 1fr; }
    .label-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; white-space: nowrap; }
    .label-status.aan { background: #eafaf1; color: #1e8449; }
    .label-status.uit { background: #f0eae1; color: #786a63; }
    .label-status.rood { background: #fdecea; color: #922b21; }
    .label-status.geel { background: #fff8e1; color: #7d5a00; }
    #k-code::placeholder { text-transform: none; }
    .functie-schakel { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
                       border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; border: 2px solid; }
    .functie-schakel.aan { background: #eafaf1; border-color: #7dcea0; }
    .functie-schakel.uit { background: #f6f1ea; border-color: #d5c7b8; }
    .functie-schakel strong { font-size: 1.05rem; }
    .functie-schakel p { margin: 4px 0 0 0; color: var(--grijs); font-size: 0.9rem; }
    .functie-schakel .schakel-knop { margin: 0; }
    .functie-schakel.aan .schakel-knop { background: #786a63; }
    .functie-schakel.uit .schakel-knop { background: #27ae60; }
    .code-tekst { font-family: Consolas, monospace; font-weight: bold; letter-spacing: 1px; }
    .btn-klein { background: #2980b9; color: white; padding: 7px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; display: inline-block; border: none; margin: 0; cursor: pointer; }
    .btn-klein.grijs { background: #786a63; }
    .zoek-rij { display: flex; gap: 8px; margin-top: 10px; }
    .zoek-rij input { margin-top: 0; }
    .zoek-rij button, .zoek-rij a { margin-top: 0; white-space: nowrap; }
    .samenvatting { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
    .samenvatting div { background: var(--vlak); border: 1px solid var(--lijn); border-radius: 10px; padding: 10px 14px; }
    .samenvatting strong { display: block; font-size: 1.2rem; color: var(--inkt); }
    @media (max-width: 700px) { .form-grid.drie { grid-template-columns: 1fr; } }
';
include __DIR__ . '/beheer_kop.php';
?>

    <?php if ($melding): ?>
        <div class="melding <?php echo $melding[0] === 'ok' ? 'ok' : 'fout'; ?>"><?php echo esc($melding[1]); ?></div>
    <?php endif; ?>

    <?php $functieAan = kortingFunctieAan(); ?>
    <div class="functie-schakel <?php echo $functieAan ? 'aan' : 'uit'; ?>">
        <div>
            <strong><?php echo $functieAan ? '✅ Kortingscodes en cadeaubonnen staan AAN' : '⏸️ Kortingscodes en cadeaubonnen staan UIT'; ?></strong>
            <p><?php echo $functieAan
                ? 'Gasten kunnen bij de rekening een kortingscode of cadeaubon invullen en een cadeaubon kopen.'
                : 'Gasten zien geen kortingscode-veld en kunnen geen cadeaubon kopen of gebruiken. Je kunt hieronder alvast codes en bonnen klaarzetten.'; ?></p>
        </div>
        <form method="POST">
            <?php echo csrfVeld(); ?>
            <input type="hidden" name="actie" value="functie_wissel">
            <button type="submit" class="schakel-knop"><?php echo $functieAan ? 'Uitzetten' : 'Aanzetten'; ?></button>
        </form>
    </div>

    <h2 style="margin-top: 0;">🎟️ Kortingscodes</h2>
    <p class="uitleg">Gasten vullen een kortingscode in bij de rekening. De korting geldt voor het eten en drinken (niet voor cadeaubonnen).</p>

    <div class="tabel-scroll">
        <table class="kaarten">
            <thead><tr><th>Code</th><th>Korting</th><th>Omschrijving</th><th>Gebruikt</th><th>Geldig tot</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$codes): ?>
                <tr><td colspan="7" class="uitleg">Nog geen kortingscodes. Maak er hieronder een aan.</td></tr>
            <?php endif; ?>
            <?php foreach ($codes as $k):
                [$bruikbaar, $reden] = kortingscodeBruikbaar($k);
                if (!intval($k['actief'])) { $st = ['uit', 'Uitgezet']; }
                elseif (!$bruikbaar) { $st = ['rood', $k['geldig_tot'] !== null && $k['geldig_tot'] < date('Y-m-d') ? 'Verlopen' : 'Op']; }
                else { $st = ['aan', 'Actief']; }
            ?>
                <tr>
                    <td data-label="Code" class="cel-naam"><span class="code-tekst" data-geen-vertaling><?php echo esc($k['code']); ?></span></td>
                    <td data-label="Korting"><?php echo $k['soort'] === 'procent' ? esc(rtrim(rtrim(number_format(floatval($k['waarde']), 2, ',', ''), '0'), ',')) . '%' : euroK($k['waarde']); ?></td>
                    <td data-label="Omschrijving"><?php echo esc($k['omschrijving'] ?: '-'); ?></td>
                    <td data-label="Gebruikt"><?php echo intval($k['gebruikt']); ?><?php echo intval($k['max_gebruik']) > 0 ? ' / ' . intval($k['max_gebruik']) : ''; ?></td>
                    <td data-label="Geldig tot"><?php echo $k['geldig_tot'] ? esc(date('d-m-Y', strtotime($k['geldig_tot']))) : 'Altijd'; ?></td>
                    <td data-label="Status"><span class="label-status <?php echo $st[0]; ?>"><?php echo $st[1]; ?></span></td>
                    <td class="cel-acties">
                        <form method="POST" class="inline-form">
                            <?php echo csrfVeld(); ?>
                            <input type="hidden" name="actie" value="korting_wissel">
                            <input type="hidden" name="id" value="<?php echo intval($k['id']); ?>">
                            <button type="submit" class="btn-bewerk" style="border: none; margin: 0;"><?php echo intval($k['actief']) ? 'Uitzetten' : 'Aanzetten'; ?></button>
                        </form>
                        <form method="POST" class="inline-form" onsubmit="return confirm('Kortingscode <?php echo esc($k['code']); ?> verwijderen?');">
                            <?php echo csrfVeld(); ?>
                            <input type="hidden" name="actie" value="korting_verwijder">
                            <input type="hidden" name="id" value="<?php echo intval($k['id']); ?>">
                            <button type="submit" class="btn-verwijder">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-box">
        <h3 style="margin-top: 0;">➕ Nieuwe kortingscode</h3>
        <form method="POST">
            <?php echo csrfVeld(); ?>
            <input type="hidden" name="actie" value="korting_nieuw">
            <div class="form-grid drie">
                <div>
                    <label for="k-code">Code</label>
                    <input type="text" id="k-code" name="code" maxlength="30" placeholder="Bijv. ZOMER10" required style="text-transform: uppercase;">
                </div>
                <div>
                    <label for="k-soort">Soort korting</label>
                    <select id="k-soort" name="soort">
                        <option value="procent">Percentage (%)</option>
                        <option value="bedrag">Vast bedrag (€)</option>
                    </select>
                </div>
                <div>
                    <label for="k-waarde">Hoeveel</label>
                    <input type="number" id="k-waarde" name="waarde" min="0.01" max="1000" step="0.01" placeholder="Bijv. 10" required>
                </div>
                <div>
                    <label for="k-omschrijving">Omschrijving (voor jezelf)</label>
                    <input type="text" id="k-omschrijving" name="omschrijving" maxlength="100" placeholder="Bijv. Zomeractie">
                </div>
                <div>
                    <label for="k-geldig">Geldig tot (optioneel)</label>
                    <input type="date" id="k-geldig" name="geldig_tot">
                </div>
                <div>
                    <label for="k-max">Hoe vaak te gebruiken</label>
                    <input type="number" id="k-max" name="max_gebruik" min="0" step="1" value="0">
                    <span class="uitleg" style="font-size: 0.8rem;">0 = onbeperkt</span>
                </div>
            </div>
            <button type="submit">Kortingscode aanmaken</button>
        </form>
    </div>

    <h2 id="cadeaubonnen">🎁 Cadeaubonnen</h2>
    <p class="uitleg">Gasten kopen een cadeaubon aan tafel; hij komt op hun rekening en wordt geldig zodra die betaald is. Wat een gast niet opmaakt, blijft op de bon staan.</p>
    <div class="samenvatting">
        <div>Openstaand tegoed<strong><?php echo euroK($totaalTegoed); ?></strong></div>
    </div>

    <form method="GET" class="zoek-rij" action="korting_beheer.php#cadeaubonnen">
        <input type="text" name="zoek" id="bon-zoek" placeholder="Zoek op code, bijv. LT-ABCD" autocomplete="off" value="<?php echo esc($_GET['zoek'] ?? ''); ?>">
        <button type="submit">Zoeken</button>
        <a href="korting_beheer.php#cadeaubonnen" class="btn-annuleer" id="bon-zoek-wis" <?php echo $zoek === '' ? 'style="display: none;"' : ''; ?>>Alles</a>
    </form>

    <div class="tabel-scroll">
        <table class="kaarten">
            <thead><tr><th>Code</th><th>Bedrag</th><th>Saldo</th><th>Voor</th><th>Status</th><th>Gekocht</th><th></th></tr></thead>
            <tbody>
            <?php if (!$bonnen): ?>
                <tr><td colspan="7" class="uitleg">Nog geen cadeaubonnen.</td></tr>
            <?php else: ?>
                <tr id="geen-bon" <?php echo $aantalGevonden > 0 ? 'style="display: none;"' : ''; ?>><td colspan="7" class="uitleg">Geen cadeaubon gevonden met deze code.</td></tr>
            <?php endif; ?>
            <?php foreach ($bonnen as $b):
                if ($b['status'] === 'open') $st = ['geel', 'Nog niet betaald'];
                elseif ($b['status'] === 'geblokkeerd') $st = ['rood', 'Geblokkeerd'];
                elseif (floatval($b['saldo']) <= 0) $st = ['uit', 'Tegoed op'];
                else $st = ['aan', 'Geldig'];
            ?>
                <tr class="bon-rij" data-code="<?php echo esc($b['code']); ?>" <?php echo codePastBij($b['code'], $zoek) ? '' : 'style="display: none;"'; ?>>
                    <td data-label="Code" class="cel-naam"><span class="code-tekst" data-geen-vertaling><?php echo esc(toonCadeauCode($b['code'])); ?></span></td>
                    <td data-label="Bedrag"><?php echo euroK($b['bedrag']); ?></td>
                    <td data-label="Saldo"><strong><?php echo euroK($b['saldo']); ?></strong></td>
                    <td data-label="Voor"><span data-geen-vertaling><?php echo esc($b['voor_naam'] ?: '-'); ?></span></td>
                    <td data-label="Status"><span class="label-status <?php echo $st[0]; ?>"><?php echo $st[1]; ?></span></td>
                    <td data-label="Gekocht"><?php echo esc(date('d-m-Y', strtotime($b['gekocht_op']))); ?>
                        <br><small class="uitleg"><?php echo intval($b['tafel']) > 0 ? 'Tafel ' . intval($b['tafel']) : esc($b['gemaakt_door'] ?: 'Beheer'); ?></small></td>
                    <td class="cel-acties">
                        <?php if ($b['status'] !== 'open'): ?>
                            <a href="cadeaubon.php?code=<?php echo urlencode(toonCadeauCode($b['code'])); ?>" target="_blank" rel="noopener" class="btn-klein">Bekijken</a>
                            <form method="POST" class="inline-form">
                                <?php echo csrfVeld(); ?>
                                <input type="hidden" name="actie" value="cadeau_blokkeer">
                                <input type="hidden" name="id" value="<?php echo intval($b['id']); ?>">
                                <button type="submit" class="btn-klein grijs"><?php echo $b['status'] === 'geblokkeerd' ? 'Deblokkeren' : 'Blokkeren'; ?></button>
                            </form>
                        <?php else: ?>
                            <span class="uitleg" style="font-size: 0.8rem;">Wordt geldig na betaling</span>
                        <?php endif; ?>
                        <form method="POST" class="inline-form" onsubmit="return confirm('<?php
                            echo $b['status'] === 'open'
                                ? 'Deze cadeaubon staat nog op de rekening van tafel ' . intval($b['tafel']) . '. Verwijderen? Hij verdwijnt dan ook van die rekening.'
                                : (floatval($b['saldo']) > 0 ? 'Cadeaubon ' . esc(toonCadeauCode($b['code'])) . ' verwijderen? Er staat nog ' . esc(euroK($b['saldo'])) . ' op. De gast kan hem daarna niet meer gebruiken.'
                                                              : 'Cadeaubon ' . esc(toonCadeauCode($b['code'])) . ' verwijderen?'); ?>');">
                            <?php echo csrfVeld(); ?>
                            <input type="hidden" name="actie" value="cadeau_verwijder">
                            <input type="hidden" name="id" value="<?php echo intval($b['id']); ?>">
                            <button type="submit" class="btn-verwijder">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-box">
        <h3 style="margin-top: 0;">➕ Cadeaubon aanmaken</h3>
        <p class="uitleg" style="margin-top: 0;">Bijvoorbeeld als iemand aan de bar een cadeaubon koopt, of als cadeau van het restaurant. Deze bon is meteen geldig.</p>
        <form method="POST">
            <?php echo csrfVeld(); ?>
            <input type="hidden" name="actie" value="cadeau_nieuw">
            <div class="form-grid drie">
                <div>
                    <label for="c-bedrag">Bedrag (€)</label>
                    <input type="number" id="c-bedrag" name="bedrag" min="5" max="500" step="0.01" placeholder="Bijv. 50" required>
                </div>
                <div>
                    <label for="c-voor">Voor wie (optioneel)</label>
                    <input type="text" id="c-voor" name="voor" maxlength="60" placeholder="Bijv. Oma Rosa">
                </div>
                <div>
                    <label for="c-boodschap">Boodschap (optioneel)</label>
                    <input type="text" id="c-boodschap" name="boodschap" maxlength="200" placeholder="Bijv. Gefeliciteerd!">
                </div>
            </div>
            <button type="submit">Cadeaubon aanmaken</button>
        </form>
    </div>
</div>
<script>
    // Live zoeken: bij elke letter vallen de cadeaubonnen weg die niet passen
    (function () {
        const veld = document.getElementById('bon-zoek');
        const wis = document.getElementById('bon-zoek-wis');
        const geen = document.getElementById('geen-bon');
        const rijen = document.querySelectorAll('tr.bon-rij');
        function filter() {
            const zoek = veld.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            let zichtbaar = 0;
            rijen.forEach(tr => {
                const code = tr.dataset.code;
                const past = !zoek || ('LT' + code).includes(zoek) || code.includes(zoek);
                tr.style.display = past ? '' : 'none';
                if (past) zichtbaar++;
            });
            if (geen) geen.style.display = zichtbaar === 0 ? '' : 'none';
            wis.style.display = zoek ? '' : 'none';
        }
        veld.addEventListener('input', filter);
        wis.addEventListener('click', e => { e.preventDefault(); veld.value = ''; filter(); veld.focus(); });
        // Enter hoeft de pagina niet te herladen
        veld.form.addEventListener('submit', e => { e.preventDefault(); filter(); });
    })();
</script>
</body>
</html>
