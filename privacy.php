<?php
/*
 * Las Tapas - privacy.php
 * Privacyverklaring voor gasten en personeel (Nederlands, Engels en Spaans).
 * De bewaartermijnen komen uit de instellingen, zodat deze pagina altijd klopt met wat het systeem doet.
 */
require __DIR__ . '/gedeeld.php';

$maanden = 3;
$jaren = 7;
try {
    db();
    $maanden = max(1, intval(instelling('bewaar_namen_maanden', 3)));
    $jaren = max(7, intval(instelling('bewaar_archief_jaren', 7)));
} catch (Throwable $e) {
    // Geen database? Dan de standaardtermijnen tonen.
}
$bijgewerkt = '28-09-2026';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacyverklaring - Las Tapas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,800&display=swap" rel="stylesheet">
    <script src="taal.js?v=20261007c" defer></script>
    <style>
        :root { --rood: #a8232b; --creme: #fbf5ea; --inkt: #2b1d19; --grijs: #786a63; --azul: #1f4e8c; --lijn: #ecdcc8;
            --tegel: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 28 28'%3E%3Crect width='28' height='28' fill='%23fdf6e3'/%3E%3Cpath d='M14 3C17 9 19 11 25 14C19 17 17 19 14 25C11 19 9 17 3 14C9 11 11 9 14 3Z' fill='%231f4e8c'/%3E%3Ccircle cx='14' cy='14' r='3' fill='%23e9a23b'/%3E%3Cpath d='M0 0h5v5H0zM23 0h5v5h-5zM0 23h5v5H0zM23 23h5v5h-5z' fill='%231f4e8c' opacity='.5'/%3E%3C/svg%3E"); }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--creme); color: var(--inkt); font-family: 'Segoe UI', Tahoma, sans-serif; line-height: 1.6; }
        header { background: linear-gradient(135deg, #b52a31, #8e1c23); color: white; padding: 16px 20px; position: relative; margin-bottom: 14px;
                 display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: nowrap; }
        header::after { content: ''; position: absolute; left: 0; right: 0; bottom: -14px; height: 14px; background: var(--tegel) repeat-x; background-size: 14px 14px; }
        header strong { font-family: 'Fraunces', Georgia, serif; font-size: 1.4rem; white-space: nowrap; }
        .kop-rechts { display: flex; gap: 8px; align-items: center; }
        .terug { white-space: nowrap; color: white; text-decoration: none; font-weight: bold; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.5); padding: 6px 12px; border-radius: 16px; }
        main { max-width: 760px; margin: 0 auto; padding: 24px 18px 50px; }
        h1 { font-family: 'Fraunces', Georgia, serif; color: var(--rood); margin: 0 0 4px; font-size: 2rem; line-height: 1.2; }
        h2 { font-family: 'Fraunces', Georgia, serif; color: var(--rood); font-size: 1.25rem; margin: 28px 0 8px; }
        .datum { color: var(--grijs); font-size: 0.85rem; margin: 0 0 18px; }
        .kort { background: white; border: 1px solid var(--lijn); border-left: 5px solid var(--azul); border-radius: 10px; padding: 14px 16px; }
        .kort ul { margin: 6px 0 0; padding-left: 20px; }
        .let-op { background: #fff8e1; border: 1px solid #f5d98b; border-radius: 8px; padding: 10px 14px; font-size: 0.9rem; }
        .tabel { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; font-size: 0.9rem; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #f2e8dc; vertical-align: top; }
        th { background: #fbf1e6; color: var(--inkt); }
        td:first-child { font-weight: bold; }
        a { color: var(--azul); }
        @media (max-width: 600px) {
            h1 { font-size: 1.6rem; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            tr { background: white; border: 1px solid var(--lijn); border-radius: 10px; margin-bottom: 10px; padding: 8px 12px; }
            td { border: none; padding: 3px 0; }
            td[data-label]::before { content: attr(data-label) ": "; font-weight: bold; color: var(--grijs); }
            td:first-child { font-size: 1rem; color: var(--rood); }
        }
        section[hidden] { display: none; }
    </style>
</head>
<body>
    <header>
        <strong>🍷 Las Tapas</strong>
        <div class="kop-rechts">
            <span id="taal-plek"></span>
            <a class="terug" href="javascript:history.length > 1 ? history.back() : window.close()">← Terug</a>
        </div>
    </header>

    <main>
        <!-- =================== NEDERLANDS =================== -->
        <section lang="nl" data-geen-vertaling>
            <h1>Privacyverklaring</h1>
            <p class="datum">Laatst bijgewerkt: <?php echo $bijgewerkt; ?></p>

            <div class="kort">
                <strong>In het kort</strong>
                <ul>
                    <li>We vragen alleen wat nodig is om je te bedienen: een naam, het aantal personen en je bestelling.</li>
                    <li>Je e-mailadres gebruiken we alleen om je bon te sturen. We bewaren het niet in ons systeem.</li>
                    <li>Je naam wordt na <?php echo $maanden; ?> maanden gewist. Bedragen bewaren we <?php echo $jaren; ?> jaar, omdat de wet dat verplicht.</li>
                    <li>Geen advertenties, geen tracking, geen verkoop van gegevens.</li>
                </ul>
            </div>

            <h2>Wie is verantwoordelijk?</h2>
            <p>Restaurant Las Tapas is verantwoordelijk voor de gegevens die via dit bestelsysteem worden verwerkt.
               Vragen of verzoeken? Mail naar <strong>privacy@lastapas.nl</strong> of vraag het aan het personeel.</p>
            <p class="let-op">Dit systeem is gemaakt als schoolproject (Gilde Opleidingen). Het e-mailadres hierboven is een voorbeeld.
               Bij echt gebruik vult het restaurant hier zijn eigen gegevens in.</p>

            <h2>Welke gegevens verwerken we van gasten?</h2>
            <div class="tabel"><table>
                <thead><tr><th>Gegeven</th><th>Waarom</th><th>Hoe lang</th></tr></thead>
                <tbody>
                    <tr><td>Naam (hoofdboeker), aantal personen, arrangement</td><td data-label="Waarom">Je bestellingen en rekening aan jullie tafel koppelen</td><td data-label="Hoe lang">Naam: <?php echo $maanden; ?> maanden na je bezoek. Aantallen en arrangement: <?php echo $jaren; ?> jaar (zonder naam)</td></tr>
                    <tr><td>Bestellingen en opmerkingen (bijv. allergieën)</td><td data-label="Waarom">Het eten en drinken bereiden en serveren. Allergie-informatie geef je vrijwillig en gebruiken we alleen voor de bereiding</td><td data-label="Hoe lang">Opmerkingen worden gewist zodra jullie tafel wordt afgesloten. Welke gerechten er verkocht zijn, bewaren we <?php echo $jaren; ?> jaar zonder naam</td></tr>
                    <tr><td>Rekening: bedragen, betaalmethode (contant/online), tijdstip</td><td data-label="Waarom">Afrekenen en onze boekhouding</td><td data-label="Hoe lang"><?php echo $jaren; ?> jaar (wettelijke fiscale bewaarplicht)</td></tr>
                    <tr><td>E-mailadres (alleen als je een bon per e-mail kiest)</td><td data-label="Waarom">Je bon naar je toe sturen</td><td data-label="Hoe lang">Gewist uit ons systeem zodra jullie tafel wordt afgesloten. De verstuurde bon staat nog in de map Verzonden van de restaurant-mail</td></tr>
                    <tr><td>Kortingscode of cadeaubon die je gebruikt</td><td data-label="Waarom">Korting of tegoed verrekenen op je rekening</td><td data-label="Hoe lang">Het bedrag staat op de rekening (<?php echo $jaren; ?> jaar). De code zelf wordt niet aan je naam gekoppeld</td></tr>
                    <tr><td>Cadeaubon die je koopt: bedrag, saldo, en als je dat invult de naam van de ontvanger en een boodschap</td><td data-label="Waarom">De cadeaubon maken en het tegoed bijhouden</td><td data-label="Hoe lang">Zolang er tegoed op staat. Naam en boodschap: <?php echo $maanden; ?> maanden nadat het tegoed op is. Niet betaalde bonnen: na 1 dag verwijderd</td></tr>
                    <tr><td>Beoordeling (sterren, tekst en de naam die je er zelf bij zet; zonder naam staat er Anoniem)</td><td data-label="Waarom">Onze service verbeteren</td><td data-label="Hoe lang">Tekst en naam: <?php echo $maanden; ?> maanden. Sterren: anoniem <?php echo $jaren; ?> jaar</td></tr>
                    <tr><td>Hulpvraag aan de bediening (onderwerp en toelichting)</td><td data-label="Waarom">Je snel helpen</td><td data-label="Hoe lang">Gewist zodra de serveerster hem heeft afgehandeld</td></tr>
                    <tr><td>Reservering: naam, e-mailadres, telefoonnummer, datum, tijd, tafel, aantal personen en een eventuele opmerking</td><td data-label="Waarom">Je reservering vastleggen, je een bevestiging mailen, je bellen of mailen als er iets verandert en je bij binnenkomst herkennen</td><td data-label="Hoe lang"><?php echo $maanden; ?> maanden na de datum van de reservering, daarna helemaal verwijderd. Geannuleerde reserveringen: 15 minuten na het annuleren verwijderd</td></tr>
                </tbody>
            </table></div>

            <h2>Betalen</h2>
            <p>Bij contant betalen verwerken we alleen het bedrag en de betaalmethode. <strong>Online betalen via de QR-code is in deze versie een simulatie:</strong>
               er worden geen bank- of kaartgegevens verwerkt, en een serveerster controleert of er betaald is. Wordt later een echte betaaldienst gekoppeld
               (bijvoorbeeld Mollie), dan verwerkt die dienst je betaalgegevens volgens zijn eigen privacyverklaring. Las Tapas ontvangt dan alleen of de betaling gelukt is.</p>

            <h2>Wat staat er op je eigen telefoon?</h2>
            <p>Om de pagina te laten werken bewaart je browser een paar dingen op je eigen apparaat: je naam, het arrangement, het aantal personen,
               een sessiecode voor jullie bezoek, je taalkeuze en (als je dat kiest) je e-mailadres voor de bon, zodat je het de volgende keer niet opnieuw hoeft te typen.
               Deze gegevens gaan niet naar ons, behalve als je een bestelling of bon verstuurt. Na het afrekenen wordt het meeste automatisch gewist.
               Je kunt alles zelf verwijderen via de instellingen van je browser (sitegegevens wissen).</p>
            <p>We gebruiken <strong>geen</strong> cookies voor advertenties of statistieken en geen trackingdiensten.</p>

            <h2>Diensten van anderen</h2>
            <ul>
                <li><strong>Google Fonts</strong>: voor het lettertype van de koppen. Je browser haalt dat op bij Google, waardoor Google je IP-adres ziet.</li>
                <li><strong>QR Server (api.qrserver.com)</strong>: maakt de afbeelding van de betaal-QR-code.</li>
                <li><strong>E-mail (Gmail van het restaurant)</strong>: verstuurt de bon als je daarvoor kiest.</li>
                <li><strong>ngrok</strong>: alleen tijdens het testen, om de site via internet bereikbaar te maken.</li>
            </ul>

            <h2>Personeel</h2>
            <p>Van medewerkers bewaren we naam, gebruikersnaam, rol en een versleuteld wachtwoord (niemand kan het terugzien).
               Bij bestellingen en betalingen wordt vastgelegd welke medewerker iets heeft gedaan. Mislukte inlogpogingen (gebruikersnaam en IP-adres)
               worden na 1 dag gewist. Na het inloggen gebruikt de site één noodzakelijke sessiecookie.</p>

            <h2>Jouw rechten</h2>
            <p>Je mag vragen welke gegevens we van je hebben, ze laten verbeteren of verwijderen, of bezwaar maken.
               Neem daarvoor contact met ons op. Ben je het niet eens met hoe we met je gegevens omgaan, dan kun je een klacht indienen bij de
               <a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>.</p>

            <h2>Beveiliging</h2>
            <p>Wachtwoorden worden versleuteld opgeslagen, via internet loopt de verbinding altijd beveiligd (https),
               elke tafel heeft een geheime code in de QR-code en alleen ingelogd personeel kan gegevens van andere tafels zien.</p>
        </section>

        <!-- =================== ENGLISH =================== -->
        <section lang="en" data-geen-vertaling hidden>
            <h1>Privacy statement</h1>
            <p class="datum">Last updated: <?php echo $bijgewerkt; ?></p>

            <div class="kort">
                <strong>In short</strong>
                <ul>
                    <li>We only ask what we need to serve you: a name, the number of people and your order.</li>
                    <li>We only use your email address to send your receipt. We do not keep it in our system.</li>
                    <li>Your name is deleted after <?php echo $maanden; ?> months. We keep amounts for <?php echo $jaren; ?> years because the law requires it.</li>
                    <li>No advertising, no tracking, no selling of data.</li>
                </ul>
            </div>

            <h2>Who is responsible?</h2>
            <p>Restaurant Las Tapas is responsible for the data processed through this ordering system.
               Questions or requests? Email <strong>privacy@lastapas.nl</strong> or ask our staff.</p>
            <p class="let-op">This system was made as a school project (Gilde Opleidingen). The email address above is an example.
               In real use, the restaurant fills in its own details here.</p>

            <h2>What guest data do we process?</h2>
            <div class="tabel"><table>
                <thead><tr><th>Data</th><th>Why</th><th>How long</th></tr></thead>
                <tbody>
                    <tr><td>Name (main booker), number of people, package</td><td data-label="Why">To link your orders and bill to your table</td><td data-label="How long">Name: <?php echo $maanden; ?> months after your visit. Numbers and package: <?php echo $jaren; ?> years (without name)</td></tr>
                    <tr><td>Orders and notes (e.g. allergies)</td><td data-label="Why">To prepare and serve your food and drinks. Allergy information is voluntary and only used for preparation</td><td data-label="How long">Notes are deleted as soon as your table is closed. Which dishes were sold is kept for <?php echo $jaren; ?> years without name</td></tr>
                    <tr><td>Bill: amounts, payment method (cash/online), time</td><td data-label="Why">Payment and our accounting</td><td data-label="How long"><?php echo $jaren; ?> years (legal tax retention obligation)</td></tr>
                    <tr><td>Email address (only if you choose an emailed receipt)</td><td data-label="Why">To send you your receipt</td><td data-label="How long">Deleted from our system as soon as your table is closed. The sent receipt remains in the restaurant's Sent mail folder</td></tr>
                    <tr><td>Discount code or gift card you use</td><td data-label="Why">To apply the discount or balance to your bill</td><td data-label="How long">The amount is on the bill (<?php echo $jaren; ?> years). The code itself is not linked to your name</td></tr>
                    <tr><td>Gift card you buy: amount, balance and, if you fill it in, the recipient's name and a message</td><td data-label="Why">To create the gift card and keep track of the balance</td><td data-label="How long">As long as there is a balance. Name and message: <?php echo $maanden; ?> months after the balance is used up. Unpaid cards: deleted after 1 day</td></tr>
                    <tr><td>Review (stars, text and the name you add yourself; without a name it shows as Anonymous)</td><td data-label="Why">To improve our service</td><td data-label="How long">Text and name: <?php echo $maanden; ?> months. Stars: anonymously for <?php echo $jaren; ?> years</td></tr>
                    <tr><td>Request for help (topic and details)</td><td data-label="Why">To help you quickly</td><td data-label="How long">Deleted as soon as the waiter has handled it</td></tr>
                    <tr><td>Reservation: name, email address, phone number, date, time, table, number of people and an optional note</td><td data-label="Why">To record your reservation, email you a confirmation, call or email you if something changes and recognise you when you arrive</td><td data-label="How long"><?php echo $maanden; ?> months after the date of the reservation, then deleted completely. Cancelled reservations: deleted 15 minutes after cancelling</td></tr>
                </tbody>
            </table></div>

            <h2>Payment</h2>
            <p>When you pay in cash we only process the amount and payment method. <strong>Paying online via the QR code is a simulation in this version:</strong>
               no bank or card details are processed, and a waiter checks the payment. If a real payment provider (e.g. Mollie) is connected later,
               that provider processes your payment data under its own privacy statement. Las Tapas then only receives whether the payment succeeded.</p>

            <h2>What is stored on your own phone?</h2>
            <p>To make the page work, your browser stores a few things on your own device: your name, the package, the number of people,
               a session code for your visit, your language choice and (if you choose) your email address for the receipt, so you don't have to type it again next time.
               This data is not sent to us unless you place an order or request a receipt. Most of it is deleted automatically after paying.
               You can delete everything yourself in your browser settings (clear site data).</p>
            <p>We do <strong>not</strong> use cookies for advertising or statistics, and no tracking services.</p>

            <h2>Services from others</h2>
            <ul>
                <li><strong>Google Fonts</strong>: for the heading font. Your browser loads it from Google, so Google sees your IP address.</li>
                <li><strong>QR Server (api.qrserver.com)</strong>: creates the image of the payment QR code.</li>
                <li><strong>Email (the restaurant's Gmail)</strong>: sends your receipt if you choose that.</li>
                <li><strong>ngrok</strong>: only during testing, to make the site reachable over the internet.</li>
            </ul>

            <h2>Staff</h2>
            <p>For staff we store name, username, role and an encrypted password (nobody can read it back).
               For orders and payments we record which staff member did what. Failed login attempts (username and IP address)
               are deleted after 1 day. After logging in, the site uses one necessary session cookie.</p>

            <h2>Your rights</h2>
            <p>You may ask which data we hold about you, have it corrected or deleted, or object. Please contact us to do so.
               If you disagree with how we handle your data, you can file a complaint with the Dutch Data Protection Authority
               (<a href="https://www.autoriteitpersoonsgegevens.nl/en" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>).</p>

            <h2>Security</h2>
            <p>Passwords are stored encrypted, over the internet the connection is always secure (https),
               each table has a secret code in its QR code and only logged-in staff can see data from other tables.</p>
        </section>

        <!-- =================== ESPAÑOL =================== -->
        <section lang="es" data-geen-vertaling hidden>
            <h1>Política de privacidad</h1>
            <p class="datum">Última actualización: <?php echo $bijgewerkt; ?></p>

            <div class="kort">
                <strong>En resumen</strong>
                <ul>
                    <li>Solo pedimos lo necesario para atenderte: un nombre, el número de personas y tu pedido.</li>
                    <li>Solo usamos tu correo electrónico para enviarte el recibo. No lo guardamos en nuestro sistema.</li>
                    <li>Tu nombre se borra a los <?php echo $maanden; ?> meses. Guardamos los importes <?php echo $jaren; ?> años porque la ley lo exige.</li>
                    <li>Sin publicidad, sin seguimiento y sin venta de datos.</li>
                </ul>
            </div>

            <h2>¿Quién es el responsable?</h2>
            <p>El restaurante Las Tapas es responsable de los datos tratados mediante este sistema de pedidos.
               ¿Preguntas o solicitudes? Escribe a <strong>privacy@lastapas.nl</strong> o pregunta al personal.</p>
            <p class="let-op">Este sistema se ha creado como proyecto escolar (Gilde Opleidingen). La dirección de correo de arriba es un ejemplo.
               En un uso real, el restaurante indica aquí sus propios datos.</p>

            <h2>¿Qué datos de los clientes tratamos?</h2>
            <div class="tabel"><table>
                <thead><tr><th>Dato</th><th>Para qué</th><th>Cuánto tiempo</th></tr></thead>
                <tbody>
                    <tr><td>Nombre (titular), número de personas, menú</td><td data-label="Para qué">Vincular vuestros pedidos y la cuenta a vuestra mesa</td><td data-label="Cuánto tiempo">Nombre: <?php echo $maanden; ?> meses tras la visita. Número y menú: <?php echo $jaren; ?> años (sin nombre)</td></tr>
                    <tr><td>Pedidos y notas (p. ej. alergias)</td><td data-label="Para qué">Preparar y servir la comida y la bebida. La información sobre alergias es voluntaria y solo se usa para la preparación</td><td data-label="Cuánto tiempo">Las notas se borran al cerrar vuestra mesa. Qué platos se han vendido se guarda <?php echo $jaren; ?> años sin nombre</td></tr>
                    <tr><td>Cuenta: importes, forma de pago (efectivo/online), hora</td><td data-label="Para qué">Cobrar y nuestra contabilidad</td><td data-label="Cuánto tiempo"><?php echo $jaren; ?> años (obligación fiscal de conservación)</td></tr>
                    <tr><td>Correo electrónico (solo si eliges el recibo por correo)</td><td data-label="Para qué">Enviarte el recibo</td><td data-label="Cuánto tiempo">Se borra de nuestro sistema al cerrar vuestra mesa. El recibo enviado queda en la carpeta de enviados del correo del restaurante</td></tr>
                    <tr><td>Código de descuento o tarjeta regalo que usas</td><td data-label="Para qué">Aplicar el descuento o el saldo a tu cuenta</td><td data-label="Cuánto tiempo">El importe figura en la cuenta (<?php echo $jaren; ?> años). El código no se vincula a tu nombre</td></tr>
                    <tr><td>Tarjeta regalo que compras: importe, saldo y, si lo rellenas, el nombre del destinatario y un mensaje</td><td data-label="Para qué">Crear la tarjeta regalo y controlar el saldo</td><td data-label="Cuánto tiempo">Mientras quede saldo. Nombre y mensaje: <?php echo $maanden; ?> meses después de agotarse el saldo. Tarjetas no pagadas: se borran después de 1 día</td></tr>
                    <tr><td>Valoración (estrellas, texto y el nombre que pongas tú; sin nombre aparece como Anónimo)</td><td data-label="Para qué">Mejorar nuestro servicio</td><td data-label="Cuánto tiempo">Texto y nombre: <?php echo $maanden; ?> meses. Estrellas: de forma anónima <?php echo $jaren; ?> años</td></tr>
                    <tr><td>Solicitud de ayuda (tema y detalles)</td><td data-label="Para qué">Ayudarte rápidamente</td><td data-label="Cuánto tiempo">Se borra en cuanto el camarero la atiende</td></tr>
                    <tr><td>Reserva: nombre, correo electrónico, teléfono, fecha, hora, mesa, número de personas y una nota opcional</td><td data-label="Para qué">Registrar tu reserva, enviarte una confirmación, llamarte o escribirte si algo cambia y reconocerte al llegar</td><td data-label="Cuánto tiempo"><?php echo $maanden; ?> meses después de la fecha de la reserva; luego se borra por completo. Reservas canceladas: se borran 15 minutos después de cancelarlas</td></tr>
                </tbody>
            </table></div>

            <h2>Pago</h2>
            <p>Si pagas en efectivo, solo tratamos el importe y la forma de pago. <strong>El pago online con el código QR es una simulación en esta versión:</strong>
               no se tratan datos bancarios ni de tarjetas, y un camarero comprueba el pago. Si más adelante se conecta un proveedor de pagos real
               (por ejemplo Mollie), ese proveedor trata tus datos de pago según su propia política de privacidad. Las Tapas solo recibe si el pago se ha realizado.</p>

            <h2>¿Qué se guarda en tu propio móvil?</h2>
            <p>Para que la página funcione, tu navegador guarda algunos datos en tu propio dispositivo: tu nombre, el menú, el número de personas,
               un código de sesión de tu visita, el idioma elegido y (si lo eliges) tu correo para el recibo, para que no tengas que escribirlo otra vez.
               Estos datos no nos llegan, salvo cuando haces un pedido o pides un recibo. Casi todo se borra automáticamente después de pagar.
               Puedes borrarlo todo tú mismo en los ajustes del navegador (borrar datos del sitio).</p>
            <p><strong>No</strong> usamos cookies de publicidad ni de estadísticas, ni servicios de seguimiento.</p>

            <h2>Servicios de terceros</h2>
            <ul>
                <li><strong>Google Fonts</strong>: para la tipografía de los títulos. Tu navegador la descarga de Google, por lo que Google ve tu dirección IP.</li>
                <li><strong>QR Server (api.qrserver.com)</strong>: genera la imagen del código QR de pago.</li>
                <li><strong>Correo (Gmail del restaurante)</strong>: envía tu recibo si lo eliges.</li>
                <li><strong>ngrok</strong>: solo durante las pruebas, para que la web sea accesible por internet.</li>
            </ul>

            <h2>Personal</h2>
            <p>Del personal guardamos nombre, usuario, rol y una contraseña cifrada (nadie puede verla).
               En pedidos y pagos se registra qué empleado hizo qué. Los intentos de acceso fallidos (usuario y dirección IP)
               se borran después de 1 día. Tras iniciar sesión, la web usa una única cookie de sesión necesaria.</p>

            <h2>Tus derechos</h2>
            <p>Puedes preguntar qué datos tenemos sobre ti, pedir que se corrijan o se borren, u oponerte. Para ello, ponte en contacto con nosotros.
               Si no estás de acuerdo con cómo tratamos tus datos, puedes presentar una reclamación ante la autoridad neerlandesa de protección de datos
               (<a href="https://www.autoriteitpersoonsgegevens.nl/en" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>).</p>

            <h2>Seguridad</h2>
            <p>Las contraseñas se guardan cifradas, a través de internet la conexión es siempre segura (https),
               cada mesa tiene un código secreto en su código QR y solo el personal con sesión iniciada puede ver datos de otras mesas.</p>
        </section>
    </main>

    <script>
        // De juiste taal tonen (en meewisselen als de taal met de knop wordt veranderd)
        function toonTaal() {
            const taal = document.documentElement.lang || 'nl';
            const secties = document.querySelectorAll('main > section');
            const bestaat = [...secties].some(s => s.lang === taal);
            secties.forEach(s => s.hidden = s.lang !== (bestaat ? taal : 'nl'));
            const titels = { nl: 'Privacyverklaring', en: 'Privacy statement', es: 'Política de privacidad' };
            document.title = (titels[bestaat ? taal : 'nl']) + ' - Las Tapas';
            const terug = document.querySelector('.terug');
            terug.textContent = { nl: '← Terug', en: '← Back', es: '← Volver' }[bestaat ? taal : 'nl'];
        }
        try { document.documentElement.lang = localStorage.getItem('taal') || 'nl'; } catch (e) {}
        toonTaal();
        new MutationObserver(toonTaal).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
    </script>
</body>
</html>
