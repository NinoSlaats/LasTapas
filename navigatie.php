<?php
/*
 * Las Tapas - navigatie.php
 * Navigatie voor alle medewerkerspagina's. Iedereen ziet op elke pagina dezelfde keuzes, passend bij de eigen rol.
 *  - Medewerkers: losse knoppen voor hun paar pagina's.
 *  - Baas: rechtsboven de pagina's van het onderdeel waar je bent (Werkplek of Beheer) als losse knoppen,
 *    plus een uitklapmenu om naar het andere onderdeel te gaan (en om uit te loggen).
 * De pagina waar je bent, staat altijd dikgedrukt en gemarkeerd.
 *
 * Gebruik:  in de kop:      require_once __DIR__ . '/navigatie.php'; toonNavigatie();
 *           onder de kop:   toonSectieTabs();   (alleen zichtbaar voor de baas)
 */

// Welke pagina's mag welke rol openen? (moet overeenkomen met de controles bovenaan elke pagina)
function navigatieItems($rol) {
    $werkplek = [
        'chef.php'          => ['🍳', 'Keuken',        ['baas', 'chef']],
        'bar.php'           => ['🍷', 'Bar',           ['baas', 'bar']],
        'serveerster.php'   => ['🏃', 'Bediening',     ['baas', 'serveerster']],
        'voorraad.php'      => ['📦', 'Voorraad',      ['baas', 'chef', 'bar']],
        'reserveringen.php' => ['📅', 'Reserveringen', ['baas', 'serveerster', 'chef', 'bar']],
    ];
    $beheer = [
        'baas_paneel.php'    => ['👥', 'Medewerkers',            ['baas']],
        'menu_beheer.php'    => ['🍽️', 'Menu & prijzen',         ['baas']],
        'korting_beheer.php' => ['🎁', 'Korting & cadeaubonnen', ['baas']],
        'statistieken.php'   => ['📊', 'Statistieken',           ['baas']],
        'qr_overzicht.php'   => ['🔳', 'QR-codes',               ['baas']],
    ];
    $filter = fn($lijst) => array_filter($lijst, fn($item) => in_array($rol, $item[2], true));
    return array_filter(['Werkplek' => $filter($werkplek), 'Beheer' => $filter($beheer)]);
}

// $zonderMenu = true: alleen de paginaknoppen (de beheerpagina's zetten het uitklapmenu apart rechtsboven)
function toonNavigatie($zonderMenu = false) {
    $rol = $_SESSION['rol'] ?? '';
    $hier = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $groepen = navigatieItems($rol);

    if ($rol === 'baas') {
        // Baas: de pagina's van het huidige onderdeel als losse knoppen, plus een uitklapmenu om van onderdeel te wisselen
        $onderdelen = ['Werkplek' => ['🛎️', 'chef.php'], 'Beheer' => ['⚙️', 'baas_paneel.php']];
        $huidig = navigatieOnderdeel() ?: 'Werkplek';
        $items = $groepen[$huidig] ?? [];
        ?>
        <nav class="nav-knoppen" aria-label="<?php echo $huidig; ?>">
            <?php foreach ($items as $bestand => [$icoon, $label]): ?>
                <?php if ($bestand === $hier): ?>
                    <span class="nav-knop-los hier" aria-current="page"><?php echo $icoon; ?> <?php echo $label; ?></span>
                <?php else: ?>
                    <a class="nav-knop-los" href="<?php echo $bestand; ?>"><?php echo $icoon; ?> <?php echo $label; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$zonderMenu) toonOnderdeelMenu(); ?>
        </nav>
        <?php
        navigatieStijl();
        return;
    }

    // Medewerkers hebben maar een paar pagina's: gewone knoppen, op elke pagina dezelfde
    ?>
    <nav class="nav-knoppen" aria-label="Pagina's">
        <?php foreach ($groepen as $items): ?>
            <?php foreach ($items as $bestand => [$icoon, $label]): ?>
                <?php if ($bestand === $hier): ?>
                    <span class="nav-knop-los hier" aria-current="page"><?php echo $icoon; ?> <?php echo $label; ?></span>
                <?php else: ?>
                    <a class="nav-knop-los" href="<?php echo $bestand; ?>"><?php echo $icoon; ?> <?php echo $label; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <a class="nav-knop-los nav-uit-los" href="logout.php">🚪 Uitloggen</a>
    </nav>
    <?php
    navigatieStijl();
}

