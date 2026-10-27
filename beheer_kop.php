<?php
/*
 * Las Tapas - beheer_kop.php
 * Gedeelde stijl en menubalk voor de beheerpagina's van de baas.
 * Gebruik: $paginaTitel en $actievePagina instellen, dan: include 'beheer_kop.php';
 */
$isBaas = ($_SESSION['rol'] ?? '') === 'baas';   // een serveerster ziet alleen de QR-pagina, zonder beheermenu
$beheerPaginas = [
    'baas_paneel.php'   => '👥 Medewerkers',
    'menu_beheer.php'   => '🍽️ Menu & prijzen',
    'korting_beheer.php' => '🎁 Korting & cadeaubonnen',
    'statistieken.php'  => '📊 Statistieken',
    'qr_overzicht.php'  => '🔳 QR-codes',
    'reserveringen.php' => '📅 Reserveringen',
];
$rolNamen = ['baas' => 'Eigenaar', 'serveerster' => 'Serveerster', 'chef' => 'Chef-kok', 'bar' => 'Barman'];
$mijnRol = $_SESSION['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Tapas - <?php echo esc($paginaTitel ?? 'Beheer'); ?></title>
    <style>
        :root { --rood: #b33939; --donkerrood: #822626; --creme: #fdfbf7; --inkt: #2d211d; --grijs: #786a63; --lijn: #e0d5cb; --vlak: #fcf8f2; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: var(--creme); color: var(--inkt); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1, h2, h3 { color: var(--rood); font-family: Georgia, serif; }
        h2 { margin-top: 30px; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .header-flex h1 { margin: 0 0 4px 0; }
        .header-flex p { margin: 0; }
        .header-knoppen { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-uitlog { background: #786a63; text-decoration: none; padding: 8px 15px; border-radius: 6px; color: white; font-size: 0.9rem; font-weight: bold; }

        /* Menubalk tussen de beheerpagina's */
        .beheer-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 25px; }
        .beheer-nav a { background: #f0eae1; color: var(--grijs); text-decoration: none; padding: 9px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .beheer-nav a.actief { background: var(--rood); color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: top; }
        th { background: var(--vlak); color: var(--grijs); }
        .tabel-scroll { overflow-x: auto; }

        .form-box { background: var(--vlak); padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid var(--lijn); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { font-weight: bold; font-size: 0.9rem; }
        input[type=text], input[type=password], input[type=number], select, textarea {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 0.95rem;
        }
        button, .knop { padding: 10px 20px; background: var(--rood); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-family: inherit; font-size: 0.95rem; text-decoration: none; display: inline-block; }
        button:hover, .knop:hover { background: var(--donkerrood); }
        .btn-bewerk { background: #f39c12; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; display: inline-block; }
        .btn-verwijder { background: white; color: #c0392b; border: 2px solid #c0392b; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; margin-top: 0; cursor: pointer; }
        .btn-verwijder:hover { background: #fdecea; }
        .btn-annuleer { background: #786a63; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 15px; font-weight: bold; }
        .inline-form { display: inline; margin: 0; }

        .melding { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .melding.ok { background: #eafaf1; color: #1e8449; border: 1px solid #abebc6; }
        .melding.fout { background: #fdecea; color: #922b21; border: 1px solid #f5c6c0; }
        .uitleg { color: var(--grijs); font-size: 0.9rem; }

        /* Telefoon: tabellen als kaarten */
        @media (max-width: 700px) {
            body { padding: 10px; }
            .container { padding: 15px; }
            .form-grid { grid-template-columns: 1fr; }
            h1 { font-size: 1.5rem; }
            table.kaarten thead { display: none; }
            table.kaarten, table.kaarten tbody, table.kaarten tr, table.kaarten td { display: block; width: 100%; }
            table.kaarten tr { background: var(--vlak); border: 1px solid var(--lijn); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; }
            table.kaarten td { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 6px 0; border-bottom: none; }
            table.kaarten td::before { content: attr(data-label); color: var(--grijs); font-size: 0.85rem; font-weight: bold; }
            table.kaarten td.cel-naam { font-size: 1.1rem; font-weight: bold; }
            table.kaarten td.cel-acties { gap: 16px; margin-top: 10px; padding-top: 14px; border-top: 1px solid var(--lijn); }
            table.kaarten td.cel-acties::before { display: none; }
            .cel-acties > * { flex: 1; text-align: center; }
            .cel-acties .btn-bewerk, .cel-acties .btn-verwijder { width: 100%; padding: 12px 10px; font-size: 0.95rem; }
        }

        /* Kop: titel links, taal en (voor de baas) het uitklapmenu rechts; de paginaknoppen op een eigen rij eronder */
        .header-flex { flex-wrap: wrap; }
        .header-flex > div:first-child { flex: 1 1 260px; min-width: 0; }
        .header-flex h1, .header-flex p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-knoppen { align-items: center; flex-shrink: 0; }
        .kop-pagina-knoppen { flex: 1 1 100%; }
        .kop-pagina-knoppen .nav-knoppen { display: flex; flex-wrap: wrap; }
        /* ===== Rode kop (werkplek-pagina's), gelijk aan voorraad.php ===== */
        body.met-rode-kop { padding: 0; }
        body.met-rode-kop .container { margin: 30px auto; width: calc(100% - 40px); }
        header.rode-kop { background: linear-gradient(135deg, #b33939, #822626); color: white; padding: 20px 30px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); gap: 20px; }
        .rode-kop .header-title h1 { margin: 0; font-size: 2rem; font-family: Georgia, serif; color: white; }
        .rode-kop .header-title p { margin: 5px 0 0 0; opacity: 0.9; font-size: 0.95rem; }
        .rode-kop .user-info { text-align: right; display: flex; align-items: center; gap: 12px; }
        .rode-kop .user-info > div:last-child { display: flex; align-items: center; gap: 6px; }
        @media (min-width: 701px) {
            .rode-kop .header-title { flex: 1 1 auto; min-width: 0; }
            .rode-kop .header-title h1, .rode-kop .header-title p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .rode-kop .user-info { flex: 0 0 auto; }
        }
        @media (min-width: 701px) and (max-width: 1365px) {
            header.rode-kop { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 12px 20px; }
            .rode-kop .user-info { display: contents; }
            .rode-kop .user-details { grid-column: 2; grid-row: 1; white-space: nowrap; }
            .rode-kop .user-info > div:last-child { grid-column: 1 / -1; flex-wrap: nowrap; overflow-x: auto; scrollbar-width: thin; padding-bottom: 2px; }
            .rode-kop .user-info > div:last-child > :first-child { margin-left: auto; }
        }
        @media (max-width: 700px) {
            header.rode-kop { flex-direction: column; align-items: stretch; gap: 12px; padding: 15px 16px; }
            .rode-kop .header-title h1 { font-size: 1.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .rode-kop .header-title p { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; min-height: 2.6em; }
            .rode-kop .user-info { flex-direction: column; align-items: stretch; gap: 10px; text-align: left; }
            .rode-kop .user-info > div:last-child { flex-wrap: wrap; }
            body.met-rode-kop .container { margin: 15px auto; width: calc(100% - 20px); }
        }
        <?php echo $extraStijl ?? ''; ?>
    </style>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<?php if (!empty($rodeKop)): ?>
<body class="met-rode-kop">
<!-- Werkplek-pagina (bijv. Reserveringen): dezelfde rode kop als Keuken, Bar, Bediening en Voorraad -->
<header class="rode-kop">
    <div class="header-title">
        <h1><?php echo esc($kopTitel ?? ('🍷 Las Tapas - ' . ($paginaTitel ?? ''))); ?></h1>
        <p><?php echo esc($kopOndertitel ?? ''); ?></p>
    </div>
    <div class="user-info">
        <div class="user-details">
            <span>Ingelogd als: <strong data-geen-vertaling><?php echo esc($_SESSION['gebruiker'] ?? ''); ?></strong></span>
        </div>
        <div>
            <span id="taal-plek" style="margin-right: 5px;"></span>
            <?php require_once __DIR__ . '/navigatie.php'; toonNavigatie(); ?>
        </div>
    </div>
</header>
<div class="container">
<?php else: ?>
<body>
<div class="container">
    <div class="header-flex">
        <div>
            <h1>🍷 Las Tapas - Beheer</h1>
            <p>Welkom, <strong><?php echo esc($_SESSION['gebruiker'] ?? ''); ?></strong> (<?php echo esc($rolNamen[$mijnRol] ?? $mijnRol); ?>)</p>
        </div>
        <div class="header-knoppen">
            <span id="taal-plek"></span>
            <?php require_once __DIR__ . '/navigatie.php'; toonOnderdeelMenu(); ?>
        </div>
        <div class="kop-pagina-knoppen"><?php toonNavigatie(true); ?></div>
    </div>
    <?php endif; ?>
