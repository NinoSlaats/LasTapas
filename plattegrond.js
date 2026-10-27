/*
 * Las Tapas - plattegrond.js
 * Bovenaanzicht ("eagle eye view") van het restaurant, getekend als SVG.
 * Gebaseerd op de plattegrond van de product owners: begane grond (tafels 1-10) en eerste verdieping (tafels 11-26).
 *
 * Gebruik:
 *   el.innerHTML = Plattegrond.svg(1, { klasse: nr => 'vrij', label: nr => 'Tafel ' + nr });
 *   Plattegrond.koppel(el, nr => { ... });   // klik of Enter op een tafel
 *
 * Elke tafel is een <g class="pt-tafel ..." data-tafel="nr">. De pagina bepaalt met 'klasse' de kleur:
 *   'vrij', 'gekozen', 'bezet', 'te_klein', 'qr' of 'actief'.
 */
(function () {
    // Tafelposities (midden van de tafel) per verdieping, in de coördinaten van de tekening
    const TAFELS = {
        1: { 1: [331, 290], 2: [471, 290], 3: [598, 290],
             4: [331, 388], 5: [471, 388], 6: [598, 388],
             7: [331, 487], 8: [471, 487], 9: [598, 487],
             10: [93, 648] },
        2: { 11: [125, 268], 12: [255, 268], 13: [392, 268], 14: [530, 268],
             15: [125, 380], 16: [255, 380], 17: [392, 380], 18: [530, 380],
             19: [125, 488], 20: [255, 488], 21: [392, 488], 22: [530, 488],
             23: [125, 600], 24: [255, 600], 25: [392, 600], 26: [530, 600] },
    };
    const MATEN = { 1: [730, 723], 2: [705, 715] };
    const NAMEN = { 1: 'Begane grond', 2: 'Eerste verdieping' };

    function etageVan(nr) { return nr <= 10 ? 1 : 2; }

    // ---------- Stijl (één keer in de pagina zetten) ----------
    const STIJL = `
        .pt-svg { width: 100%; height: auto; display: block; font-family: 'Segoe UI', Arial, sans-serif; user-select: none; -webkit-user-select: none; }
        .pt-svg .muur { fill: none; stroke: #2b2523; stroke-width: 12; }
        .pt-svg .binnenmuur { stroke: #2b2523; stroke-width: 6; }
        .pt-svg .vloer { fill: #b97a45; }
        .pt-svg .ruimte-label { font-size: 13px; font-weight: 700; fill: #2b2523; text-anchor: middle; letter-spacing: 0.5px; text-transform: uppercase; }
        .pt-svg .klein-label { font-size: 9px; font-weight: 700; fill: #4a4340; text-anchor: middle; text-transform: uppercase; }
        .pt-svg .licht-label { fill: #fdf6ea; }
        .pt-svg .staal { fill: #c9cdd1; stroke: #8d949a; stroke-width: 1.5; }
        .pt-svg .kast { fill: #a0723f; stroke: #6f4b25; stroke-width: 1.5; }
        .pt-svg .raam { stroke: #dbe9f5; stroke-width: 7; }
        .pt-svg .plant { fill: #3f7a35; }
        .pt-svg .plant2 { fill: #5e9b44; }
        .pt-svg .pot { fill: #6b4a2f; }
        .pt-svg .stoel { fill: #6d4524; stroke: #3d2511; stroke-width: 1.2; }
        .pt-svg .blad { fill: #8f5a2c; stroke: #4a2c12; stroke-width: 2; }
        .pt-svg .nummer-bol { fill: #fffaf2; stroke: #2b2523; stroke-width: 1.5; }
        .pt-svg .nummer { font-size: 17px; font-weight: 800; fill: #2b2523; text-anchor: middle; dominant-baseline: central; }
        .pt-svg .op-tafel { font-size: 15px; text-anchor: middle; dominant-baseline: central; pointer-events: none; }
        .pt-svg .pt-tafel { cursor: pointer; outline: none; }
        .pt-svg .pt-tafel .raak { fill: transparent; }
        .pt-svg .pt-tafel:hover .blad, .pt-svg .pt-tafel:focus-visible .blad { stroke: #e9a23b; stroke-width: 4; }
        .pt-svg .pt-tafel:focus-visible .nummer-bol { stroke: #e9a23b; stroke-width: 3; }
        /* Statussen */
        .pt-svg .pt-tafel.vrij .blad { fill: #7fae4f; stroke: #3f5f1f; }
        .pt-svg .pt-tafel.vrij .nummer-bol { fill: #eef6e4; stroke: #3f5f1f; }
        .pt-svg .pt-tafel.gekozen .blad { fill: #a8232b; stroke: #5e0f14; stroke-width: 3; }
        .pt-svg .pt-tafel.gekozen .nummer-bol { fill: #a8232b; stroke: #5e0f14; }
        .pt-svg .pt-tafel.gekozen .nummer { fill: white; }
        .pt-svg .pt-tafel.bezet, .pt-svg .pt-tafel.te_klein { cursor: not-allowed; }
        .pt-svg .pt-tafel.uit { cursor: default; opacity: 0.6; }
        .pt-svg .pt-tafel.uit:hover .blad { stroke: #4a2c12; stroke-width: 2; }
        .pt-svg .pt-tafel.bezet .blad { fill: url(#pt-arcering); stroke: #777; }
        .pt-svg .pt-tafel.bezet .stoel, .pt-svg .pt-tafel.te_klein .stoel { fill: #9a8f87; stroke: #6f6660; }
        .pt-svg .pt-tafel.bezet .nummer-bol { fill: #e4e1de; stroke: #8a8582; }
        .pt-svg .pt-tafel.bezet .nummer, .pt-svg .pt-tafel.te_klein .nummer { fill: #7a7370; }
        .pt-svg .pt-tafel.te_klein .blad { fill: #d9cbbb; stroke: #a99a8a; stroke-dasharray: 4 3; }
        .pt-svg .pt-tafel.actief .blad { fill: #c0562f; stroke: #6d2a12; }
        .pt-svg .pt-tafel.actief .nummer-bol { fill: #c0562f; stroke: #6d2a12; }
        .pt-svg .pt-tafel.actief .nummer { fill: white; }
        .pt-zoom { position: relative; }
        .pt-zoom-knop { display: none; position: absolute; right: 8px; bottom: 8px; z-index: 2; margin: 0; border: none; border-radius: 18px;
                        background: rgba(43,37,35,0.85); color: #fff; font: bold 0.8rem 'Segoe UI', Arial, sans-serif; padding: 7px 12px; cursor: pointer; }
        .pt-zoom.groot .pt-schuif { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .pt-zoom.groot .pt-svg { width: 600px; max-width: none; }
        @media (max-width: 700px) { .pt-zoom-knop { display: block; } }
        .pt-kop { display: flex; justify-content: space-between; align-items: center; background: #2b2523; color: #fdf6ea;
                  padding: 7px 12px; border-radius: 10px 10px 0 0; font-weight: 700; font-size: 0.95rem; }
        .pt-kop small { font-weight: normal; opacity: 0.8; }
        .pt-verdieping { border: 2px solid #2b2523; border-radius: 12px; overflow: hidden; background: #f4efe7; }
    `;
    function zetStijl() {
        if (document.getElementById('pt-stijl')) return;
        const s = document.createElement('style');
        s.id = 'pt-stijl';
        s.textContent = STIJL;
        document.head.appendChild(s);
    }

    // ---------- Kleine bouwstenen ----------
    const r = (x, y, b, h, k, extra = '') => `<rect x="${x}" y="${y}" width="${b}" height="${h}" class="${k}" ${extra}/>`;
    const lijn = (x1, y1, x2, y2, k) => `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" class="${k}"/>`;
    const tekst = (x, y, t, k = 'ruimte-label') => `<text x="${x}" y="${y}" class="${k}">${t}</text>`;
    function plant(x, y, s = 1) {
        return `<g transform="translate(${x} ${y}) scale(${s})"><circle r="11" class="pot"/>`
            + [0, 60, 120, 180, 240, 300].map(h => `<ellipse rx="5" ry="12" transform="rotate(${h}) translate(0 -9)" class="plant"/>`).join('')
            + `<circle r="5" class="plant2"/></g>`;
    }
    function sneeuw(x, y) {
        return `<g transform="translate(${x} ${y})" stroke="#4f8fd0" stroke-width="2.4" stroke-linecap="round">`
            + [0, 60, 120].map(h => `<line x1="0" y1="-12" x2="0" y2="12" transform="rotate(${h})"/>`).join('') + `</g>`;
    }
    function trap(x, y, b, h, omhoog) {
        let s = r(x, y, b, h, '', 'fill="#c79a66" stroke="#6f4b25" stroke-width="1.5"');
        for (let i = 1; i < 10; i++) s += `<line x1="${x}" y1="${y + i * h / 10}" x2="${x + b}" y2="${y + i * h / 10}" stroke="#6f4b25" stroke-width="1.2"/>`;
        const mx = x + b / 2, y1 = omhoog ? y + h - 14 : y + 14, y2 = omhoog ? y + 18 : y + h - 18;
        s += `<line x1="${mx}" y1="${y1}" x2="${mx}" y2="${y2}" stroke="#2b2523" stroke-width="3"/>`
           + `<path d="M${mx - 7} ${y2 + (omhoog ? 8 : -8)} L${mx} ${y2} L${mx + 7} ${y2 + (omhoog ? 8 : -8)}" fill="none" stroke="#2b2523" stroke-width="3"/>`;
        return s;
    }
    function planken(x, y, b, h, rijen) {
        let s = r(x, y, b, h, 'kast');
        for (let i = 0; i < rijen; i++) {
            const yy = y + 4 + i * (h - 8) / rijen;
            for (let xx = x + 4; xx < x + b - 12; xx += 14) s += `<rect x="${xx}" y="${yy}" width="10" height="${(h - 8) / rijen - 3}" fill="#d7b07a" stroke="#8a6234" stroke-width="0.8"/>`;
        }
        return s;
    }
    function deur(x, y, straal, hoek) {
        // kwart cirkel (deur die openzwaait)
        const rad = a => a * Math.PI / 180;
        const x2 = x + straal * Math.cos(rad(hoek)), y2 = y + straal * Math.sin(rad(hoek));
        const x3 = x + straal * Math.cos(rad(hoek + 90)), y3 = y + straal * Math.sin(rad(hoek + 90));
        return `<path d="M${x} ${y} L${x2} ${y2} A${straal} ${straal} 0 0 1 ${x3} ${y3}" fill="none" stroke="#2b2523" stroke-width="1.5"/>`;
    }

    // Eén tafel met 4 stoelen en het nummer eronder
    function tafel(nr, cx, cy, opties) {
        const k = opties.klasse ? opties.klasse(nr) : '';
        const label = opties.label ? opties.label(nr) : 'Tafel ' + nr;
        const icoon = opties.icoon ? opties.icoon(nr) : '';
        const b = 46;
        const stoel = (x, y) => `<rect x="${x}" y="${y}" width="16" height="11" rx="3" class="stoel"/>`;
        return `<g class="pt-tafel ${k}" data-tafel="${nr}" tabindex="0" role="button" aria-label="${label}">`
            + `<rect class="raak" x="${cx - 34}" y="${cy - 40}" width="68" height="92"/>`
            + stoel(cx - 19, cy - b / 2 - 12) + stoel(cx + 3, cy - b / 2 - 12)
            + stoel(cx - 19, cy + b / 2 + 1) + stoel(cx + 3, cy + b / 2 + 1)
            + `<rect x="${cx - b / 2}" y="${cy - b / 2}" width="${b}" height="${b}" rx="5" class="blad"/>`
            + (icoon ? `<text x="${cx}" y="${cy}" class="op-tafel">${icoon}</text>` : '')
            + `<rect x="${cx - 18}" y="${cy + b / 2 + 13}" width="36" height="23" rx="11.5" class="nummer-bol"/>`
            + `<text x="${cx}" y="${cy + b / 2 + 25}" class="nummer" data-geen-vertaling>${nr}</text>`
            + `</g>`;
    }

    // ---------- Begane grond ----------
    function beganeGrond() {
        let s = '';
        s += r(0, 0, 730, 723, '', 'fill="#efeae2"');
        // Restaurantvloer
        s += r(10, 228, 710, 485, 'vloer', 'fill="url(#pt-vloer)"');
        // Keuken
        s += r(10, 10, 430, 218, '', 'fill="#e6e9ec"');
        s += r(14, 14, 44, 182, 'staal');                         // afwas / koeling
        s += lijn(14, 100, 58, 100, 'binnenmuur');
        s += r(100, 14, 336, 44, 'staal');                        // kooklijn
        [130, 175, 220, 265, 310, 355, 400].forEach(x => s += `<circle cx="${x}" cy="36" r="8" fill="#7f868c"/>`);
        s += r(118, 132, 254, 36, 'staal');                       // werkeiland
        s += r(115, 204, 257, 20, 'kast');                        // uitgifte
        s += tekst(252, 108, 'Keuken');
        s += tekst(36, 60, 'Afwas', 'klein-label') + tekst(36, 150, 'Koeling', 'klein-label');
        s += tekst(195, 78, 'Kooklijn', 'klein-label') + tekst(330, 78, 'Oven / grill', 'klein-label');
        s += tekst(160, 196, 'Voorbereiding', 'klein-label') + tekst(320, 196, 'Uitgifte', 'klein-label');
        // Voorraad, vriezer, koelcel
        s += r(444, 10, 158, 218, '', 'fill="#eadcc4"');
        s += planken(452, 18, 140, 28, 1) + planken(452, 120, 40, 90, 3);
        s += tekst(530, 92, 'Voorraad');
        s += plant(560, 190, 1.2);
        s += r(606, 10, 114, 112, '', 'fill="#d5e3f1"') + tekst(663, 50, 'Vriezer') + sneeuw(663, 80);
        s += r(606, 126, 114, 102, '', 'fill="#d5e3f1"') + tekst(663, 160, 'Koelcel') + sneeuw(663, 192);
        // Binnenmuren keuken/voorraad
        s += lijn(10, 228, 228, 228, 'binnenmuur') + lijn(276, 228, 720, 228, 'binnenmuur');
        s += lijn(441, 10, 441, 228, 'binnenmuur') + lijn(603, 10, 603, 228, 'binnenmuur') + lijn(603, 124, 720, 124, 'binnenmuur');
        s += deur(392, 228, 36, 270) + deur(560, 228, 30, 270);
        // Bar
        s += r(14, 236, 168, 48, '', 'fill="#3a2a22"');
        const flescolor = ['#7a1d1d', '#d9a441', '#2e6b3a', '#c9c9c9', '#6b3fa0', '#b5651d'];
        for (let i = 0; i < 20; i++) s += `<rect x="${20 + i * 8}" y="${242 + (i % 2) * 22}" width="5" height="16" rx="2" fill="${flescolor[i % 6]}"/>`;
        s += r(40, 300, 110, 36, '', 'fill="#1f1a18"');           // spoelbak/koelkast
        s += r(186, 236, 24, 168, 'kast') + r(74, 380, 136, 24, 'kast');
        [262, 292, 322, 352].forEach(y => s += `<circle cx="226" cy="${y}" r="9" class="stoel"/>`);
        [92, 122, 152, 182].forEach(x => s += `<circle cx="${x}" cy="420" r="9" class="stoel"/>`);
        s += tekst(110, 368, 'Bar', 'ruimte-label licht-label');
        s += plant(40, 400, 1.4);
        // Trap + kast + kassa
        s += r(10, 432, 70, 2, '', 'fill="#2b2523"');
        s += trap(16, 440, 56, 178, true);
        s += r(72, 490, 46, 92, 'kast') + tekst(95, 540, 'Kast', 'klein-label licht-label');
        s += tekst(44, 632, 'Trap', 'klein-label licht-label');
        s += r(145, 568, 100, 46, 'kast') + `<rect x="170" y="576" width="34" height="22" rx="2" fill="#2b2523"/>`;
        s += tekst(195, 556, 'Kassa / host', 'klein-label licht-label');
        s += plant(225, 591, 0.9);
        // Toiletten
        s += r(412, 566, 308, 147, '', 'fill="#d3d8ea"');
        s += lijn(412, 566, 412, 713, 'binnenmuur') + lijn(412, 566, 720, 566, 'binnenmuur') + lijn(557, 566, 557, 713, 'binnenmuur');
        [598, 640, 682].forEach(y => { s += `<ellipse cx="475" cy="${y}" rx="9" ry="12" fill="#fff" stroke="#888"/>` + `<ellipse cx="520" cy="${y}" rx="9" ry="12" fill="#fff" stroke="#888"/>`; s += `<ellipse cx="690" cy="${y}" rx="9" ry="12" fill="#fff" stroke="#888"/>`; });
        [430, 430].forEach(() => {});
        s += `<rect x="418" y="590" width="16" height="70" rx="3" fill="#f5f5f5" stroke="#999"/>`;
        s += tekst(484, 712 - 10, 'Toilet dames', 'klein-label') + tekst(640, 712 - 10, 'Toilet heren', 'klein-label');
        s += deur(440, 566, 26, 90) + deur(590, 566, 26, 90);
        // Entree
        s += tekst(309, 626, 'Entree', 'ruimte-label licht-label');
        s += `<path d="M309 712 L309 676 M300 686 L309 676 L318 686" stroke="#2b2523" stroke-width="3" fill="none"/>`;
        s += plant(225, 680, 1.2) + plant(390, 680, 1.2) + plant(693, 470, 1.3) + plant(693, 260, 0.9);
        // Planten/schermen langs de rechtermuur
        s += r(700, 240, 12, 300, '', 'fill="#4e7d3a"');
        // Ramen + buitenmuur
        s += lijn(170, 0, 250, 0, 'raam') + lijn(510, 0, 590, 0, 'raam') + lijn(730, 260, 730, 400, 'raam')
           + lijn(60, 723, 140, 723, 'raam') + lijn(440, 723, 520, 723, 'raam') + lijn(620, 723, 700, 723, 'raam');
        s += `<rect x="0" y="0" width="730" height="723" class="muur"/>`;
        s += `<line x1="262" y1="723" x2="356" y2="723" stroke="#efeae2" stroke-width="14"/>`;       // deuropening entree
        s += deur(262, 723, 46, 270) + `<path d="M356 723 L356 677 A46 46 0 0 0 310 723" fill="none" stroke="#2b2523" stroke-width="1.5"/>`;
        return s;
    }

    // ---------- Eerste verdieping ----------
    function eersteVerdieping() {
        let s = '';
        s += r(0, 0, 705, 715, '', 'fill="#efeae2"');
        s += r(10, 198, 685, 507, 'vloer', 'fill="url(#pt-vloer)"');
        // Trap
        s += trap(14, 14, 92, 180, false);
        s += tekst(60, 100, '', 'klein-label');
        // Kantoor
        s += r(112, 10, 212, 188, '', 'fill="#eadcc4"');
        s += r(196, 46, 72, 44, 'kast') + `<rect x="214" y="52" width="34" height="18" rx="2" fill="#2b2523"/>` + `<circle cx="232" cy="104" r="11" fill="#2b2523"/>`;
        s += r(118, 18, 18, 110, 'kast') + r(300, 40, 18, 140, 'kast');
        s += tekst(220, 150, 'Kantoor') + plant(296, 32, 0.9);
        // Droge opslag
        s += r(330, 10, 216, 188, '', 'fill="#eadcc4"');
        s += planken(342, 18, 190, 28, 1) + planken(342, 110, 54, 70, 3) + planken(470, 140, 64, 40, 1);
        s += tekst(438, 90, 'Droge opslag');
        // Koelcel
        s += r(552, 10, 143, 188, '', 'fill="#d5e3f1"') + tekst(623, 90, 'Koelcel') + sneeuw(623, 124);
        // Binnenmuren + deuren
        s += lijn(10, 198, 695, 198, 'binnenmuur') + lijn(110, 10, 110, 198, 'binnenmuur') + lijn(327, 10, 327, 198, 'binnenmuur') + lijn(549, 10, 549, 198, 'binnenmuur');
        s += `<line x1="16" y1="198" x2="104" y2="198" stroke="#b97a45" stroke-width="7"/>`;           // trap is open naar de zaal
        s += deur(150, 198, 34, 270) + deur(410, 198, 34, 270) + deur(580, 198, 30, 270);
        // Lounge
        s += `<path d="M640 470 h46 v185 h-46 v-22 h24 v-141 h-24 z" fill="#5a3a24" stroke="#2f1d10" stroke-width="2"/>`;
        s += r(600, 520, 34, 70, 'kast');
        s += tekst(650, 452, 'Lounge', 'klein-label licht-label');
        // Planten
        [282, 338, 450, 505, 590, 650].forEach(y => s += plant(32, y, 1.05));
        s += plant(672, 400, 1.1) + plant(672, 680, 1.1) + plant(300, 30, 0);
        s += r(683, 230, 8, 70, '', 'fill="#4e7d3a"');
        // Ramen + buitenmuur
        s += lijn(0, 250, 0, 370, 'raam') + lijn(705, 230, 705, 330, 'raam')
           + lijn(50, 715, 130, 715, 'raam') + lijn(220, 715, 300, 715, 'raam') + lijn(400, 715, 480, 715, 'raam') + lijn(570, 715, 650, 715, 'raam')
           + lijn(170, 0, 250, 0, 'raam') + lijn(380, 0, 460, 0, 'raam');
        s += `<rect x="0" y="0" width="705" height="715" class="muur"/>`;
        return s;
    }

    const DEFS = `<defs>
        <pattern id="pt-vloer" width="40" height="40" patternUnits="userSpaceOnUse">
            <rect width="40" height="40" fill="#b97a45"/>
            <path d="M0 0 H40 M0 20 H40 M20 0 V20 M0 20 V40 M40 20 V40" stroke="#a86a38" stroke-width="1"/>
        </pattern>
        <pattern id="pt-arcering" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <rect width="8" height="8" fill="#d6d2ce"/><rect width="4" height="8" fill="#bdb8b3"/>
        </pattern>
    </defs>`;

    function svg(verdieping, opties = {}) {
        zetStijl();
        const [b, h] = MATEN[verdieping];
        const tekening = verdieping === 1 ? beganeGrond() : eersteVerdieping();
        const tafels = Object.entries(TAFELS[verdieping]).map(([nr, [x, y]]) => tafel(Number(nr), x, y, opties)).join('');
 return `<div class="pt-zoom"><button type="button" class="pt-zoom-knop" onclick="Plattegrond.zoom(this)">🔍 Vergroten</button><div class="pt-schuif">`
            + `<svg class="pt-svg" viewBox="-6 -6 ${b + 12} ${h + 12}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="${NAMEN[verdieping]}">`
            + DEFS + tekening + tafels + `</svg></div></div>`;
    }

    // Klik/Enter op een tafel doorgeven aan de pagina
    function koppel(el, onKlik) {
        if (el.__ptGekoppeld) { el.__ptKlik = onKlik; return; }
        el.__ptGekoppeld = true;
        el.__ptKlik = onKlik;
        const zoek = e => e.target.closest && e.target.closest('.pt-tafel');
        el.addEventListener('click', e => { const g = zoek(e); if (g) el.__ptKlik(Number(g.dataset.tafel), g, e); });
        el.addEventListener('keydown', e => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const g = zoek(e);
            if (g) { e.preventDefault(); el.__ptKlik(Number(g.dataset.tafel), g, e); }
        });
    }

    // Op de telefoon: verdieping groter tonen (dan schuif je hem opzij)
    function zoom(knop) {
        const vak = knop.closest('.pt-zoom');
        const groot = vak.classList.toggle('groot');
        knop.textContent = groot ? '↩ Kleiner' : '🔍 Vergroten';
        if (groot) {
            // Meteen naar het midden van de tafels schuiven
            const schuif = vak.querySelector('.pt-schuif');
            const links = schuif.getBoundingClientRect().left;
            const r = [...vak.querySelectorAll('.pt-tafel')].map(g => g.getBoundingClientRect());
            const midden = (Math.min(...r.map(x => x.left)) + Math.max(...r.map(x => x.right))) / 2 - links;
            schuif.scrollLeft = midden - schuif.clientWidth / 2;
        }
    }

    window.Plattegrond = { svg, koppel, zoom, etageVan, NAMEN, TAFELS, zetStijl };
})();
