<?php
/*
 * Las Tapas - qr_overzicht.php
 * Alle QR-codes op één plek:
 *  - Tafels: bekijken, groot tonen (om te laten scannen), openen, link kopiëren en printen.
 *  - Medewerkers: QR-code naar de inlogpagina voor personeel.
 * Alleen voor de baas, omdat de QR-codes de geheime tafelcodes bevatten.
 */
session_start();
require __DIR__ . '/gedeeld.php';

if (($_SESSION['rol'] ?? '') !== 'baas') {
    // Medewerkers die wel ingelogd zijn, gaan terug naar hun eigen pagina
    $terug = ['serveerster' => 'serveerster.php', 'chef' => 'chef.php', 'bar' => 'bar.php'];
    header('Location: ' . ($terug[$_SESSION['rol'] ?? ''] ?? 'login.html'));
    exit();
}
set_exception_handler('toonFoutPagina');
db();

$aantalTafels = count(TAFEL_INDELING);   // 26 tafels: 1-10 begane grond, 11-26 eerste verdieping
$codes = [];
for ($i = 1; $i <= $aantalTafels; $i++) $codes[$i] = tafelCode($i);

$paginaTitel = 'QR-codes';
$actievePagina = 'qr_overzicht.php';
$extraStijl = '
    /* Tabbladen */
    .sectie-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--lijn); margin-bottom: 18px; flex-wrap: wrap; }
    .sectie-tabs button { background: none; color: var(--grijs); border-radius: 0; margin: 0; padding: 12px 18px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
    .sectie-tabs button:hover { background: var(--vlak); }
    .sectie-tabs button.actief { color: var(--rood); border-bottom-color: var(--rood); }

    /* Werkbalk */
    .werkbalk { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
    .chips { display: flex; gap: 6px; flex-wrap: wrap; }
    .chips button { margin: 0; background: #f0eae1; color: var(--grijs); border-radius: 20px; padding: 8px 14px; font-size: 0.85rem; }
    .chips button.actief { background: var(--rood); color: white; }
    .werkbalk .rechts { margin-left: auto; display: flex; gap: 6px; flex-wrap: wrap; }
    .werkbalk .rechts input { width: 100px; margin: 0; padding: 8px 10px; border-radius: 20px; }
    .werkbalk .rechts button { margin: 0; padding: 8px 14px; border-radius: 20px; font-size: 0.85rem; }
    .knop-blauw { background: #1f4e8c !important; }
    .knop-groen { background: #27ae60 !important; }


    /* Weergave: plattegrond of kaartjes */
    .weergave-knoppen { display: inline-flex; background: #f0eae1; border-radius: 22px; padding: 3px; gap: 2px; }
    .weergave-knoppen button { margin: 0; background: none; color: var(--grijs); border-radius: 20px; padding: 7px 14px; font-size: 0.85rem; }
    .weergave-knoppen button.actief { background: white; color: var(--rood); box-shadow: 0 1px 4px rgba(0,0,0,0.12); }
    .weergave-knoppen button:hover { background: rgba(255,255,255,0.6); }
    .plattegronden { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .plattegronden[hidden], .raster[hidden] { display: none !important; }
    .plattegronden.een { grid-template-columns: minmax(0, 720px); justify-content: center; }
    .plattegronden .pt-tafel.qr .nummer-bol { fill: #fffaf2; }
    @media (max-width: 900px) { .plattegronden { grid-template-columns: 1fr; } }

    /* Popup bij een tafel */
    .tafel-popup { position: fixed; z-index: 2100; width: 270px; background: white; border-radius: 14px; box-shadow: 0 10px 34px rgba(0,0,0,0.28); padding: 16px; text-align: center; border-top: 6px solid var(--rood); }
    .tafel-popup[hidden] { display: none; }
    .tafel-popup h3 { margin: 0; font-size: 1.5rem; }
    .tafel-popup .etage { margin-bottom: 6px; }
    .tafel-popup img { width: 190px; height: 190px; display: block; margin: 4px auto 6px; cursor: zoom-in; }
    .tafel-popup .popup-link { display: block; font-family: Consolas, monospace; font-size: 0.68rem; color: #1f4e8c; word-break: break-all; background: var(--vlak); border-radius: 6px; padding: 6px; text-decoration: none; margin-bottom: 10px; }
    .tafel-popup .popup-knoppen { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .tafel-popup .popup-knoppen > * { margin: 0; padding: 8px 6px; font-size: 0.82rem; border-radius: 8px; text-align: center; text-decoration: none; font-weight: bold; }
    .tafel-popup .popup-knoppen .open { background: var(--rood); color: white; }
    .tafel-popup .popup-knoppen .kopieer { background: #f0eae1; color: var(--inkt); }
    .tafel-popup .popup-knoppen .groot-knop { grid-column: 1 / -1; background: #1f4e8c; }
    .tafel-popup .sluit-x { position: absolute; top: 6px; right: 8px; margin: 0; background: none; color: var(--grijs); font-size: 1.3rem; padding: 2px 8px; line-height: 1; }
    .tafel-popup .sluit-x:hover { background: #f0eae1; }
    .popup-achter { position: fixed; inset: 0; z-index: 2050; background: rgba(0,0,0,0.25); }
    .popup-achter[hidden] { display: none; }
    @media (min-width: 701px) { .popup-achter { background: transparent; } }

    /* Kaartjes */
    .raster { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 14px; }
    .qr-kaart { background: white; border: 1px solid var(--lijn); border-top: 4px solid var(--rood); border-radius: 10px; padding: 12px; text-align: center; }
    .qr-kaart h3 { margin: 0; font-size: 1.25rem; }
    .etage { display: block; font-size: 0.75rem; color: var(--grijs); font-weight: bold; margin: 2px 0 8px; }
    .qr-knop { background: none !important; border: none; padding: 0; margin: 0 auto; display: block; cursor: zoom-in; }
    .qr-knop img { width: 140px; height: 140px; display: block; }
    .scan-klein { font-size: 0.7rem; color: #444; margin: 6px 0 0 0; }
    .kaart-acties { display: flex; gap: 4px; margin-top: 10px; }
    .kaart-acties > * { flex: 1; margin: 0; padding: 7px 4px; font-size: 0.75rem; border-radius: 6px; text-align: center; text-decoration: none; }
    .kaart-acties .open { background: white; color: var(--rood); border: 2px solid var(--rood); font-weight: bold; }
    .kaart-acties .kopieer { background: #f0eae1; color: var(--grijs); }

    /* Medewerkers */
    .medewerker-kaart { max-width: 360px; margin: 0 auto; background: white; border: 1px solid var(--lijn); border-top: 8px solid var(--inkt); border-radius: 12px; padding: 24px; text-align: center; }
    .medewerker-kaart img { width: 220px; height: 220px; }
    .medewerker-kaart .link { font-size: 0.75rem; color: var(--grijs); word-break: break-all; }
    .reserveer-kaart { border-top-color: #5f6f2f; }
    .voor-gasten { background: #5f6f2f !important; }
    .alleen { display: inline-block; margin-top: 10px; background: var(--inkt); color: white; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; }

    /* Schermvullend */
    .groot { position: fixed; inset: 0; z-index: 2200; background: var(--creme); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; padding: 20px; }
    .groot[hidden] { display: none; }
    .groot h2 { margin: 0; font-size: clamp(2rem, 7vw, 4rem); line-height: 1; }
    .groot img { width: min(70vw, 62vh); height: min(70vw, 62vh); background: white; padding: 14px; border-radius: 16px; box-shadow: 0 6px 24px rgba(0,0,0,0.12); }
    .groot .scan { margin: 0; font-size: 1.1rem; font-weight: bold; }
    .groot-nav { display: flex; gap: 12px; }
    .groot-nav button { margin: 0; width: 56px; height: 56px; border-radius: 50%; font-size: 1.5rem; padding: 0; background: white; color: var(--inkt); border: 2px solid var(--lijn); }
    .groot-nav .sluit { width: auto; border-radius: 28px; padding: 0 22px; font-size: 1rem; background: var(--rood); color: white; border-color: var(--rood); }

    /* Plattegrond schermvullend, met pijltjes naar de andere verdieping */
    .groot-pt { position: fixed; inset: 0; z-index: 2000; background: var(--creme); display: flex; flex-direction: column; padding: 14px 16px 16px; gap: 10px; }
    .groot-pt[hidden] { display: none; }
    .gp-kop { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .gp-kop h2 { margin: 0; font-size: clamp(1.3rem, 3.5vw, 2rem); }
    .gp-kop small { color: var(--grijs); font-weight: bold; }
    .gp-kop .sluit { margin: 0 0 0 auto; border-radius: 24px; padding: 10px 20px; background: var(--rood); color: white; }
    .gp-midden { flex: 1; min-height: 0; display: flex; align-items: center; gap: 12px; }
    .gp-plan { flex: 1; min-width: 0; height: 100%; display: flex; align-items: center; justify-content: center; }
    .gp-plan .pt-zoom, .gp-plan .pt-schuif { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
    .gp-plan .pt-svg { width: 100%; height: 100%; max-width: none; max-height: 100%; }
    .gp-plan .pt-zoom-knop { display: none !important; }
    .gp-pijl { margin: 0; flex-shrink: 0; width: 60px; height: 60px; border-radius: 50%; font-size: 1.7rem; padding: 0; background: white; color: var(--inkt); border: 2px solid var(--lijn); box-shadow: 0 3px 10px rgba(0,0,0,0.12); }
    .gp-pijl:hover { background: var(--vlak); }
    .gp-pijl.weg { visibility: hidden; }
    .gp-stippen { display: flex; justify-content: center; gap: 8px; }
    .gp-stippen span { width: 10px; height: 10px; border-radius: 50%; background: #d8cbb8; }
    .gp-stippen span.aan { background: var(--rood); }
    @media (max-width: 700px) {
        .gp-midden { gap: 4px; }
        .gp-pijl { width: 44px; height: 44px; font-size: 1.3rem; }
        .gp-kop small { display: none; }
    }

    @media (max-width: 560px) {
        .werkbalk .rechts { margin-left: 0; width: 100%; }
        .werkbalk .rechts input { flex: 1; }
        .raster { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .qr-knop img { width: 110px; height: 110px; }
        .kaart-acties { flex-direction: column; }
    }

    /* Printen: alleen de QR-kaartjes */
    @media print {
        body { background: white; padding: 0; }
        .container { box-shadow: none; padding: 0; max-width: none; }
        .header-flex, .beheer-nav, .sectie-tabs, .werkbalk, .kaart-acties, .groot, .groot-pt, .uitleg, .taal-kiezer, .plattegronden, .tafel-popup, .popup-achter { display: none !important; }
        body.print-tafels #raster { display: grid !important; }
        .raster { grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .qr-kaart { border: 2px dashed var(--rood); break-inside: avoid; }
        body.print-medewerker #sectie-tafels, body.print-tafels #sectie-medewerkers, body.print-reserveren #sectie-tafels, body.print-reserveren #sectie-medewerkers,
        body.print-tafels #sectie-reserveren, body.print-medewerker #sectie-reserveren { display: none !important; }
        body.print-reserveren .medewerker-kaart { box-shadow: none; margin-top: 40px; }
        .sectie[hidden] { display: block !important; }
        body.print-medewerker .medewerker-kaart { box-shadow: none; margin-top: 40px; }
        body.print-medewerker .medewerker-knoppen, body.print-reserveren .medewerker-knoppen { display: none !important; }
    }
';
include __DIR__ . '/beheer_kop.php';
?>

    <div class="sectie-tabs">
        <button type="button" data-sectie="tafels" class="actief" onclick="toonSectie('tafels')">🍽️ Tafels</button>
        <button type="button" data-sectie="medewerkers" onclick="toonSectie('medewerkers')">👤 Medewerkers</button>
        <button type="button" data-sectie="reserveren" onclick="toonSectie('reserveren')">📅 Reserveren</button>
    </div>

    <!-- ===== TAFELS ===== -->
    <section class="sectie" id="sectie-tafels">
        <div class="werkbalk">
            <div class="chips">
                <button type="button" class="actief" data-etage="alle" onclick="filterEtage('alle')">Alle tafels</button>
                <button type="button" data-etage="1" onclick="filterEtage('1')">Begane grond</button>
                <button type="button" data-etage="2" onclick="filterEtage('2')">Eerste verdieping</button>
            </div>
            <div class="weergave-knoppen">
                <button type="button" data-weergave="plattegrond" class="actief" onclick="kiesWeergave('plattegrond')">🗺️ Plattegrond</button>
                <button type="button" data-weergave="kaartjes" onclick="kiesWeergave('kaartjes')">🔳 QR-kaartjes</button>
            </div>
            <form class="rechts" onsubmit="event.preventDefault(); gaNaarTafel();">
                <input type="number" id="zoek-tafel" min="1" max="<?php echo $aantalTafels; ?>" placeholder="Tafel nr.">
                <button type="submit" class="knop-blauw">🔍 Groot tonen</button>
                <button type="button" class="knop-groen" onclick="printen('tafels')">🖨️ Printen</button>
            </form>
        </div>
        <p class="uitleg" id="uitleg-plattegrond">Bovenaanzicht van het restaurant. Tik op een tafel voor de QR-code en de link. "Groot tonen" laat de plattegrond schermvullend zien (vul je een tafelnummer in, dan alleen die QR-code). "Printen" print de QR-kaartjes van de tafels die je nu ziet.</p>
        <p class="uitleg" id="uitleg-kaartjes" hidden>Tik op een QR-code om hem schermvullend te tonen. "Printen" print de tafels die je nu ziet (alle, of één verdieping).</p>


        <div class="plattegronden" id="plattegronden"></div>
        <div class="raster" id="raster" hidden></div>
    </section>

    <!-- Popup bij een tafel op de plattegrond -->
    <div class="popup-achter" id="popup-achter" hidden onclick="sluitPopup()"></div>
    <div class="tafel-popup" id="tafel-popup" hidden role="dialog" aria-labelledby="popup-titel">
        <button type="button" class="sluit-x" onclick="sluitPopup()" aria-label="Sluiten">×</button>
        <h3 id="popup-titel">Mesa 1</h3>
        <span class="etage" id="popup-etage"></span>
        <img id="popup-qr" alt="QR-code" onclick="toonGroot(popupTafel); sluitPopup();">
        <a class="popup-link" id="popup-link" href="#" target="_blank" rel="noopener" data-geen-vertaling></a>
        <div class="popup-knoppen">
            <a class="open" id="popup-open" href="#" target="_blank" rel="noopener">🔗 Openen</a>
            <button type="button" class="kopieer" id="popup-kopieer">📋 Kopieer link</button>
            <button type="button" class="groot-knop" onclick="toonGroot(popupTafel); sluitPopup();">🔍 Groot tonen</button>
        </div>
    </div>

    <!-- ===== MEDEWERKERS ===== -->
    <section class="sectie" id="sectie-medewerkers" hidden>
        <div class="medewerker-kaart">
            <h3 style="margin-top: 0;">🍷 Las Tapas</h3>
            <p style="font-weight: bold; color: var(--grijs); margin-top: 0;">Inloggen personeel</p>
            <img id="medewerker-qr" alt="QR-code naar de inlogpagina voor medewerkers">
            <p class="uitleg">Scan met je telefoon of tablet om in te loggen als chef, serveerster of beheerder.</p>
            <p class="link" id="medewerker-link"></p>
            <span class="alleen">Alleen voor medewerkers</span>
            <div class="medewerker-knoppen" style="display: flex; gap: 8px; justify-content: center;">
                <button type="button" class="knop-blauw" onclick="toonGroot('login')">🔍 Groot tonen</button>
                <button type="button" class="knop-groen" onclick="printen('medewerker')">🖨️ Printen</button>
            </div>
        </div>
        <p class="uitleg" style="text-align: center; margin-top: 15px;">Hang deze QR-code op een plek waar alleen personeel komt, bijvoorbeeld in de keuken.</p>
    </section>

    <!-- ===== RESERVEREN ===== -->
    <section class="sectie" id="sectie-reserveren" hidden>
        <div class="medewerker-kaart reserveer-kaart">
            <h3 style="margin-top: 0;">🍷 Las Tapas</h3>
            <p style="font-weight: bold; color: var(--grijs); margin-top: 0;">Reserveer je tafel</p>
            <img id="reserveer-qr" alt="QR-code naar de reserveerpagina">
            <p class="uitleg">Scan om online een tafel te reserveren: kies een dag, tijd en je eigen tafel op de plattegrond.</p>
            <p class="link" id="reserveer-link"></p>
            <span class="alleen voor-gasten">Voor gasten</span>
            <div class="medewerker-knoppen" style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                <button type="button" class="knop-blauw" onclick="toonGroot('reserveren')">🔍 Groot tonen</button>
                <button type="button" class="knop-groen" onclick="printen('reserveren')">🖨️ Printen</button>
                <a class="knop" id="reserveer-open" href="reserveren.php" target="_blank" rel="noopener" style="margin-top: 15px;">🔗 Openen</a>
            </div>
        </div>
        <p class="uitleg" style="text-align: center; margin-top: 15px;">Zet deze QR-code bijvoorbeeld op de rekening, op het raam of op flyers. Alle reserveringen zie je bij <a href="reserveringen.php">📅 Reserveringen</a>.</p>
    </section>

</div>

<!-- Schermvullende plattegrond (pijltjes: andere verdieping) -->
<div class="groot-pt" id="groot-pt" hidden role="dialog" aria-labelledby="gp-titel">
    <div class="gp-kop">
        <h2 id="gp-titel">🏢 Begane grond</h2>
        <small id="gp-sub">Tafels 1 - 10 · tik op een tafel voor de QR-code</small>
        <button type="button" class="sluit" onclick="sluitGrootPlattegrond()">✕ Sluiten</button>
    </div>
    <div class="gp-midden">
        <button type="button" class="gp-pijl" id="gp-links" onclick="bladerPlattegrond(-1)" aria-label="Vorige verdieping">←</button>
        <div class="gp-plan" id="gp-plan"></div>
        <button type="button" class="gp-pijl" id="gp-rechts" onclick="bladerPlattegrond(1)" aria-label="Volgende verdieping">→</button>
    </div>
    <div class="gp-stippen" aria-hidden="true"><span></span><span></span></div>
</div>

<!-- Schermvullende QR-code -->
<div class="groot" id="groot" hidden onclick="if (event.target === this) sluitGroot()">
    <h2 id="groot-titel">Mesa 1</h2>
    <span class="etage" id="groot-etage"></span>
    <img id="groot-qr" alt="QR-code">
    <p class="scan" id="groot-scan">📱 Scan om te bestellen</p>
    <div class="groot-nav">
        <button type="button" id="groot-vorige" onclick="bladeren(-1)" aria-label="Vorige tafel">←</button>
        <button type="button" class="sluit" onclick="sluitGroot()">Sluiten</button>
        <button type="button" id="groot-volgende" onclick="bladeren(1)" aria-label="Volgende tafel">→</button>
    </div>
</div>

<script src="plattegrond.js?v=20261002a"></script>
<script>
    const codes = <?php echo json_encode($codes); ?>;     // geheime tafelcodes (berekend op de server)
    const STANDAARD_ADRES = 'https://candied-smugness-concise.ngrok-free.dev/lastapas/klant.html';

    // Welk adres komt in de QR-codes?
    //  - Open je deze pagina via ngrok (of een ander echt adres), dan dat adres: zo kloppen de QR-codes altijd.
    //  - Via localhost werkt een QR-code niet op een telefoon; dan het vaste ngrok-adres (STANDAARD_ADRES).
    const HIER = location.origin + location.pathname.replace(/[^\/]*$/, '') + 'klant.html';
    const OP_LOCALHOST = ['localhost', '127.0.0.1', '::1', '[::1]'].includes(location.hostname);
    const adres = OP_LOCALHOST ? STANDAARD_ADRES : HIER;
    // "Openen" moet op deze computer altijd werken, ook als ngrok uit staat
    const openVan = link => OP_LOCALHOST ? link.replace(adres, HIER) : link;
    let etageFilter = 'alle';
    let grootTafel = null;
    let weergave = 'plattegrond';
    let popupTafel = null;
    try { weergave = localStorage.getItem('qr_weergave') || 'plattegrond'; } catch (e) {}
    const etageNaam = t => etageVan(t) === '1' ? 'Begane grond' : 'Eerste verdieping';

    const etageVan = t => (t <= 10 ? '1' : '2');   // '1' = begane grond, '2' = eerste verdieping
    const linkVan = t => `${adres}?tafel=${t}&code=${codes[t]}`;
    const loginLink = () => adres.replace(/klant\.html$/, 'login.html');
    const reserveerLink = () => adres.replace(/klant\.html$/, 'reserveren.php');
    const qrVoor = (data, px) => `https://api.qrserver.com/v1/create-qr-code/?size=${px}x${px}&margin=4&data=${encodeURIComponent(data)}`;
    const zichtbareTafels = () => Object.keys(codes).map(Number).filter(t => etageFilter === 'alle' || etageVan(t) === etageFilter);

    function toonSectie(sectie) {
        document.querySelectorAll('.sectie').forEach(el => el.hidden = el.id !== 'sectie-' + sectie);
        document.querySelectorAll('.sectie-tabs button').forEach(b => b.classList.toggle('actief', b.dataset.sectie === sectie));
    }

    function tekenRaster() {
        const raster = document.getElementById('raster');
        raster.innerHTML = '';
        zichtbareTafels().forEach(t => {
            const kaart = document.createElement('div');
            kaart.className = 'qr-kaart';
            kaart.innerHTML = `
                <h3>Mesa ${t}</h3>
                <span class="etage">🏢 ${etageNaam(t)}</span>
                <button type="button" class="qr-knop" aria-label="QR-code van tafel ${t} groot tonen">
                    <img src="${qrVoor(linkVan(t), 300)}" alt="QR-code tafel ${t}" loading="lazy">
                </button>
                <p class="scan-klein">Scan om te bestellen</p>
                <div class="kaart-acties">
                    <a class="open" href="${openVan(linkVan(t))}" target="_blank" rel="noopener">🔗 Openen</a>
                    <button type="button" class="kopieer">📋 Kopieer link</button>
                </div>`;
            kaart.querySelector('.qr-knop').addEventListener('click', () => toonGroot(t));
            kaart.querySelector('.kopieer').addEventListener('click', e => kopieer(linkVan(t), e.currentTarget));
            raster.appendChild(kaart);
        });
        // Medewerkers-QR hoort bij hetzelfde adres
        document.getElementById('medewerker-qr').src = qrVoor(loginLink(), 440);
        document.getElementById('medewerker-link').textContent = loginLink();
        document.getElementById('reserveer-qr').src = qrVoor(reserveerLink(), 440);
        document.getElementById('reserveer-link').textContent = reserveerLink();
        document.getElementById('reserveer-open').href = openVan(reserveerLink());
        tekenPlattegrond();
    }

    function filterEtage(etage) {
        etageFilter = etage;
        document.querySelectorAll('.chips button').forEach(b => b.classList.toggle('actief', b.dataset.etage === etage));
        sluitPopup();
        tekenRaster();
    }

    // ---------- Plattegrond (bovenaanzicht) ----------
    function kiesWeergave(w) {
        weergave = w;
        try { localStorage.setItem('qr_weergave', w); } catch (e) {}
        document.querySelectorAll('.weergave-knoppen button').forEach(b => b.classList.toggle('actief', b.dataset.weergave === w));
        document.getElementById('plattegronden').hidden = w !== 'plattegrond';
        document.getElementById('raster').hidden = w !== 'kaartjes';
        document.getElementById('uitleg-plattegrond').hidden = w !== 'plattegrond';
        document.getElementById('uitleg-kaartjes').hidden = w !== 'kaartjes';
        sluitPopup();
    }

    function tekenPlattegrond() {
        const vak = document.getElementById('plattegronden');
        const etages = etageFilter === 'alle' ? [1, 2] : [Number(etageFilter)];
        vak.classList.toggle('een', etages.length === 1);
        vak.innerHTML = etages.map(e => `
            <div class="pt-verdieping">
                <div class="pt-kop"><span>🏢 ${e === 1 ? 'Begane grond' : 'Eerste verdieping'}</span><small>${e === 1 ? 'Tafels 1 - 10' : 'Tafels 11 - 26'}</small></div>
                ${Plattegrond.svg(e, { klasse: () => 'qr', label: t => `QR-code van tafel ${t}`, icoon: () => '🔳' })}
            </div>`).join('');
        Plattegrond.koppel(vak, (t, g) => openPopup(t, g));
    }

    function openPopup(t, g) {
        popupTafel = t;
        const p = document.getElementById('tafel-popup');
        document.getElementById('popup-titel').textContent = `Mesa ${t}`;
        document.getElementById('popup-etage').textContent = `🏢 ${etageNaam(t)} · Tafel ${t}`;
        document.getElementById('popup-qr').src = qrVoor(linkVan(t), 380);
        document.getElementById('popup-qr').alt = `QR-code tafel ${t}`;
        const link = document.getElementById('popup-link');
        link.textContent = linkVan(t);
        link.href = openVan(linkVan(t));
        document.getElementById('popup-open').href = openVan(linkVan(t));
        document.getElementById('popup-kopieer').onclick = e => kopieer(linkVan(t), e.currentTarget);
        document.getElementById('popup-kopieer').textContent = '📋 Kopieer link';
        p.hidden = false;
        document.getElementById('popup-achter').hidden = false;
        // Op een groot scherm naast de tafel, op de telefoon in het midden
        const b = p.offsetWidth, h = p.offsetHeight, vw = window.innerWidth, vh = window.innerHeight;
        let x = (vw - b) / 2, y = (vh - h) / 2;
        if (vw > 700 && g) {
            const r = g.getBoundingClientRect();
            x = r.right + 12 + b < vw ? r.right + 12 : r.left - 12 - b;
            y = Math.min(Math.max(10, r.top + r.height / 2 - h / 2), vh - h - 10);
            x = Math.min(Math.max(10, x), vw - b - 10);
        }
        p.style.left = x + 'px';
        p.style.top = Math.max(10, y) + 'px';
        p.querySelector('.sluit-x').focus({ preventScroll: true });
    }

    function sluitPopup() {
        document.getElementById('tafel-popup').hidden = true;
        document.getElementById('popup-achter').hidden = true;
        popupTafel = null;
    }
    window.addEventListener('resize', sluitPopup);


    // Schermvullend tonen (een tafel, 'login' voor de medewerkers of 'reserveren' voor gasten)
    function toonGroot(t) {
        grootTafel = t;
        const login = t === 'login', res = t === 'reserveren', los = login || res;
        document.getElementById('groot-titel').textContent = los ? 'Las Tapas' : `Mesa ${t}`;
        document.getElementById('groot-etage').textContent = login ? '👤 Inloggen personeel' : res ? '📅 Reserveer je tafel' : `🏢 ${etageNaam(t)} · Tafel ${t}`;
        document.getElementById('groot-scan').textContent = login ? '📱 Scan om in te loggen' : res ? '📱 Scan om te reserveren' : '📱 Scan om te bestellen';
        document.getElementById('groot-qr').src = qrVoor(login ? loginLink() : res ? reserveerLink() : linkVan(t), 600);
        document.getElementById('groot-vorige').hidden = los;
        document.getElementById('groot-volgende').hidden = los;
        document.getElementById('groot').hidden = false;
    }

    function sluitGroot() {
        document.getElementById('groot').hidden = true;
        grootTafel = null;
    }

    function bladeren(stap) {
        if (grootTafel === 'login' || grootTafel === 'reserveren') return;
        const lijst = zichtbareTafels();
        const i = lijst.indexOf(grootTafel);
        toonGroot(lijst[(i + stap + lijst.length) % lijst.length]);
    }

    // "Groot tonen": met een tafelnummer de QR-code van die tafel, zonder nummer de plattegrond
    function gaNaarTafel() {
        const t = parseInt(document.getElementById('zoek-tafel').value);
        if (codes[t]) toonGroot(t);
        else toonGrootPlattegrond(etageFilter === '2' ? 2 : 1);
    }

    // ---------- Plattegrond schermvullend ----------
    let grootEtage = null;
    function toonGrootPlattegrond(e) {
        grootEtage = e;
        const plan = document.getElementById('gp-plan');
        plan.innerHTML = Plattegrond.svg(e, { klasse: () => 'qr', label: t => `QR-code van tafel ${t}`, icoon: () => '🔳' });
        Plattegrond.koppel(plan, (t, g) => openPopup(t, g));
        document.getElementById('gp-titel').textContent = e === 1 ? '🏢 Begane grond' : '🏢 Eerste verdieping';
        document.getElementById('gp-sub').textContent = (e === 1 ? 'Tafels 1 - 10' : 'Tafels 11 - 26') + ' · tik op een tafel voor de QR-code';
        document.getElementById('gp-links').classList.toggle('weg', e === 1);
        document.getElementById('gp-rechts').classList.toggle('weg', e === 2);
        document.querySelectorAll('.gp-stippen span').forEach((s, i) => s.classList.toggle('aan', i === e - 1));
        sluitPopup();
        document.getElementById('groot-pt').hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function bladerPlattegrond(stap) {
        const e = grootEtage + stap;
        if (e >= 1 && e <= 2) toonGrootPlattegrond(e);
    }
    function sluitGrootPlattegrond() {
        document.getElementById('groot-pt').hidden = true;
        document.body.style.overflow = '';
        grootEtage = null;
        sluitPopup();
    }

    // Alleen de tafels of alleen de medewerkers-QR printen
    function printen(wat) {
        document.body.classList.remove('print-tafels', 'print-medewerker', 'print-reserveren');
        document.body.classList.add(wat === 'medewerker' ? 'print-medewerker' : wat === 'reserveren' ? 'print-reserveren' : 'print-tafels');
        window.print();
    }

    function kopieer(link, knop) {
        const klaar = () => { knop.textContent = '✅ Gekopieerd'; setTimeout(() => knop.textContent = '📋 Kopieer link', 1500); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link).then(klaar).catch(() => prompt('Kopieer deze link:', link));
        } else {
            prompt('Kopieer deze link:', link);
        }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && popupTafel !== null) { sluitPopup(); return; }
        if (grootTafel === null && grootEtage !== null) {
            if (e.key === 'ArrowRight') bladerPlattegrond(1);
            if (e.key === 'ArrowLeft') bladerPlattegrond(-1);
            if (e.key === 'Escape') sluitGrootPlattegrond();
            return;
        }
        if (grootTafel === null) return;
        if (e.key === 'ArrowRight') bladeren(1);
        if (e.key === 'ArrowLeft') bladeren(-1);
        if (e.key === 'Escape') sluitGroot();
    });

    tekenRaster();
    kiesWeergave(weergave);
</script>
</body>
</html>
