<?php
session_start();
// Beveiliging: Alleen serveersters én de baas hebben toegang tot het bedieningsscherm
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'serveerster' &&$_SESSION['rol'] !== 'baas')) {
    header("Location: login.html"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Tapas - Sala (Bediening)</title>
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
            max-width: 220px;
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

        /* Badge voor meldingen op tabbladen */
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
        
        .order-kaart.nieuw { border-left-color: #e74c3c; }
        .order-kaart.bezig { border-left-color: #f39c12; }
        .order-kaart.klaar { border-left-color: #27ae60; }
        .order-kaart.onderweg { border-left-color: #3498db; }
        .order-kaart.bezorgd { border-left-color: #2ecc71; opacity: 0.8; }
        .order-kaart.betalen { border-left-color: #e74c3c; background: #fff5f5; }
        .order-kaart.geaccepteerd { border-left-color: #f39c12; background: #fffaf0; }
        .badge.geaccepteerd { background: #fef3cd; color: #b7791f; }

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
        .badge.betalen { background: #fadbd8; color: #c0392b; }

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
            width: 100%;
            text-align: center;
        }
        .btn:hover { opacity: 0.85; }
        .btn-onderweg { background: #2980b9; color: white; }
        .btn-bezorgd { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }

        .geen-orders {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--grijs);
            font-style: italic;
            background: white;
            border-radius: 12px;
        }

.betaalmethode { font-weight: bold; padding: 6px 8px; border-radius: 6px; }
        .betaalmethode.online { background: #ebf5fb; color: #1f618d; }
        .betaalmethode.contant { background: #eafaf1; color: #1e8449; }
        .rekening { background: #fcf8f2; border: 1px solid #e0d5cb; border-radius: 8px; padding: 8px 10px; margin: 8px 0; }
        .rekening-regel { display: flex; justify-content: space-between; gap: 10px; font-size: 0.85rem; padding: 2px 0; }
        .rekening-totaal { display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #e0d5cb; margin-top: 4px; padding-top: 5px; }
        .res-blok { grid-column: 1 / -1; background: #eef4fb; border: 2px solid #1f4e8c; border-radius: 12px; padding: 12px 14px; }
        .res-kop { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-weight: bold; color: #1f4e8c; margin-bottom: 8px; }
        .res-kop a { color: #1f4e8c; font-size: 0.9rem; }
        .res-rij { display: grid; grid-template-columns: 56px 110px 1fr 56px auto; gap: 8px; align-items: center; padding: 6px 0; border-top: 1px solid #d6e2f0; font-size: 0.92rem; }
        .res-rij.binnen { opacity: 0.65; }
        .res-st { font-size: 0.8rem; font-weight: bold; }
        .res-waarschuwing { background: #eef4fb; color: #1f4e8c; padding: 6px 10px; border-radius: 8px; }
        @media (max-width: 600px) { .res-rij { grid-template-columns: 50px 70px 1fr; } .res-rij > span:nth-child(4), .res-rij .res-st { grid-column: auto; } }
        .btn-geluid { background: #8e44ad; color: white; border: none; cursor: pointer; margin-right: 5px; font-family: inherit; }
        .btn-geluid.uit { background: #7f8c8d; }
        .geluid-balk { background: #fff3cd; color: #7d5a00; border-bottom: 2px solid #f1c40f; text-align: center; padding: 10px 16px;
                       font-weight: bold; cursor: pointer; animation: geluid-knipper 1.6s ease-in-out infinite; }
        .geluid-balk[hidden] { display: none; }
        @keyframes geluid-knipper { 50% { background: #ffe8a1; } }
        @media (prefers-reduced-motion: reduce) { .geluid-balk { animation: none; } }
        .groep-kop { grid-column: 1 / -1; margin: 6px 0 -6px 0; font-size: 1.05rem; color: var(--grijs); font-family: inherit;
                     border-bottom: 2px solid #eadfd3; padding-bottom: 6px; }
        .groep-kop.actief { color: #2980b9; }
        .groep-kop span { font-weight: normal; }
        .afdeling-tag { font-size: 0.7rem; font-weight: bold; padding: 2px 8px; border-radius: 10px; vertical-align: middle; margin-left: 4px; white-space: nowrap; }
        .afdeling-tag.keuken { background: #eafaf1; color: #1e8449; }
        .afdeling-tag.bar { background: #f4ecf7; color: #6c3483; }
        .wacht-tag { font-size: 0.8rem; color: var(--grijs); margin: 6px 0 0 0 !important; }
        .wacht-tag .badge { font-size: 0.7rem; padding: 2px 8px; vertical-align: middle; }
        .wacht-uitleg { width: 100%; margin: 0 0 8px 0; font-size: 0.8rem; color: #9a6400; background: #fff8e1; padding: 6px 10px; border-radius: 6px; }
        .btn-bon { background: white; color: #1f4e8c; border: 2px solid #1f4e8c; }
        .bon-tag { background: #eef4fb; color: #1f4e8c; padding: 6px 8px; border-radius: 6px; font-size: 0.85rem !important; }
        .bon-tag.papier { background: #fff8e1; color: #7d5a00; }
        .btn-verlaten { background: white; color: #c0392b; border: 2px solid #c0392b; }
        .opmerking-tag {
            background: #fff3cd; border-left: 4px solid #f39c12; color: #7d5a00;
            padding: 6px 10px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; margin: 6px 0;
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
        @media (min-width: 701px) and (max-width: 1365px) {
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
            <h1>🍷 Las Tapas - Sala (Bediening)</h1>
            <p>Overzicht voor het uitserveren, bezorgen en betaalverzoeken</p>
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
    <div id="geluid-balk" class="geluid-balk" hidden onclick="ontgrendelGeluid()">🔔 Tik ergens op het scherm om het geluid van meldingen aan te zetten</div>

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

            <!-- Filter tabs met omgedraaide volgorde en 'Bezorgd' tabblad -->
            <div class="filter-tabs">
                <button class="tab-btn active" onclick="zetTab('klaar', this)" style="position: relative;">
                    Klaar & Onderweg 🏃‍♂️ <span id="klaar-badge" class="badge-melding" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="zetTab('bezorgd_tab', this)" style="position: relative;">
                    Bezorgd ✅
                </button>
                <button class="tab-btn" onclick="zetTab('betalingen', this)" style="position: relative;">
                    Betaalverzoeken 💳 <span id="betaal-badge" class="badge-melding" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="zetTab('oproepen', this)" style="position: relative;">
                    Oproepen 🙋 <span id="oproep-badge" class="badge-melding" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="zetTab('tafels', this)" style="position: relative;">
                    Tafels 🪑 <span id="tafels-badge" class="badge-melding" style="display:none;">0</span>
                </button>
            </div>
        </div>

        <div class="orders-raster" id="orders-raster">
            <div class="geen-orders">Bestellingen laden...</div>
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

        // Tekst veilig tonen: voorkomt dat een klant via zijn naam code op dit scherm kan uitvoeren
        function esc(tekst) {
            return String(tekst ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
        function euro(bedrag) {
            const n = Number(bedrag || 0);
            return (n < 0 ? '- ' : '') + '€ ' + Math.abs(n).toFixed(2).replace('.', ',');
        }

        let huidigeTab = 'klaar';
        let alleBestellingenCache = [];
        let tafelsStatusCache = {};
        let bezorgdTimestamps = {};

        function laadData() {
            Promise.all([
                fetch('bestelling.php').then(res => res.json()),
                fetch('bestelling.php?view=tafels').then(res => res.json())
            ])
            .then(([bestellingenData, tafelsData]) => {
                let nu = Date.now();
                let opgeschoondeData = [];

                (bestellingenData || []).forEach(order => {
                    if (order.status === 'bezorgd') {
                        if (!bezorgdTimestamps[order.id]) {
                            bezorgdTimestamps[order.id] = nu;
                        }
                        let verstrekenTijd = nu - bezorgdTimestamps[order.id];
                        if (verstrekenTijd > 10 * 60 * 1000) {
                            return; 
                        }
                    } else {
                        delete bezorgdTimestamps[order.id];
                    }
                    opgeschoondeData.push(order);
                });

                alleBestellingenCache = opgeschoondeData;
                tafelsStatusCache = tafelsData || {};

                updateTafelDropdowns();
                updateAlleBadges();
                filterBestellingen();
            })
            .catch(err => console.error('Fout bij ophalen data:', err));
        }

        function updateTafelDropdowns() {
            let bezetteTafels = new Set();
            alleBestellingenCache.forEach(order => {
                if (order.status !== 'bezorgd') {
                    bezetteTafels.add(parseInt(order.tafel));
                }
            });

            const select1 = document.getElementById('filter-etage-1');
            let html1 = '<option value="">-- Status tafels begane grond --</option>';
            for (let i = 1; i <= 10; i++) {
                let isBezet = bezetteTafels.has(i);
                let tafelInfo = tafelsStatusCache[i];
                let statusTekst = isBezet ? 'Bezet' : 'Open';
                let emoji = isBezet ? '🔴' : '🟢';
                const res = reserveringVoorTafel(i);
                if (res && !isBezet && !tafelInfo) {
                    statusTekst = res.status === 'aangekomen' ? 'Gast aangekomen' : `Gereserveerd ${res.tijd}`;
                    emoji = '📅';
                }
                if (tafelInfo && tafelInfo.status === 'betalen') {
                    statusTekst = 'WIL BETALEN!';
                    emoji = '💳';
                } else if (tafelInfo && tafelInfo.status === 'geaccepteerd') {
                    statusTekst = 'BETALING ONDERWEG';
                    emoji = '🚶';
                } else if (tafelInfo && tafelInfo.status === 'bezet' && tafelInfo.seconden_over !== null && tafelInfo.seconden_over <= 0) {
                    statusTekst = 'TIJD IS OM';
                    emoji = '⏰';
                }
                if (tafelInfo && tafelInfo.oproep_status === 'open') {
                    statusTekst = 'ROEPT OM HULP';
                    emoji = '🙋';
                }
                html1 += `<option value="${i}">${emoji} Tafel ${i}: ${statusTekst}</option>`;
            }
            select1.innerHTML = html1;

            const select2 = document.getElementById('filter-etage-2');
            let html2 = '<option value="">-- Status tafels 1e verdieping --</option>';
            for (let i = 11; i <= 26; i++) {
                let isBezet = bezetteTafels.has(i);
                let tafelInfo = tafelsStatusCache[i];
                let statusTekst = isBezet ? 'Bezet' : 'Open';
                let emoji = isBezet ? '🔴' : '🟢';
                const res = reserveringVoorTafel(i);
                if (res && !isBezet && !tafelInfo) {
                    statusTekst = res.status === 'aangekomen' ? 'Gast aangekomen' : `Gereserveerd ${res.tijd}`;
                    emoji = '📅';
                }
                if (tafelInfo && tafelInfo.status === 'betalen') {
                    statusTekst = 'WIL BETALEN!';
                    emoji = '💳';
                } else if (tafelInfo && tafelInfo.status === 'geaccepteerd') {
                    statusTekst = 'BETALING ONDERWEG';
                    emoji = '🚶';
                } else if (tafelInfo && tafelInfo.status === 'bezet' && tafelInfo.seconden_over !== null && tafelInfo.seconden_over <= 0) {
                    statusTekst = 'TIJD IS OM';
                    emoji = '⏰';
                }
                if (tafelInfo && tafelInfo.oproep_status === 'open') {
                    statusTekst = 'ROEPT OM HULP';
                    emoji = '🙋';
                }
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

        let bekendeMeldingen = null;   // null = eerste keer laden (dan nog geen geluid)

        // Pling bij een nieuw betaalverzoek of een gerecht dat klaarstaat
        function controleerNieuweMeldingen() {
            const huidige = [];
            alleBestellingenCache.forEach(o => { if (o.status === 'klaar') huidige.push('klaar-' + o.id); });
            for (let t in tafelsStatusCache) {
                if (tafelsStatusCache[t].status === 'betalen') huidige.push('betalen-' + t);
                if (tafelsStatusCache[t].oproep_status === 'open') huidige.push('oproep-' + t + '-' + tafelsStatusCache[t].oproep_tijd);
            }
            if (bekendeMeldingen !== null && huidige.some(m => !bekendeMeldingen.has(m))) {
                speelPling();
            }
            bekendeMeldingen = new Set(huidige);
        }

        function updateAlleBadges() {
            controleerNieuweMeldingen();
            // Tafels waarvan de tijd om is, krijgen een melding op het tabblad "Tafels"
            let tijdOm = 0;
            for (let t in tafelsStatusCache) {
                const info = tafelsStatusCache[t];
                if (info.status === 'bezet' && info.seconden_over !== null && info.seconden_over <= 0) tijdOm++;
            }
            zetBadge('tafels-badge', tijdOm);
            let openOproepen = 0;
            for (let t in tafelsStatusCache) {
                if (tafelsStatusCache[t].oproep_status === 'open') openOproepen++;
            }
            zetBadge('oproep-badge', openOproepen);
            // Eén tabblad voor klaar + onderweg: de melding telt alles wat nog gebracht moet worden
            let klaarCount = telKaarten('klaar') + telKaarten('onderweg');
            let onderwegCount = 0;

            // Alleen 'betalen' (nog niet geaccepteerd) telt als nieuwe melding.
            // Zodra een serveerster een verzoek heeft geaccepteerd, is het al 'in behandeling'.
            let betaalCount = 0;
            for (let tafelNummer in tafelsStatusCache) {
                if (tafelsStatusCache[tafelNummer].status === 'betalen') {
                    betaalCount++;
                }
            }

            zetBadge('klaar-badge', klaarCount);
            zetBadge('onderweg-badge', onderwegCount);
            zetBadge('betaal-badge', betaalCount);
        }

        function zetTab(tab, element) {
            huidigeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            filterBestellingen();
        }

        // ---------- Reserveringen van vandaag (elke 30 seconden verversen) ----------
        let reserveringenVandaag = [];
        function laadReserveringenVandaag() {
            fetch('reservering_api.php?actie=vandaag', { cache: 'no-store' })
                .then(r => r.json())
                .then(d => { if (d.succes) { reserveringenVandaag = d.reserveringen; if (huidigeTab === 'tafels') filterBestellingen(); } })
                .catch(() => {});
        }
        function reserveringVoorTafel(nr) {
            return reserveringenVandaag.find(r => r.tafels.includes(nr)) || null;
        }
        function reserveringenBlok() {
            if (reserveringenVandaag.length === 0) return '';
            const rijen = reserveringenVandaag.map(r => `
                <div class="res-rij ${r.status === 'aangekomen' ? 'binnen' : ''}">
                    <strong data-geen-vertaling>${esc(r.tijd)}</strong>
                    <span>Tafel <b data-geen-vertaling>${esc(r.tafel_tekst)}</b></span>
                    <span data-geen-vertaling>${esc(r.naam)}</span>
                    <span>👥 <span data-geen-vertaling>${r.personen}</span></span>
                    <span class="res-st">${r.status === 'aangekomen' ? '✅ Aangekomen' : '⏳ Verwacht'}</span>
                </div>`).join('');
            return `<div class="res-blok"><div class="res-kop"><span>📅 Reserveringen vandaag</span><a href="reserveringen.php">Alle reserveringen →</a></div>${rijen}</div>`;
        }

        function tekenTafelsOverzicht(raster) {
            const actief = Object.values(tafelsStatusCache).sort((a, b) => a.tafel - b.tafel);
            if (actief.length === 0) {
                raster.innerHTML = reserveringenBlok() + '<div class="geen-orders">Er zijn op dit moment geen tafels in gebruik.</div>';
                return;
            }
            const statusTekst = { bezet: 'Bezet', betalen: 'Wil betalen', geaccepteerd: 'Betaling onderweg' };
            raster.innerHTML = reserveringenBlok() + actief.map(info => {
                const tijdOm = info.status === 'bezet' && info.seconden_over !== null && info.seconden_over <= 0;
                const res = reserveringenVandaag.find(r => r.tafels.includes(Number(info.tafel)) && r.status === 'gepland');
                const tijd = info.eind_tijd_kort
                    ? (tijdOm ? `⏰ <strong>Tijd is om</strong> (was ${esc(info.eind_tijd_kort)})` : `🕘 Dineren tot <strong>${esc(info.eind_tijd_kort)}</strong>`)
                    : '🕘 Nog niets besteld';
                return `
                    <div class="order-kaart ${tijdOm ? 'betalen' : 'klaar'}">
                        <div>
                            <div class="order-header">
                                <h3>Mesa ${esc(info.tafel)} (Tafel)</h3>
                                <span class="badge ${tijdOm ? 'betalen' : 'klaar'}">${esc(statusTekst[info.status] || info.status)}</span>
                            </div>
                            <div class="pakket-tag">📦 ${esc(info.pakket || '-')}</div>
                            <div class="order-body">
                                <p><strong>Gast naam:</strong> ${esc(info.gast_naam || 'Onbekend')}</p>
                                <p><strong>Gezelschap:</strong> ${esc(info.gezelschap || '-')}</p>
                                <p>${tijd}</p>
                                ${res ? `<p class="res-waarschuwing">📅 Om <strong data-geen-vertaling>${esc(res.tijd)}</strong> gereserveerd voor <strong data-geen-vertaling>${esc(res.naam)}</strong></p>` : ''}
                                <p><small style="color: var(--grijs);">Laatste activiteit: ${esc(info.laatste_activiteit_kort || '-')}</small></p>
                                <p><strong>Rekening tot nu toe:</strong> ${info.rekening ? euro(info.rekening.totaal) : '-'}</p>
                            </div>
                        </div>
                        <div class="order-footer">
                            <button class="btn btn-bon" onclick="printBon(${Number(info.tafel)})">🖨️ Bon printen</button>
                            <button class="btn btn-bezorgd" onclick="zetTafelVrij(${Number(info.tafel)}, 'contant', 'betaald')">💶 Betaald & vrijmaken</button>
                            <button class="btn btn-verlaten" onclick="zetTafelVrij(${Number(info.tafel)}, '', 'verlaten')">🚪 Vertrokken zonder betalen</button>
                        </div>
                    </div>`;
            }).join('');
        }

        const REDEN_TEKST = {
            vraag: '❓ Heeft een vraag',
            probleem: '⚠️ Probleem met bestelling',
            allergie: '🥜 Allergie of dieet',
            anders: '💬 Iets anders'
        };

        function tekenOproepen(raster) {
            const oproepen = Object.values(tafelsStatusCache)
                .filter(t => t.oproep_status)
                .sort((a, b) => String(a.oproep_tijd).localeCompare(String(b.oproep_tijd)));   // oudste eerst
            if (oproepen.length === 0) {
                raster.innerHTML = '<div class="geen-orders">Geen gasten die om hulp vragen op dit moment.</div>';
                return;
            }
            raster.innerHTML = oproepen.map(info => {
                const geaccepteerd = info.oproep_status === 'geaccepteerd';
                const minuten = info.oproep_minuten || 0;
                const wachttijd = minuten === 0 ? 'zojuist' : `${minuten} min geleden`;
                return `
                    <div class="order-kaart betalen ${geaccepteerd ? 'geaccepteerd' : ''}">
                        <div>
                            <div class="order-header">
                                <h3>Mesa ${esc(info.tafel)} (Tafel)</h3>
                                <span class="badge ${geaccepteerd ? 'geaccepteerd' : 'betalen'}">${geaccepteerd ? 'Onderweg' : 'Roept om hulp'}</span>
                            </div>
                            <div class="order-body">
                                <p class="betaalmethode contant" style="background: #fff8e1; color: #7d5a00;">${esc(REDEN_TEKST[info.oproep_reden] || 'Vraagt om een serveerster')}</p>
                                ${info.oproep_tekst ? `<div class="opmerking-tag">💬 ${esc(info.oproep_tekst)}</div>` : ''}
                                <p><strong>Gast naam:</strong> ${esc(info.gast_naam || 'Onbekend')}</p>
                                <p><small style="color: var(--grijs);">🕒 Geroepen om ${esc(info.oproep_tijd_kort || '--:--')} (${wachttijd})</small></p>
                                ${geaccepteerd ? `<p class="medewerker-tag">🙋‍♀️ Geaccepteerd door: <strong>${esc(info.oproep_door || 'Onbekend')}</strong></p>` : ''}
                            </div>
                        </div>
                        <div class="order-footer">
                            ${!geaccepteerd
                                ? `<button class="btn btn-danger" onclick="accepteerOproep(${Number(info.tafel)})">✅ Accepteren (ik kom eraan)</button>`
                                : `<button class="btn btn-bezorgd" onclick="oproepAfgehandeld(${Number(info.tafel)})">✔️ Afgehandeld</button>`}
                        </div>
                    </div>`;
            }).join('');
        }

        function accepteerOproep(tafel) {
            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'roep_accepteer', tafel: tafel })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.succes) alert(data.melding || 'Accepteren is niet gelukt.');
                laadData();
            });
        }

        function oproepAfgehandeld(tafel) {
            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'roep_afgehandeld', tafel: tafel })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.succes) alert(data.melding || 'Afhandelen is niet gelukt.');
                laadData();
            });
        }

        function filterBestellingen() {
            const raster = document.getElementById('orders-raster');

            if (huidigeTab === 'oproepen') {
                tekenOproepen(raster);
                return;
            }

            if (huidigeTab === 'tafels') {
                tekenTafelsOverzicht(raster);
                return;
            }

            if (huidigeTab === 'betalingen') {
                let betalendeTafelsHtml = '';
                let aantalBetalingen = 0;

                for (let t = 1; t <= 26; t++) {
                    let info = tafelsStatusCache[t];
                    if (info && (info.status === 'betalen' || info.status === 'geaccepteerd')) {
                        aantalBetalingen++;
                        let geaccepteerd = info.status === 'geaccepteerd';
                        let online = info.betaalmethode === 'online';
                        let rekeningHtml = '';
                        if (info.rekening && info.rekening.regels.length) {
                            rekeningHtml = `<div class="rekening">` + info.rekening.regels.map(r =>
                                `<div class="rekening-regel"><span>${esc(r.omschrijving)}</span><span>${euro(r.bedrag)}</span></div>`
                            ).join('') + `<div class="rekening-totaal"><span>Totaal</span><span>${euro(info.rekening.totaal)}</span></div></div>`;
                        }
                        betalendeTafelsHtml += `
                            <div class="order-kaart betalen ${geaccepteerd ? 'geaccepteerd' : ''}">
                                <div>
                                    <div class="order-header">
                                        <h3>Mesa ${t} (Tafel)</h3>
                                        <span class="badge ${geaccepteerd ? 'geaccepteerd' : 'betalen'}">${geaccepteerd ? 'Onderweg' : 'Betalen gewenst'}</span>
                                    </div>
                                    <div class="pakket-tag">📦 ${esc(info.pakket || 'Onbekend arrangement')}</div>
                                    <div class="order-body">
                                        <p class="betaalmethode ${online ? 'online' : 'contant'}">
                                            ${online ? '📱 Online betaald: controleer of de betaling gelukt is' : '💶 Wil contant afrekenen aan tafel'}
                                        </p>
                                        <p><strong>Gast naam:</strong> ${esc(info.gast_naam || 'Onbekend')}</p>
                                        <p><strong>Gezelschap:</strong> ${esc(info.gezelschap || '-')}</p>
                                        ${rekeningHtml}
                                        ${bonRegel(info)}
                                        ${geaccepteerd ? `<p class="medewerker-tag">🙋‍♀️ Geaccepteerd door: <strong>${esc(info.geaccepteerd_door || 'Onbekend')}</strong></p>` : ''}
                                    </div>
                                </div>
                                <div class="order-footer">
                                    <button class="btn btn-bon" onclick="printBon(${t})">🖨️ Bon printen</button>
                                    ${!geaccepteerd ? `
                                        <button class="btn btn-danger" onclick="accepteerBetaling(${t})">✅ Accepteren (${online ? 'ik ga controleren' : 'ik kom naar de tafel'})</button>
                                    ` : `
                                        <button class="btn btn-danger" onclick="zetTafelVrij(${t}, '${online ? 'online' : 'contant'}')">${online ? '✔️ Betaling gecontroleerd & tafel vrijmaken' : '💶 Contant ontvangen & tafel vrijmaken'}</button>
                                    `}
                                </div>
                            </div>
                        `;
                    }
                }

                if (aantalBetalingen === 0) {
                    raster.innerHTML = '<div class="geen-orders">Geen openstaande betaalverzoeken op dit moment.</div>';
                } else {
                    raster.innerHTML = betalendeTafelsHtml;
                }
                return;
            }

            // Keuken- en bardeel van dezelfde bestelling komen samen op één kaart
            const kaartenMet = status => maakGroepen(alleBestellingenCache)
                .map(groep => ({ groep, delen: groep.filter(o => o.status === status) }))
                .filter(k => k.delen.length);

            // Tabblad "Klaar & Onderweg": wat je al aan het brengen bent bovenaan, daaronder wat klaarstaat
            let kaarten;
            if (huidigeTab === 'klaar') {
                const onderweg = kaartenMet('onderweg'), klaar = kaartenMet('klaar');
                if (onderweg.length) onderweg[0].kop = `<h3 class="groep-kop actief">🏃‍♂️ Onderweg: ben je aan het brengen <span>(${onderweg.length})</span></h3>`;
                if (klaar.length) klaar[0].kop = `<h3 class="groep-kop">🟢 Klaar: kan opgehaald worden <span>(${klaar.length})</span></h3>`;
                kaarten = [...onderweg, ...klaar];
            } else {
                kaarten = kaartenMet(huidigeTab === 'bezorgd_tab' ? 'bezorgd' : huidigeTab);
            }

            if (kaarten.length === 0) {
                raster.innerHTML = '<div class="geen-orders">Geen bestellingen gevonden in deze categorie.</div>';
                return;
            }

            raster.innerHTML = kaarten.map(({ groep, delen, kop }) => {
                const eerste = delen[0];
                const statusKlasse = eerste.status || 'nieuw';
                const ids = JSON.stringify(delen.map(o => Number(o.id)));
                const rest = groep.filter(o => !delen.includes(o));
                const afdelingTag = o => `<span class="afdeling-tag ${o.afdeling === 'bar' ? 'bar' : 'keuken'}">${o.afdeling === 'bar' ? '🍷 Bar' : '🍳 Keuken'}</span>`;
                const opmerkingen = [...new Set(delen.map(o => o.opmerking).filter(Boolean))];
                const bezorgers = [...new Set(delen.map(o => o.serveerster_naam).filter(Boolean))];

                return (kop || '') + `
                    <div class="order-kaart ${esc(statusKlasse)}">
                        <div>
                            <div class="order-header">
                                <h3>Mesa ${Number(eerste.tafel)} (Tafel) ${delen.map(afdelingTag).join(' ')}</h3>
                                <span class="badge ${esc(statusKlasse)}">${esc(eerste.status)}</span>
                            </div>
                            <div class="pakket-tag">📦 ${esc(eerste.pakket || 'Onbekend arrangement')}</div>
                            <div class="order-body">
                                ${delen.map(o => `
                                    <p><strong>${o.afdeling === 'bar' ? '🍷 Drankjes' : '🍳 Tapa / Gerecht'}:</strong> ${esc(o.gerecht)}</p>
                                    ${Number(o.aangepast) ? `<p style="background: #f4ecf7; color: #6c3483; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; display: inline-block;">✏️ ${o.afdeling === 'bar' ? 'Aangepast door de bar' : 'Aangepast door de keuken'}</p>` : ''}
                                `).join('')}
                                <p><small style="color: var(--grijs);">🕒 Besteld om: ${esc(eerste.tijd || '--:--')}</small></p>
                                ${opmerkingen.map(t => `<div class="opmerking-tag">📝 ${esc(t)}</div>`).join('')}
                                ${delen.filter(o => o.chef_naam).map(o => `<p class="medewerker-tag">${o.afdeling === 'bar' ? '🍷' : '👨‍🍳'} Bereid door: <strong>${esc(o.chef_naam)}</strong></p>`).join('')}
                                ${bezorgers.length ? `<p class="medewerker-tag">🏃‍♀️ ${eerste.status === 'bezorgd' ? 'Bezorgd door' : 'Onderweg met'}: <strong>${esc(bezorgers.join(', '))}</strong></p>` : ''}
                                ${rest.map(o => `<p class="wacht-tag">🔗 Hoort bij deze bestelling: ${afdelingTag(o)} <span class="badge ${esc(o.status)}">${esc(o.status)}</span></p>`).join('')}
                            </div>
                        </div>
                        <div class="order-footer">
                            ${statusKlasse === 'klaar' ? `
                                ${rest.some(o => o.status === 'nieuw' || o.status === 'bezig') ? `<p class="wacht-uitleg">⏳ Het andere deel is nog niet klaar. Wacht even als je alles in één keer wilt brengen.</p>` : ''}
                                <button class="btn btn-onderweg" onclick='updateStatusGroep(${ids}, "onderweg")'>🏃‍♂️ Meenemen / Onderweg</button>
                            ` : ''}
                            ${statusKlasse === 'onderweg' ? `
                                <button class="btn btn-bezorgd" onclick='updateStatusGroep(${ids}, "bezorgd")'>✅ Bezorgd / Uitgeserveerd</button>
                            ` : ''}
                            ${statusKlasse === 'bezorgd' ? `
                                <p style="margin: 0; font-size: 0.85rem; color: #27ae60;"><strong>Uitgeserveerd!</strong> (Verdwijnt 10 min na bezorging uit dit overzicht)</p>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Bestellingen met dezelfde groepscode (keuken + bar) horen bij elkaar
        function maakGroepen(bestellingen) {
            const groepen = new Map();
            bestellingen.forEach(o => {
                const sleutel = o.groep ? 'g' + o.groep : 'i' + o.id;
                if (!groepen.has(sleutel)) groepen.set(sleutel, []);
                groepen.get(sleutel).push(o);
            });
            return [...groepen.values()].map(g => g.sort((a, b) => (a.afdeling === 'bar') - (b.afdeling === 'bar')));
        }

        // Aantal kaarten met een bepaalde status (keuken + bar van één bestelling = één kaart)
        function telKaarten(status) {
            return maakGroepen(alleBestellingenCache).filter(g => g.some(o => o.status === status)).length;
        }

        function updateStatusGroep(ids, nieuweStatus) {
            Promise.all(ids.map(id => fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'update_status', id: id, nieuwe_status: nieuweStatus })
            }).then(res => res.json())))
            .then(antwoorden => {
                const fout = antwoorden.find(d => !d.succes && d.melding);
                if (fout) alert(fout.melding);
                laadData();
            })
            .catch(() => laadData());
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

        function accepteerBetaling(tafelNummer) {
            // Stap 1: serveerster pakt het verzoek op. De klant ziet direct wie er komt.
            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'update_tafel_status', tafel: tafelNummer, status: 'geaccepteerd' })
            })
            .then(res => res.json())
            .then(response => {
                if (!response.succes) alert(response.melding || 'Fout bij accepteren betaalverzoek.');
                laadData();
            });
        }

        // Welke bon wil de gast?
        function bonRegel(info) {
            if (info.bon_keuze === 'email') return `<p class="bon-tag">📧 Bon per e-mail naar <strong>${esc(info.bon_email)}</strong> (gaat automatisch bij vrijmaken)</p>`;
            if (info.bon_keuze === 'papier') return `<p class="bon-tag papier">🧾 Wil een papieren bon: print hem en neem hem mee</p>`;
            return '';
        }

        function printBon(tafel) {
            window.open('bon.php?tafel=' + encodeURIComponent(tafel), '_blank');
        }

        // Tafel afsluiten. reden: 'betaald' (normaal) of 'verlaten' (gasten zonder betalen vertrokken)
        function zetTafelVrij(tafelNummer, methode, reden = 'betaald') {
            const info = tafelsStatusCache[tafelNummer];
            const bedrag = info && info.rekening ? ` (${euro(info.rekening.totaal)})` : '';
            let vraag;
            if (reden === 'verlaten') {
                vraag = `Tafel ${tafelNummer} vrijmaken omdat de gasten zijn vertrokken ZONDER te betalen${bedrag}? Dit wordt apart bijgehouden in de statistieken.`;
            } else if (methode === 'online') {
                vraag = `Bevestig: heb je gecontroleerd dat de online betaling van Tafel ${tafelNummer}${bedrag} echt gelukt is? De tafel komt daarna weer vrij.`;
            } else {
                vraag = `Bevestig: is de rekening van Tafel ${tafelNummer}${bedrag} betaald? De tafel komt daarna weer vrij.`;
            }
            if (!confirm(vraag)) return;

            fetch('bestelling.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'update_tafel_status', tafel: tafelNummer, status: 'vrij', reden: reden, betaalmethode: methode })
            })
            .then(res => res.json())
            .then(response => {
                if (!response.succes) alert(response.melding || 'Fout bij vrijmaken tafel.');
                else if (response.bon && response.bon.melding) alert(response.bon.melding);
                laadData();
            });
        }

        setInterval(laadData, 3000);
        laadReserveringenVandaag();
        setInterval(laadReserveringenVandaag, 30000);
        laadData();
    </script>
</body>
</html>