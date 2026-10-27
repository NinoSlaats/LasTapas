<?php
/*
 * Las Tapas - reserveringen.php
 * Overzicht van alle reserveringen voor het personeel (baas, bediening, keuken en bar).
 *  - Per dag: lijst met tijd, gast, telefoon, e-mail, tafel en status
 *  - Gast aangekomen / niet gekomen aanvinken, verplaatsen of annuleren (de gast krijgt dan een e-mail)
 *  - Bezettingsschema: welke tafel is wanneer gereserveerd
 *  - Zoeken op naam, telefoonnummer of reserveringsnummer
 */
session_start();
require __DIR__ . '/gedeeld.php';

if (!in_array($_SESSION['rol'] ?? '', ['baas', 'serveerster', 'chef', 'bar'], true)) {
    header('Location: login.html');
    exit();
}
set_exception_handler('toonFoutPagina');
db();
$inst = reserveerInstellingen();
ruimGeannuleerdeReserveringenOp();

$paginaTitel = 'Reserveringen';
$rodeKop = true;   // werkplek-pagina: rode kop zoals Keuken, Bediening en Voorraad
$kopTitel = '📅 Las Tapas - Reserveringen';
$kopOndertitel = 'Wie komt er wanneer, en aan welke tafel';
$actievePagina = 'reserveringen.php';
$extraStijl = '
    .dag-balk { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
    .dag-balk button { margin-top: 0; padding: 9px 14px; }
    .dag-balk .licht { background: #f0eae1; color: var(--inkt); }
    .dag-balk .licht:hover { background: #e4dccf; }
    .dag-balk input[type=date] { padding: 9px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; font-family: inherit; }
    .dag-titel { font-family: Georgia, serif; font-size: 1.35rem; color: var(--rood); margin: 4px 0 12px; }
    /* Komende dagen met reserveringen */
    .komend { background: #eef4fb; border: 1px solid #c9d9ee; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; }
    .komend-kop { font-weight: bold; color: #1f4e8c; margin-bottom: 8px; }
    .komend-dagen { display: flex; gap: 8px; flex-wrap: wrap; }
    .komend-dag { margin: 0 !important; background: white !important; color: var(--inkt) !important; border: 2px solid #c9d9ee !important; border-radius: 12px !important;
                  padding: 8px 12px !important; text-align: left; font-weight: normal !important; font-size: 0.85rem !important; line-height: 1.35; }
    .komend-dag:hover { border-color: #1f4e8c !important; }
    .komend-dag b { display: block; font-size: 0.95rem; color: #1f4e8c; }
    .komend-leeg { color: var(--grijs); font-size: 0.9rem; }
    .tellers { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
    .teller { background: var(--vlak); border: 1px solid var(--lijn); border-radius: 10px; padding: 10px 14px; min-width: 120px; }
    .teller b { display: block; font-size: 1.4rem; color: var(--inkt); }
    .teller span { color: var(--grijs); font-size: 0.85rem; }
    .zoekbalk { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .zoekbalk input { flex: 1 1 260px; margin-top: 0 !important; }
    .zoekbalk .knop { margin-top: 0; background: #5f6f2f; }
    .weergave { display: flex; gap: 0; border-bottom: 2px solid var(--lijn); margin-bottom: 16px; }
    .weergave button { background: none; color: var(--grijs); border-radius: 0; margin: 0; padding: 10px 16px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
    .weergave button:hover { background: var(--vlak); }
    .weergave button.actief { color: var(--rood); border-bottom-color: var(--rood); }
    .res-kaart { display: grid; grid-template-columns: 80px 1fr auto; gap: 14px; align-items: start; border: 1px solid var(--lijn); border-left: 6px solid #1f4e8c; border-radius: 10px; padding: 14px; margin-bottom: 12px; background: white; }
    .res-kaart.aangekomen { border-left-color: #27ae60; background: #f6fcf8; }
    .res-kaart.niet_gekomen { border-left-color: #c0392b; background: #fff8f7; }
    .res-kaart.geannuleerd { border-left-color: #aaa; background: #f7f7f7; opacity: 0.7; }
    .res-tijd { font-size: 1.5rem; font-weight: bold; font-family: Georgia, serif; line-height: 1.1; }
    .res-tijd small { display: block; font-size: 0.75rem; color: var(--grijs); font-family: "Segoe UI", sans-serif; font-weight: normal; }
    .res-naam { font-size: 1.1rem; font-weight: bold; margin: 0 0 4px; }
    .res-info { display: flex; gap: 6px 14px; flex-wrap: wrap; font-size: 0.9rem; color: var(--inkt); margin-bottom: 6px; }
    .res-info a { color: #1f4e8c; font-weight: bold; text-decoration: none; }
    .res-opm { background: #fff8e7; border-left: 3px solid #e9a23b; padding: 6px 10px; border-radius: 6px; font-size: 0.88rem; margin-top: 6px; }
    .res-meta { color: var(--grijs); font-size: 0.78rem; margin-top: 6px; }
    .st { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: bold; margin-left: 6px; vertical-align: middle; }
    .st-gepland { background: #eef4fb; color: #1f4e8c; }
    .st-aangekomen { background: #eafaf1; color: #1e8449; }
    .st-niet_gekomen { background: #fdecea; color: #922b21; }
    .st-geannuleerd { background: #eee; color: #666; }
    .res-acties { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
    .res-acties button { margin-top: 0; padding: 8px 12px; font-size: 0.85rem; }
    .b-groen { background: #27ae60; } .b-groen:hover { background: #1e8449; }
    .b-blauw { background: #1f4e8c; } .b-blauw:hover { background: #163a69; }
    .b-rand { background: white; color: var(--rood); border: 2px solid var(--rood) !important; } .b-rand:hover { background: #fdecea; }
    .b-grijs { background: #786a63; } .b-grijs:hover { background: #5d524c; }
    .leeg { text-align: center; color: var(--grijs); padding: 30px; background: var(--vlak); border-radius: 10px; }

    /* Bezettingsschema */
    .schema-scroll { overflow-x: auto; border: 1px solid var(--lijn); border-radius: 10px; }
    table.schema { margin: 0; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; min-width: 760px; }
    table.schema th, table.schema td { padding: 0; border-bottom: 1px solid #f0e8dd; border-right: 1px solid #f5efe7; text-align: center; height: 32px; }
    table.schema th { background: var(--vlak); padding: 6px 4px; font-size: 0.75rem; position: sticky; top: 0; }
    table.schema th.tafel-kol, table.schema td.tafel-kol { position: sticky; left: 0; background: white; text-align: left; padding: 0 8px; min-width: 92px; z-index: 1; font-weight: bold; white-space: nowrap; }
    table.schema tr.etage-rij td { background: #2b1d19; color: white; font-weight: bold; text-align: left; padding: 6px 10px; height: auto; }
    table.schema td.vol { background: #1f4e8c; color: white; cursor: pointer; }
    table.schema td.vol.aangekomen { background: #27ae60; }
    table.schema td.vol.niet_gekomen { background: #c0392b; }
    table.schema td.vol .s-naam { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 90px; padding: 0 4px; font-weight: bold; }
    table.schema td.nu { box-shadow: inset 2px 0 0 #e9a23b; }
    .schema-legenda { display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.82rem; color: var(--grijs); margin-top: 10px; }
    .schema-legenda i { display: inline-block; width: 14px; height: 14px; border-radius: 3px; vertical-align: -2px; margin-right: 5px; }

    /* Venster verplaatsen / annuleren */
    .venster-achter { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
    .venster-achter[hidden] { display: none; }
    .venster { background: white; border-radius: 14px; padding: 22px; width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; }
    .venster h3 { margin: 0 0 6px; }
    .venster .rij3 { display: grid; grid-template-columns: 1fr 1fr 90px; gap: 10px; }
    .venster input[type=date] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 0.95rem; }
    .kies-tafels { display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 6px; margin-top: 8px; }
    .kies-tafels button { margin: 0; padding: 7px 4px; font-size: 0.8rem; background: #eef6e4; color: var(--inkt); border: 2px solid #5f6f2f; }
    .kies-tafels button small { display: block; font-weight: normal; font-size: 0.7rem; }
    .kies-tafels.raster-1, .kies-tafels.raster-2 { grid-template-columns: repeat(4, minmax(60px, 1fr)); }
    .kies-tafels button.gekozen { background: var(--rood); border-color: var(--rood); color: white; }
    .kies-tafels button:disabled { background: #eee; border-color: #ccc; color: #999; cursor: not-allowed; }
    .etage-label { font-weight: bold; font-size: 0.85rem; color: var(--grijs); margin-top: 10px; }
    .venster-knoppen { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
    .venster-knoppen button { margin-top: 0; flex: 1; }
    .venster .fout-tekst { color: #c0392b; font-weight: bold; margin: 10px 0 0; }
    .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #2b1d19; color: white; padding: 12px 20px; border-radius: 10px; z-index: 1100; font-weight: bold; max-width: 90vw; }
    .toast[hidden] { display: none; }

    @media (max-width: 700px) {
        .res-kaart { grid-template-columns: 64px 1fr; }
        .res-acties { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; min-width: 0; }
        .res-acties button { flex: 1 1 130px; }
        .venster .rij3 { grid-template-columns: 1fr 1fr; }
    }
';
include __DIR__ . '/beheer_kop.php';
?>
    <?php if (!$inst['aan']): ?>
        <div class="melding fout">Online reserveren staat uit. Gasten kunnen nu niet via de site reserveren. <?php if ($isBaas): ?>Zet het aan bij <a href="menu_beheer.php">Menu &amp; prijzen → Instellingen</a>.<?php endif; ?></div>
    <?php endif; ?>

    <div class="dag-balk">
        <button class="licht" onclick="verschuifDag(-1)" aria-label="Vorige dag">◀</button>
        <input type="date" id="datum" onchange="kiesDatum(this.value)">
        <button class="licht" onclick="verschuifDag(1)" aria-label="Volgende dag">▶</button>
        <button onclick="kiesDatum(VANDAAG)">Vandaag</button>
        <a href="reserveren.php" target="_blank" class="knop" style="margin-top: 0; background: #5f6f2f;">➕ Telefonische reservering</a>
    </div>

    <div class="zoekbalk">
        <input type="text" id="zoek" placeholder="🔍 Zoek op naam, telefoonnummer of reserveringsnummer (bijv. R-0012)" oninput="zoekVertraagd()">
    </div>

    <div id="zoek-resultaat" hidden></div>

    <div id="dag-weergave">
        <div class="dag-titel" id="dag-titel"></div>
        <div class="tellers" id="tellers"></div>
        <div class="komend" id="komend" hidden></div>
        <div class="weergave">
            <button class="actief" id="w-lijst" onclick="kiesWeergave('lijst')">📋 Lijst</button>
            <button id="w-schema" onclick="kiesWeergave('schema')">🗓️ Bezettingsschema</button>
        </div>
        <div id="lijst"><div class="leeg">Laden…</div></div>
        <div id="schema" hidden></div>
    </div>
</div>

<!-- Venster: verplaatsen -->
<div class="venster-achter" id="verplaats-venster" hidden onclick="if (event.target === this) sluitVensters()">
    <div class="venster">
        <h3>🔁 Reservering verplaatsen</h3>
        <p class="uitleg" id="verplaats-uitleg"></p>
        <div class="rij3">
            <div><label>Datum</label><input type="date" id="v-datum" onchange="vulTijden(this.value, document.getElementById('v-tijd').value).then(laadVrijeTafels)"></div>
            <div><label>Tijd</label><select id="v-tijd" onchange="laadVrijeTafels()"></select></div>
            <div><label>Personen</label><input type="number" id="v-personen" min="1" max="104" onchange="laadVrijeTafels()"></div>
        </div>
        <div id="v-tafels"></div>
        <p class="uitleg" style="margin: 10px 0 0;">📧 De gast krijgt automatisch een e-mail met de nieuwe gegevens.</p>
        <p class="fout-tekst" id="v-fout"></p>
        <div class="venster-knoppen">
            <button class="b-grijs" onclick="sluitVensters()">Terug</button>
            <button class="b-blauw" id="v-opslaan" onclick="slaVerplaatsingOp()">Opslaan &amp; gast mailen</button>
        </div>
    </div>
</div>

<!-- Venster: annuleren -->
<div class="venster-achter" id="annuleer-venster" hidden onclick="if (event.target === this) sluitVensters()">
    <div class="venster">
        <h3>✖ Reservering annuleren</h3>
        <p id="annuleer-uitleg"></p>
        <label style="display: flex; gap: 8px; align-items: center; font-weight: normal;"><input type="checkbox" id="a-mail" checked> Gast een e-mail sturen dat de reservering is geannuleerd</label>
        <p class="fout-tekst" id="a-fout"></p>
        <div class="venster-knoppen">
            <button class="b-grijs" onclick="sluitVensters()">Terug</button>
            <button id="a-ok" onclick="bevestigAnnuleren()">Ja, annuleren</button>
        </div>
    </div>
</div>
<div class="toast" id="toast" hidden></div>

<script>
    const VANDAAG = <?php echo json_encode(date('Y-m-d')); ?>;
    const TAFELS = <?php echo json_encode(array_map(fn($nr, $t) => ['nr' => $nr] + $t, array_keys(TAFEL_INDELING), TAFEL_INDELING)); ?>;
    const PLEK_NAMEN = { raam: 'raam', midden: 'midden', bar: 'bar', entree: 'entree', lounge: 'lounge' };
    const ETAGE_NAMEN = { 1: 'Begane grond', 2: 'Eerste verdieping' };
    const STATUS_NAMEN = { gepland: 'Verwacht', aangekomen: 'Aangekomen', niet_gekomen: 'Niet gekomen', geannuleerd: 'Geannuleerd' };
    let datum = new URLSearchParams(location.search).get('datum') || VANDAAG;
    let weergave = 'lijst';
    let data = { reserveringen: [], tijden: [], duur: 120 };
    let bezig = null;           // reservering in het venster
    let gekozenTafels = [];
    let zoekTimer = null;

    function esc(t) { return String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function locale() { return { nl: 'nl-NL', en: 'en-GB', es: 'es-ES' }[document.documentElement.lang] || 'nl-NL'; }
    function datumTekst(iso) { return new Date(iso + 'T12:00:00').toLocaleDateString(locale(), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); }
    function kortDatum(iso) { return new Date(iso + 'T12:00:00').toLocaleDateString(locale(), { weekday: 'short', day: 'numeric', month: 'short' }); }
    function plusDagen(iso, n) { const d = new Date(iso + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); }
    function minuten(hhmm) { const [h, m] = hhmm.split(':').map(Number); return h * 60 + m; }
    function toast(tekst) {
        const t = document.getElementById('toast');
        t.textContent = tekst; t.hidden = false;
        clearTimeout(toast.timer); toast.timer = setTimeout(() => t.hidden = true, 4500);
    }
    function api(params) { return fetch('reservering_api.php?' + new URLSearchParams(params), { cache: 'no-store', headers: { 'ngrok-skip-browser-warning': 'true' } }).then(r => r.json()); }
    function apiPost(body) {
        return fetch('reservering_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true' }, body: JSON.stringify(body) }).then(r => r.json());
    }
    function mailMelding(d, basis) {
        if (!d.mail || d.mail.melding === '') return basis;
        return basis + (d.mail.gelukt ? ' De gast heeft een e-mail gekregen.' : ' Let op: de e-mail kon niet worden verstuurd – bel de gast even.');
    }

    // ---------- Dag laden ----------
    function kiesDatum(iso) {
        if (!iso) return;
        datum = iso;
        history.replaceState(null, '', '?datum=' + iso);
        laad();
    }
    function verschuifDag(n) { kiesDatum(plusDagen(datum, n)); }
    function kiesWeergave(w) {
        weergave = w;
        document.getElementById('w-lijst').classList.toggle('actief', w === 'lijst');
        document.getElementById('w-schema').classList.toggle('actief', w === 'schema');
        document.getElementById('lijst').hidden = w !== 'lijst';
        document.getElementById('schema').hidden = w !== 'schema';
    }

    function laad() {
        document.getElementById('datum').value = datum;
        laadKomend();
        return api({ actie: 'lijst', datum }).then(d => {
            if (!d.succes) { document.getElementById('lijst').innerHTML = `<div class="leeg">${esc(d.melding)}</div>`; return; }
            data = d;
            teken();
        }).catch(() => {});
    }

    // Komende dagen met reserveringen (na de dag die je bekijkt), zodat je die vanaf vandaag al ziet
    let komendeDagen = [];
    function laadKomend() {
        return api({ actie: 'aankomend', datum }).then(d => {
            if (!d.succes) return;
            komendeDagen = d.dagen;
            tekenKomend();
            if (!data.reserveringen.length) teken();   // melding "geen reserveringen" aanvullen
        }).catch(() => {});
    }
    function dagLabel(iso) {
        if (iso === VANDAAG) return 'Vandaag';
        if (iso === plusDagen(VANDAAG, 1)) return 'Morgen';
        return kortDatum(iso);
    }
    function tekenKomend() {
        const vak = document.getElementById('komend');
        vak.hidden = false;
        if (!komendeDagen.length) {
            vak.innerHTML = `<div class="komend-kop">📆 Komende reserveringen</div><div class="komend-leeg">Geen reserveringen in de 14 dagen hierna.</div>`;
            return;
        }
        vak.innerHTML = `<div class="komend-kop">📆 Komende reserveringen</div><div class="komend-dagen">`
            + komendeDagen.map(k => `<button type="button" class="komend-dag" onclick="kiesDatum('${k.datum}')">
                    <b>${esc(dagLabel(k.datum))}</b>
                    <span><span data-geen-vertaling>${k.aantal}</span> ${k.aantal === 1 ? 'reservering' : 'reserveringen'}</span> ·
                    <span><span data-geen-vertaling>${k.gasten}</span> gasten</span><br>
                    <span>vanaf <span data-geen-vertaling>${esc(k.eerste)}</span></span>
                </button>`).join('') + `</div>`;
    }

    function teken() {
        const r = data.reserveringen;
        const actief = r.filter(x => x.status !== 'geannuleerd');
        document.getElementById('dag-titel').textContent = datumTekst(datum) + (datum === VANDAAG ? ' (vandaag)' : '') + (data.tijden.length ? '' : ' · gesloten');
        document.getElementById('tellers').innerHTML =
            `<div class="teller"><b data-geen-vertaling>${actief.length}</b><span>reserveringen</span></div>`
            + `<div class="teller"><b data-geen-vertaling>${actief.reduce((s, x) => s + x.personen, 0)}</b><span>gasten verwacht</span></div>`
            + `<div class="teller"><b data-geen-vertaling>${r.filter(x => x.status === 'aangekomen').length}</b><span>aangekomen</span></div>`
            + `<div class="teller"><b data-geen-vertaling>${r.filter(x => x.status === 'niet_gekomen').length}</b><span>niet gekomen</span></div>`;
        document.getElementById('lijst').innerHTML = r.length
            ? r.map(kaartHtml).join('')
            : '<div class="leeg">Geen reserveringen op deze dag.' + (komendeDagen.length ? ' Hierboven zie je de eerstvolgende dagen met reserveringen.' : '') + '</div>';
        tekenSchema();
    }

    function kaartHtml(x) {
        const kanWijzigen = x.status !== 'geannuleerd';
        let knoppen = '';
        if (x.status === 'gepland') {
            knoppen += `<button class="b-groen" onclick="zetStatus(${x.id}, 'aangekomen')">✅ Aangekomen</button>`
                     + `<button class="b-grijs" onclick="zetStatus(${x.id}, 'niet_gekomen')">🚫 Niet gekomen</button>`;
        } else if (x.status !== 'geannuleerd') {
            knoppen += `<button class="b-grijs" onclick="zetStatus(${x.id}, 'gepland')">↩ Terug naar verwacht</button>`;
        }
        if (kanWijzigen) {
            knoppen += `<button class="b-blauw" onclick="openVerplaats(${x.id})">🔁 Verplaatsen</button>`
                     + `<button class="b-rand" onclick="openAnnuleer(${x.id})">✖ Annuleren</button>`;
        }
        const tafel = TAFELS.find(t => t.nr === x.tafel) || {};
        return `<div class="res-kaart ${x.status}">
            <div class="res-tijd"><span data-geen-vertaling>${esc(x.tijd)}</span><small>tot <span data-geen-vertaling>${esc(x.tot)}</span></small></div>
            <div>
                <p class="res-naam"><span data-geen-vertaling>${esc(x.naam)}</span> <span class="st st-${x.status}">${STATUS_NAMEN[x.status] || x.status}</span></p>
                <div class="res-info">
                    <span>🔖 <span data-geen-vertaling>${esc(x.nummer)}</span></span>
                    <span>👥 <span data-geen-vertaling>${x.personen}</span> <span>personen</span></span>
                    <span>🪑 ${x.tafels.length > 1 ? 'Tafels' : 'Tafel'} <b data-geen-vertaling>${esc(x.tafel_tekst)}</b> · <span>${ETAGE_NAMEN[x.etage]}</span> (<span>${x.tafels.length > 1 ? 'aangeschoven' : (PLEK_NAMEN[tafel.plek] || '')}</span>)</span>
                </div>
                <div class="res-info">
                    <a href="tel:${esc(x.telefoon)}" data-geen-vertaling>📞 ${esc(x.telefoon)}</a>
                    <a href="mailto:${esc(x.email)}" data-geen-vertaling>✉️ ${esc(x.email)}</a>
                </div>
                ${x.opmerking ? `<div class="res-opm">💬 <span data-geen-vertaling>${esc(x.opmerking)}</span></div>` : ''}
                ${x.aangekomen_op ? `<div class="res-meta">Binnengekomen om <span data-geen-vertaling>${esc(x.aangekomen_op)}</span></div>` : ''}
                ${x.gewijzigd_door ? `<div class="res-meta">Laatst aangepast door <span data-geen-vertaling>${esc(x.gewijzigd_door)}</span></div>` : ''}
            </div>
            <div class="res-acties">${knoppen}</div>
        </div>`;
    }

    // ---------- Bezettingsschema: tafels x tijden ----------
    function tekenSchema() {
        const tijden = data.tijden;
        if (!tijden.length) {
            document.getElementById('schema').innerHTML = '<div class="leeg">Op deze dag zijn we gesloten.</div>';
            return;
        }
        const stap = tijden.length > 1 ? minuten(tijden[1]) - minuten(tijden[0]) : 30;
        const nu = new Date();
        const nuMin = datum === VANDAAG ? nu.getHours() * 60 + nu.getMinutes() : -1;
        let html = `<div class="schema-scroll"><table class="schema"><thead><tr><th class="tafel-kol">Tafel</th>`
                 + tijden.map(t => `<th data-geen-vertaling>${t}</th>`).join('') + `</tr></thead><tbody>`;
        [1, 2].forEach(etage => {
            html += `<tr class="etage-rij"><td colspan="${tijden.length + 1}">🏢 ${ETAGE_NAMEN[etage]}</td></tr>`;
            TAFELS.filter(t => t.etage === etage).forEach(t => {
                html += `<tr><td class="tafel-kol"><span data-geen-vertaling>${t.nr}</span> <small style="color: var(--grijs); font-weight: normal;" data-geen-vertaling>👥 ${t.plaatsen}</small></td>`;
                let i = 0;
                while (i < tijden.length) {
                    const m = minuten(tijden[i]);
                    const res = data.reserveringen.find(x => x.tafels.includes(t.nr) && x.status !== 'geannuleerd'
                        && minuten(x.tijd) <= m && m < minuten(x.tijd) + data.duur);
                    if (res) {
                        // Aaneengesloten blok over alle tijdvakken van deze reservering
                        let span = 0;
                        while (i + span < tijden.length && minuten(tijden[i + span]) < minuten(res.tijd) + data.duur) span++;
                        html += `<td class="vol ${res.status}" colspan="${span}" title="${esc(res.naam)} · ${res.personen} pers. · ${esc(res.tijd)}–${esc(res.tot)}" data-geen-vertaling onclick="toonInLijst(${res.id})">`
                              + `<span class="s-naam" data-geen-vertaling>${esc(res.naam)} (${res.personen})</span></td>`;
                        i += span;
                    } else {
                        const isNu = nuMin >= m && nuMin < m + stap;
                        html += `<td class="${isNu ? 'nu' : ''}"></td>`;
                        i++;
                    }
                }
                html += `</tr>`;
            });
        });
        html += `</tbody></table></div>
            <div class="schema-legenda">
                <span><i style="background: #1f4e8c;"></i>Verwacht</span>
                <span><i style="background: #27ae60;"></i>Aangekomen</span>
                <span><i style="background: #c0392b;"></i>Niet gekomen</span>
                <span><i style="background: white; border: 1px solid #ccc;"></i>Vrij</span>
            </div>`;
        document.getElementById('schema').innerHTML = html;
    }
    function toonInLijst(id) {
        kiesWeergave('lijst');
        const i = data.reserveringen.findIndex(x => x.id === id);
        const kaart = document.querySelectorAll('#lijst .res-kaart')[i];
        if (kaart) { kaart.scrollIntoView({ behavior: 'smooth', block: 'center' }); kaart.style.outline = '3px solid #e9a23b'; setTimeout(() => kaart.style.outline = '', 2000); }
    }

    // ---------- Zoeken ----------
    function zoekVertraagd() {
        clearTimeout(zoekTimer);
        zoekTimer = setTimeout(zoek, 300);
    }
    function zoek() {
        const term = document.getElementById('zoek').value.trim();
        const vak = document.getElementById('zoek-resultaat');
        if (term.length < 2) { vak.hidden = true; document.getElementById('dag-weergave').hidden = false; return; }
        api({ actie: 'zoek', q: term }).then(d => {
            if (!d.succes) return;
            document.getElementById('dag-weergave').hidden = true;
            vak.hidden = false;
            zoekResultaten = d.reserveringen;
            vak.innerHTML = `<div class="dag-titel">Zoekresultaten (<span data-geen-vertaling>${d.reserveringen.length}</span>)</div>`
                + (d.reserveringen.length
                    ? d.reserveringen.map(x => `<div class="res-meta" style="font-weight: bold; margin: 10px 0 4px;">📅 ${esc(kortDatum(x.datum))}</div>` + kaartHtml(x)).join('')
                    : '<div class="leeg">Niets gevonden. Probeer een deel van de naam of het telefoonnummer.</div>');
        });
    }
    let zoekResultaten = [];
    function vindReservering(id) {
        return data.reserveringen.find(x => x.id === id) || zoekResultaten.find(x => x.id === id);
    }
    function verversAlles() {
        laad();
        if (!document.getElementById('zoek-resultaat').hidden) zoek();
    }

    // ---------- Status ----------
    function zetStatus(id, status) {
        apiPost({ actie: 'status', id, status }).then(d => {
            if (!d.succes) return toast(d.melding);
            toast(status === 'aangekomen' ? 'Gast staat op aangekomen.' : status === 'niet_gekomen' ? 'Gemarkeerd als niet gekomen.' : 'Status teruggezet.');
            verversAlles();
        });
    }

    // ---------- Verplaatsen ----------
    function openVerplaats(id) {
        bezig = vindReservering(id);
        if (!bezig) return;
        gekozenTafels = bezig.tafels.slice();
        document.getElementById('verplaats-uitleg').innerHTML =
            `<strong data-geen-vertaling>${esc(bezig.naam)}</strong> · <span data-geen-vertaling>${esc(bezig.nummer)}</span>`;
        document.getElementById('v-datum').value = bezig.datum;
        document.getElementById('v-personen').value = bezig.personen;
        document.getElementById('v-fout').textContent = '';
        document.getElementById('verplaats-venster').hidden = false;
        vulTijden(bezig.datum, bezig.tijd).then(laadVrijeTafels);
    }
    // Starttijden van de gekozen dag (volgens de openingstijden) in de keuzelijst zetten
    function vulTijden(dag, gewenst) {
        const kies = document.getElementById('v-tijd');
        return api({ actie: 'beschikbaar', datum: dag, personen: 1, negeer_id: bezig.id }).then(d => {
            const tijden = d.succes ? d.tijden.map(t => t.tijd) : [];
            if (!tijden.length) {
                kies.innerHTML = '<option value="">Gesloten</option>';
                return;
            }
            kies.innerHTML = tijden.map(t => `<option value="${t}" ${t === gewenst ? 'selected' : ''}>${t}</option>`).join('');
        }).catch(() => {});
    }
    function laadVrijeTafels() {
        const vak = document.getElementById('v-tafels');
        if (!document.getElementById('v-tijd').value) {
            vak.innerHTML = '<p class="fout-tekst">Op deze dag zijn we gesloten. Kies een andere datum.</p>';
            return;
        }
        const personen = Math.max(1, parseInt(document.getElementById('v-personen').value) || 1);
        api({ actie: 'beschikbaar', datum: document.getElementById('v-datum').value, personen,
              tijd: document.getElementById('v-tijd').value, negeer_id: bezig.id }).then(d => {
            if (!d.succes || !d.tafels) { vak.innerHTML = `<p class="fout-tekst">${esc(d.melding || 'Kies een geldige datum en tijd.')}</p>`; return; }
            gekozenTafels = gekozenTafels.filter(nr => d.tafels[nr] === 'vrij');
            const nodig = Math.ceil(personen / 4);
            // Tafels in dezelfde opstelling als in het restaurant, zodat je ziet welke naast elkaar staan
            vak.innerHTML = (nodig > 1 ? `<p class="uitleg" style="margin: 10px 0 0;">Groep van ${personen}: kies ${nodig} tafels die naast of achter elkaar staan.</p>` : '')
                + [1, 2].map(etage => `<div class="etage-label">🏢 ${ETAGE_NAMEN[etage]}</div><div class="kies-tafels raster-${etage}">`
                + TAFELS.filter(t => t.etage === etage).map(t => {
                    const st = d.tafels[t.nr];
                    const sub = st === 'bezet' ? 'bezet' : st === 'te_klein' ? 'te klein' : `👥 ${t.plaatsen}`;
                    return `<button type="button" style="grid-row: ${t.rij}; grid-column: ${t.etage === 1 ? t.kol + 1 : t.kol};" data-tafel="${t.nr}" class="${gekozenTafels.includes(t.nr) ? 'gekozen' : ''}" ${st !== 'vrij' ? 'disabled' : ''} onclick="kiesVTafel(${t.nr})">`
                         + `Tafel <span data-geen-vertaling>${t.nr}</span><small>${sub}</small></button>`;
                }).join('') + `</div>`).join('');
        });
    }
    function kiesVTafel(nr) {
        const personen = Math.max(1, parseInt(document.getElementById('v-personen').value) || 1);
        if (Math.ceil(personen / 4) <= 1) gekozenTafels = [nr];
        else gekozenTafels = gekozenTafels.includes(nr) ? gekozenTafels.filter(t => t !== nr) : [...gekozenTafels, nr];
        document.querySelectorAll('#v-tafels button[data-tafel]').forEach(b => b.classList.toggle('gekozen', gekozenTafels.includes(Number(b.dataset.tafel))));
        document.getElementById('v-fout').textContent = '';
    }
    function slaVerplaatsingOp() {
        const fout = document.getElementById('v-fout');
        if (!gekozenTafels.length) { fout.textContent = 'Kies een vrije tafel.'; return; }
        const knop = document.getElementById('v-opslaan');
        knop.disabled = true;
        apiPost({ actie: 'staf_wijzig', id: bezig.id, datum: document.getElementById('v-datum').value, tijd: document.getElementById('v-tijd').value,
                  personen: parseInt(document.getElementById('v-personen').value) || bezig.personen, tafels: gekozenTafels }).then(d => {
            knop.disabled = false;
            if (!d.succes) { fout.textContent = d.melding; return; }
            sluitVensters();
            toast(mailMelding(d, 'Reservering verplaatst.'));
            verversAlles();
        }).catch(() => { knop.disabled = false; fout.textContent = 'Kan de server niet bereiken.'; });
    }

    // ---------- Annuleren ----------
    function openAnnuleer(id) {
        bezig = vindReservering(id);
        if (!bezig) return;
        document.getElementById('annuleer-uitleg').innerHTML =
            esc(`Reservering van ${bezig.naam} op ${kortDatum(bezig.datum)} om ${bezig.tijd} annuleren?`);
        document.getElementById('a-fout').textContent = '';
        document.getElementById('annuleer-venster').hidden = false;
    }
    function bevestigAnnuleren() {
        const knop = document.getElementById('a-ok');
        knop.disabled = true;
        apiPost({ actie: 'staf_annuleer', id: bezig.id, mail: document.getElementById('a-mail').checked }).then(d => {
            knop.disabled = false;
            if (!d.succes) { document.getElementById('a-fout').textContent = d.melding; return; }
            sluitVensters();
            toast(mailMelding(d, 'Reservering geannuleerd.'));
            verversAlles();
        }).catch(() => { knop.disabled = false; });
    }
    function sluitVensters() {
        document.getElementById('verplaats-venster').hidden = true;
        document.getElementById('annuleer-venster').hidden = true;
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') sluitVensters(); });

    // Andere taal gekozen? Datums opnieuw tekenen
    new MutationObserver(() => { teken(); tekenKomend(); }).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

    laad();
    // Elke 30 seconden verversen (nieuwe online reserveringen), maar niet terwijl er een venster open is
    setInterval(() => { if (document.getElementById('verplaats-venster').hidden && document.getElementById('annuleer-venster').hidden) verversAlles(); }, 30000);
</script>
</body>
</html>
