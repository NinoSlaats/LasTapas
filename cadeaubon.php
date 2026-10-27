<?php
/*
 * Las Tapas - cadeaubon.php
 * Een cadeaubon bekijken, printen of doorsturen, en het saldo controleren.
 * Gebruik: cadeaubon.php?code=LT-ABCD-EFGH-JKLM  (de code zelf is het geheim)
 */
require __DIR__ . '/gedeeld.php';

$invoer = trim((string)($_GET['code'] ?? ''));
$bon = null;
$melding = '';

$functieAan = true;
try {
    db();
    $functieAan = kortingFunctieAan();
    if ($invoer !== '' && $functieAan) {
        // Tegen raden: maximaal 20 opvragingen per 15 minuten per apparaat
        $sleutel = 'saldo-' . ($_SERVER['REMOTE_ADDR'] ?? '');
        $n = intval(q("SELECT COUNT(*) AS n FROM login_pogingen WHERE gebruikersnaam = ? AND tijd > (NOW() - INTERVAL 15 MINUTE)",
                      "s", [$sleutel])->fetch_assoc()['n']);
        if ($n >= 20) {
            $melding = 'Te vaak geprobeerd. Probeer het over een kwartier opnieuw.';
        } else {
            $bon = vindCadeaubon($invoer);
            if (!$bon) {
                q("INSERT INTO login_pogingen (gebruikersnaam, ip, tijd) VALUES (?, ?, NOW())", "ss", [$sleutel, $_SERVER['REMOTE_ADDR'] ?? '']);
                $melding = 'Deze code kennen we niet. Controleer de code en probeer het opnieuw.';
            }
        }
    }
} catch (Throwable $e) {
    $melding = 'De cadeaubonnen kunnen nu niet worden opgehaald. Probeer het later opnieuw.';
}

