<?php
/*
 * Las Tapas - mijn_reservering.php
 * De gast opent deze pagina via de link in de bevestigingsmail (mijn_reservering.php?code=...).
 * Hier ziet de gast de reservering en kan hij/zij verplaatsen of annuleren. De code zelf is het geheim.
 */
require __DIR__ . '/gedeeld.php';
$code = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_GET['code'] ?? '')));
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn reservering - Las Tapas</title>
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
        .teller span { min-width: 52px; text-align: center; font-weight: bold; font-size: 1.15rem; }
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
            .zone { gap: 14px; }
        }
    
        main { max-width: 560px; }
        .status { display: inline-block; padding: 5px 14px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; margin-bottom: 10px; }
        .st-gepland { background: #eafaf1; color: #1e8449; }
        .st-aangekomen { background: #eef4fb; color: var(--azul); }
        .st-geannuleerd, .st-niet_gekomen { background: #fdecea; color: #922b21; }
        .knoppen { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .knoppen a, .knoppen button { flex: 1 1 180px; text-align: center; text-decoration: none; border: none; padding: 13px; border-radius: 10px; font-weight: bold; font-size: 1rem; cursor: pointer; font-family: inherit; }
        .knop-blauw { background: var(--azul); color: white; }
        .knop-rand { background: white; color: var(--rood); border: 2px solid var(--rood) !important; }
        .knop-rood { background: var(--rood); color: white; }
        .let-op { background: #fff8e7; border-left: 4px solid var(--geel); padding: 10px 12px; border-radius: 8px; font-size: 0.9rem; margin-top: 14px; }
        .bevestig { margin-top: 16px; border: 2px solid var(--rood); border-radius: 12px; padding: 14px; background: #fff6f6; }
        .bevestig p { margin: 0 0 6px; font-weight: bold; }
    </style>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<body>
<header>
    <span id="taal-plek"></span>
    <h1>🍷 Las Tapas</h1>
    <p>Mijn reservering</p>
</header>
<div class="tegels"></div>
<main>
    <div class="kaart" id="laden"><p class="uitleg">Reservering wordt geladen…</p></div>

    <div class="kaart verborgen" id="niet-gevonden">
        <h2>🔍 Niet gevonden</h2>
        <p class="uitleg" id="niet-gevonden-tekst">Deze reservering kunnen we niet vinden. Controleer de link uit je e-mail.</p>
        <a class="link-knop" href="reserveren.php">📅 Nieuwe reservering maken</a>
    </div>

    <div class="kaart verborgen" id="gegevens">
        <span class="status" id="status"></span>
        <h2>📅 Jouw reservering</h2>
        <dl class="samenvatting" id="samenvatting"></dl>
        <div id="acties"></div>
        <div class="bevestig verborgen" id="annuleer-vraag">
            <p>Weet je zeker dat je wilt annuleren?</p>
            <p class="uitleg" style="font-weight: normal;">Je tafel komt dan vrij voor andere gasten. Dit kun je niet ongedaan maken.</p>
            <div class="knoppen">
                <button class="knop-rood" id="ja-annuleren" onclick="annuleer()">Ja, annuleren</button>
                <button class="knop-rand" onclick="toonAnnuleerVraag(false)">Nee, terug</button>
            </div>
        </div>
        <p class="fout" id="fout"></p>
        <div id="melding"></div>
    </div>
    <p class="uitleg" style="text-align: center;">Vragen of iets bijzonders? Bel ons gerust, we helpen je graag.</p>
</main>
<footer><a href="privacy.php">🔒 Privacyverklaring</a></footer>

<script>
    const CODE = <?php echo json_encode($code); ?>;
    const PLEK_NAMEN = { raam: 'aan het raam', midden: 'midden in de zaal', bar: 'bij de bar', entree: 'bij de entree', lounge: 'bij de lounge' };
    const ETAGE_NAMEN = { 1: 'Begane grond', 2: 'Eerste verdieping' };
    const STATUS_NAMEN = { gepland: 'Bevestigd', aangekomen: 'Je bent binnen – eet smakelijk!', niet_gekomen: 'Niet gekomen', geannuleerd: 'Geannuleerd' };
    let res = null;

    function esc(t) { return String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function datumTekst(iso) {
        const d = new Date(iso + 'T12:00:00');
        const tl = { nl: 'nl-NL', en: 'en-GB', es: 'es-ES' }[document.documentElement.lang] || 'nl-NL';
        return d.toLocaleDateString(tl, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }
    function headers() { return { 'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true' }; }

    function laad() {
        if (!CODE) return nietGevonden();
        fetch('reservering_api.php?actie=mijn&code=' + encodeURIComponent(CODE), { headers: headers(), cache: 'no-store' })
            .then(r => r.json())
            .then(d => { if (!d.succes) return nietGevonden(d.melding); res = d.reservering; teken(); })
            .catch(() => nietGevonden('Kan de server niet bereiken. Probeer het opnieuw.'));
    }
    function nietGevonden(melding) {
        document.getElementById('laden').classList.add('verborgen');
        document.getElementById('niet-gevonden').classList.remove('verborgen');
        if (melding) document.getElementById('niet-gevonden-tekst').textContent = melding;
    }

    function teken() {
        document.getElementById('laden').classList.add('verborgen');
        document.getElementById('gegevens').classList.remove('verborgen');
        const st = document.getElementById('status');
        st.className = 'status st-' + res.status;
        st.textContent = STATUS_NAMEN[res.status] || res.status;
        document.getElementById('samenvatting').innerHTML =
            `<dt>Reserveringsnummer</dt><dd data-geen-vertaling>${esc(res.nummer)}</dd>`
            + `<dt>Naam</dt><dd data-geen-vertaling>${esc(res.naam)}</dd>`
            + `<dt>Datum</dt><dd>${esc(datumTekst(res.datum))}</dd>`
            + `<dt>Tijd</dt><dd data-geen-vertaling>${res.tijd} – ${res.tot}</dd>`
            + `<dt>Personen</dt><dd data-geen-vertaling>${res.personen}</dd>`
            + `<dt>Tafel</dt><dd><span data-geen-vertaling>${esc(res.tafel_tekst)}</span> · <span>${ETAGE_NAMEN[res.etage]}</span> · <span>${res.tafels.length > 1 ? 'aangeschoven' : esc(PLEK_NAMEN[res.plek] || '')}</span></dd>`
            + (res.opmerking ? `<dt>Opmerking</dt><dd data-geen-vertaling>${esc(res.opmerking)}</dd>` : '');

        const acties = document.getElementById('acties');
        if (res.status === 'gepland' && !res.voorbij) {
            acties.innerHTML = `<div class="knoppen">
                    <a class="knop-blauw" href="reserveren.php?wijzig=${encodeURIComponent(CODE)}">🔁 Verplaatsen</a>
                    <button class="knop-rand" onclick="toonAnnuleerVraag(true)">✖ Annuleren</button>
                </div>
                <p class="let-op">Laat bij binnenkomst je reserveringsnummer of naam zien, dan brengen we je meteen naar je tafel.</p>`;
        } else if (res.status === 'geannuleerd') {
            acties.innerHTML = `<p class="uitleg">Deze reservering is geannuleerd.</p><a class="link-knop" href="reserveren.php">📅 Nieuwe reservering maken</a>`;
        } else {
            acties.innerHTML = `<p class="uitleg">Deze reservering kan niet meer online worden aangepast.</p><a class="link-knop" href="reserveren.php">📅 Nieuwe reservering maken</a>`;
        }
    }

    function toonAnnuleerVraag(aan) {
        document.getElementById('annuleer-vraag').classList.toggle('verborgen', !aan);
        document.getElementById('fout').textContent = '';
    }

    function annuleer() {
        const knop = document.getElementById('ja-annuleren');
        knop.disabled = true;
        fetch('reservering_api.php', { method: 'POST', headers: headers(), body: JSON.stringify({ actie: 'annuleer', code: CODE }) })
            .then(r => r.json())
            .then(d => {
                knop.disabled = false;
                if (!d.succes) { document.getElementById('fout').textContent = d.melding; return; }
                res.status = 'geannuleerd';
                toonAnnuleerVraag(false);
                teken();
                document.getElementById('melding').innerHTML = d.mail && d.mail.gelukt
                    ? '<p class="mail-ok">📧 Je reservering is geannuleerd. Je krijgt hiervan een bevestiging per e-mail.</p>'
                    : '<p class="mail-ok">✅ Je reservering is geannuleerd.</p>';
            })
            .catch(() => { knop.disabled = false; document.getElementById('fout').textContent = 'Kan de server niet bereiken. Probeer het opnieuw.'; });
    }

    new MutationObserver(() => { if (res) teken(); }).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
    laad();
</script>
</body>
</html>