// Uitklapmenu van de baas: wisselen tussen Werkplek en Beheer, en uitloggen
function toonOnderdeelMenu() {
    if (($_SESSION['rol'] ?? '') !== 'baas') return;
    $onderdelen = ['Werkplek' => ['🛎️', 'chef.php'], 'Beheer' => ['⚙️', 'baas_paneel.php']];
    $huidig = navigatieOnderdeel() ?: 'Werkplek';
    ?>
        <span class="nav-menu">
            <button type="button" class="nav-knop-los nav-onderdeel" id="nav-knop" aria-haspopup="true" aria-expanded="false" aria-controls="nav-lijst" onclick="wisselNavigatie(event)">
                <?php echo $onderdelen[$huidig][0]; ?> <?php echo $huidig; ?> <span class="nav-pijl">▾</span>
            </button>
            <span class="nav-lijst" id="nav-lijst" hidden role="menu">
                <?php foreach ($onderdelen as $naam => [$icoon, $eerste]): ?>
                <?php if ($naam === $huidig): ?>
                    <span class="nav-item hier" role="menuitem" aria-current="true"><span class="nav-icoon"><?php echo $icoon; ?></span> <?php echo $naam; ?> <small>Je bent hier</small></span>
                <?php else: ?>
                    <a class="nav-item" role="menuitem" href="<?php echo $eerste; ?>"><span class="nav-icoon"><?php echo $icoon; ?></span> <?php echo $naam; ?></a>
                <?php endif; ?>
                <?php endforeach; ?>
                <a class="nav-item nav-uit" role="menuitem" href="logout.php"><span class="nav-icoon">🚪</span> Uitloggen</a>
            </span>
        </span>
    <?php
    navigatieStijl();
    navigatieScript();
}

// In welk onderdeel (Werkplek of Beheer) zit de baas nu?
function navigatieOnderdeel() {
    $hier = basename($_SERVER['SCRIPT_NAME'] ?? '');
    foreach (navigatieItems('baas') as $groep => $items) if (isset($items[$hier])) return $groep;
    return '';
}

// Tabbladen met alle pagina's van het huidige onderdeel (alleen voor de baas).
// $inKaart = true op de beheerpagina's (binnen de witte kaart), anders een balk onder de rode kop.
// (Vroeger tabbladen onder de kop; de pagina's staan nu als knoppen rechtsboven. Blijft bestaan zodat oude aanroepen niets doen.)
function toonSectieTabs($inKaart = false) {
}

