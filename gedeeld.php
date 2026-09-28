<?php
/*
 * Las Tapas - gedeeld.php
 * Code die door alle PHP-bestanden wordt gebruikt:
 *  - de databaseverbinding
 *  - het (eenmalig) aanmaken van alle tabellen en startgegevens
 *  - hulpfuncties voor menu, arrangementen, instellingen, tafelcodes en beveiliging
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Amsterdam');
mysqli_report(MYSQLI_REPORT_OFF);

// Verhoog dit getal als er tabellen/kolommen bijkomen; dan wordt de database bijgewerkt.
const SCHEMA_VERSIE = '15';

// De 14 allergenen die een restaurant in de EU moet kunnen benoemen
const ALLERGENEN = [
    'gluten'      => ['label' => 'Gluten',      'icoon' => '🌾'],
    'schaaldieren'=> ['label' => 'Schaaldieren','icoon' => '🦐'],
    'eieren'      => ['label' => 'Ei',          'icoon' => '🥚'],
    'vis'         => ['label' => 'Vis',         'icoon' => '🐟'],
    'pinda'       => ['label' => 'Pinda',       'icoon' => '🥜'],
    'soja'        => ['label' => 'Soja',        'icoon' => '🫘'],
    'melk'        => ['label' => 'Melk',        'icoon' => '🥛'],
    'noten'       => ['label' => 'Noten',       'icoon' => '🌰'],
    'selderij'    => ['label' => 'Selderij',    'icoon' => '🥬'],
    'mosterd'     => ['label' => 'Mosterd',     'icoon' => '🟡'],
    'sesam'       => ['label' => 'Sesam',       'icoon' => '⚪'],
    'sulfiet'     => ['label' => 'Sulfiet',     'icoon' => '🍷'],
    'lupine'      => ['label' => 'Lupine',      'icoon' => '🌼'],
    'weekdieren'  => ['label' => 'Weekdieren',  'icoon' => '🦪'],
];

const CATEGORIEEN = [
    'voorgerecht'  => '🫒 Voorgerechten',
    'hoofdgerecht' => '🔥 Warme tapas',
    'drankje'      => '🍷 Drankjes',
    'toetje'       => '🍨 Toetjes',
];

// ===================== DATABASE =====================

function db() {
    static $conn = null;
    if ($conn === null) {
        $c = @new mysqli('localhost', 'root', '', 'las_tapas_db');
        if ($c->connect_error) {
            throw new RuntimeException('DB Verbinding mislukt: ' . $c->connect_error);
        }
        $c->set_charset('utf8mb4');
        $conn = $c;
        setupDatabase($conn);
    }
    return $conn;
}

// Prepared statement uitvoeren: q("SELECT ... WHERE id = ?", "i", [5])
function q($sql, $types = '', $params = []) {
    $conn = db();
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('SQL-fout: ' . $conn->error);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('SQL-fout: ' . $stmt->error);
    $res = $stmt->get_result();
    return $res ?: $stmt;
}

function alleRijen($res) {
    $lijst = [];
    while ($r = $res->fetch_assoc()) $lijst[] = $r;
    return $lijst;
}

function kolomToevoegen($conn, $tabel, $kolom, $definitie) {
    $check = $conn->query("SHOW COLUMNS FROM `$tabel` LIKE '$kolom'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `$tabel` ADD COLUMN `$kolom` $definitie");
    }
}

// Maakt alle tabellen en startgegevens aan. Draait alleen als SCHEMA_VERSIE is veranderd.
function setupDatabase($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS instellingen (
        sleutel VARCHAR(50) PRIMARY KEY,
        waarde VARCHAR(255) NOT NULL DEFAULT ''
    )");
    $r = $conn->query("SELECT waarde FROM instellingen WHERE sleutel = 'schema_versie'");
    $rij = $r ? $r->fetch_assoc() : null;
    if ($rij && $rij['waarde'] === SCHEMA_VERSIE) return;

    // --- Bestaande tabellen ---
    $conn->query("CREATE TABLE IF NOT EXISTS chefs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        naam VARCHAR(100) NOT NULL,
        gebruikersnaam VARCHAR(100) NOT NULL UNIQUE,
        wachtwoord VARCHAR(255) NOT NULL,
        rol VARCHAR(20) NOT NULL DEFAULT 'chef'
    )");
    // Oude databases hebben 'rol' als ENUM zonder 'bar' -> dan wordt de rol leeg opgeslagen
    $k = $conn->query("SHOW COLUMNS FROM chefs LIKE 'rol'");
    $ki = $k ? $k->fetch_assoc() : null;
    if ($ki && stripos($ki['Type'], 'varchar') === false) {
        $conn->query("ALTER TABLE chefs MODIFY rol VARCHAR(20) NOT NULL DEFAULT 'chef'");
    }
    $k = $conn->query("SHOW COLUMNS FROM chefs LIKE 'wachtwoord'");
    $ki = $k ? $k->fetch_assoc() : null;
    if ($ki && stripos($ki['Type'], 'varchar(255)') === false) {
        $conn->query("ALTER TABLE chefs MODIFY wachtwoord VARCHAR(255) NOT NULL");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS bestellingen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tafel INT NOT NULL,
        pakket VARCHAR(255) DEFAULT '',
        gerecht TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'nieuw',
        tijd VARCHAR(50) DEFAULT ''
    )");
    kolomToevoegen($conn, 'bestellingen', 'bezorgd_op', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'bestellingen', 'chef_naam', "VARCHAR(100) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'bestellingen', 'serveerster_naam', "VARCHAR(100) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'bestellingen', 'bedrag', "DECIMAL(8,2) NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'bestellingen', 'opmerking', "VARCHAR(255) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'bestellingen', 'besteld_op', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'bestellingen', 'aangepast', "TINYINT NOT NULL DEFAULT 0");   // door de keuken gewijzigd
    kolomToevoegen($conn, 'bestellingen', 'afdeling', "VARCHAR(10) NOT NULL DEFAULT 'keuken'");   // 'keuken' of 'bar'
    kolomToevoegen($conn, 'bestellingen', 'groep', "VARCHAR(16) NOT NULL DEFAULT ''");   // zelfde code = keuken- en bardeel van één bestelling

    $conn->query("CREATE TABLE IF NOT EXISTS voorraad (
        id INT AUTO_INCREMENT PRIMARY KEY,
        naam VARCHAR(100) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        aantal INT NOT NULL DEFAULT 0,
        minimum_drempel INT NOT NULL DEFAULT 5,
        eenheid VARCHAR(20) NOT NULL DEFAULT 'Porties'
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS tafels (
        tafel INT PRIMARY KEY,
        status VARCHAR(20) DEFAULT 'bezet',
        gast_naam VARCHAR(100) DEFAULT '',
        gezelschap VARCHAR(100) DEFAULT '',
        pakket VARCHAR(100) DEFAULT ''
    )");
    kolomToevoegen($conn, 'tafels', 'geaccepteerd_door', "VARCHAR(100) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'volw', "INT NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'tafels', 'sen', "INT NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'tafels', 'kind', "INT NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'tafels', 'start_tijd', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'tafels', 'eind_tijd', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'tafels', 'verlengingen', "INT NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'tafels', 'drank_kosten', "DECIMAL(8,2) NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'tafels', 'betaalmethode', "VARCHAR(20) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'laatste_activiteit', "DATETIME NULL DEFAULT NULL");
    // Bon: 'geen', 'email' of 'papier' (gekozen door de gast bij het afrekenen)
    kolomToevoegen($conn, 'tafels', 'bon_keuze', "VARCHAR(10) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'bon_email', "VARCHAR(190) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'bon_taal', "VARCHAR(2) NOT NULL DEFAULT 'nl'");

    // Unieke code per bezoek: telefoons van een afgesloten bezoek kunnen de tafel niet opnieuw openen
    kolomToevoegen($conn, 'tafels', 'sessie', "VARCHAR(32) NOT NULL DEFAULT ''");
    // Serveerster roepen: '' (geen), 'open' (wacht op serveerster) of 'geaccepteerd'
    kolomToevoegen($conn, 'tafels', 'oproep_status', "VARCHAR(20) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'oproep_reden', "VARCHAR(30) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'oproep_tekst', "VARCHAR(200) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'oproep_tijd', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'tafels', 'oproep_door', "VARCHAR(100) NOT NULL DEFAULT ''");

    // --- Nieuwe tabellen ---
    $conn->query("CREATE TABLE IF NOT EXISTS menu (
        id VARCHAR(40) PRIMARY KEY,
        naam VARCHAR(100) NOT NULL,
        omschrijving VARCHAR(255) NOT NULL DEFAULT '',
        categorie VARCHAR(20) NOT NULL DEFAULT 'hoofdgerecht',
        prijs DECIMAL(6,2) NOT NULL DEFAULT 0,
        allergenen VARCHAR(255) NOT NULL DEFAULT '',
        actief TINYINT NOT NULL DEFAULT 1,
        volgorde INT NOT NULL DEFAULT 0
    )");
    kolomToevoegen($conn, 'menu', 'foto', "VARCHAR(255) NOT NULL DEFAULT ''");   // bijv. fotos/patatas-3f9a1c.jpg

    $conn->query("CREATE TABLE IF NOT EXISTS pakketten (
        naam VARCHAR(100) PRIMARY KEY,
        omschrijving VARCHAR(255) NOT NULL DEFAULT '',
        duur INT NOT NULL DEFAULT 120,
        drank TINYINT NOT NULL DEFAULT 0,
        prijs_volw DECIMAL(6,2) NOT NULL DEFAULT 0,
        prijs_sen DECIMAL(6,2) NOT NULL DEFAULT 0,
        prijs_kind DECIMAL(6,2) NOT NULL DEFAULT 0,
        volgorde INT NOT NULL DEFAULT 0
    )");

    // Afgeronde tafels (voor omzet en statistieken)
    $conn->query("CREATE TABLE IF NOT EXISTS archief (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tafel INT NOT NULL,
        gast_naam VARCHAR(100) NOT NULL DEFAULT '',
        pakket VARCHAR(100) NOT NULL DEFAULT '',
        volw INT NOT NULL DEFAULT 0,
        sen INT NOT NULL DEFAULT 0,
        kind INT NOT NULL DEFAULT 0,
        verlengingen INT NOT NULL DEFAULT 0,
        drank_kosten DECIMAL(8,2) NOT NULL DEFAULT 0,
        totaal DECIMAL(8,2) NOT NULL DEFAULT 0,
        betaalmethode VARCHAR(20) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'betaald',
        afgerond_door VARCHAR(100) NOT NULL DEFAULT '',
        start_tijd DATETIME NULL DEFAULT NULL,
        afgerond_op DATETIME NOT NULL
    )");

    // Beoordeling van de gast na het betalen (1 t/m 5 sterren)
    kolomToevoegen($conn, 'archief', 'beoordeling', "TINYINT NULL DEFAULT NULL");
    kolomToevoegen($conn, 'archief', 'review_tekst', "VARCHAR(300) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'archief', 'review_op', "DATETIME NULL DEFAULT NULL");
    kolomToevoegen($conn, 'archief', 'bon_keuze', "VARCHAR(10) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'archief', 'bon_status', "VARCHAR(20) NOT NULL DEFAULT ''");

    // Elk besteld gerecht (voor 'populairste gerechten' en de ronde-limiet)
    $conn->query("CREATE TABLE IF NOT EXISTS verkocht (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bestelling_id INT NOT NULL,
        tafel INT NOT NULL,
        naam VARCHAR(100) NOT NULL,
        categorie VARCHAR(20) NOT NULL DEFAULT '',
        aantal INT NOT NULL DEFAULT 1,
        datum DATETIME NOT NULL,
        INDEX (bestelling_id),
        INDEX (tafel, datum)
    )");

    // Mislukte inlogpogingen (om wachtwoorden raden te blokkeren)
    $conn->query("CREATE TABLE IF NOT EXISTS login_pogingen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gebruikersnaam VARCHAR(100) NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        tijd DATETIME NOT NULL,
        INDEX (gebruikersnaam, tijd)
    )");

    // --- Startgegevens: menu (alleen als het menu nog leeg is) ---
    $r = $conn->query("SELECT COUNT(*) AS n FROM menu");
    if ($r && intval($r->fetch_assoc()['n']) === 0) {
        $startMenu = [
            ['pan', 'Pan con Tomate', 'Geroosterde boerenboterham met verse tomaat, knoflook en olijfolie', 'voorgerecht', 0, 'gluten'],
            ['olijven', 'Aceitunas & Queso', 'Spaanse manzanilla olijven met blokjes oude Manchego kaas', 'voorgerecht', 0, 'melk'],
            ['jamon', 'Jamón Serrano', 'Authentieke gedroogde Spaanse serranoham', 'voorgerecht', 0, ''],
            ['patatas', 'Patatas Bravas', 'Krokante aardappeltjes met pittige brava-saus en huisgemaakte aioli', 'hoofdgerecht', 0, 'eieren'],
            ['gambas', 'Gambas al Ajillo', 'Sissende knoflookgarnalen met chilipeper en verse peterselie', 'hoofdgerecht', 0, 'schaaldieren'],
            ['albondigas', 'Albóndigas', 'Spaanse rundergehaktballetjes in rijke tomaten-kruidensaus', 'hoofdgerecht', 0, 'gluten,eieren,selderij'],
            ['paella', 'Paella Valenciana', 'Traditionele saffraanrijst met malse kip en mediterrane groenten', 'hoofdgerecht', 0, 'selderij'],
            ['croquetas', 'Croquetas de Jamón', 'Romige hamkroketjes met een krokante korst (4 stuks)', 'hoofdgerecht', 0, 'gluten,eieren,melk'],
            ['chorizo', 'Chorizo al Vino', 'Pikante Spaanse chorizo worst gestoofd in rode wijn', 'hoofdgerecht', 0, 'sulfiet'],
            ['sangria', 'Sangría Tradicional', 'Heerlijke frisse karaf huisgemaakte traditionele sangría', 'drankje', 16.50, 'sulfiet'],
            ['tinto', 'Tinto de Verano', 'Spaanse zomerse cocktail van rode wijn met citroenlimonade', 'drankje', 5.50, 'sulfiet'],
            ['cerveza', 'Cerveza San Miguel', 'Koud verfrissend Spaans tapbier (30cl)', 'drankje', 4.20, 'gluten'],
            ['agua', 'Agua de Valencia', 'Cocktail met cava, gin, wodka en vers sinaasappelsap', 'drankje', 8.50, 'sulfiet'],
            ['churros', 'Churros con Chocolate', 'Krokante Spaanse churros met dikke warme chocoladesaus', 'toetje', 0, 'gluten,eieren,melk'],
            ['crema', 'Crema Catalana', 'Fluwelen Spaanse vanillepudding met een gekaramelliseerd suikerlaagje', 'toetje', 0, 'eieren,melk'],
            ['helado', 'Helado de Turrón', 'Ambachtelijk Spaans noga-ijs met amandelsnippers', 'toetje', 0, 'melk,eieren,noten'],
        ];
        $stmt = $conn->prepare("INSERT INTO menu (id, naam, omschrijving, categorie, prijs, allergenen, actief, volgorde) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        foreach ($startMenu as $i => $m) {
            $volgorde = ($i + 1) * 10;
            $stmt->bind_param("ssssdsi", $m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $volgorde);
            $stmt->execute();
        }
    }

    // --- Kortingscodes en cadeaubonnen ---
    $conn->query("CREATE TABLE IF NOT EXISTS kortingscodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        soort VARCHAR(10) NOT NULL DEFAULT 'procent',      -- 'procent' of 'bedrag'
        waarde DECIMAL(8,2) NOT NULL DEFAULT 0,
        omschrijving VARCHAR(100) NOT NULL DEFAULT '',
        geldig_tot DATE NULL DEFAULT NULL,
        max_gebruik INT NOT NULL DEFAULT 0,                -- 0 = onbeperkt
        gebruikt INT NOT NULL DEFAULT 0,
        actief TINYINT NOT NULL DEFAULT 1,
        gemaakt_op DATETIME NOT NULL
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS cadeaubonnen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        bedrag DECIMAL(8,2) NOT NULL,
        saldo DECIMAL(8,2) NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'open',        -- 'open' (nog niet betaald), 'actief', 'geblokkeerd'
        voor_naam VARCHAR(60) NOT NULL DEFAULT '',
        boodschap VARCHAR(200) NOT NULL DEFAULT '',
        tafel INT NOT NULL DEFAULT 0,
        tafel_sessie VARCHAR(32) NOT NULL DEFAULT '',
        gemaakt_door VARCHAR(100) NOT NULL DEFAULT '',
        gekocht_op DATETIME NOT NULL,
        geactiveerd_op DATETIME NULL DEFAULT NULL,
        laatst_gebruikt DATETIME NULL DEFAULT NULL,
        INDEX (tafel_sessie)
    )");
    kolomToevoegen($conn, 'cadeaubonnen', 'laatst_sessie', "VARCHAR(32) NOT NULL DEFAULT ''");   // bezoek dat de bon het laatst gebruikte
    kolomToevoegen($conn, 'tafels', 'kortingscode', "VARCHAR(30) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'tafels', 'cadeaubon_code', "VARCHAR(20) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'archief', 'kortingscode', "VARCHAR(30) NOT NULL DEFAULT ''");
    kolomToevoegen($conn, 'archief', 'korting', "DECIMAL(8,2) NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'archief', 'cadeau_ingezet', "DECIMAL(8,2) NOT NULL DEFAULT 0");
    kolomToevoegen($conn, 'archief', 'cadeau_verkocht', "DECIMAL(8,2) NOT NULL DEFAULT 0");

    // --- Startgegevens: arrangementen ---
    $r = $conn->query("SELECT COUNT(*) AS n FROM pakketten");
    if ($r && intval($r->fetch_assoc()['n']) === 0) {
        $conn->query("INSERT INTO pakketten (naam, omschrijving, duur, drank, prijs_volw, prijs_sen, prijs_kind, volgorde) VALUES
            ('Tapas Esencial (2u)', 'Onbeperkt tapas eten. Drankjes kun je los bijbestellen.', 120, 0, 32.50, 27.50, 17.50, 1),
            ('Tapas & Bebidas (2u + Drank)', 'Onbeperkt tapas eten + Spaanse drankjes.', 120, 1, 42.50, 37.50, 22.50, 2),
            ('Gran Tapas (3u + Drank)', '3 uur lang onbeperkt genieten van eten én drankjes!', 180, 1, 49.50, 44.50, 25.00, 3)");
    }

    // --- Standaardinstellingen (bestaande waarden blijven staan) ---
    $geheim = bin2hex(random_bytes(16));
    $conn->query("INSERT IGNORE INTO instellingen (sleutel, waarde) VALUES
        ('verleng_minuten', '30'),
        ('verleng_prijs_pp', '6.00'),
        ('ronde_max_pp', '3'),
        ('ronde_minuten', '10'),
        ('opruimen_na_uren', '4'),
        ('tafel_geheim', '$geheim'),
        ('smtp_host', 'smtp.gmail.com'),
        ('smtp_poort', '587'),
        ('smtp_beveiliging', 'tls'),
        ('smtp_gebruiker', ''),
        ('smtp_wachtwoord', ''),
        ('smtp_controle_uit', '0'),
        ('mail_afzender_naam', 'Las Tapas'),
        ('bewaar_namen_maanden', '3'),
        ('bewaar_archief_jaren', '7'),
        ('laatste_opschoning', '')");

    // Elk menu-item krijgt een voorraadregel (koppeling op naam)
    $conn->query("INSERT INTO voorraad (naam, categorie, aantal)
                  SELECT m.naam, m.categorie, 25 FROM menu m
                  WHERE NOT EXISTS (SELECT 1 FROM voorraad v WHERE v.naam = m.naam)");

    // Oude 'vrij'-rijen opruimen (vrije tafels staan niet meer in de tabel)
    $conn->query("DELETE FROM tafels WHERE status = 'vrij'");

    $conn->query("INSERT INTO instellingen (sleutel, waarde) VALUES ('schema_versie', '" . SCHEMA_VERSIE . "')
                  ON DUPLICATE KEY UPDATE waarde = '" . SCHEMA_VERSIE . "'");
}

// ===================== INSTELLINGEN, MENU EN ARRANGEMENTEN =====================

function instellingen($vernieuw = false) {
    static $cache = null;
    if ($cache === null || $vernieuw) {
        $cache = [];
        foreach (alleRijen(q("SELECT sleutel, waarde FROM instellingen")) as $r) {
            $cache[$r['sleutel']] = $r['waarde'];
        }
    }
    return $cache;
}

function instelling($sleutel, $standaard = null) {
    $alle = instellingen();
    return $alle[$sleutel] ?? $standaard;
}

// Alle arrangementen, op naam: ['duur', 'drank', 'prijs' => ['volw','sen','kind'], 'omschrijving', 'volgorde']
function pakketten() {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (alleRijen(q("SELECT * FROM pakketten ORDER BY volgorde, naam")) as $p) {
            $cache[$p['naam']] = [
                'omschrijving' => $p['omschrijving'],
                'duur' => intval($p['duur']),
                'drank' => (bool)$p['drank'],
                'prijs' => [
                    'volw' => floatval($p['prijs_volw']),
                    'sen'  => floatval($p['prijs_sen']),
                    'kind' => floatval($p['prijs_kind']),
                ],
                'volgorde' => intval($p['volgorde']),
            ];
        }
    }
    return $cache;
}

function pakketInfo($naam) {
    $alle = pakketten();
    return $alle[$naam] ?? ['omschrijving' => '', 'duur' => 120, 'drank' => false,
                            'prijs' => ['volw' => 0, 'sen' => 0, 'kind' => 0], 'volgorde' => 0];
}

// Menu-items (standaard alleen de actieve), met allergenen als lijst
function menuItems($alleenActief = true) {
    $sql = "SELECT * FROM menu" . ($alleenActief ? " WHERE actief = 1" : "") . " ORDER BY FIELD(categorie, 'voorgerecht', 'hoofdgerecht', 'drankje', 'toetje'), volgorde, naam";
    $lijst = [];
    foreach (alleRijen(q($sql)) as $m) {
        $m['prijs'] = floatval($m['prijs']);
        $m['actief'] = intval($m['actief']);
        $m['volgorde'] = intval($m['volgorde']);
        $m['allergenen'] = array_values(array_filter(explode(',', $m['allergenen'])));
        $lijst[] = $m;
    }
    return $lijst;
}

// Actieve menu-items op naam (voor het controleren van bestellingen)
function menuOpNaam() {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (menuItems(true) as $m) $cache[$m['naam']] = $m;
    }
    return $cache;
}

// Prijzen van drankjes (alleen van toepassing bij arrangementen zonder drank)
function drankprijzen() {
    $prijzen = [];
    foreach (menuOpNaam() as $naam => $m) {
        if ($m['categorie'] === 'drankje' && $m['prijs'] > 0) $prijzen[$naam] = $m['prijs'];
    }
    return $prijzen;
}

// ===================== FOTO'S VAN GERECHTEN =====================

const FOTO_MAP = 'fotos';            // map naast de PHP-bestanden
const FOTO_MAX_PIXELS = 1200;        // langste zijde na verkleinen
const FOTO_MAX_BYTES = 15 * 1024 * 1024;

// Zorgt dat de map voor foto's bestaat (met een lege index, zodat niemand de map kan doorbladeren)
function fotoMapKlaar() {
    $map = __DIR__ . '/' . FOTO_MAP;
    if (!is_dir($map) && !@mkdir($map, 0755, true)) {
        throw new RuntimeException("Kan de map '" . FOTO_MAP . "' niet aanmaken. Maak hem zelf aan in je lastapas-map.");
    }
    if (!file_exists($map . '/index.html')) @file_put_contents($map . '/index.html', '');
    return $map;
}

// Verwijdert een fotobestand (alleen bestanden binnen de fotomap)
function verwijderFotoBestand($pad) {
    if (!$pad || !preg_match('#^' . FOTO_MAP . '/[a-z0-9\-]+\.(jpg|png|webp|gif)$#', $pad)) return;
    $vol = __DIR__ . '/' . $pad;
    if (is_file($vol)) @unlink($vol);
}

// Draait een telefoonfoto rechtop (telefoons slaan de stand soms apart op)
function fotoRechtop($beeld, $bestand) {
    if (!function_exists('exif_read_data')) return $beeld;
    $exif = @exif_read_data($bestand);
    $stand = intval($exif['Orientation'] ?? 1);
    if ($stand === 3) return imagerotate($beeld, 180, 0);
    if ($stand === 6) return imagerotate($beeld, -90, 0);
    if ($stand === 8) return imagerotate($beeld, 90, 0);
    return $beeld;
}

// Slaat een geüploade foto op voor een gerecht.
// Geeft [true, 'fotos/naam.jpg'] of [false, 'foutmelding'] terug.
function verwerkFotoUpload($bestand, $menuId) {
    $fouten = [
        UPLOAD_ERR_INI_SIZE => 'De foto is te groot.',
        UPLOAD_ERR_FORM_SIZE => 'De foto is te groot.',
        UPLOAD_ERR_PARTIAL => 'Het uploaden is onderbroken. Probeer het opnieuw.',
        UPLOAD_ERR_NO_FILE => 'Er is geen foto gekozen.',
    ];
    if (!is_array($bestand) || ($bestand['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, $fouten[$bestand['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Uploaden is mislukt.'];
    }
    if (!is_uploaded_file($bestand['tmp_name'])) return [false, 'Uploaden is mislukt.'];
    if ($bestand['size'] > FOTO_MAX_BYTES) return [false, 'De foto is te groot (maximaal 15 MB).'];

    // Is het echt een afbeelding? (niet vertrouwen op de bestandsnaam)
    $info = @getimagesize($bestand['tmp_name']);
    $soorten = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
    if (defined('IMAGETYPE_WEBP')) $soorten[IMAGETYPE_WEBP] = 'webp';
    if (!$info || !isset($soorten[$info[2]])) {
        return [false, 'Dit bestand is geen foto. Kies een JPG-, PNG- of WEBP-afbeelding.'];
    }

    $map = fotoMapKlaar();
    $basis = substr(preg_replace('/[^a-z0-9\-]/', '', strtolower($menuId)), 0, 40) ?: 'gerecht';
    $naam = $basis . '-' . bin2hex(random_bytes(4));

    // Met GD (standaard aan in WampServer): verkleinen en opnieuw opslaan als JPG.
    // Dat maakt de foto klein en snel, en haalt verborgen gegevens (zoals de locatie) eruit.
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        @ini_set('memory_limit', '512M');
        $beeld = @imagecreatefromstring(file_get_contents($bestand['tmp_name']));
        if (!$beeld) return [false, 'Deze foto kon niet worden gelezen. Probeer een andere foto.'];
        if ($info[2] === IMAGETYPE_JPEG) $beeld = fotoRechtop($beeld, $bestand['tmp_name']);

        $b = imagesx($beeld);
        $h = imagesy($beeld);
        $schaal = min(1, FOTO_MAX_PIXELS / max($b, $h));
        $nb = max(1, (int)round($b * $schaal));
        $nh = max(1, (int)round($h * $schaal));
        $doel = imagecreatetruecolor($nb, $nh);
        imagefill($doel, 0, 0, imagecolorallocate($doel, 255, 255, 255));   // witte achtergrond voor doorzichtige PNG's
        imagecopyresampled($doel, $beeld, 0, 0, 0, 0, $nb, $nh, $b, $h);
        imagedestroy($beeld);

        $gelukt = imagejpeg($doel, $map . '/' . $naam . '.jpg', 82);
        imagedestroy($doel);
        if (!$gelukt) return [false, 'De foto kon niet worden opgeslagen. Controleer of de map "' . FOTO_MAP . '" schrijfbaar is.'];
        return [true, FOTO_MAP . '/' . $naam . '.jpg'];
    }

    // Zonder GD: de (in de browser al verkleinde) foto ongewijzigd bewaren
    $ext = $soorten[$info[2]];
    if (!move_uploaded_file($bestand['tmp_name'], $map . '/' . $naam . '.' . $ext)) {
        return [false, 'De foto kon niet worden opgeslagen. Controleer of de map "' . FOTO_MAP . '" schrijfbaar is.'];
    }
    return [true, FOTO_MAP . '/' . $naam . '.' . $ext];
}

// ===================== PRIVACY: BEWAARTERMIJNEN =====================
// - Namen van gasten en de tekst van beoordelingen: na X maanden gewist (AVG: niet langer dan nodig).
// - Financiële gegevens (bedragen, betaalmethode, verkochte gerechten): 7 jaar bewaard
//   (fiscale bewaarplicht), daarna verwijderd.
// Draait hooguit één keer per dag (wordt aangeroepen vanuit bestelling.php).
function privacyOpschoning() {
    $vandaag = date('Y-m-d');
    if (instelling('laatste_opschoning', '') === $vandaag) return;

    $maanden = max(1, min(24, intval(instelling('bewaar_namen_maanden', 3))));
    $jaren = max(7, min(10, intval(instelling('bewaar_archief_jaren', 7))));

    q("UPDATE archief SET gast_naam = '', review_tekst = ''
       WHERE afgerond_op < (NOW() - INTERVAL ? MONTH) AND (gast_naam <> '' OR review_tekst <> '')", "i", [$maanden]);
    q("DELETE FROM archief WHERE afgerond_op < (NOW() - INTERVAL ? YEAR)", "i", [$jaren]);
    q("DELETE FROM verkocht WHERE datum < (NOW() - INTERVAL ? YEAR)", "i", [$jaren]);
    // Cadeaubonnen: naam en boodschap wissen als het tegoed al een tijd op is; lege bonnen na de bewaartermijn weg.
    // Niet-betaalde bonnen (tafel nooit afgerekend) na een dag verwijderen.
    q("UPDATE cadeaubonnen SET voor_naam = '', boodschap = ''
       WHERE saldo <= 0 AND COALESCE(laatst_gebruikt, gekocht_op) < (NOW() - INTERVAL ? MONTH) AND (voor_naam <> '' OR boodschap <> '')", "i", [$maanden]);
    q("DELETE FROM cadeaubonnen WHERE saldo <= 0 AND COALESCE(laatst_gebruikt, gekocht_op) < (NOW() - INTERVAL ? YEAR)", "i", [$jaren]);
    q("DELETE FROM cadeaubonnen WHERE status = 'open' AND gekocht_op < (NOW() - INTERVAL 1 DAY)");

    q("INSERT INTO instellingen (sleutel, waarde) VALUES ('laatste_opschoning', ?)
       ON DUPLICATE KEY UPDATE waarde = ?", "ss", [$vandaag, $vandaag]);
    instellingen(true);
}

// ===================== REKENING =====================

function personenVan($t) {
    return intval($t['volw'] ?? 0) + intval($t['sen'] ?? 0) + intval($t['kind'] ?? 0);
}

// Teksten op de rekening in drie talen (Nederlands is de standaard op het scherm)
const REKENING_TEKSTEN = [
    'nl' => ['volw' => 'Volwassene', 'sen' => 'Senior (65+)', 'kind' => 'Kind', 'drank' => 'Drankjes (niet inbegrepen)', 'verlenging' => 'Verlenging %d min (%d pers.)',
             'korting' => 'Korting (%s)', 'cadeau_ingezet' => 'Cadeaubon %s', 'cadeau_gekocht' => 'Nieuwe cadeaubon', 'cadeau_voor' => 'Nieuwe cadeaubon voor %s'],
    'en' => ['volw' => 'Adult', 'sen' => 'Senior (65+)', 'kind' => 'Child', 'drank' => 'Drinks (not included)', 'verlenging' => 'Extension %d min (%d pers.)',
             'korting' => 'Discount (%s)', 'cadeau_ingezet' => 'Gift card %s', 'cadeau_gekocht' => 'New gift card', 'cadeau_voor' => 'New gift card for %s'],
    'es' => ['volw' => 'Adulto', 'sen' => 'Mayor (65+)', 'kind' => 'Niño', 'drank' => 'Bebidas (no incluidas)', 'verlenging' => 'Ampliación %d min (%d pers.)',
             'korting' => 'Descuento (%s)', 'cadeau_ingezet' => 'Tarjeta regalo %s', 'cadeau_gekocht' => 'Nueva tarjeta regalo', 'cadeau_voor' => 'Nueva tarjeta regalo para %s'],
];

// ===================== KORTINGSCODES EN CADEAUBONNEN =====================

const CADEAU_TEKENS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // geen 0/O en 1/I, die lijken te veel op elkaar

// Alleen letters en cijfers, in hoofdletters ("zomer-10 " -> "ZOMER10")
function normaliseerCode($tekst) {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$tekst));
}

// Nieuwe, unieke cadeauboncode van 12 tekens (wordt getoond als LT-ABCD-EFGH-JKLM)
function maakCadeauCode() {
    do {
        $code = '';
        for ($i = 0; $i < 12; $i++) $code .= CADEAU_TEKENS[random_int(0, strlen(CADEAU_TEKENS) - 1)];
        $bestaat = q("SELECT id FROM cadeaubonnen WHERE code = ?", "s", [$code])->fetch_assoc();
    } while ($bestaat);
    return $code;
}

function toonCadeauCode($code) {
    return 'LT-' . implode('-', str_split($code, 4));
}

// Alleen de laatste 4 tekens tonen (op de rekening)
function verborgenCadeauCode($code) {
    return 'LT-••••-••••-' . substr($code, -4);
}

function vindKortingscode($invoer) {
    $code = normaliseerCode($invoer);
    if ($code === '') return null;
    return q("SELECT * FROM kortingscodes WHERE code = ?", "s", [$code])->fetch_assoc() ?: null;
}

function vindCadeaubon($invoer) {
    $code = normaliseerCode($invoer);
    if (strlen($code) === 14 && str_starts_with($code, 'LT')) $code = substr($code, 2);
    if (strlen($code) !== 12) return null;
    return q("SELECT * FROM cadeaubonnen WHERE code = ?", "s", [$code])->fetch_assoc() ?: null;
}

// Mag deze kortingscode nu gebruikt worden? Geeft [true, ''] of [false, 'reden'] terug.
function kortingscodeBruikbaar($k) {
    if (!$k || !intval($k['actief'])) return [false, 'Deze kortingscode is niet (meer) geldig.'];
    if ($k['geldig_tot'] !== null && $k['geldig_tot'] < date('Y-m-d')) return [false, 'Deze kortingscode is verlopen.'];
    if (intval($k['max_gebruik']) > 0 && intval($k['gebruikt']) >= intval($k['max_gebruik'])) {
        return [false, 'Deze kortingscode is al zo vaak gebruikt als de bedoeling was.'];
    }
    return [true, ''];
}

function cadeaubonBruikbaar($b) {
    if (!$b) return [false, 'Deze cadeaubon bestaat niet.'];
    if ($b['status'] === 'open') return [false, 'Deze cadeaubon is nog niet betaald en dus nog niet geldig.'];
    if ($b['status'] === 'geblokkeerd') return [false, 'Deze cadeaubon is geblokkeerd. Vraag het personeel om hulp.'];
    if (floatval($b['saldo']) <= 0) return [false, 'Het tegoed van deze cadeaubon is op.'];
    return [true, ''];
}

// Nieuw gekochte cadeaubonnen van dit bezoek (worden actief zodra de rekening betaald is)
function gekochteCadeaubonnen($t) {
    if (empty($t['sessie'])) return [];
    return alleRijen(q("SELECT id, bedrag, voor_naam FROM cadeaubonnen WHERE status = 'open' AND tafel_sessie = ? ORDER BY id",
                       "s", [$t['sessie']]));
}

// Rekening van een tafel: arrangement per persoon + losse drankjes + verlengingen
function berekenRekening($t, $taal = 'nl') {
    $tk = REKENING_TEKSTEN[$taal] ?? REKENING_TEKSTEN['nl'];
    $info = pakketInfo($t['pakket']);
    $regels = [];
    foreach (['volw', 'sen', 'kind'] as $sleutel) {
        $n = intval($t[$sleutel] ?? 0);
        if ($n > 0) {
            $regels[] = ['omschrijving' => "{$n}× {$tk[$sleutel]}", 'bedrag' => round($n * $info['prijs'][$sleutel], 2)];
        }
    }
    $drank = floatval($t['drank_kosten'] ?? 0);
    if ($drank > 0) {
        $regels[] = ['omschrijving' => $tk['drank'], 'bedrag' => round($drank, 2)];
    }
    $verl = intval($t['verlengingen'] ?? 0);
    $personen = personenVan($t);
    if ($verl > 0 && $personen > 0) {
        $minuten = $verl * intval(instelling('verleng_minuten', 30));
        $regels[] = [
            'omschrijving' => sprintf($tk['verlenging'], $minuten, $personen),
            'bedrag' => round($verl * $personen * floatval(instelling('verleng_prijs_pp', 6)), 2),
        ];
    }
    $subtotaal = 0.0;
    foreach ($regels as $r) $subtotaal += $r['bedrag'];
    $subtotaal = round($subtotaal, 2);

    // Kortingscode (alleen op het eten en drinken, niet op nieuw gekochte cadeaubonnen)
    $korting = 0.0;
    $kortingscode = '';
    if (!empty($t['kortingscode'])) {
        $k = vindKortingscode($t['kortingscode']);
        if (kortingscodeBruikbaar($k)[0]) {
            $korting = $k['soort'] === 'procent'
                ? round($subtotaal * min(100, floatval($k['waarde'])) / 100, 2)
                : min(floatval($k['waarde']), $subtotaal);
            if ($korting > 0) {
                $kortingscode = $k['code'];
                $label = $k['soort'] === 'procent' ? $k['code'] . ' · ' . rtrim(rtrim(number_format(floatval($k['waarde']), 2, '.', ''), '0'), '.') . '%' : $k['code'];
                $regels[] = ['omschrijving' => sprintf($tk['korting'], $label), 'bedrag' => -$korting, 'soort' => 'korting'];
            }
        }
    }
    $omzet = round($subtotaal - $korting, 2);

    // Cadeaubonnen die tijdens dit bezoek gekocht worden (bijv. voor een jarig familielid)
    $verkocht = 0.0;
    $gekocht = gekochteCadeaubonnen($t);
    foreach ($gekocht as $b) {
        $verkocht += floatval($b['bedrag']);
        $regels[] = ['omschrijving' => $b['voor_naam'] !== '' ? sprintf($tk['cadeau_voor'], $b['voor_naam']) : $tk['cadeau_gekocht'],
                     'bedrag' => round(floatval($b['bedrag']), 2), 'soort' => 'cadeau_gekocht', 'id' => intval($b['id'])];
    }

    // Betalen met een cadeaubon (hooguit het bedrag van het eten en drinken)
    $ingezet = 0.0;
    $cadeaubon = '';
    if (!empty($t['cadeaubon_code'])) {
        $b = vindCadeaubon($t['cadeaubon_code']);
        if (cadeaubonBruikbaar($b)[0]) {
            $ingezet = round(min(floatval($b['saldo']), $omzet), 2);
            if ($ingezet > 0) {
                $cadeaubon = verborgenCadeauCode($b['code']);
                $regels[] = ['omschrijving' => sprintf($tk['cadeau_ingezet'], $cadeaubon), 'bedrag' => -$ingezet, 'soort' => 'cadeau_ingezet',
                             'saldo_na' => round(floatval($b['saldo']) - $ingezet, 2)];
            }
        }
    }

    return [
        'regels' => $regels,
        'totaal' => round($omzet + $verkocht - $ingezet, 2),   // wat de gast nu betaalt
        'subtotaal' => $subtotaal,
        'korting' => round($korting, 2),
        'kortingscode' => $kortingscode,
        'omzet' => $omzet,                                    // eten en drinken na korting
        'cadeau_verkocht' => round($verkocht, 2),
        'cadeau_ingezet' => $ingezet,
        'cadeaubon' => $cadeaubon,
    ];
}

// ===================== TAFELCODES =====================

// Geheime code per tafel, voor in de QR-code. Zonder deze code kan een klant
// niet bestellen voor (of kijken bij) een andere tafel.
function tafelCode($tafel) {
    return substr(hash_hmac('sha256', 'tafel-' . intval($tafel), instelling('tafel_geheim', 'lastapas')), 0, 8);
}

// ===================== BEVEILIGING VOOR FORMULIEREN =====================

function esc($tekst) {
    return htmlspecialchars((string)$tekst, ENT_QUOTES, 'UTF-8');
}

// CSRF-token: een geheime code in elk formulier, zodat een formulier alleen
// vanaf onze eigen pagina verstuurd kan worden (sessie moet actief zijn).
function csrfToken() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrfVeld() {
    return '<input type="hidden" name="csrf" value="' . esc(csrfToken()) . '">';
}

function csrfControle() {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        die('Ongeldig formulier (beveiligingscode klopt niet). Ga terug en probeer het opnieuw.');
    }
}

function vereisBaas() {
    if (($_SESSION['rol'] ?? '') !== 'baas') {
        header('Location: login.html');
        exit();
    }
}

// Duidelijke foutpagina voor de beheerpagina's als er iets misgaat met de database
function toonFoutPagina(Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Fout</title></head>'
       . '<body style="font-family: sans-serif; padding: 30px; background: #fdfbf7;">'
       . '<h2 style="color: #b33939;">Er ging iets mis</h2><p>' . esc($e->getMessage()) . '</p>'
       . '<p>Controleer of WampServer groen is en de database <strong>las_tapas_db</strong> bestaat.</p>'
       . '<a href="baas_paneel.php">Terug naar het beheerderspaneel</a></body></html>';
    exit();
}
