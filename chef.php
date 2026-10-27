<?php
/*
 * Las Tapas - chef.php
 * Het werkscherm voor de keuken. bar.php gebruikt hetzelfde scherm voor de bar
 * (met $afdeling = 'bar'): de keuken ziet alleen eten, de bar alleen drankjes.
 */
session_start();
$afdeling = (isset($afdeling) && $afdeling === 'bar') ? 'bar' : 'keuken';
$rolVoorAfdeling = $afdeling === 'bar' ? 'bar' : 'chef';

// Beveiliging: alleen de juiste medewerker (chef of bar) en de baas hebben toegang
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== $rolVoorAfdeling && $_SESSION['rol'] !== 'baas')) {
    header("Location: login.html");
    exit();
}
$isBar = $afdeling === 'bar';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isBar ? 'Las Tapas - Barra (Bar)' : 'Las Tapas - Cocina (Keuken)'; ?></title>
    <style>
        :root {
            --rood: #b33939;
            --donkerrood: #822626;
            --creme: #fdfbf7;
            --inkt: #2d211d;
            --grijs: #786a63;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--creme);
            color: var(--inkt);
            margin: 0;
            padding: 0;
        }
        header {
            background: linear-gradient(135deg, var(--rood), var(--donkerrood));
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header-title h1 { margin: 0; font-size: 2rem; font-family: Georgia, serif; }
        .header-title p { margin: 5px 0 0 0; opacity: 0.9; font-size: 0.95rem; }
        
        .user-info {
            text-align: right;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-details {
            text-align: right;
        }
        .btn-actie {
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
            transition: opacity 0.2s;
        }
        .btn-voorraad {
            background: #27ae60;
            color: white;
            margin-right: 5px;
        }
        .btn-voorraad:hover { opacity: 0.85; }
        .btn-switch {
            background: #2980b9;
            color: white;
            margin-right: 5px;
        }
        .btn-switch:hover { background: #2471a3; }
        .btn-baas {
            background: #f39c12;
            color: white;
            margin-right: 5px;
        }
        .btn-baas:hover { background: #d68910; }
        .btn-uitlog {
            background: #c0392b;
            color: white;
        }
        .btn-uitlog:hover { opacity: 0.85; }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .top-controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .etage-rij {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .etage-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fcf8f2;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e0d5cb;
        }
        .etage-box select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
            max-width: 250px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            justify-content: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: #f0eae1;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            color: var(--grijs);
            transition: all 0.2s;
            position: relative;
        }
        .tab-btn.active {
            background: var(--rood);
            color: white;
        }

        /* Badge voor meldingen op tabbladen (bijv. nieuwe bestelling binnen) */
        .badge-melding {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 0.75rem;
            position: absolute;
            top: -5px;
            right: -5px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(231, 76, 60, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        /* Nieuwe orders: zwevende knop en oplichten */
        .nieuw-pijl {
            position: fixed; left: 50%; bottom: 22px; transform: translateX(-50%); z-index: 900;
            background: #c0392b; color: white; border: none; border-radius: 30px; padding: 12px 22px;
            font-size: 1.05rem; font-weight: bold; cursor: pointer; box-shadow: 0 6px 18px rgba(0,0,0,0.3);
            display: flex; align-items: center; gap: 8px; font-family: inherit; animation: pijl-wip 1.4s ease-in-out infinite;
            white-space: nowrap; max-width: calc(100vw - 32px);
        }
        .nieuw-pijl[hidden] { display: none; }
        .nieuw-pijl .aantal { background: white; color: #c0392b; border-radius: 12px; padding: 1px 9px; }
        @keyframes pijl-wip { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, -5px); } }
        .order-kaart.net-binnen { position: relative; animation: net-binnen 1.2s ease-in-out 6; box-shadow: 0 0 0 3px #f1c40f, 0 4px 12px rgba(0,0,0,0.08); }
        @keyframes net-binnen { 50% { box-shadow: 0 0 0 6px rgba(241, 196, 15, 0.45), 0 4px 12px rgba(0,0,0,0.08); } }
        .net-binnen-label { position: absolute; top: -11px; right: 14px; background: #f1c40f; color: #2d211d; font-size: 0.75rem;
                            font-weight: bold; padding: 3px 10px; border-radius: 12px; }
        @media (prefers-reduced-motion: reduce) { .nieuw-pijl, .order-kaart.net-binnen { animation: none; } }
        .groep-kop { grid-column: 1 / -1; margin: 6px 0 -6px 0; font-size: 1.05rem; color: var(--grijs); font-family: inherit;
                     border-bottom: 2px solid #eadfd3; padding-bottom: 6px; }
        .groep-kop.actief { color: #c0392b; }
        .groep-kop span { font-weight: normal; }
        .orders-raster {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-kaart {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-left: 6px solid var(--rood);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .order-kaart.bezig { border-left-color: #f39c12; }
        .order-kaart.klaar { border-left-color: #27ae60; }
        .order-kaart.onderweg { border-left-color: #3498db; }
        .order-kaart.bezorgd { border-left-color: #2ecc71; opacity: 0.8; }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .order-header h3 { margin: 0; color: var(--inkt); font-size: 1.2rem; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge.nieuw { background: #fadbd8; color: #c0392b; }
        .badge.bezig { background: #fef9e7; color: #d35400; }
        .badge.klaar { background: #e8f8f5; color: #16a085; }
        .badge.onderweg { background: #ebf5fb; color: #2980b9; }
        .badge.bezorgd { background: #eafaf1; color: #27ae60; }

        .order-body p { margin: 6px 0; font-size: 0.95rem; }
        .pakket-tag {
            display: inline-block;
            background: #fcf8f2;
            border: 1px solid #e0d5cb;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            color: var(--grijs);
            margin-bottom: 10px;
        }

        .order-footer {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            border-top: 1px solid #f5f5f5;
            padding-top: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-start { background: #27ae60; color: white; }
        .btn-klaar { background: #27ae60; color: white; }
        .btn-verwijder { background: #c0392b; color: white; }
        
        .btn-aanpas { background-color: #8e44ad; color: white; }
        .aangepast-tag { background: #f4ecf7; color: #6c3483; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem !important; font-weight: bold; display: inline-block; }
        .aanpas-overlay { position: fixed; inset: 0; background: rgba(45, 33, 29, 0.6); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 15px; }
        .aanpas-venster { background: white; border-radius: 12px; padding: 22px; width: 100%; max-width: 440px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
        .aanpas-venster h2 { margin: 0; color: var(--rood); font-family: Georgia, serif; font-size: 1.4rem; }
        .aanpas-sub { margin: 4px 0 15px 0; color: var(--grijs); font-weight: bold; }
        .aanpas-regel { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0eae1; }
        .aanpas-naam { font-weight: bold; }
        .aanpas-knoppen { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .aanpas-knoppen button { width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e0d5cb; background: #fcf8f2; font-size: 1.1rem; font-weight: bold; cursor: pointer; }
        .aanpas-knoppen .aanpas-weg { background: white; border-color: #f5c6c0; }
        .aanpas-aantal { min-width: 22px; text-align: center; font-weight: bold; }
        .aanpas-leeg { color: #c0392b; font-weight: bold; font-size: 0.9rem; }
        .aanpas-select { width: 100%; margin-top: 14px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
        .aanpas-fout { color: #c0392b; font-weight: bold; font-size: 0.85rem; min-height: 1em; }
        .aanpas-acties { display: flex; gap: 10px; }
        .aanpas-acties .btn { flex: 1; padding: 12px; font-size: 1rem; }
        .aanpas-annuleer { background: #e0d5cb; color: var(--inkt); }

        .geen-orders {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--grijs);
            font-style: italic;
            background: white;
            border-radius: 12px;
        }

.btn-geluid { background: #8e44ad; color: white; border: none; cursor: pointer; margin-right: 5px; font-family: inherit; }
        .btn-geluid.uit { background: #7f8c8d; }
        .geluid-balk { background: #fff3cd; color: #7d5a00; border-bottom: 2px solid #f1c40f; text-align: center; padding: 10px 16px;
                       font-weight: bold; cursor: pointer; animation: geluid-knipper 1.6s ease-in-out infinite; }
        .geluid-balk[hidden] { display: none; }
        @keyframes geluid-knipper { 50% { background: #ffe8a1; } }
        @media (prefers-reduced-motion: reduce) { .geluid-balk { animation: none; } }
        .opmerking-tag {
            background: #fff3cd; border-left: 4px solid #f39c12; color: #7d5a00;
            padding: 6px 10px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; margin-top: 8px;
        }
        .medewerker-tag {
            background: #eef6fb;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 0.85rem !important;
            color: #1f4e6b;
        }

        /* ===== Responsive: tablet ===== */
        @media (max-width: 700px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 15px 16px;
            }
            .user-info {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                text-align: left;
            }
            .user-details { text-align: left; }
            .container { padding: 0 12px; margin: 15px auto; }
            .etage-rij { grid-template-columns: 1fr; }
        }

        /* ===== Responsive: telefoon ===== */
        @media (max-width: 480px) {
            .header-title h1 { font-size: 1.3rem; }
            .top-controls { padding: 12px; }
            .filter-tabs { justify-content: flex-start; }
            .tab-btn { padding: 8px 12px; font-size: 0.8rem; }
            .orders-raster { grid-template-columns: 1fr; }
        }
            /* Kop gelijk houden in alle talen: titel en knoppen blijven op één regel
           (te lange titels eindigen op een smal scherm met "…" in plaats van dat de balk hoger wordt) */
        @media (min-width: 701px) {
            header { gap: 20px; }
            .header-title { flex: 1 1 auto; min-width: 0; }
            .header-title h1, .header-title p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .user-info { flex: 0 0 auto; }
        }
        /* Tablet / klein laptopscherm: knoppen onder de titel, over de volle breedte */
        @media (min-width: 701px) and (max-width: 1599px) {   /* keuken/bar: meer knoppen, dus eerder onder de titel */
            header { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 12px 20px; }
            .user-info { display: contents; }
            .user-details { grid-column: 2; grid-row: 1; }
            .user-info > div:last-child { grid-column: 1 / -1; display: flex; flex-wrap: nowrap; align-items: center; gap: 6px;
                                           overflow-x: auto; scrollbar-width: thin; padding-bottom: 2px; }
            /* Rechts uitlijnen als alles past; past het niet, dan schuift de rij (geen extra regel) */
            .user-info > div:last-child > :first-child { margin-left: auto; }
            .user-info > div:last-child .btn-actie { flex-shrink: 0; }
            .user-info > div:last-child .btn-actie { margin: 0; }
            .user-details, .user-details span, .btn-actie { white-space: nowrap; }
        }
            /* Telefoon: kop in elke taal even hoog (ondertitel altijd 2 regels ruimte, knoppen op één schuifbare rij) */
        @media (max-width: 700px) {
            .header-title { max-width: 100%; min-width: 0; }
            .header-title h1 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .header-title p { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
                              line-height: 1.3; min-height: 2.6em; }
            .user-info { flex-direction: column; align-items: stretch; gap: 10px; }
            .user-details, .user-details span { white-space: nowrap; }
            .user-info > div:last-child { display: flex; flex-wrap: nowrap; align-items: center; gap: 6px; overflow-x: auto;
                                          width: 100%; padding-bottom: 2px; scrollbar-width: thin; }
            .user-info > div:last-child .btn-actie { flex-shrink: 0; margin: 0; white-space: nowrap; }
            /* Etage-vakken: nooit breder dan het scherm (Engelse/Spaanse teksten zijn langer) */
            .etage-rij { grid-template-columns: minmax(0, 1fr); }
            .etage-box { min-width: 0; gap: 8px; }
            .etage-box span { min-width: 0; }
            .etage-box select { min-width: 0; flex: 0 1 55%; }
        }
    </style>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<body>

    <header>
        <div class="header-title">
            <?php if ($isBar): ?>
                <h1>🍷 Las Tapas - Barra (Bar)</h1>
                <p>Live overzicht van bestelde drankjes</p>
            <?php else: ?>
                <h1>🍷 Las Tapas - Cocina (Keuken)</h1>
                <p>Live overzicht van binnenkomende Spaanse lekkernijen</p>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-details">
                <span>Ingelogd als: <strong><?php echo htmlspecialchars($_SESSION['gebruiker']); ?></strong></span>
            </div>
            <div>
                <span id="taal-plek" style="margin-right: 5px;"></span>
                <button id="geluid-knop" class="btn-actie btn-geluid uit" onclick="wisselGeluid()">🔕 Geluid uit</button>
                <?php require_once __DIR__ . '/navigatie.php'; toonNavigatie(); ?>
            </div>
        </div>
    </header>
    <?php toonSectieTabs(); ?>
    <div id="geluid-balk" class="geluid-balk" hidden onclick="ontgrendelGeluid()">🔔 Tik ergens op het scherm om het geluid van nieuwe orders aan te zetten</div>

    <div class="container">
        <div class="top-controls">
            <div class="etage-rij">
                <div class="etage-box">
                    <span>🏢 Begane grond (Tafels 1 - 10)</span>
                    <select id="filter-etage-1">
                        <option value="">-- Status tafels begane grond --</option>
                    </select>
                </div>
                <div class="etage-box">
                    <span>🏢 1e verdieping (Tafels 11 - 26)</span>
                    <select id="filter-etage-2">
                        <option value="">-- Status tafels 1e verdieping --</option>
                    </select>
                </div>
            </div>

            <!-- Filter tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" onclick="zetTab('alles', this)">
                    Openstaande & Actieve Orders 🔥 <span id="nieuw-badge" class="badge-melding" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="zetTab('klaar', this)">Voltooide Orders ✅</button>
                <button class="tab-btn" onclick="zetTab('bezorging', this)">Bezorgstatus 🏃‍♂️</button>
            </div>
        </div>

        <div class="orders-raster" id="orders-raster">
            <div class="geen-orders">Bestellingen laden...</div>
        </div>
    </div>

    <!-- Venster: bestelling aanpassen -->
    <div id="aanpas-modal" class="aanpas-overlay" style="display: none;" onclick="if (event.target === this) sluitAanpassen()">
        <div class="aanpas-venster">
            <h2>✏️ Bestelling aanpassen</h2>
            <p class="aanpas-sub" id="aanpas-titel"></p>
            <div id="aanpas-regels"></div>
            <select id="aanpas-nieuw" class="aanpas-select" onchange="voegRegelToe(this.value)"></select>
            <p id="aanpas-melding" class="aanpas-fout"></p>
            <div class="aanpas-acties">
                <button type="button" class="btn aanpas-annuleer" onclick="sluitAanpassen()">Annuleren</button>
                <button type="button" class="btn btn-klaar" id="aanpas-opslaan" onclick="slaAanpassingOp()">Opslaan</button>
            </div>
        </div>
    </div>

    <script>
        // Voor ngrok (gratis versie): sla de ngrok-waarschuwingspagina over bij
        // alle data-aanvragen, anders krijgt de pagina HTML terug in plaats van data.
        (function () {
            const origineleFetch = window.fetch;
            window.fetch = function (url, opties = {}) {
                opties.headers = Object.assign({}, opties.headers, { 'ngrok-skip-browser-warning': 'true' });
                return origineleFetch(url, opties);
            };
        })();

        // Keuken of bar? (de keuken krijgt eten, de bar krijgt drankjes)
        const AFDELING = <?php echo json_encode($afdeling); ?>;
        const hoortBijAfdeling = cat => AFDELING === 'bar' ? cat === 'drankje' : cat !== 'drankje';

        // Keuzelijst "gerecht aanpassen": wordt gevuld vanuit het menu in de database
        let menuLijst = [];   // alle gerechten van het menu (voor het aanpasvenster)

        function laadMenuOpties() {
            fetch('bestelling.php?view=config&_=' + Date.now(), { cache: 'no-store' })
                .then(res => res.json())
                .then(cfg => {
                    menuLijst = (cfg.menu || []).filter(m => hoortBijAfdeling(m.categorie))
                                                .map(m => ({ naam: m.naam, categorie: m.categorie }));
                })
                .catch(err => console.error('Fout bij laden menu:', err));
        }

        // ===== Geluid bij nieuwe meldingen =====
        // Browsers spelen pas geluid af na een tik/klik op de pagina; daarom een aan/uit-knop.
        let geluidAan = false;
        try { geluidAan = localStorage.getItem('geluid_aan') === '1'; } catch (e) {}
        let audioCtx = null;

        function maakAudio() {
            if (!audioCtx) {
                const AC = window.AudioContext || window.webkitAudioContext;
                if (AC) audioCtx = new AC();
            }
            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
        }

        function speelPling() {
            if (!geluidAan) return;
            maakAudio();
            if (!audioCtx) return;
            const nu = audioCtx.currentTime;
            [880, 1320].forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const vol = audioCtx.createGain();
                const start = nu + i * 0.18;
                osc.type = 'sine';
                osc.frequency.value = freq;
                vol.gain.setValueAtTime(0.0001, start);
                vol.gain.exponentialRampToValueAtTime(0.3, start + 0.02);
                vol.gain.exponentialRampToValueAtTime(0.0001, start + 0.35);
                osc.connect(vol).connect(audioCtx.destination);
                osc.start(start);
                osc.stop(start + 0.4);
            });
        }

        function zetGeluidKnop() {
            const knop = document.getElementById('geluid-knop');
            if (!knop) return;
            knop.innerText = geluidAan ? '🔔 Geluid aan' : '🔕 Geluid uit';
            knop.classList.toggle('uit', !geluidAan);
        }

        function wisselGeluid() {
            geluidAan = !geluidAan;
            try { localStorage.setItem('geluid_aan', geluidAan ? '1' : '0'); } catch (e) {}
            if (geluidAan) { maakAudio(); speelPling(); }
            zetGeluidKnop();
            setTimeout(updateGeluidBalk, 150);
        }

        // Browsers spelen pas geluid af na een aanraking OP DEZE pagina (een klik op de vorige pagina telt niet).
        // Zolang dat nog niet gebeurd is en het geluid aan staat, tonen we een balk "tik om geluid aan te zetten".
        function geluidGeblokkeerd() {
            return geluidAan && (!audioCtx || audioCtx.state !== 'running');
        }
        function updateGeluidBalk() {
            const balk = document.getElementById('geluid-balk');
            if (balk) balk.hidden = !geluidGeblokkeerd();
        }
        function ontgrendelGeluid() {
            if (!geluidAan) { updateGeluidBalk(); return; }
            maakAudio();
            if (audioCtx && audioCtx.state !== 'running') audioCtx.resume().then(updateGeluidBalk).catch(() => {});
            setTimeout(updateGeluidBalk, 150);
        }
        // Elke aanraking telt: klikken, tikken of een toets
        ['pointerdown', 'touchstart', 'keydown'].forEach(ev => document.addEventListener(ev, ontgrendelGeluid, { passive: true }));
        document.addEventListener('DOMContentLoaded', () => {
            if (geluidAan) maakAudio();          // sommige browsers staan het meteen toe
            setTimeout(updateGeluidBalk, 400);   // anders verschijnt de balk
        });
        document.addEventListener('DOMContentLoaded', zetGeluidKnop);



        // Tekst veilig tonen: voorkomt dat een klant via zijn naam code op dit scherm kan uitvoeren
        function esc(tekst) {
            return String(tekst ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        let huidigeTab = 'alles';
        let alleBestellingenCache = [];
        let tafelsStatusCache = {};
        let bezorgdTimestamps = {};

        function laadData() {
            // Laad zowel bestellingen als de tafels status tegelijkertijd op in WAMP
            Promise.all([
                fetch('bestelling.php').then(res => res.json()),
                fetch('bestelling.php?view=tafels').then(res => res.json())
            ])
            .then(([bestellingenData, tafelsData]) => {
                let nu = Date.now();
                let opgeschoondeData = [];

                (bestellingenData || []).forEach(order => {
                    if ((order.afdeling || 'keuken') !== AFDELING) return;   // bestelling voor de andere afdeling
                    if (order.status === 'bezorgd') {
                        if (!bezorgdTimestamps[order.id]) {
                            bezorgdTimestamps[order.id] = nu;
                        }
                        let verstrekenTijd = nu - bezorgdTimestamps[order.id];
                        if (verstrekenTijd > 10 * 60 * 1000) {
                            return; // Na 10 minuten automatisch verbergen
                        }
                    } else {
                        delete bezorgdTimestamps[order.id];
                    }
                    opgeschoondeData.push(order);
                });

                alleBestellingenCache = opgeschoondeData;
                tafelsStatusCache = tafelsData || {};
                
                updateTafelDropdowns();
                updateNieuwBadge();
                filterBestellingen();
                updateNieuwPijl();
            })
            .catch(err => console.error('Fout bij ophalen keukendata:', err));
        }

        function updateTafelDropdowns() {
            // Vul de begane grond (Tafels 1 t/m 10)
            const select1 = document.getElementById('filter-etage-1');
            let html1 = '<option value="">-- Status tafels begane grond --</option>';
            for (let i = 1; i <= 10; i++) {
                let tafelInfo = tafelsStatusCache[i];
                let isBezet = tafelInfo && tafelInfo.status === 'bezet';
                let statusTekst = isBezet ? `Bezet (${esc(tafelInfo.gast_naam || 'Gast')})` : 'Open';
                let emoji = isBezet ? '🔴' : '🟢';
                html1 += `<option value="${i}">${emoji} Tafel ${i}: ${statusTekst}</option>`;
            }
            select1.innerHTML = html1;

            // Vul de eerste verdieping (Tafels 11 t/m 26)
            const select2 = document.getElementById('filter-etage-2');
            let html2 = '<option value="">-- Status tafels 1e verdieping --</option>';
            for (let i = 11; i <= 26; i++) {
                let tafelInfo = tafelsStatusCache[i];
                let isBezet = tafelInfo && tafelInfo.status === 'bezet';
                let statusTekst = isBezet ? `Bezet (${esc(tafelInfo.gast_naam || 'Gast')})` : 'Open';
                let emoji = isBezet ? '🔴' : '🟢';
                html2 += `<option value="${i}">${emoji} Tafel ${i}: ${statusTekst}</option>`;
            }
            select2.innerHTML = html2;
        }

        function zetBadge(id, count) {
            const badge = document.getElementById(id);
            if (!badge) return;
            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.innerText = count;
            } else {
                badge.style.display = 'none';
            }
        }

        let bekendeNieuweOrders = null;   // null = eerste keer laden (dan nog geen geluid)
        const netBinnen = {};              // order-id -> tijdstip waarop hij binnenkwam (voor het oplichten)
        const OPLICHTEN_MS = 20000;        // zo lang licht een net binnengekomen order op

        function controleerNieuweOrders() {
            const nieuw = alleBestellingenCache.filter(o => o.status === 'nieuw').map(o => String(o.id));
            if (bekendeNieuweOrders !== null) {
                const binnen = nieuw.filter(id => !bekendeNieuweOrders.has(id));
                if (binnen.length) speelPling();
                binnen.forEach(id => { netBinnen[id] = Date.now(); });
            }
            bekendeNieuweOrders = new Set(nieuw);
        }

        // ===== Zwevende knop: nieuwe orders die buiten beeld staan =====
        function updateNieuwPijl() {
            const knop = document.getElementById('nieuw-pijl');
            const aantalNieuw = alleBestellingenCache.filter(o => o.status === 'nieuw').length;
            let richting = '', aantal = 0;
            if (aantalNieuw > 0 && huidigeTab !== 'alles') {
                // Op een ander tabblad: knop brengt je naar de nieuwe orders
                richting = '📂'; aantal = aantalNieuw;
            } else if (aantalNieuw > 0) {
                const kaarten = [...document.querySelectorAll('.order-kaart[data-status="nieuw"]')];
                const onder = kaarten.filter(k => k.getBoundingClientRect().top > window.innerHeight - 80);
                const boven = kaarten.filter(k => k.getBoundingClientRect().bottom < 60);
                if (onder.length) { richting = '⬇'; aantal = onder.length; }
                else if (boven.length) { richting = '⬆'; aantal = boven.length; }
            }
            if (!aantal) { knop.hidden = true; return; }
            knop.querySelector('.pijl').textContent = richting;
            knop.querySelector('.aantal').textContent = aantal;
            knop.querySelector('.tekst').textContent = aantal === 1 ? 'nieuwe order' : 'nieuwe orders';
            knop.hidden = false;
        }

        function naarNieuweOrders() {
            if (huidigeTab !== 'alles') {
                zetTab('alles', document.querySelector('.filter-tabs .tab-btn'));
            }
            const kaarten = [...document.querySelectorAll('.order-kaart[data-status="nieuw"]')];
            // Eerst de nieuwe order die nog buiten beeld staat, anders het kopje "Openstaand"
            const doel = kaarten.find(k => k.getBoundingClientRect().top > window.innerHeight - 80 || k.getBoundingClientRect().bottom < 60)
                      || document.querySelector('.groep-kop:not(.actief)') || kaarten[0];
            if (doel) window.scrollTo({ top: doel.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
        }
        window.addEventListener('scroll', updateNieuwPijl, { passive: true });
        window.addEventListener('resize', updateNieuwPijl);

        function updateNieuwBadge() {
            controleerNieuweOrders();
            // Openstaande (nieuwe) orders en actieve orders (in bereiding) krijgen een melding
            // Eén tabblad voor openstaand + actief: de melding telt alle orders die nog gemaakt moeten worden
            zetBadge('nieuw-badge', alleBestellingenCache.filter(o => o.status === 'nieuw' || o.status === 'bezig').length);
        }

        function zetTab(tab, element) {
            huidigeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            filterBestellingen();
        }

        function filterBestellingen() {
            const raster = document.getElementById('orders-raster');
            let data = alleBestellingenCache;

            if (huidigeTab === 'alles') {
                // Actieve orders (in bereiding) bovenaan, daaronder de nieuwe orders die nog opgepakt moeten worden
                data = [...data.filter(o => o.status === 'bezig'), ...data.filter(o => o.status === 'nieuw')];
            } else if (huidigeTab === 'klaar') {
                data = data.filter(o => o.status === 'klaar');
            } else if (huidigeTab === 'bezorging') {
                data = data.filter(o => o.status === 'onderweg' || o.status === 'bezorgd');
            }

            if (!data || data.length === 0) {
                raster.innerHTML = '<div class="geen-orders">¡Todo listo! Geen orders in deze categorie.</div>';
                return;
            }

            // Kopjes tussen de groepen op het tabblad "Openstaande & Actieve Orders"
            const kopje = (order, i) => {
                if (huidigeTab !== 'alles' || (i > 0 && data[i - 1].status === order.status)) return '';
                const aantal = data.filter(o => o.status === order.status).length;
                return order.status === 'bezig'
                    ? `<h3 class="groep-kop actief">🔥 Actief: in bereiding <span>(${aantal})</span></h3>`
                    : `<h3 class="groep-kop">📂 Openstaand: nog oppakken <span>(${aantal})</span></h3>`;
            };

            raster.innerHTML = data.map((order, i) => {
                let statusKlasse = order.status || 'nieuw';



                let isBezorgTab = (huidigeTab === 'bezorging');

                let voorraadWaarschuwing = '';
                if (order.voorraad_aantal !== null && order.voorraad_aantal <= 5) {
                    voorraadWaarschuwing = `<div style="background: #fadbd8; color: #c0392b; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; margin-top: 8px; border-left: 4px solid #c0392b;">⚠️ Let op: Nog maar ${order.voorraad_aantal} over in voorraad!</div>`;
                }

                return kopje(order, i) + `
                    <div class="order-kaart ${esc(statusKlasse)}${order.status === 'nieuw' && netBinnen[order.id] && Date.now() - netBinnen[order.id] < OPLICHTEN_MS ? ' net-binnen' : ''}" data-status="${esc(statusKlasse)}">
                        ${order.status === 'nieuw' && netBinnen[order.id] && Date.now() - netBinnen[order.id] < OPLICHTEN_MS ? '<span class="net-binnen-label">🆕 Net binnen</span>' : ''}
                        <div>
                            <div class="order-header">
                                <h3>Mesa ${order.tafel} (Tafel)</h3>
                                <span class="badge ${esc(statusKlasse)}">${esc(order.status)}</span>
                            </div>
                            <div class="pakket-tag">📦 ${esc(order.pakket || 'Onbekend arrangement')}</div>
                            <div class="order-body">
                                <p><strong>${AFDELING === 'bar' ? 'Drankjes' : 'Tapa / Gerecht'}:</strong> ${esc(order.gerecht)}</p>
                                ${Number(order.aangepast) ? '<p class="aangepast-tag">✏️ Aangepast door de keuken</p>' : ''}
                                <p><small style="color: var(--grijs);">🕒 Besteld om: ${esc(order.tijd || '--:--')}</small></p>
                                ${order.chef_naam ? `<p class="medewerker-tag">👨‍🍳 Geaccepteerd door: <strong>${esc(order.chef_naam)}</strong></p>` : ''}
                                ${order.serveerster_naam ? `<p class="medewerker-tag">🏃‍♀️ ${order.status === 'bezorgd' ? 'Bezorgd door' : 'Onderweg met'}: <strong>${esc(order.serveerster_naam)}</strong></p>` : ''}
                                ${order.opmerking ? `<div class="opmerking-tag">📝 ${esc(order.opmerking)}</div>` : ''}
                                ${voorraadWaarschuwing}
                            </div>
                        </div>
                        <div class="order-footer">
                            ${!isBezorgTab ? `
                                ${order.status === 'nieuw' ? `<button class="btn btn-start" onclick="updateStatus(${order.id}, 'bezig')">Start Bereiding</button>` : ''}
                                ${order.status !== 'klaar' ? `<button class="btn btn-klaar" onclick="updateStatus(${order.id}, 'klaar')">Klaar</button>` : ''}
                                
                                ${(order.status === 'nieuw' || order.status === 'bezig')
                                    ? `<button class="btn btn-aanpas" onclick="openAanpassen(${Number(order.id)})">✏️ Aanpassen</button>` : ''}

                                <button class="btn btn-verwijder" onclick="verwijderOrder(${order.id})">Verwijderen</button>
                            ` : `
                                <p style="margin: 0; font-size: 0.85rem; color: var(--grijs);"><em>Status wordt beheerd door bediening (Verdwijnt 10 min na bezorging uit dit overzicht)</em></p>
                            `}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateStatus(id, nieuweStatus) {
            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'update_status', id: id, nieuwe_status: nieuweStatus })
            })
            .then(res => res.json())
            .then(data => { if (!data.succes && data.melding) alert(data.melding); laadData(); });
        }

        // ===== Bestelling aanpassen (bijv. als een gerecht op is) =====
        let aanpasId = null;
        let aanpasRegels = [];   // [{ naam, aantal }]

        function openAanpassen(id) {
            const order = alleBestellingenCache.find(o => Number(o.id) === Number(id));
            if (!order) return;
            aanpasId = order.id;
            aanpasRegels = String(order.gerecht).split(',').map(deel => {
                const m = deel.trim().match(/^(\d+)\s*x\s+(.+)$/i);
                return m ? { naam: m[2].trim(), aantal: parseInt(m[1]) } : null;
            }).filter(Boolean);

            document.getElementById('aanpas-titel').innerText = `Mesa ${order.tafel} (Tafel)`;
            document.getElementById('aanpas-melding').innerText = '';
            const kies = document.getElementById('aanpas-nieuw');
            const groepen = { voorgerecht: 'Koude tapas', hoofdgerecht: 'Warme tapas', specialiteit: 'Specialiteiten', toetje: 'Toetjes', drankje: 'Drankjes' };
            kies.innerHTML = '<option value="">➕ Gerecht toevoegen...</option>' + Object.entries(groepen).filter(([cat]) => menuLijst.some(m => m.categorie === cat)).map(([cat, label]) =>
                `<optgroup label="${esc(label)}">` + menuLijst.filter(m => m.categorie === cat)
                    .map(m => `<option value="${esc(m.naam)}">${esc(m.naam)}</option>`).join('') + '</optgroup>'
            ).join('');
            tekenAanpasRegels();
            document.getElementById('aanpas-modal').style.display = 'flex';
        }

        function tekenAanpasRegels() {
            const lijst = document.getElementById('aanpas-regels');
            if (aanpasRegels.length === 0) {
                lijst.innerHTML = '<p class="aanpas-leeg">Alle gerechten zijn weggehaald. Bij opslaan wordt de hele bestelling verwijderd.</p>';
                return;
            }
            lijst.innerHTML = aanpasRegels.map((r, i) => `
                <div class="aanpas-regel">
                    <span class="aanpas-naam">${esc(r.naam)}</span>
                    <div class="aanpas-knoppen">
                        <button type="button" onclick="wijzigAantal(${i}, -1)" aria-label="Minder">−</button>
                        <span class="aanpas-aantal">${r.aantal}</span>
                        <button type="button" onclick="wijzigAantal(${i}, 1)" aria-label="Meer">+</button>
                        <button type="button" class="aanpas-weg" onclick="verwijderRegel(${i})" aria-label="Weghalen">🗑️</button>
                    </div>
                </div>`).join('');
        }

        function wijzigAantal(i, stap) {
            aanpasRegels[i].aantal = Math.max(0, Math.min(20, aanpasRegels[i].aantal + stap));
            if (aanpasRegels[i].aantal === 0) aanpasRegels.splice(i, 1);
            tekenAanpasRegels();
        }

        function verwijderRegel(i) {
            aanpasRegels.splice(i, 1);
            tekenAanpasRegels();
        }

        function voegRegelToe(naam) {
            if (!naam) return;
            const bestaand = aanpasRegels.find(r => r.naam === naam);
            if (bestaand) bestaand.aantal = Math.min(20, bestaand.aantal + 1);
            else aanpasRegels.push({ naam: naam, aantal: 1 });
            document.getElementById('aanpas-nieuw').value = '';
            tekenAanpasRegels();
        }

        function sluitAanpassen() {
            document.getElementById('aanpas-modal').style.display = 'none';
            aanpasId = null;
        }

        function slaAanpassingOp() {
            if (aanpasId === null) return;
            // Alles weggehaald? Dan de hele bestelling verwijderen (met bevestiging)
            if (aanpasRegels.length === 0) {
                const id = aanpasId;
                sluitAanpassen();
                verwijderOrder(id);
                return;
            }
            const knop = document.getElementById('aanpas-opslaan');
            knop.disabled = true;
            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    actie: 'bewerk_gerecht',
                    id: aanpasId,
                    gerecht: aanpasRegels.map(r => `${r.aantal}x ${r.naam}`).join(', ')
                })
            })
            .then(res => res.json())
            .then(data => {
                knop.disabled = false;
                if (data.succes) {
                    sluitAanpassen();
                } else {
                    document.getElementById('aanpas-melding').innerText = data.melding || 'Fout bij opslaan van het gerecht.';
                }
                laadData();
            })
            .catch(() => {
                knop.disabled = false;
                document.getElementById('aanpas-melding').innerText = 'Kan de server niet bereiken.';
            });
        }

        function verwijderOrder(id) {
            if (confirm('Weet je zeker dat je deze bestelling wilt verwijderen?')) {
                fetch('bestelling.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ actie: 'verwijder', id: id })
                })
                .then(res => res.json())
                .then(data => { if (!data.succes) alert(data.melding || 'Verwijderen is niet gelukt.'); laadData(); });
            }
        }

        laadMenuOpties();
        setInterval(laadMenuOpties, 60000);
        setInterval(laadData, 3000);
        laadData();
    </script>
    <!-- Zwevende knop: er staan nieuwe orders buiten beeld -->
    <button type="button" id="nieuw-pijl" class="nieuw-pijl" hidden onclick="naarNieuweOrders()">
        <span class="pijl">⬇</span> <span class="aantal" data-geen-vertaling>1</span> <span class="tekst">nieuwe order</span>
    </button>
</body>
</html>