function navigatieStijl() {
    static $stijlGedaan = false;
    if ($stijlGedaan) return;
    $stijlGedaan = true;
    ?>
    <style>
        /* Medewerkers: losse knoppen */
        .nav-knoppen { display: inline-flex; flex-wrap: wrap; gap: 6px; vertical-align: middle; }
        .nav-knop-los { display: inline-flex; align-items: center; gap: 5px; background: #fff; color: #2b1d19 !important; text-decoration: none !important;
            border: 2px solid rgba(43, 29, 25, 0.15); border-radius: 22px; padding: 7px 14px; font: 500 0.92rem 'Segoe UI', Tahoma, sans-serif; white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); line-height: 1.2; }
        a.nav-knop-los:hover { background: #fdf3e6; }
        .nav-knop-los.hier { background: #2b1d19; color: #fff !important; border-color: #2b1d19; font-weight: 800; box-shadow: inset 0 -3px 0 #e9a23b; cursor: default; }
        .nav-uit-los { color: #a8232b !important; font-weight: bold; }
        /* Telefoon: knoppen op een eigen rij over de hele breedte */
        .nav-knoppen { flex-shrink: 0; }
        .nav-knoppen { flex-wrap: nowrap; }
        @media (max-width: 700px) {
            .user-info > div:last-child:has(.nav-knoppen) { flex-wrap: wrap !important; overflow: visible !important; }
            .nav-knoppen { display: flex; flex-wrap: wrap; flex-basis: 100%; width: 100%; margin-top: 4px; }
            .nav-knop-los { flex: 1 1 auto; justify-content: center; padding: 8px 10px; }
        }
        /* Baas: uitklapmenu om van onderdeel te wisselen */
        .nav-menu { display: inline-flex; flex-shrink: 0; }
        .nav-onderdeel { cursor: pointer; font-weight: bold !important; background: #fdf3e6; margin: 0; }
        .nav-onderdeel[aria-expanded="true"] .nav-pijl { display: inline-block; transform: rotate(180deg); }
        .nav-lijst { position: fixed; z-index: 5000; min-width: 230px; background: #fff; color: #2b1d19; border-radius: 14px; box-shadow: 0 12px 34px rgba(0, 0, 0, 0.28);
            padding: 8px; text-align: left; font-family: 'Segoe UI', Tahoma, sans-serif; display: block; }
        .nav-lijst[hidden] { display: none; }
        .nav-item { display: flex; align-items: center; gap: 8px; padding: 11px 10px; border-radius: 8px; color: #2b1d19 !important; text-decoration: none !important; font-weight: 500; font-size: 0.98rem; }
        .nav-item:hover, .nav-item:focus-visible { background: #fdf3e6; outline: none; }
        .nav-icoon { width: 22px; text-align: center; flex-shrink: 0; }
        .nav-item.hier { background: #a8232b; color: #fff !important; font-weight: 800; }
        .nav-item.hier small { margin-left: auto; font-weight: normal; font-size: 0.72rem; opacity: 0.85; padding-left: 10px; }
        .nav-uit { font-weight: bold; margin-top: 6px; border-top: 1px solid #eee3d6; border-radius: 0 0 8px 8px; color: #a8232b !important; }
        @media print { .nav-knoppen, .nav-lijst { display: none !important; } }
    </style>
    <?php
}

function navigatieScript() {
    ?>
    <script>
        // Uitklapmenu (Werkplek / Beheer / Uitloggen). 'fixed', zodat het nooit wordt afgeknipt door een schuifbare knoppenrij.
        function wisselNavigatie(e) {
            if (e) e.stopPropagation();
            const knop = document.getElementById('nav-knop'), lijst = document.getElementById('nav-lijst');
            if (!lijst.hidden) return sluitNavigatie();
            lijst.hidden = false;
            knop.setAttribute('aria-expanded', 'true');
            const r = knop.getBoundingClientRect(), b = lijst.offsetWidth, h = lijst.offsetHeight;
            lijst.style.left = Math.max(10, Math.min(r.right - b, window.innerWidth - b - 10)) + 'px';
            let boven = r.bottom + 6;
            if (boven + h > window.innerHeight - 10) boven = Math.max(10, r.top - h - 6);
            lijst.style.top = boven + 'px';
        }
        function sluitNavigatie() {
            const knop = document.getElementById('nav-knop'), lijst = document.getElementById('nav-lijst');
            if (!lijst || lijst.hidden) return;
            lijst.hidden = true;
            knop.setAttribute('aria-expanded', 'false');
        }
        document.addEventListener('click', e => { if (!e.target.closest('#nav-lijst')) sluitNavigatie(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') sluitNavigatie(); });
        window.addEventListener('resize', sluitNavigatie);
        window.addEventListener('scroll', sluitNavigatie, { passive: true });
    </script>
    <?php
}
