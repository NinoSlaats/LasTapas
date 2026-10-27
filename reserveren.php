<?php
/*
 * Las Tapas - reserveren.php
 * Online een tafel reserveren: datum, aantal personen, tijd en een tafel kiezen op de plattegrond.
 * Met ?wijzig=CODE (uit de e-mail) verplaatst de gast een bestaande reservering.
 */
require __DIR__ . '/gedeeld.php';
$wijzigCode = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_GET['wijzig'] ?? '')));
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserveren - Las Tapas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --rood: #a8232b; --geel: #e9a23b; --creme: #fbf5ea; --inkt: #2b1d19; --grijs: #7a6b63;
            --azul: #1f4e8c; --terracota: #c0562f; --oliva: #5f6f2f; --lijn: #e8d9c6;
            --kopfont: 'Fraunces', Georgia, serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--creme); color: var(--inkt); }
        header { background: var(--rood); color: white; padding: 22px 20px 18px; text-align: center; position: relative; }
        header h1 { margin: 0; font-family: var(--kopfont); font-size: 2rem; }
        header p { margin: 4px 0 0; letter-spacing: 3px; text-transform: uppercase; font-size: 0.72rem; opacity: 0.85; }
        #taal-plek { position: absolute; top: 14px; right: 14px; }
        .tegels { height: 12px; background: repeating-linear-gradient(90deg, var(--azul) 0 12px, #fdf6e3 12px 24px); }
        main { max-width: 980px; margin: 0 auto; padding: 22px 16px 60px; }
        .kaart { background: white; border-radius: 14px; box-shadow: 0 4px 16px rgba(43,29,25,0.07); padding: 20px; margin-bottom: 18px; }
        .kaart h2 { font-family: var(--kopfont); color: var(--rood); margin: 0 0 4px; font-size: 1.35rem; display: flex; align-items: center; gap: 10px; }
        .stap-nr { background: var(--rood); color: white; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; flex-shrink: 0; }
        .uitleg { color: var(--grijs); font-size: 0.9rem; margin: 0 0 14px; }
        .rij { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
        label { font-weight: bold; font-size: 0.9rem; display: block; margin-bottom: 5px; }
        input[type=date], input[type=text], input[type=email], input[type=tel], textarea {
            width: 100%; padding: 11px 12px; border: 1px solid #d9c6b0; border-radius: 8px; font-size: 16px; font-family: inherit; background: #fffdf8;
        }
        .veld { flex: 1 1 220px; }
        .teller { display: flex; align-items: center; border: 1px solid #d9c6b0; border-radius: 8px; overflow: hidden; background: #fffdf8; width: max-content; }
        .teller button { width: 44px; height: 44px; border: none; background: #f4ebdf; font-size: 1.3rem; font-weight: bold; cursor: pointer; color: var(--inkt); }
        .teller input { width: 60px; height: 44px; border: none; border-left: 1px solid #ecdcc8; border-right: 1px solid #ecdcc8; background: white;
                        text-align: center; font-weight: bold; font-size: 1.15rem; font-family: inherit; color: var(--inkt); padding: 0; -moz-appearance: textfield; }
        .teller input::-webkit-outer-spin-button, .teller input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .teller input:focus { outline: 3px solid var(--geel); outline-offset: -3px; }
        /* Kalender */
        .kalender { border: 1px solid #e8d9c6; border-radius: 12px; padding: 10px; background: #fffdf8; max-width: 380px; }
        .kal-kop { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .kal-kop strong { font-family: var(--kopfont); font-size: 1.1rem; color: var(--inkt); text-transform: capitalize; }
        .kal-kop button { width: 38px; height: 38px; border-radius: 50%; border: 1px solid #e8d9c6; background: white; font-size: 1.1rem; cursor: pointer; color: var(--inkt); }
        .kal-kop button:disabled { opacity: 0.3; cursor: default; }
        .kal-raster { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .kal-wd { text-align: center; font-size: 0.72rem; font-weight: bold; color: var(--grijs); padding: 4px 0; text-transform: capitalize; }
        .kal-dag { aspect-ratio: 1 / 1; border: 2px solid transparent; border-radius: 10px; background: #eef6e4; color: var(--inkt); font-weight: bold; font-size: 0.95rem;
                   cursor: pointer; font-family: inherit; padding: 0; }
        .kal-dag:hover:not(:disabled) { border-color: var(--oliva); }
        .kal-dag.vandaag { box-shadow: inset 0 0 0 2px var(--geel); }
        .kal-dag.gekozen { background: var(--rood); color: white; border-color: var(--rood); }
        .kal-dag:disabled { background: transparent; color: #c4b6a7; cursor: not-allowed; font-weight: normal; }
        .kal-dag.gesloten:disabled { text-decoration: line-through; }
        .kal-legenda { display: flex; gap: 12px; flex-wrap: wrap; font-size: 0.75rem; color: var(--grijs); margin-top: 8px; }
        .kal-legenda i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; vertical-align: -1px; margin-right: 4px; }
        /* Openingstijden */
        .openingstijden { margin-top: 16px; border: 1px solid #e8d9c6; border-radius: 12px; padding: 10px 14px; background: #fffaf2; min-width: 230px; }
        .openingstijden h3 { margin: 0 0 6px; font-size: 0.95rem; }
        .openingstijden table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .openingstijden td { padding: 3px 0; }
        .openingstijden td:first-child { text-transform: capitalize; padding-right: 14px; }
        .openingstijden td:last-child { text-align: right; white-space: nowrap; }
        .openingstijden tr.dicht td:last-child { color: var(--grijs); }
        .openingstijden tr.vandaag td { font-weight: bold; color: var(--rood); }
        .snel { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
        .snel button, .tijden button {
            border: 2px solid var(--lijn); background: white; border-radius: 20px; padding: 7px 13px; font-weight: bold; cursor: pointer; font-family: inherit; color: var(--inkt); font-size: 0.9rem;
        }
        .snel button.gekozen, .tijden button.gekozen { border-color: var(--rood); background: var(--rood); color: white; }
        .tijden { display: grid; grid-template-columns: repeat(auto-fill, minmax(92px, 1fr)); gap: 8px; }
        .tijden button { border-radius: 10px; padding: 9px 6px; display: flex; flex-direction: column; align-items: center; gap: 2px; }
        .tijden button small { font-weight: normal; font-size: 0.72rem; color: var(--oliva); }
        .tijden button.gekozen small { color: #ffe3c2; }
        .tijden button:disabled { opacity: 0.45; cursor: not-allowed; background: #f3eee8; }
        .tijden button:disabled small { color: var(--grijs); }

        /* ===== Plattegrond ===== */
        .legenda { display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.82rem; color: var(--grijs); margin-bottom: 14px; }
        .legenda span { display: inline-flex; align-items: center; gap: 6px; }
        .legenda i { width: 16px; height: 16px; border-radius: 4px; display: inline-block; }
        .l-vrij { background: #eef6e4; border: 2px solid var(--oliva); }
        .l-gekozen { background: var(--rood); border: 2px solid var(--rood); }
        .l-bezet { background: repeating-linear-gradient(45deg, #ddd 0 4px, #eee 4px 8px); border: 2px solid #bbb; }
        .l-klein { background: #faf6f1; border: 2px dashed #d7c8b8; }
        .etages { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .etage { border: 2px solid var(--lijn); border-radius: 12px; overflow: hidden; background: #fffaf2; }
        .etage-kop { background: var(--inkt); color: white; padding: 8px 12px; font-weight: bold; display: flex; justify-content: space-between; font-size: 0.95rem; }
        .etage-kop small { font-weight: normal; opacity: 0.8; }
        .zone-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--grijs); text-align: center; padding: 5px; background: #f4ebdf; }
        .zone-label.raam { background: linear-gradient(#dcebf7, #eef5fb); color: var(--azul); }
        .zone { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 12px; padding: 14px 10px; min-height: 92px; }
        .zone + .zone { border-top: 1px dashed var(--lijn); }
        .tafel {
            position: relative; border: 2px solid var(--oliva); background: #eef6e4; color: var(--inkt); cursor: pointer;
            display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: inherit; padding: 4px;
            transition: transform 0.12s, box-shadow 0.12s;
        }
        .tafel:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
        .tafel .nr { font-weight: bold; font-size: 0.95rem; }
        .tafel .pl { font-size: 0.72rem; }
        .tafel.p2 { width: 58px; height: 58px; border-radius: 50%; }
        .tafel.p4 { width: 70px; height: 70px; border-radius: 10px; }
        .tafel.p6 { width: 100px; height: 66px; border-radius: 10px; }
        .tafel.p8 { width: 124px; height: 66px; border-radius: 10px; }
        .tafel.p10 { width: 156px; height: 70px; border-radius: 12px; }
        /* stoeltjes rond de tafel */
        .tafel::before { content: ''; position: absolute; inset: -9px; border-radius: inherit; border: 5px dotted #c9b59c; opacity: 0.55; pointer-events: none; }
        .tafel.gekozen { background: var(--rood); border-color: var(--rood); color: white; box-shadow: 0 0 0 4px rgba(168,35,43,0.25); }
        .tafel.bezet { background: repeating-linear-gradient(45deg, #ddd 0 6px, #eee 6px 12px); border-color: #bbb; color: #888; cursor: not-allowed; }
        .tafel.te_klein { background: #faf6f1; border: 2px dashed #d7c8b8; color: #b3a596; cursor: not-allowed; }
        .tafel:disabled::before { opacity: 0.25; }
        .gekozen-tafel { margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #fff3e6; border-left: 5px solid var(--geel); font-weight: bold; }
        .gekozen-tafel.leeg { background: #f6f1ea; border-left-color: #d9c6b0; font-weight: normal; color: var(--grijs); }

        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .privacy-tip { font-size: 0.78rem; color: var(--grijs); margin: 10px 0 0; line-height: 1.45; }
        .privacy-tip a { color: var(--azul); font-weight: bold; }
        .hoofdknop { width: 100%; margin-top: 16px; background: var(--oliva); color: white; border: none; padding: 15px; font-size: 1.1rem; font-weight: bold; border-radius: 10px; cursor: pointer; font-family: inherit; }
        .hoofdknop:disabled { opacity: 0.5; cursor: not-allowed; }
        .fout { color: var(--rood); font-weight: bold; margin: 10px 0 0; min-height: 0; }
        .verborgen { display: none !important; }
        .samenvatting { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; font-size: 1rem; margin: 14px 0; }
        .samenvatting dt { color: var(--grijs); }
        .samenvatting dd { margin: 0; font-weight: bold; }
        .klaar { text-align: center; }
        .klaar .groot { font-size: 3.2rem; }
        .mail-ok { background: #eafaf1; color: #1e8449; padding: 12px; border-radius: 10px; font-weight: bold; }
        .mail-mis { background: #fff3cd; color: #7d5a00; padding: 12px; border-radius: 10px; }
        .link-knop { display: inline-block; margin-top: 12px; background: var(--rood); color: white; text-decoration: none; font-weight: bold; padding: 12px 20px; border-radius: 10px; }
        .wijzig-balk { background: #eef4fb; border: 2px solid var(--azul); color: var(--azul); border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-weight: bold; }
        footer { text-align: center; font-size: 0.8rem; color: var(--grijs); padding: 10px 16px 30px; }
        footer a { color: var(--azul); font-weight: bold; }
        @media (max-width: 700px) {
            .etages, .grid2 { grid-template-columns: 1fr; }
            header h1 { font-size: 1.6rem; padding-right: 60px; }
            .rij > div[style*="margin-left"] { margin-left: 0 !important; }
            .zone { gap: 14px; }
        }
    </style>
    <script src="plattegrond.js?v=20261002a"></script>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<body>
    <header>
        <span id="taal-plek"></span>
        <h1>🍷 Las Tapas</h1>
        <p>Reserveer je tafel</p>
    </header>
    <div class="tegels"></div>

    <main>
        <div id="uit-melding" class="kaart verborgen"><p>Online reserveren is op dit moment niet mogelijk. Bel ons gerust om te reserveren.</p></div>
        <div id="wijzig-balk" class="wijzig-balk verborgen">✏️ Je verplaatst reservering <span id="wijzig-nummer" data-geen-vertaling></span>. Kies een nieuwe datum, tijd of tafel.</div>

        <div id="formulier">
            <!-- Stap 1 -->
            <section class="kaart">
                <h2><span class="stap-nr">1</span> Wanneer en met hoeveel?</h2>
                <div class="rij">
                    <div class="veld" style="flex: 0 1 400px;">
                        <label>Datum</label>
                        <div class="kalender" id="kalender"></div>
                    </div>
                    <div style="align-self: flex-start; margin-left: 16px;">
                        <label>Aantal personen</label>
                        <div class="teller">
                            <button type="button" onclick="wijzigPersonen(-1)" aria-label="Minder personen">−</button>
                            <input type="number" id="personen" value="2" min="1" inputmode="numeric" aria-label="Aantal personen"
                                   oninput="typPersonen(this.value, false)" onchange="typPersonen(this.value, true)" onkeydown="if (event.key === 'Enter') this.blur()">
                            <button type="button" onclick="wijzigPersonen(1)" aria-label="Meer personen">+</button>
                        </div>
                        <p class="uitleg" id="groep-uitleg" style="margin: 10px 0 0; max-width: 260px;"></p>
                        <div class="openingstijden" id="openingstijden"></div>
                    </div>
                </div>
            </section>

            <!-- Stap 2 -->
            <section class="kaart">
                <h2><span class="stap-nr">2</span> Hoe laat?</h2>
                <p class="uitleg" id="duur-uitleg"></p>
                <div class="tijden" id="tijden"></div>
            </section>

            <!-- Stap 3 -->
            <section class="kaart">
                <h2><span class="stap-nr">3</span> Kies je tafel</h2>
                <p class="uitleg">Dit is het restaurant van bovenaf gezien. Tik op een vrije (groene) tafel. Elke tafel heeft 4 plaatsen; het nummer staat onder de tafel.</p>
                <div class="legenda">
                    <span><i class="l-vrij"></i> Vrij</span>
                    <span><i class="l-gekozen"></i> Jouw keuze</span>
                    <span><i class="l-bezet"></i> Gereserveerd</span>
                    <span><i class="l-klein"></i> Te klein voor jullie</span>
                </div>
                <div class="etages" id="etages"></div>
                <div class="gekozen-tafel leeg" id="gekozen-tafel">Kies eerst een tijd, daarna een tafel.</div>
            </section>

            <!-- Stap 4 -->
            <section class="kaart" id="stap-gegevens">
                <h2><span class="stap-nr">4</span> Jouw gegevens</h2>
                <p class="uitleg">We hebben deze gegevens nodig om je reservering te bevestigen en om je te bereiken als er iets verandert.</p>
                <div class="grid2">
                    <div><label for="naam">Naam *</label><input type="text" id="naam" maxlength="100" autocomplete="name" placeholder="Bijv. Maria Jansen"></div>
                    <div><label for="telefoon">Telefoonnummer *</label><input type="tel" id="telefoon" maxlength="30" autocomplete="tel" placeholder="Bijv. 06 12345678"></div>
                    <div><label for="email">E-mailadres *</label><input type="email" id="email" maxlength="190" autocomplete="email" placeholder="jouw@email.nl"></div>
                    <div><label for="opmerking">Opmerking (optioneel)</label><input type="text" id="opmerking" maxlength="255" placeholder="Bijv. kinderstoel, allergie, verjaardag"></div>
                </div>
                <p class="privacy-tip">🔒 We gebruiken je gegevens alleen voor deze reservering: je krijgt een bevestiging per e-mail, en we bellen of mailen alleen als er iets verandert. <a href="privacy.php" target="_blank" rel="noopener">Privacyverklaring</a></p>
            </section>
            <div id="gegevens-vast" class="kaart verborgen"></div>

            <section class="kaart">
                <div class="samenvatting" id="samenvatting"></div>
                <button type="button" class="hoofdknop" id="bevestig-knop" onclick="verstuur()" disabled>Reservering bevestigen</button>
                <p class="fout" id="fout"></p>
            </section>
        </div>

        <!-- Klaar -->
        <section class="kaart klaar verborgen" id="klaar">
            <div class="groot">✅</div>
            <h2 style="justify-content: center;" id="klaar-titel">Je reservering is bevestigd!</h2>
            <div class="samenvatting" id="klaar-samenvatting" style="max-width: 420px; margin: 16px auto; text-align: left;"></div>
            <div id="klaar-mail"></div>
            <a class="link-knop" id="klaar-link" href="#">Bekijken, verplaatsen of annuleren</a>
        </section>
    </main>
    <footer><a href="privacy.php" target="_blank" rel="noopener">🔒 Privacyverklaring</a></footer>

<script>
    const WIJZIG_CODE = <?php echo json_encode($wijzigCode); ?>;
    const PLEK_NAMEN = { raam: 'aan het raam', midden: 'midden in de zaal', bar: 'bij de bar', entree: 'bij de entree', lounge: 'bij de lounge' };
    const ETAGE_NAMEN = { 1: 'Begane grond', 2: 'Eerste verdieping' };
    let cfg = null, wijzig = null;
    const keuze = { datum: '', personen: 2, tijd: '', tafels: [] };
    let tafelStatus = {};

    function api(params) {
        return fetch('reservering_api.php?' + new URLSearchParams(params), { headers: { 'ngrok-skip-browser-warning': 'true' }, cache: 'no-store' }).then(r => r.json());
    }
    function apiPost(data) {
        return fetch('reservering_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true' }, body: JSON.stringify(data) }).then(r => r.json());
    }
    function esc(t) { return String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function taal() { return document.documentElement.lang || 'nl'; }
    // Datum in de taal van de pagina, bijv. "vrijdag 3 oktober"
    function datumTekst(iso, kort) {
        const d = new Date(iso + 'T12:00:00');
        return d.toLocaleDateString(taal(), kort ? { weekday: 'short', day: 'numeric', month: 'short' } : { weekday: 'long', day: 'numeric', month: 'long' });
    }

    function init() {
        api({ actie: 'config' }).then(c => {
            cfg = c;
            if (!c.aan && !WIJZIG_CODE) {
                document.getElementById('uit-melding').classList.remove('verborgen');
                document.getElementById('formulier').classList.add('verborgen');
                return;
            }
            document.getElementById('personen').max = c.max_personen;
            keuze.datum = c.eerste_dag || c.vandaag;     // eerste dag waarop je nog kunt reserveren
            kalMaand = keuze.datum.slice(0, 7);
            tekenOpeningstijden();
            document.getElementById('duur-uitleg').textContent = `Je hebt de tafel ${c.duur / 60} uur.`;
            if (WIJZIG_CODE) return laadWijziging();
            tekenKalender();
            laadBeschikbaar();
        });
    }

    // Bestaande reservering laden om te verplaatsen
    function laadWijziging() {
        api({ actie: 'mijn', code: WIJZIG_CODE }).then(d => {
            if (!d.succes) { toonFout(d.melding); return; }
            wijzig = d.reservering;
            if (wijzig.status !== 'gepland' || wijzig.voorbij) {
                location.href = 'mijn_reservering.php?code=' + encodeURIComponent(WIJZIG_CODE);
                return;
            }
            Object.assign(keuze, { datum: wijzig.datum, personen: wijzig.personen, tijd: wijzig.tijd, tafels: wijzig.tafels.slice() });
            kalMaand = wijzig.datum.slice(0, 7);
            document.getElementById('personen').value = wijzig.personen;
            document.getElementById('wijzig-nummer').textContent = wijzig.nummer;
            document.getElementById('wijzig-balk').classList.remove('verborgen');
            document.getElementById('stap-gegevens').classList.add('verborgen');
            const vast = document.getElementById('gegevens-vast');
            vast.classList.remove('verborgen');
            vast.innerHTML = `<h2><span class="stap-nr">4</span> Jouw gegevens</h2><p style="margin: 0;"><strong data-geen-vertaling>${esc(wijzig.naam)}</strong> · <span data-geen-vertaling>${esc(wijzig.email)}</span> · <span data-geen-vertaling>${esc(wijzig.telefoon)}</span></p>`;
            document.getElementById('bevestig-knop').textContent = 'Wijziging opslaan';
            tekenKalender();
            laadBeschikbaar();
        });
    }

    // ---------- Kalender: alleen dagen waarop we open zijn en waarop je online kunt reserveren ----------
    let kalMaand = '';   // 'YYYY-MM'
    const isoVan = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const weekdagNr = iso => { const d = new Date(iso + 'T12:00:00'); return d.getDay() === 0 ? 7 : d.getDay(); };   // 1 = maandag
    const isOpen = iso => !!(cfg.openingstijden && cfg.openingstijden[weekdagNr(iso)]);
    function dagKiesbaar(iso) {
        return iso >= cfg.vandaag && iso <= cfg.tot && isOpen(iso) && !(iso === cfg.vandaag && cfg.eerste_dag && cfg.eerste_dag > cfg.vandaag);
    }

    function tekenKalender() {
        const [j, m] = kalMaand.split('-').map(Number);
        const eerste = new Date(j, m - 1, 1, 12);
        const dagenInMaand = new Date(j, m, 0).getDate();
        const leeg = (eerste.getDay() + 6) % 7;               // maandag als eerste kolom
        const maandNaam = eerste.toLocaleDateString(taal(), { month: 'long', year: 'numeric' });
        const wd = [...Array(7)].map((_, i) => new Date(2026, 0, 5 + i, 12).toLocaleDateString(taal(), { weekday: 'short' }));
        let html = `<div class="kal-kop">
                <button type="button" onclick="bladerMaand(-1)" aria-label="Vorige maand" ${kalMaand <= cfg.vandaag.slice(0, 7) ? 'disabled' : ''}>‹</button>
                <strong>${esc(maandNaam)}</strong>
                <button type="button" onclick="bladerMaand(1)" aria-label="Volgende maand" ${kalMaand >= cfg.tot.slice(0, 7) ? 'disabled' : ''}>›</button>
            </div><div class="kal-raster">` + wd.map(w => `<div class="kal-wd">${esc(w)}</div>`).join('');
        for (let i = 0; i < leeg; i++) html += '<div></div>';
        for (let d = 1; d <= dagenInMaand; d++) {
            const iso = isoVan(new Date(j, m - 1, d, 12));
            const kan = dagKiesbaar(iso);
            const klassen = ['kal-dag', iso === cfg.vandaag ? 'vandaag' : '', iso === keuze.datum ? 'gekozen' : '',
                             !isOpen(iso) && iso >= cfg.vandaag ? 'gesloten' : ''].join(' ');
            const titel = !isOpen(iso) ? 'Gesloten' : iso < cfg.vandaag ? '' : iso > cfg.tot ? 'Nog niet te reserveren' : '';
            html += `<button type="button" class="${klassen}" ${kan ? `onclick="kiesDatum('${iso}')"` : 'disabled'} ${titel ? `title="${titel}"` : ''}
                         aria-label="${esc(datumTekst(iso))}${titel ? ' – ' + titel : ''}"><span data-geen-vertaling>${d}</span></button>`;
        }
        html += `</div><div class="kal-legenda"><span><i style="background: #eef6e4;"></i>Open</span>
                 <span><i style="background: var(--rood);"></i>Jouw keuze</span>
                 <span><i style="border: 1px solid #c4b6a7;"></i><s>Gesloten</s></span></div>`;
        document.getElementById('kalender').innerHTML = html;
    }
    function bladerMaand(stap) {
        const [j, m] = kalMaand.split('-').map(Number);
        const d = new Date(j, m - 1 + stap, 1, 12);
        kalMaand = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        tekenKalender();
    }

    // Openingstijden (maandag t/m zondag), vandaag dikgedrukt
    function tekenOpeningstijden() {
        const vandaagNr = weekdagNr(cfg.vandaag);
        const rijen = [1, 2, 3, 4, 5, 6, 7].map(nr => {
            const naam = new Date(2026, 0, 4 + nr, 12).toLocaleDateString(taal(), { weekday: 'long' });
            const o = cfg.openingstijden[nr];
            return `<tr class="${o ? '' : 'dicht'} ${nr === vandaagNr ? 'vandaag' : ''}"><td>${esc(naam)}</td>`
                 + `<td>${o ? `<span data-geen-vertaling>${o.open} – ${o.sluit}</span>` : 'Gesloten'}</td></tr>`;
        }).join('');
        document.getElementById('openingstijden').innerHTML = `<h3>🕒 Openingstijden</h3><table>${rijen}</table>`;
    }

    function kiesDatum(iso) {
        keuze.datum = iso; keuze.tijd = '';
        tekenKalender();
        laadBeschikbaar();
    }

    function wijzigPersonen(stap) {
        zetPersonen(keuze.personen + stap);
    }

    // Tekst onder het aantal personen: hoeveel tafels er aangeschoven worden, en vanaf het maximum: bel ons
    function werkGroepUitlegBij() {
        const max = cfg.max_personen, n = tafelsNodig();
        const groep = n > 1 ? `Jullie zijn met ${keuze.personen}: daarvoor schuiven we ${n} tafels aan elkaar.` : '';
        const bellen = keuze.personen >= max ? `Met meer dan ${max} personen? Bel ons, dan regelen we samen een plek.` : '';
        document.getElementById('groep-uitleg').innerHTML = [groep, bellen].filter(Boolean).map(t => `<span>${esc(t)}</span>`).join('<br>');
    }

    // Aantal personen vastleggen (begrensd tussen 1 en het maximum) en de beschikbaarheid opnieuw ophalen
    function zetPersonen(aantal) {
        const max = cfg ? cfg.max_personen : 10;
        const nieuw = Math.max(1, Math.min(max, Math.round(aantal) || 1));
        document.getElementById('personen').value = nieuw;
        if (nieuw === keuze.personen) { if (cfg) werkGroepUitlegBij(); return; }
        keuze.personen = nieuw;
        // Meer tafels gekozen dan nodig? Opnieuw kiezen
        if (keuze.tafels.length > tafelsNodig()) keuze.tafels = [];
        laadBeschikbaar();
    }

    // Getypt aantal: tijdens het typen alleen bijwerken als het een geldig getal is,
    // bij het verlaten van het vak (of Enter) altijd netjes begrenzen.
    let typTimer = null;
    function typPersonen(tekst, klaar) {
        clearTimeout(typTimer);
        const getal = parseInt(tekst, 10);
        const max = cfg ? cfg.max_personen : 10;
        if (klaar) { zetPersonen(getal); return; }
        if (!isNaN(getal) && getal >= 1) {
            if (getal > max) {
                document.getElementById('groep-uitleg').textContent = `Met meer dan ${max} personen? Bel ons, dan regelen we samen een plek.`;
                typTimer = setTimeout(() => zetPersonen(getal), 900);
                return;
            }
            typTimer = setTimeout(() => zetPersonen(getal), 400);
        }
    }

    // Vrije tijden (en als er een tijd gekozen is: de tafels) ophalen
    function laadBeschikbaar() {
        werkGroepUitlegBij();
        const params = { actie: 'beschikbaar', datum: keuze.datum, personen: keuze.personen };
        if (keuze.tijd) params.tijd = keuze.tijd;
        if (WIJZIG_CODE) params.wijzig = WIJZIG_CODE;
        api(params).then(d => {
            if (!d.succes) { toonFout(d.melding); return; }
            // Gekozen tijd niet meer mogelijk? Dan opnieuw kiezen
            const gekozenTijd = d.tijden.find(t => t.tijd === keuze.tijd);
            if (keuze.tijd && (!gekozenTijd || gekozenTijd.voorbij || (gekozenTijd.vrij === 0 && !d.tafels))) { keuze.tijd = ''; }
            tekenTijden(d.tijden);
            tafelStatus = d.tafels || {};
            keuze.tafels = keuze.tafels.filter(nr => tafelStatus[nr] === 'vrij');
            tekenPlattegrond();
            werkSamenvattingBij();
        });
    }

    function tekenTijden(tijden) {
        const el = document.getElementById('tijden');
        if (!tijden.length) {
            el.innerHTML = '<p class="uitleg">Op deze dag zijn we gesloten. Kies een andere dag.</p>';
            return;
        }
        if (tijden.every(t => t.voorbij)) {
            el.innerHTML = '<p class="uitleg">Op deze dag kun je niet meer reserveren. Kies een andere datum.</p>';
            return;
        }
        el.innerHTML = tijden.map(t => {
            const vol = t.voorbij || t.vrij === 0;
            const sub = t.voorbij ? 'voorbij' : (t.vrij === 0 ? 'vol' : (t.vrij === 1 ? 'nog 1 tafel' : `${t.vrij} tafels vrij`));
            return `<button type="button" class="${t.tijd === keuze.tijd ? 'gekozen' : ''}" ${vol ? 'disabled' : ''} onclick="kiesTijd('${t.tijd}')"><span data-geen-vertaling>${t.tijd}</span><small>${sub}</small></button>`;
        }).join('');
    }

    function kiesTijd(tijd) {
        keuze.tijd = tijd;
        laadBeschikbaar();
    }

    // ---------- Tafels kiezen (groepen: meerdere tafels naast elkaar) ----------
    // Hoeveel tafels heeft dit gezelschap nodig? (zelfde berekening als de server)
    function tafelsNodig() {
        if (!cfg) return 1;
        const plaatsen = cfg.tafels.map(t => t.plaatsen).sort((a, b) => b - a);
        let som = 0;
        for (let i = 0; i < plaatsen.length; i++) { som += plaatsen[i]; if (som >= keuze.personen) return i + 1; }
        return plaatsen.length;
    }
    const tafelInfo = nr => cfg.tafels.find(t => t.nr === nr);
    function buren(a, b) {
        const x = tafelInfo(a), y = tafelInfo(b);
        return x && y && x.etage === y.etage && Math.abs(x.rij - y.rij) + Math.abs(x.kol - y.kol) === 1;
    }
    function aaneengesloten(lijst) {
        if (!lijst.length) return false;
        const gezien = new Set([lijst[0]]), rij = [lijst[0]];
        while (rij.length) { const t = rij.shift(); lijst.forEach(u => { if (!gezien.has(u) && buren(t, u)) { gezien.add(u); rij.push(u); } }); }
        return gezien.size === lijst.length;
    }
    // Vanaf één tafel de dichtstbijzijnde vrije buurtafels erbij pakken
    function groepVanaf(nr, nodig) {
        const groep = [nr], rij = [nr];
        while (rij.length && groep.length < nodig) {
            const t = rij.shift();
            cfg.tafels.map(x => x.nr).filter(u => !groep.includes(u) && tafelStatus[u] === 'vrij' && buren(t, u))
                .forEach(u => { if (groep.length < nodig) { groep.push(u); rij.push(u); } });
        }
        return groep;
    }

    // Bovenaanzicht van beide verdiepingen (plattegrond.js), met per tafel de status op het gekozen moment
    function tekenPlattegrond() {
        const klasse = nr => {
            if (keuze.tafels.includes(nr)) return 'gekozen';
            if (!keuze.tijd) return 'uit';
            const st = tafelStatus[nr] || 'vrij';
            return st === 'bezet' || st === 'te_klein' ? st : 'vrij';
        };
        const label = nr => {
            const st = keuze.tijd ? (tafelStatus[nr] || 'vrij') : '';
            return st === 'bezet' ? `Tafel ${nr}: gereserveerd` : st === 'te_klein' ? `Tafel ${nr}: te klein` : `Tafel ${nr}`;
        };
        const vak = document.getElementById('etages');
        vak.innerHTML = [1, 2].map(e => `
            <div class="pt-verdieping">
                <div class="pt-kop"><span>🏢 ${ETAGE_NAMEN[e]}</span><small>${e === 1 ? 'Tafels 1 - 10' : 'Tafels 11 - 26'}</small></div>
                ${Plattegrond.svg(e, { klasse, label })}
            </div>`).join('');
        Plattegrond.koppel(vak, nr => {
            const k = klasse(nr);
            if (k === 'vrij' || k === 'gekozen') kiesTafel(nr);
            else if (!keuze.tijd) toonFout('Kies eerst een tijd, daarna een tafel.');
        });
    }

    function kiesTafel(nr) {
        toonFout('');
        const nodig = tafelsNodig();
        const gekozen = keuze.tafels;
        if (nodig === 1) {
            keuze.tafels = [nr];
        } else if (gekozen.includes(nr)) {
            // Tafel weer weghalen (de rest moet wel aan elkaar blijven)
            const rest = gekozen.filter(t => t !== nr);
            keuze.tafels = aaneengesloten(rest) ? rest : [];
        } else if (gekozen.length && gekozen.length < nodig && gekozen.some(t => buren(t, nr))) {
            keuze.tafels = [...gekozen, nr];
        } else {
            // Nieuwe plek: deze tafel + vrije buurtafels automatisch erbij
            keuze.tafels = groepVanaf(nr, nodig);
            if (keuze.tafels.length < nodig) toonFout(`Rond tafel ${nr} zijn niet genoeg vrije tafels. Kies een andere plek.`);
        }
        keuze.tafels.sort((a, b) => a - b);
        tekenPlattegrond();
        werkSamenvattingBij();
    }

    function tafelsKlaar() {
        return keuze.tafels.length === tafelsNodig() && aaneengesloten(keuze.tafels);
    }

    function werkSamenvattingBij() {
        const tafelEl = document.getElementById('gekozen-tafel');
        const nodig = tafelsNodig();
        const t = keuze.tafels.length ? tafelInfo(keuze.tafels[0]) : null;
        const nummers = keuze.tafels.join(' + ');
        const plaatsen = keuze.tafels.reduce((som, nr) => som + tafelInfo(nr).plaatsen, 0);
        if (t && tafelsKlaar()) {
            tafelEl.classList.remove('leeg');
            tafelEl.innerHTML = keuze.tafels.length > 1
                ? `✅ <span>Tafels</span> <span data-geen-vertaling>${nummers}</span> · <span>${ETAGE_NAMEN[t.etage]}</span> · <span>aangeschoven</span> · <span data-geen-vertaling>${plaatsen}</span> <span>plaatsen</span>`
                : `✅ Tafel <span data-geen-vertaling>${t.nr}</span> · <span>${ETAGE_NAMEN[t.etage]}</span> · <span>${esc(PLEK_NAMEN[t.plek])}</span> · <span data-geen-vertaling>${t.plaatsen}</span> <span>plaatsen</span>`;
        } else {
            tafelEl.classList.add('leeg');
            if (!keuze.tijd) tafelEl.textContent = 'Kies eerst een tijd, daarna een tafel.';
            else if (nodig > 1 && keuze.tafels.length) tafelEl.textContent = `Gekozen: tafel ${nummers}. Kies er nog ${nodig - keuze.tafels.length} direct naast.`;
            else if (nodig > 1) tafelEl.textContent = `Tik op een vrije (groene) tafel: we kiezen er ${nodig - 1} vrije tafel(s) naast. Tik op een tafel om hem te wisselen.`;
            else tafelEl.textContent = 'Tik op een vrije (groene) tafel op de plattegrond.';
        }
        const sam = document.getElementById('samenvatting');
        sam.innerHTML = `<dt>Datum</dt><dd>${esc(datumTekst(keuze.datum))}</dd>`
            + `<dt>Tijd</dt><dd data-geen-vertaling>${keuze.tijd || '–'}</dd>`
            + `<dt>Personen</dt><dd data-geen-vertaling>${keuze.personen}</dd>`
            + `<dt>Tafel</dt><dd>${t && tafelsKlaar() ? `<span data-geen-vertaling>${nummers}</span> (<span>${ETAGE_NAMEN[t.etage]}</span>)` : '–'}</dd>`;
        document.getElementById('bevestig-knop').disabled = !(keuze.datum && keuze.tijd && tafelsKlaar());
    }

    function toonFout(melding) {
        const el = document.getElementById('fout');
        el.textContent = melding || '';
        if (melding) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function verstuur() {
        toonFout('');
        const knop = document.getElementById('bevestig-knop');
        const data = { datum: keuze.datum, tijd: keuze.tijd, personen: keuze.personen, tafels: keuze.tafels, taal: taal() };
        if (WIJZIG_CODE) {
            Object.assign(data, { actie: 'wijzig', code: WIJZIG_CODE });
        } else {
            Object.assign(data, {
                actie: 'reserveer',
                naam: document.getElementById('naam').value.trim(),
                email: document.getElementById('email').value.trim(),
                telefoon: document.getElementById('telefoon').value.trim(),
                opmerking: document.getElementById('opmerking').value.trim(),
            });
            if (!data.naam || !data.email || !data.telefoon) { toonFout('Vul je naam, e-mailadres en telefoonnummer in.'); return; }
        }
        knop.disabled = true;
        knop.textContent = 'Even geduld...';
        apiPost(data).then(d => {
            knop.textContent = WIJZIG_CODE ? 'Wijziging opslaan' : 'Reservering bevestigen';
            if (!d.succes) {
                knop.disabled = false;
                toonFout(d.melding);
                laadBeschikbaar();
                return;
            }
            toonKlaar(d);
        }).catch(() => { knop.disabled = false; knop.textContent = 'Reservering bevestigen'; toonFout('Kan de server niet bereiken. Probeer het opnieuw.'); });
    }

    function toonKlaar(d) {
        const r = d.reservering;
        document.getElementById('formulier').classList.add('verborgen');
        document.getElementById('wijzig-balk').classList.add('verborgen');
        document.getElementById('klaar').classList.remove('verborgen');
        document.getElementById('klaar-titel').textContent = WIJZIG_CODE ? 'Je reservering is gewijzigd!' : 'Je reservering is bevestigd!';
        document.getElementById('klaar-samenvatting').innerHTML =
            `<dt>Reserveringsnummer</dt><dd data-geen-vertaling>${esc(r.nummer)}</dd>`
            + `<dt>Datum</dt><dd>${esc(datumTekst(r.datum))}</dd>`
            + `<dt>Tijd</dt><dd data-geen-vertaling>${r.tijd} – ${r.tot}</dd>`
            + `<dt>Personen</dt><dd data-geen-vertaling>${r.personen}</dd>`
            + `<dt>Tafel</dt><dd><span data-geen-vertaling>${esc(r.tafel_tekst)}</span> (<span>${ETAGE_NAMEN[r.etage]}</span>)</dd>`;
        const link = 'mijn_reservering.php?code=' + encodeURIComponent(d.code);
        document.getElementById('klaar-link').href = link;
        document.getElementById('klaar-mail').innerHTML = d.mail && d.mail.gelukt
            ? `<p class="mail-ok">📧 We hebben een bevestiging gestuurd naar je e-mail. Daarin staat ook een link om te verplaatsen of te annuleren.</p>`
            : `<p class="mail-mis">⚠️ De bevestiging kon niet worden gemaild. Bewaar daarom de link hieronder (bijvoorbeeld als bladwijzer of met een screenshot) om je reservering later te bekijken, verplaatsen of annuleren.</p>`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Andere taal gekozen? Datums opnieuw tekenen in die taal
    new MutationObserver(() => { if (cfg && keuze.datum) { tekenKalender(); tekenOpeningstijden(); werkSamenvattingBij(); } })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

    init();
</script>
</body>
</html>