function euroBon($bedrag) {
    return '€ ' . number_format((float)$bedrag, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadeaubon - Las Tapas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&display=swap" rel="stylesheet">
    <style>
        :root { --rood: #a8232b; --geel: #e9a23b; --azul: #1f4e8c; --inkt: #2b1d19; --grijs: #786a63; --creme: #fbf5ea; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--creme); color: var(--inkt); font-family: 'Segoe UI', Arial, sans-serif; padding: 24px 16px 40px; }
        .boven { max-width: 480px; margin: 0 auto 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .boven a { color: var(--azul); font-weight: bold; text-decoration: none; font-size: 0.9rem; }
        .bon {
            max-width: 480px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 8px 24px rgba(43, 29, 25, 0.12);
        }
        .bon-kop { background: var(--rood); color: white; text-align: center; padding: 26px 20px 20px; }
        .bon-kop .logo { font-family: 'Fraunces', Georgia, serif; font-size: 2rem; font-weight: 800; margin: 0; }
        .bon-kop .sub { font-size: 0.72rem; letter-spacing: 3px; text-transform: uppercase; opacity: 0.85; margin-top: 4px; }
        .tegels { height: 10px; background: repeating-linear-gradient(90deg, var(--azul) 0 10px, #fdf6e3 10px 20px); }
        .bon-inhoud { padding: 24px 22px 26px; text-align: center; }
        .bon-inhoud h1 { font-family: 'Fraunces', Georgia, serif; color: var(--rood); margin: 0 0 4px; font-size: 1.6rem; }
        .voor { color: var(--grijs); margin: 0 0 16px; }
        .bedrag { font-family: 'Fraunces', Georgia, serif; font-size: 3rem; font-weight: 800; color: var(--inkt); margin: 6px 0; }
        .saldo { color: var(--grijs); margin: 0 0 18px; font-size: 0.95rem; }
        .boodschap { background: #fff8e7; border-left: 4px solid var(--geel); padding: 12px 14px; border-radius: 8px; font-style: italic; text-align: left; margin: 0 0 18px; white-space: pre-line; }
        .code { font-family: Consolas, 'Courier New', monospace; font-size: 1.35rem; font-weight: bold; letter-spacing: 2px; color: var(--rood);
                border: 2px dashed var(--geel); border-radius: 10px; padding: 12px; margin: 0 0 10px; word-break: break-all; }
        .uitleg { font-size: 0.85rem; color: var(--grijs); margin: 0; line-height: 1.5; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; margin-bottom: 12px; }
        .status.actief { background: #eafaf1; color: #1e8449; }
        .status.leeg, .status.geblokkeerd { background: #fdecea; color: #922b21; }
        .status.open { background: #fff8e1; color: #7d5a00; }
        .knoppen { max-width: 480px; margin: 16px auto 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .knoppen button { flex: 1; min-width: 140px; background: var(--rood); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 0.95rem; cursor: pointer; font-family: inherit; }
        .knoppen button.licht { background: white; color: var(--rood); border: 2px solid var(--rood); }
        .zoek { max-width: 480px; margin: 0 auto; background: white; border-radius: 16px; padding: 24px 22px; box-shadow: 0 8px 24px rgba(43, 29, 25, 0.12); }
        .zoek h1 { font-family: 'Fraunces', Georgia, serif; color: var(--rood); margin: 0 0 8px; }
        .zoek input { width: 100%; padding: 12px; font-size: 1.1rem; border: 2px solid #e0ccb8; border-radius: 8px; font-family: Consolas, monospace; text-transform: uppercase; margin: 10px 0; }
        .zoek button { width: 100%; background: var(--rood); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer; }
        .fout { background: #fdecea; color: #922b21; padding: 10px 12px; border-radius: 8px; font-weight: bold; margin: 0 0 12px; }
        .klein { max-width: 480px; margin: 18px auto 0; text-align: center; font-size: 0.8rem; color: var(--grijs); }
        .klein a { color: var(--azul); }
        @media print {
            body { background: white; padding: 0; }
            .boven, .knoppen, .klein, .zoek-los { display: none; }
            .bon { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<body>
    <div class="boven">
        <a href="cadeaubon.php">🎁 Saldo checken</a>
        <span id="taal-plek"></span>
    </div>

<?php if (!$functieAan): ?>
    <div class="zoek">
        <h1>🎁 Cadeaubonnen</h1>
        <p class="uitleg" style="text-align: left;">Cadeaubonnen zijn op dit moment niet beschikbaar. Vraag het personeel als je een vraag hebt over je cadeaubon.</p>
    </div>
<?php elseif ($bon && $bon['status'] !== 'open'): ?>
    <?php
        $leeg = floatval($bon['saldo']) <= 0;
        $statusKlasse = $bon['status'] === 'geblokkeerd' ? 'geblokkeerd' : ($leeg ? 'leeg' : 'actief');
        $statusTekst = $bon['status'] === 'geblokkeerd' ? 'Geblokkeerd' : ($leeg ? 'Tegoed op' : 'Geldig');
    ?>
    <div class="bon">
        <div class="bon-kop">
            <p class="logo">🍷 Las Tapas</p>
            <div class="sub">Taberna española</div>
        </div>
        <div class="tegels"></div>
        <div class="bon-inhoud">
            <span class="status <?php echo $statusKlasse; ?>"><?php echo $statusTekst; ?></span>
            <h1>🎁 Cadeaubon</h1>
            <?php if ($bon['voor_naam'] !== ''): ?>
                <p class="voor">Voor <strong data-geen-vertaling><?php echo esc($bon['voor_naam']); ?></strong></p>
            <?php else: ?>
                <p class="voor">Een cadeautje om samen tapas te eten</p>
            <?php endif; ?>
            <div class="bedrag"><?php echo euroBon($bon['bedrag']); ?></div>
            <?php if (floatval($bon['saldo']) < floatval($bon['bedrag'])): ?>
                <p class="saldo">Nog te besteden: <strong><?php echo euroBon($bon['saldo']); ?></strong></p>
            <?php endif; ?>
            <?php if ($bon['boodschap'] !== ''): ?>
                <p class="boodschap" data-geen-vertaling><?php echo esc($bon['boodschap']); ?></p>
            <?php endif; ?>
            <div class="code" data-geen-vertaling><?php echo esc(toonCadeauCode($bon['code'])); ?></div>
            <p class="uitleg">Te gebruiken bij Las Tapas: vul deze code in bij de rekening. Wat je niet opmaakt, blijft op de bon staan.</p>
        </div>
    </div>
    <div class="knoppen">
        <button onclick="window.print()">🖨️ Printen</button>
        <button class="licht" onclick="deel()">📤 Doorsturen</button>
    </div>
    <p class="klein">Bewaar deze code goed: wie de code heeft, kan de cadeaubon gebruiken.<br><a href="privacy.php">🔒 Privacyverklaring</a></p>
    <script>
        function deel() {
            const tekst = 'Een cadeaubon van Las Tapas: <?php echo esc(toonCadeauCode($bon['code'])); ?>';
            if (navigator.share) {
                navigator.share({ title: 'Cadeaubon Las Tapas', text: tekst, url: location.href }).catch(() => {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(location.href).then(() => alert('De link naar de cadeaubon is gekopieerd.'));
            } else {
                prompt('Kopieer deze link:', location.href);
            }
        }
    </script>
<?php else: ?>
    <div class="zoek">
        <h1>🎁 Saldo checken</h1>
        <p class="uitleg" style="text-align: left;">Vul de code van je cadeaubon in om het tegoed te zien.</p>
        <?php if ($bon && $bon['status'] === 'open'): ?>
            <p class="fout">Deze cadeaubon is nog niet betaald en dus nog niet geldig.</p>
        <?php elseif ($melding): ?>
            <p class="fout"><?php echo esc($melding); ?></p>
        <?php endif; ?>
        <form method="GET">
            <input type="text" name="code" placeholder="LT-XXXX-XXXX-XXXX" value="<?php echo esc($invoer); ?>" autocomplete="off" required>
            <button type="submit">Bekijken</button>
        </form>
    </div>
    <p class="klein"><a href="privacy.php">🔒 Privacyverklaring</a></p>
<?php endif; ?>
</body>
</html>
