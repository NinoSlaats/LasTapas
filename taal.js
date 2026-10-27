/*
 * Las Tapas - taal.js
 * Vertaalt elke pagina naar Nederlands (nl), Engels (en) of Spaans (es).
 *
 * Hoe het werkt:
 *  - Alle teksten op de pagina worden opgezocht in het woordenboek hieronder (op basis van de Nederlandse tekst).
 *  - Teksten met een getal of naam erin worden vertaald met patronen, bijv. "Tafel {1}" -> "Table {1}".
 *  - Nieuwe teksten (nieuwe bestellingen, meldingen, pop-ups) worden automatisch meteen vertaald.
 *  - De gekozen taal wordt per apparaat onthouden.
 *
 * Gebruik op een pagina: <script src="taal.js"></script> en een <span id="taal-plek"></span> waar de knop moet komen.
 * Een tekst niet in het woordenboek? Dan blijft hij gewoon Nederlands. Voeg hem hieronder toe: 'Nederlands': ['English', 'Español'].
 */
(function () {
    'use strict';

    const TALEN = {
        nl: { vlag: '🇳🇱', naam: 'Nederlands' },
        en: { vlag: '🇬🇧', naam: 'English' },
        es: { vlag: '🇪🇸', naam: 'Español' },
    };

    // ======================= WOORDENBOEK (vaste teksten) =======================
    const W = {
        // ---------- Algemeen ----------
        'Annuleren': ['Cancel', 'Cancelar'],
        'Opslaan': ['Save', 'Guardar'],
        'Verwijderen': ['Delete', 'Eliminar'],
        'Aanpassen': ['Edit', 'Editar'],
        'Terug': ['Back', 'Volver'],
        'Uitloggen': ['Log out', 'Salir'],
        'Laden': ['Loading', 'Cargando'],
        'Status': ['Status', 'Estado'],
        'Naam': ['Name', 'Nombre'],
        'Tafel': ['Table', 'Mesa'],
        'Tafels': ['Tables', 'Mesas'],
        'Totaal': ['Total', 'Total'],
        'Gast': ['Guest', 'Cliente'],
        'Gasten': ['Guests', 'Clientes'],
        'Gast naam': ['Guest name', 'Nombre del cliente'],
        'Gezelschap': ['Party', 'Grupo'],
        'Arrangement': ['Package', 'Menú'],
        'Arrangementen': ['Packages', 'Menús'],
        'Categorie': ['Category', 'Categoría'],
        'Omschrijving': ['Description', 'Descripción'],
        'Volgorde': ['Order', 'Orden'],
        'Door': ['By', 'Por'],
        'Tijd': ['Time', 'Hora'],
        'Open': ['Open', 'Libre'],
        'Bezet': ['Occupied', 'Ocupada'],
        'Onbekend': ['Unknown', 'Desconocido'],
        'Onbekend arrangement': ['Unknown package', 'Menú desconocido'],
        'Welkom': ['Welcome', 'Bienvenido/a'],
        'Eigenaar': ['Owner', 'Propietario'],
        'Ingelogd als': ['Logged in as', 'Conectado como'],
        'Geluid uit': ['Sound off', 'Sin sonido'],
        'Geluid aan': ['Sound on', 'Con sonido'],
        'Keuken': ['Kitchen', 'Cocina'],
        'Bediening': ['Service', 'Sala'],
        'Beheerderspaneel': ['Admin panel', 'Administración'],
        'Voorraad': ['Stock', 'Existencias'],
        'Kan de server niet bereiken': ['Cannot reach the server', 'No se puede conectar con el servidor'],
        'Bijv. Jan de Vries': ['E.g. John Smith', 'Ej. Juan García'],

        // ---------- Allergenen ----------
        'Allergenen': ['Allergens', 'Alérgenos'],
        'Gluten': ['Gluten', 'Gluten'],
        'Schaaldieren': ['Crustaceans', 'Crustáceos'],
        'Ei': ['Egg', 'Huevo'],
        'Vis': ['Fish', 'Pescado'],
        'Pinda': ['Peanut', 'Cacahuete'],
        'Soja': ['Soy', 'Soja'],
        'Melk': ['Milk', 'Leche'],
        'Noten': ['Nuts', 'Frutos secos'],
        'Selderij': ['Celery', 'Apio'],
        'Mosterd': ['Mustard', 'Mostaza'],
        'Sesam': ['Sesame', 'Sésamo'],
        'Sulfiet': ['Sulphites', 'Sulfitos'],
        'Lupine': ['Lupin', 'Altramuces'],
        'Weekdieren': ['Molluscs', 'Moluscos'],

        // ---------- Categorieën ----------
        'Voorgerechten': ['Starters', 'Entrantes'],
        'Warme tapas': ['Hot tapas', 'Tapas calientes'],
        'Warme Tapas': ['Hot tapas', 'Tapas calientes'],
        'Drankjes': ['Drinks', 'Bebidas'],
        'Toetjes': ['Desserts', 'Postres'],
        'alles': ['all', ''],
        'voorgerechten': ['starters', ''],
        'warm': ['hot', ''],
        'warme tapas': ['hot tapas', ''],
        'drankjes': ['drinks', ''],
        'toetjes': ['desserts', ''],

        // ---------- Menu-omschrijvingen (standaardmenu) ----------
        'Geroosterde boerenboterham met verse tomaat, knoflook en olijfolie': ['Toasted country bread with fresh tomato, garlic and olive oil', 'Pan de pueblo tostado con tomate fresco, ajo y aceite de oliva'],
        'Spaanse manzanilla olijven met blokjes oude Manchego kaas': ['Spanish manzanilla olives with cubes of aged Manchego cheese', 'Aceitunas manzanilla con dados de queso manchego curado'],
        'Authentieke gedroogde Spaanse serranoham': ['Authentic cured Spanish Serrano ham', 'Auténtico jamón serrano curado'],
        'Krokante aardappeltjes met pittige brava-saus en huisgemaakte aioli': ['Crispy potatoes with spicy brava sauce and homemade aioli', 'Patatas crujientes con salsa brava picante y alioli casero'],
        'Sissende knoflookgarnalen met chilipeper en verse peterselie': ['Sizzling garlic prawns with chilli and fresh parsley', 'Gambas chisporroteantes al ajillo con guindilla y perejil fresco'],
        'Spaanse rundergehaktballetjes in rijke tomaten-kruidensaus': ['Spanish beef meatballs in a rich tomato and herb sauce', 'Albóndigas de ternera en salsa de tomate y hierbas'],
        'Traditionele saffraanrijst met malse kip en mediterrane groenten': ['Traditional saffron rice with tender chicken and Mediterranean vegetables', 'Arroz tradicional con azafrán, pollo tierno y verduras mediterráneas'],
        'Romige hamkroketjes met een krokante korst (4 stuks)': ['Creamy ham croquettes with a crispy coating (4 pieces)', 'Cremosas croquetas de jamón con rebozado crujiente (4 unidades)'],
        'Pikante Spaanse chorizo worst gestoofd in rode wijn': ['Spicy Spanish chorizo braised in red wine', 'Chorizo picante guisado en vino tinto'],
        'Heerlijke frisse karaf huisgemaakte traditionele sangría': ['A refreshing carafe of homemade traditional sangría', 'Jarra fresca de sangría tradicional casera'],
        'Spaanse zomerse cocktail van rode wijn met citroenlimonade': ['Spanish summer cocktail of red wine with lemon soda', 'Cóctel veraniego de vino tinto con gaseosa de limón'],
        'Koud verfrissend Spaans tapbier (30cl)': ['Cold refreshing Spanish draught beer (30cl)', 'Caña de cerveza española bien fría (30cl)'],
        'Cocktail met cava, gin, wodka en vers sinaasappelsap': ['Cocktail with cava, gin, vodka and fresh orange juice', 'Cóctel de cava, ginebra, vodka y zumo de naranja natural'],
        'Krokante Spaanse churros met dikke warme chocoladesaus': ['Crispy Spanish churros with thick hot chocolate sauce', 'Churros crujientes con chocolate caliente espeso'],
        'Fluwelen Spaanse vanillepudding met een gekaramelliseerd suikerlaagje': ['Velvety Spanish vanilla custard with a caramelised sugar top', 'Suave crema de vainilla con costra de azúcar caramelizado'],
        'Ambachtelijk Spaans noga-ijs met amandelsnippers': ['Artisanal Spanish nougat ice cream with almond flakes', 'Helado artesanal de turrón con almendra laminada'],

        // ---------- Arrangementen (standaard) ----------
        'Onbeperkt tapas eten. Drankjes kun je los bijbestellen': ['Unlimited tapas. Drinks can be ordered separately', 'Tapas ilimitadas. Las bebidas se piden aparte'],
        'Onbeperkt tapas eten + Spaanse drankjes': ['Unlimited tapas + Spanish drinks', 'Tapas ilimitadas + bebidas españolas'],
        '3 uur lang onbeperkt genieten van eten én drankjes': ['3 hours of unlimited food and drinks', '3 horas de comida y bebida ilimitadas'],

        // ---------- Klant: kop, tabs, menu ----------
        'Taberna española · all you can eat': ['Spanish taberna · all you can eat', 'Taberna española · todo lo que puedas comer'],
        'Serveerster roepen': ['Call a waiter', 'Llamar al camarero'],
        'Hulp': ['Help', 'Ayuda'],
        'Klaar, ik wil betalen': ['Done, I want to pay', 'Listo, quiero pagar'],
        'Betalen': ['Pay', 'Pagar'],
        'Het Menu': ['The Menu', 'La Carta'],
        'Mijn Bestellingen': ['My Orders', 'Mis Pedidos'],
        'Live': ['Live', 'En directo'],
        'Onze kaart · onbeperkt tapas': ['Our menu · unlimited tapas', 'Tapas ilimitadas'],
        'Allergie of dieetwens? Zet het in de opmerking bij je bestelling of vraag het personeel': ['Allergy or dietary need? Add it to the note with your order or ask our staff', '¿Alergia o dieta especial? Indícalo en la nota de tu pedido o pregunta al personal'],
        'Menu laden': ['Loading menu', 'Cargando la carta'],
        'niet inbegrepen': ['not included', 'no incluido'],
        'Uitverkocht': ['Sold out', 'Agotado'],
        'Jouw bestelling': ['Your order', 'Lo que vas a pedir'],
        'Je mandje is nog leeg': ['Your basket is still empty', 'Tu pedido está vacío'],
        'Totaal items': ['Total items', 'Total de artículos'],
        'Drankjes (niet inbegrepen)': ['Drinks (not included)', 'Bebidas (no incluidas)'],
        'Opmerking of allergie (optioneel)': ['Note or allergy (optional)', 'Nota o alergia (opcional)'],
        'Bijv. zonder knoflook, notenallergie': ['E.g. no garlic, nut allergy', 'Ej. sin ajo, alergia a los frutos secos'],
        'Pedir Ahora! (Bestel)': ['Pedir Ahora! (Order)', 'Pedir Ahora!'],
        'Tijd is om: verleng om te bestellen': ['Time is up: extend to order', 'Se acabó el tiempo: amplía para pedir'],
        'Bestelling versturen': ['Sending order', 'Enviando pedido'],
        'Olé! Bestelling verzonden naar de keuken': ['Olé! Order sent to the kitchen', '¡Olé! Pedido enviado a la cocina'],
        'Verstuurd naar de keuken': ['Sent to the kitchen', 'Enviado a la cocina'],
        'Tik op + om iets te kiezen': ['Tap + to choose something', 'Pulsa + para elegir algo'],
        '1 item gekozen': ['1 item chosen', '1 artículo elegido'],
        'Bekijken': ['View', 'Ver'],
        'Mandje sluiten': ['Close basket', 'Cerrar pedido'],
        'Nog geen bestellingen geplaatst vanuit deze tafel': ['No orders placed from this table yet', 'Todavía no se ha hecho ningún pedido desde esta mesa'],
        'In behandeling bij de keuken': ['Being prepared in the kitchen', 'Preparándose en la cocina'],
        'Klaar voor bezorging': ['Ready to be served', 'Listo para servir'],
        'Onderweg naar jouw tafel': ['On its way to your table', 'De camino a tu mesa'],
        'Bezorgd / Eet smakelijk': ['Served / Enjoy your meal', 'Servido / ¡Buen provecho'],
                'Jullie bestellingen, live bijgewerkt': ['Your orders, updated live', 'Vuestros pedidos, actualizados en directo'],
        'Bestellingen laden': ['Loading orders', 'Cargando pedidos'],
        'Jullie hebben nog niets besteld': ['You have not ordered anything yet', 'Todavía no habéis pedido nada'],
        'Nog geen bestellingen voor deze tafel': ['No orders for this table yet', 'Aún no hay pedidos para esta mesa'],
        'Buen provecho': ['Buen provecho! (Enjoy your meal)', 'Buen provecho'],
        'Las Tapas · Taberna española · Allergie of vraag? Tik op 🙋 bovenin': ['Las Tapas · Spanish taberna · Allergy or question? Tap 🙋 at the top', 'Las Tapas · Taberna española · ¿Alergia o pregunta? Pulsa 🙋 arriba'],

        // Status van een bestelling
        'nieuw': ['new', 'nuevo'],
        'bezig': ['in progress', 'en preparación'],
        'klaar': ['ready', 'listo'],
        'onderweg': ['on its way', 'en camino'],
        'bezorgd': ['delivered', 'servido'],
        'Nieuw': ['New', 'Nuevo'],
        'In bereiding': ['Being prepared', 'En preparación'],
        'Klaar': ['Ready', 'Listo'],
        'Onderweg': ['On its way', 'En camino'],
        'Bezorgd': ['Delivered', 'Servido'],
        'Ontvangen': ['Received', 'Recibido'],
        'Wordt bereid': ['Being prepared', 'Preparándose'],
        'Klaar om te serveren': ['Ready to serve', 'Listo para servir'],
        'Wordt gebracht': ['On its way', 'De camino a la mesa'],
        'Staat op tafel': ['Served', 'En la mesa'],

        // ---------- Klant: welkom ----------
        'Bienvenidos a Las Tapas': ['Bienvenidos a Las Tapas! (Welcome)', 'Bienvenidos a Las Tapas'],
        'Vul je gegevens in om het menu te bekijken': ['Enter your details to see the menu', 'Introduce tus datos para ver la carta'],
        'Jouw naam (Hoofdboeker)': ['Your name (main booker)', 'Tu nombre (titular de la reserva)'],
        'Volwassenen': ['Adults', 'Adultos'],
        'Senioren (65+)': ['Seniors (65+)', 'Mayores (65+)'],
        'Kinderen': ['Children', 'Niños'],
        'Kies jullie arrangement': ['Choose your package', 'Elegid vuestro menú'],
        'Arrangementen laden': ['Loading packages', 'Cargando menús'],
        'Vul eerst je naam in': ['Please enter your name first', 'Introduce primero tu nombre'],
        'Kies minimaal 1 persoon': ['Choose at least 1 person', 'Elige al menos 1 persona'],
        'Deze QR-code werkt niet (meer)': ['This QR code no longer works', 'Este código QR ya no funciona'],
        'Scan de QR-code die op jullie tafel staat, of vraag het personeel om hulp': ['Scan the QR code on your table, or ask our staff for help', 'Escanead el código QR de vuestra mesa o pedid ayuda al personal'],

        // ---------- Klant: afgesloten bezoek en aansluiten ----------
        'Jullie vorige bezoek aan deze tafel is afgesloten. Welkom! Vul jullie gegevens opnieuw in': ['Your previous visit to this table has been closed. Welcome! Please enter your details again', 'Vuestra visita anterior a esta mesa se ha cerrado. ¡Bienvenidos! Introducid de nuevo vuestros datos'],
        'Deze tafel is al in gebruik': ['This table is already in use', 'Esta mesa ya está ocupada'],
        'Hoor je bij dit gezelschap': ['Are you part of this group', '¿Formas parte de este grupo'],
        'Ja, aansluiten': ['Yes, join', 'Sí, unirme'],
        'Nee, dit is niet mijn tafel': ['No, this is not my table', 'No, esta no es mi mesa'],
        'Scan de QR-code die op je eigen tafel staat, of vraag het personeel om hulp': ['Scan the QR code on your own table, or ask our staff for help', 'Escanea el código QR de tu propia mesa o pide ayuda al personal'],
        'Aansluiten is niet gelukt': ['Joining failed', 'No se ha podido unir'],
        'een gezelschap': ['a group', 'un grupo'],
        'Jullie bezoek aan deze tafel is afgesloten. Meld je opnieuw aan': ['Your visit to this table has been closed. Please register again', 'Vuestra visita a esta mesa se ha cerrado. Registraos de nuevo'],
        'Deze tafel is niet (meer) in gebruik. Meld je opnieuw aan': ['This table is not (or no longer) in use. Please register again', 'Esta mesa no está (ya) en uso. Regístrate de nuevo'],

        // ---------- Klant: tijd en verlengen ----------
        'Jullie tijd begint bij de eerste bestelling': ['Your time starts with the first order', 'Vuestro tiempo empieza con el primer pedido'],
        'Jullie kunnen dineren tot': ['You can dine until', 'Podéis cenar hasta las'],
        'Jullie tijd zit erop': ['Your time is up', 'Se ha acabado vuestro tiempo'],
        'Tijd verlengen': ['Extend time', 'Ampliar tiempo'],
        'We hopen dat het heeft gesmaakt! Willen jullie nog wat langer blijven': ['We hope you enjoyed it! Would you like to stay a little longer', '¡Esperamos que os haya gustado! ¿Queréis quedaros un poco más'],
        'Verlengen met': ['Extending by', 'Ampliar'],
        'Jullie kunnen dan dineren tot': ['You can then dine until', 'Podréis cenar hasta las'],
        'Afrekenen': ['Pay the bill', 'Pedir la cuenta'],
        'Terug naar het menu': ['Back to the menu', 'Volver a la carta'],
        'Niet nu': ['Not now', 'Ahora no'],
        'Verlengen': ['Extend', 'Ampliar'],
        'Verlengen is niet gelukt': ['Extending failed', 'No se ha podido ampliar'],
        'Jullie tijd start pas bij de eerste bestelling': ['Your time only starts with the first order', 'Vuestro tiempo empieza con el primer pedido'],
        'Jullie tijd zit erop. Verleng de tijd om nog iets te bestellen': ['Your time is up. Extend your time to order more', 'Se ha acabado vuestro tiempo. Ampliadlo para seguir pidiendo'],
        'Deze ronde nog': ['Still available this round:', 'Quedan en esta ronda:'],
        'Deze ronde is vol. Vanaf': ['This round is full. From', 'Esta ronda está completa. A partir de las'],
        'kunnen jullie weer gerechten bestellen. Drankjes kunnen altijd': ['you can order dishes again. Drinks are always possible', 'podréis volver a pedir platos. Las bebidas se pueden pedir siempre'],
        'Deze ronde is vol. Drankjes kunnen altijd': ['This round is full. Drinks are always possible', 'Esta ronda está completa. Las bebidas se pueden pedir siempre'],

        // ---------- Klant: betalen ----------
        'Kies je betaalmethode': ['Choose how to pay', 'Elige cómo pagar'],
        'Hoe willen jullie de rekening voldoen': ['How would you like to pay the bill', '¿Cómo queréis pagar la cuenta'],
        'Online betalen': ['Pay online', 'Pagar online'],
        'Contant betalen': ['Pay in cash', 'Pagar en efectivo'],
        'Scan om te betalen': ['Scan to pay', 'Escanea para pagar'],
        'Scan deze QR-code met je telefoon om de betaling af te ronden': ['Scan this QR code with your phone to complete the payment', 'Escanea este código QR con tu móvil para completar el pago'],
        'Tik na het betalen op de knop hieronder. Een serveerster komt daarna even controleren of de betaling gelukt is': ['After paying, tap the button below. A waiter will then come to check that the payment went through', 'Después de pagar, pulsa el botón de abajo. Un camarero vendrá a comprobar que el pago se ha realizado'],
        'Ik heb betaald': ['I have paid', 'Ya he pagado'],
        'Betaling controleren': ['Checking payment', 'Comprobando el pago'],
        'Verzoek ontvangen': ['Request received', 'Solicitud recibida'],
        'Serveerster onderweg': ['Waiter on the way', 'Camarero en camino'],
        'Een moment geduld. Dit scherm sluit automatisch zodra de betaling is afgerond': ['One moment please. This screen closes automatically once the payment is complete', 'Un momento, por favor. Esta pantalla se cerrará sola cuando se complete el pago'],
        'Bedankt! De bediening heeft een melding ontvangen. Een serveerster komt even langs om te controleren of de betaling gelukt is': ['Thank you! Our staff have been notified. A waiter will come by to check that the payment went through', '¡Gracias! El personal ha recibido un aviso. Un camarero pasará a comprobar que el pago se ha realizado'],
        'De bediening heeft een melding ontvangen dat jullie contant willen betalen. Zodra een serveerster het verzoek accepteert, zie je dat hier': ['Our staff have been notified that you want to pay in cash. As soon as a waiter accepts the request, you will see it here', 'El personal ha recibido un aviso de que queréis pagar en efectivo. En cuanto un camarero acepte la solicitud, lo veréis aquí'],
        'Betaalverzoek versturen is niet gelukt': ['Sending the payment request failed', 'No se ha podido enviar la solicitud de pago'],
        'Te betalen': ['To pay', 'A pagar'],
        'Volwassene': ['Adult', 'Adulto'],
        'Senior (65+)': ['Senior (65+)', 'Mayor (65+)'],
        'Kind': ['Child', 'Niño'],

        // ---------- Bon ----------
        'Wil je een bon': ['Would you like a receipt', '¿Quieres un recibo'],
        'Geen bon': ['No receipt', 'Sin recibo'],
        'Per e-mail': ['By email', 'Por correo'],
        'Op papier': ['On paper', 'En papel'],
        'De bon wordt gemaild zodra de betaling is afgerond': ['The receipt will be emailed once the payment is complete', 'El recibo se enviará por correo cuando se complete el pago'],
        'De serveerster neemt een papieren bon voor je mee': ['The waiter will bring you a paper receipt', 'El camarero te traerá un recibo en papel'],
        'Vul een geldig e-mailadres in voor de bon': ['Please enter a valid email address for the receipt', 'Introduce un correo electrónico válido para el recibo'],
        'De bon wordt gemaild naar': ['The receipt will be emailed to', 'El recibo se enviará a'],
        'De serveerster neemt een papieren bon mee': ['The waiter will bring a paper receipt', 'El camarero traerá un recibo en papel'],
        'Bon printen': ['Print receipt', 'Imprimir recibo'],
        'Bon per e-mail naar': ['Receipt by email to', 'Recibo por correo a'],
        'gaat automatisch bij vrijmaken': ['sent automatically when freeing up the table', 'se envía automáticamente al liberar la mesa'],
        'Wil een papieren bon: print hem en neem hem mee': ['Wants a paper receipt: print it and bring it along', 'Quiere un recibo en papel: imprímelo y llévalo'],
        'E-mail voor de bon': ['Email for receipts', 'Correo para los recibos'],
        'E-mailadres (afzender)': ['Email address (sender)', 'Correo electrónico (remitente)'],
        'App-wachtwoord': ['App password', 'Contraseña de aplicación'],
        'Naam afzender': ['Sender name', 'Nombre del remitente'],
        'Mailserver': ['Mail server', 'Servidor de correo'],
        'Poort': ['Port', 'Puerto'],
        'Beveiliging': ['Security', 'Seguridad'],
        'Certificaat niet controleren': ['Do not verify certificate', 'No comprobar el certificado'],
        'Testmail sturen naar': ['Send test email to', 'Enviar correo de prueba a'],
        'leeg = naar het afzenderadres': ['empty = to the sender address', 'vacío = a la dirección del remitente'],
        'Opslaan & testmail sturen': ['Save & send test email', 'Guardar y enviar correo de prueba'],

        // ---------- Klant: serveerster roepen ----------
        'Waarmee kunnen we helpen? Kies een onderwerp': ['How can we help? Choose a topic', '¿En qué podemos ayudar? Elige un tema'],
        'Ik heb een vraag': ['I have a question', 'Tengo una pregunta'],
        'Probleem met bestelling': ['Problem with my order', 'Problema con el pedido'],
        'Allergie of dieet': ['Allergy or diet', 'Alergia o dieta'],
        'Iets anders': ['Something else', 'Otra cosa'],
        'Toelichting (optioneel)': ['Details (optional)', 'Detalles (opcional)'],
        'Bijv. we missen nog de patatas bravas': ['E.g. we are still missing the patatas bravas', 'Ej. nos faltan las patatas bravas'],
        'Roep een serveerster': ['Call a waiter', 'Llamar a un camarero'],
        'Je oproep is verstuurd. Een serveerster komt zo snel mogelijk': ['Your call has been sent. A waiter will come as soon as possible', 'Tu llamada se ha enviado. Un camarero vendrá lo antes posible'],
        'Oproep afgehandeld': ['Request handled', 'Solicitud atendida'],
        'Vul eerst je naam in en kies een arrangement': ['Please enter your name and choose a package first', 'Introduce primero tu nombre y elige un menú'],
        'Roepen is niet gelukt': ['Calling failed', 'No se ha podido llamar'],
        'Annuleren is niet gelukt': ['Cancelling failed', 'No se ha podido cancelar'],
        'Er is al een serveerster geroepen voor deze tafel': ['A waiter has already been called for this table', 'Ya se ha llamado a un camarero para esta mesa'],

        // ---------- Klant: beoordeling ----------
        'Hoe was het bij Las Tapas': ['How was your visit to Las Tapas', '¿Qué tal en Las Tapas'],
        'Bedankt voor jullie bezoek! Laat je weten hoe je het vond': ['Thank you for your visit! Would you tell us what you thought', '¡Gracias por vuestra visita! ¿Nos contáis qué os ha parecido'],
        'Tik op de sterren': ['Tap the stars', 'Pulsa las estrellas'],
        'Slecht': ['Poor', 'Malo'],
        'Matig': ['Fair', 'Regular'],
        'Goed': ['Good', 'Bueno'],
        'Erg goed': ['Very good', 'Muy bueno'],
        'Wil je nog iets kwijt? (optioneel)': ['Anything else you would like to share? (optional)', '¿Algo más que quieras contarnos? (opcional)'],
        'Wat vond je lekker, wat kan beter': ['What did you enjoy, what could be better', '¿Qué te ha gustado y qué podemos mejorar'],
        'Beoordeling versturen': ['Send review', 'Enviar valoración'],
        'Overslaan': ['Skip', 'Omitir'],
        'Bedankt voor je beoordeling. Graag tot ziens bij Las Tapas': ['Thank you for your review. We hope to see you again at Las Tapas', 'Gracias por tu valoración. ¡Hasta pronto en Las Tapas'],
        'Er is geen recent bezoek gevonden om te beoordelen': ['No recent visit was found to review', 'No se ha encontrado ninguna visita reciente para valorar'],
        'Kies 1 tot 5 sterren': ['Choose 1 to 5 stars', 'Elige de 1 a 5 estrellas'],
        '1 ster': ['1 star', '1 estrella'],

        // ---------- Servermeldingen ----------
        'Deze QR-code is ongeldig of verouderd. Vraag het personeel om hulp': ['This QR code is invalid or outdated. Please ask our staff for help', 'Este código QR no es válido o está caducado. Pide ayuda al personal'],
        'Meld je eerst aan bij deze tafel (naam en arrangement kiezen)': ['Please register at this table first (enter name and choose a package)', 'Regístrate primero en esta mesa (nombre y menú)'],
        'Meld je eerst aan bij deze tafel': ['Please register at this table first', 'Regístrate primero en esta mesa'],
        'Jullie zijn al aan het afrekenen': ['You are already paying', 'Ya estáis pagando'],
        'Je mandje is leeg': ['Your basket is empty', 'Tu pedido está vacío'],
        'Er loopt al een betaling voor deze tafel': ['A payment is already in progress for this table', 'Ya hay un pago en curso para esta mesa'],
        'Deze tafel is niet in gebruik': ['This table is not in use', 'Esta mesa no está en uso'],
        'Er is geen open betaalverzoek voor deze tafel': ['There is no open payment request for this table', 'No hay ninguna solicitud de pago abierta para esta mesa'],
        'Geen toegang: log in als medewerker om dit te doen': ['No access: log in as staff to do this', 'Sin acceso: inicia sesión como empleado para hacerlo'],
        'Geef minimaal 1 persoon op': ['Enter at least 1 person', 'Indica al menos 1 persona'],
        'Onbekend arrangement': ['Unknown package', 'Menú desconocido'],
        'Bestelling niet gevonden': ['Order not found', 'Pedido no encontrado'],
        'Deze oproep bestaat niet meer (misschien geannuleerd door de gast)': ['This request no longer exists (perhaps cancelled by the guest)', 'Esta llamada ya no existe (quizá la canceló el cliente)'],
        'Fout bij bestellen': ['Error while ordering', 'Error al hacer el pedido'],

        // ---------- Chef ----------
        'Las Tapas - Cocina (Keuken)': ['Las Tapas - Kitchen', 'Las Tapas - Cocina'],
        'Live overzicht van binnenkomende Spaanse lekkernijen': ['Live overview of incoming Spanish delicacies', 'Resumen en directo de las delicias españolas entrantes'],
        'Openstaande Orders': ['Open Orders', 'Pedidos pendientes'],
        'Actieve Orders': ['Active Orders', 'Pedidos activos'],
        'Voltooide Orders': ['Completed Orders', 'Pedidos completados'],
        'Bezorgstatus': ['Delivery status', 'Estado de entrega'],
        'Tapa / Gerecht': ['Tapa / Dish', 'Tapa / Plato'],
        'Start Bereiding': ['Start preparing', 'Empezar a preparar'],
        'Gereed melden': ['Mark as ready', 'Marcar como listo'],
        'Klaar melden': ['Mark as ready', 'Marcar como listo'],
        'Weet je zeker dat je deze bestelling wilt verwijderen': ['Are you sure you want to delete this order', '¿Seguro que quieres eliminar este pedido'],
        'Fout bij opslaan van het gerecht': ['Error saving the dish', 'Error al guardar el plato'],
        'Verwijderen is niet gelukt': ['Deleting failed', 'No se ha podido eliminar'],
        'Geen openstaande bestellingen': ['No open orders', 'No hay pedidos pendientes'],
        'Status wordt beheerd door bediening (Verdwijnt 10 min na bezorging uit dit overzicht)': ['Status is managed by the service staff (Disappears from this overview 10 min after delivery)', 'El estado lo gestiona la sala (Desaparece de esta lista 10 min después de servir)'],
        'Status 1e Etage tafels': ['Status of 1st floor tables', 'Estado mesas 1ª planta'],
        'Status 2e Etage tafels': ['Status of 2nd floor tables', 'Estado mesas 2ª planta'],
        '1e Etage (Tafels 1 - 10)': ['1st floor (Tables 1 - 10)', '1ª planta (Mesas 1 - 10)'],
        '2e Etage (Tafels 11 - 20)': ['2nd floor (Tables 11 - 20)', '2ª planta (Mesas 11 - 20)'],
        '1e Etage': ['1st floor', '1ª planta'],
        '2e Etage': ['2nd floor', '2ª planta'],
        'Naar Serveerster Scherm': ['To service screen', 'A la sala'],
        'Naar Bediening': ['To service', 'A la sala'],
        'Naar Keuken Scherm': ['To kitchen screen', 'A la cocina'],
        'Naar Keuken': ['To kitchen', 'A la cocina'],

        'Aangepast door de keuken': ['Changed by the kitchen', 'Modificado por la cocina'],
        'Aangepast door de bar': ['Changed by the bar', 'Modificado por la barra'],
        'Hoort bij deze bestelling:': ['Part of this order:', 'Forma parte de este pedido:'],
        'Het andere deel is nog niet klaar. Wacht even als je alles in één keer wilt brengen.': ['The other part is not ready yet. Wait a moment if you want to bring everything at once.', 'La otra parte aún no está lista. Espera un momento si quieres llevarlo todo de una vez.'],
        'Bestelling aanpassen': ['Edit order', 'Modificar pedido'],
        'Gerecht toevoegen': ['Add dish', 'Añadir plato'],
        'Alle gerechten zijn weggehaald. Bij opslaan wordt de hele bestelling verwijderd': ['All dishes have been removed. Saving will delete the whole order', 'Se han quitado todos los platos. Al guardar se eliminará todo el pedido'],
        'Deze bestelling is al klaar en kan niet meer worden aangepast': ['This order is already ready and can no longer be changed', 'Este pedido ya está listo y no se puede modificar'],
        'Minder': ['Less', 'Menos'],
        'Meer': ['More', 'Más'],
        'Weghalen': ['Remove', 'Quitar'],

        // ---------- Foto's van gerechten ----------
        'Foto toevoegen': ['Add photo', 'Añadir foto'],
        'Foto wijzigen': ['Change photo', 'Cambiar foto'],
        'Foto verwijderen': ['Delete photo', 'Eliminar foto'],
        'Andere foto kiezen': ['Choose another photo', 'Elegir otra foto'],
        'Foto weghalen': ['Remove photo', 'Quitar foto'],
        'Nog geen foto': ['No photo yet', 'Aún sin foto'],
        'Foto uploaden': ['Uploading photo', 'Subiendo foto'],
        'optioneel': ['optional', 'opcional'],
        'Foto opgeslagen. Gasten zien hem meteen op het menu': ['Photo saved. Guests will see it on the menu straight away', 'Foto guardada. Los clientes la verán enseguida en la carta'],
        'Foto verwijderd': ['Photo deleted', 'Foto eliminada'],
        'Dit bestand is geen foto': ['This file is not a photo', 'Este archivo no es una foto'],
        'Uploaden is mislukt': ['Upload failed', 'No se ha podido subir'],
        'Bezig met opslaan': ['Saving', 'Guardando'],
        'Toevoegen aan bestelling': ['Add to order', 'Añadir al pedido'],
        'Sluiten': ['Close', 'Cerrar'],
        'Voorbeeld van de foto': ['Photo preview', 'Vista previa de la foto'],
        'Dit bestand is geen foto. Kies een JPG-, PNG- of WEBP-afbeelding': ['This file is not a photo. Choose a JPG, PNG or WEBP image', 'Este archivo no es una foto. Elige una imagen JPG, PNG o WEBP'],
        'De foto is te groot om te uploaden. Kies een kleinere foto': ['The photo is too large to upload. Choose a smaller photo', 'La foto es demasiado grande. Elige una foto más pequeña'],
        'Je sessie is verlopen. Ververs de pagina en probeer het opnieuw': ['Your session has expired. Refresh the page and try again', 'Tu sesión ha caducado. Actualiza la página e inténtalo de nuevo'],

        // ---------- Bar ----------
        'Las Tapas - Barra (Bar)': ['Las Tapas - Barra (Bar)', 'Las Tapas - Barra'],
        'Live overzicht van bestelde drankjes': ['Live overview of ordered drinks', 'Resumen en directo de las bebidas pedidas'],
        'Naar Bar': ['To bar', 'A la barra'],
        'Bar': ['Bar', 'Barra'],
        'Barman / barvrouw (Bar)': ['Bartender (Bar)', 'Camarero/a de barra (Barra)'],
        'Geen rol: klik op Aanpassen': ['No role: click Edit', 'Sin rol: pulsa Editar'],
        'Aan een barbestelling kun je alleen drankjes toevoegen': ['You can only add drinks to a bar order', 'A un pedido de barra solo se pueden añadir bebidas'],
        'Aan een keukenbestelling kun je geen drankjes toevoegen': ['You cannot add drinks to a kitchen order', 'A un pedido de cocina no se pueden añadir bebidas'],

        // ---------- Privacy ----------
        'Privacyverklaring': ['Privacy statement', 'Política de privacidad'],
        'privacyverklaring': ['privacy statement', 'política de privacidad'],
        'We gebruiken je naam alleen om je bestelling aan deze tafel te koppelen. Een voornaam is genoeg': ['We only use your name to link your order to this table. A first name is enough', 'Solo usamos tu nombre para vincular tu pedido a esta mesa. Basta con el nombre de pila'],
        'Alleen voor de bereiding. Wordt gewist na jullie bezoek': ['Only used for preparation. Deleted after your visit', 'Solo para la preparación. Se borra después de vuestra visita'],
        'De bon wordt gemaild zodra de betaling is afgerond. We bewaren je e-mailadres niet in ons systeem': ['The receipt will be emailed once the payment is complete. We do not keep your email address in our system', 'El recibo se enviará cuando se complete el pago. No guardamos tu correo en nuestro sistema'],
        'Je naam en deze tekst worden na een paar maanden gewist; de sterren bewaren we anoniem': ['Your name and this text are deleted after a few months; we keep the stars anonymously', 'Tu nombre y este texto se borran a los pocos meses; las estrellas se guardan de forma anónima'],
        'naam gewist': ['name deleted', 'nombre borrado'],
        'Anoniem': ['Anonymous', 'Anónimo'],
        'Privacy: bewaartermijnen': ['Privacy: retention periods', 'Privacidad: plazos de conservación'],
        'Gegevens worden één keer per dag automatisch opgeruimd. Namen van gasten en de tekst van beoordelingen worden na de gekozen periode gewist; de sterren en bedragen blijven anoniem bewaard voor de statistieken. Financiële gegevens moet je wettelijk minimaal 7 jaar bewaren (fiscale bewaarplicht). Zie ook de': ['Data is cleaned up automatically once a day. Guest names and review texts are deleted after the chosen period; stars and amounts are kept anonymously for the statistics. By law you must keep financial data for at least 7 years (tax retention obligation). See also the', 'Los datos se limpian automáticamente una vez al día. Los nombres de los clientes y los textos de las valoraciones se borran tras el periodo elegido; las estrellas y los importes se guardan de forma anónima para las estadísticas. Por ley, los datos financieros deben conservarse al menos 7 años (obligación fiscal). Consulta también la'],
        'Namen en beoordelingsteksten wissen na ... maanden': ['Delete names and review texts after ... months', 'Borrar nombres y textos de valoraciones tras ... meses'],
        'Financiële gegevens verwijderen na ... jaar': ['Delete financial data after ... years', 'Eliminar datos financieros tras ... años'],
        'minimaal 7': ['at least 7', 'mínimo 7'],
        'Bewaartermijnen opslaan': ['Save retention periods', 'Guardar plazos'],

        // ---------- Serveerster ----------
        'Las Tapas - Sala (Bediening)': ['Las Tapas - Sala (Service)', 'Las Tapas - Sala'],
        'Overzicht voor het uitserveren, bezorgen en betaalverzoeken': ['Overview for serving, delivering and payment requests', 'Resumen para servir, entregar y solicitudes de pago'],
        'Onderweg / Uitgeserveerd': ['On its way / Served', 'En camino / Servido'],
        'Betaalverzoeken': ['Payment requests', 'Solicitudes de pago'],
        'Oproepen': ['Calls', 'Llamadas'],
        'Meenemen naar tafel': ['Take to table', 'Llevar a la mesa'],
        'Bezorgd op tafel': ['Delivered to table', 'Servido en la mesa'],
        'Uitgeserveerd': ['Served', 'Servido'],
        'Verdwijnt 10 min na bezorging uit dit overzicht': ['Disappears from this overview 10 min after delivery', 'Desaparece de esta lista 10 min después de servir'],
        'Wil contant afrekenen aan tafel': ['Wants to pay in cash at the table', 'Quiere pagar en efectivo en la mesa'],
        'Online betaald: controleer of de betaling gelukt is': ['Paid online: check that the payment went through', 'Pagado online: comprueba que el pago se ha realizado'],
        'Betalen gewenst': ['Wants to pay', 'Quiere pagar'],
        'Wil betalen': ['Wants to pay', 'Quiere pagar'],
        'Betaling onderweg': ['Payment in progress', 'Pago en curso'],
        'WIL BETALEN': ['WANTS TO PAY', 'QUIERE PAGAR'],
        'BETALING ONDERWEG': ['PAYMENT IN PROGRESS', 'PAGO EN CURSO'],
        'TIJD IS OM': ['TIME IS UP', 'TIEMPO AGOTADO'],
        'ROEPT OM HULP': ['CALLING FOR HELP', 'PIDE AYUDA'],
        'Accepteren (ik kom naar de tafel)': ['Accept (I will go to the table)', 'Aceptar (voy a la mesa)'],
        'Accepteren (ik ga controleren)': ['Accept (I will check)', 'Aceptar (voy a comprobar)'],
        'Accepteren (ik kom eraan)': ['Accept (on my way)', 'Aceptar (voy enseguida)'],
        'Contant ontvangen & tafel vrijmaken': ['Cash received & free up table', 'Efectivo recibido y liberar mesa'],
        'Betaling gecontroleerd & tafel vrijmaken': ['Payment checked & free up table', 'Pago comprobado y liberar mesa'],
        'Betaald & vrijmaken': ['Paid & free up', 'Pagado y liberar'],
        'Vertrokken zonder betalen': ['Left without paying', 'Se fue sin pagar'],
        'Afgehandeld': ['Handled', 'Atendido'],
        'Roept om hulp': ['Calling for help', 'Pide ayuda'],
        'Heeft een vraag': ['Has a question', 'Tiene una pregunta'],
        'Vraagt om een serveerster': ['Asks for a waiter', 'Pide un camarero'],
        'Rekening tot nu toe': ['Bill so far', 'Cuenta hasta ahora'],
        'Tijd is om': ['Time is up', 'Tiempo agotado'],
        'Dineren tot': ['Dining until', 'Cenando hasta las'],
        'Nog niets besteld': ['Nothing ordered yet', 'Aún no ha pedido nada'],
        'Geaccepteerd door': ['Accepted by', 'Aceptado por'],
        'Bereid door': ['Prepared by', 'Preparado por'],
        'Onderweg met': ['On its way with', 'En camino con'],
        'Bezorgd door': ['Delivered by', 'Servido por'],
        'zojuist': ['just now', 'ahora mismo'],
        'Er zijn op dit moment geen tafels in gebruik': ['There are no tables in use at the moment', 'Ahora mismo no hay mesas en uso'],
        'Geen gasten die om hulp vragen op dit moment': ['No guests asking for help at the moment', 'Ahora mismo ningún cliente pide ayuda'],
        'Geen openstaande betaalverzoeken op dit moment': ['No open payment requests at the moment', 'Ahora mismo no hay solicitudes de pago pendientes'],
        'Gezelschap van': ['Party of', 'Grupo de'],
        'Todo listo! Geen orders in deze categorie': ['Todo listo! No orders in this category', 'Todo listo! No hay pedidos en esta categoría'],
        'Geen bestellingen gevonden in deze categorie': ['No orders found in this category', 'No se han encontrado pedidos en esta categoría'],
        'Geen actieve bestellingen': ['No active orders', 'No hay pedidos activos'],
        'Geen bestellingen in deze categorie': ['No orders in this category', 'No hay pedidos en esta categoría'],
                'Geen betaalverzoeken op dit moment': ['No payment requests at the moment', 'Ahora mismo no hay solicitudes de pago'],
        'Accepteren is niet gelukt': ['Accepting failed', 'No se ha podido aceptar'],
        'Afhandelen is niet gelukt': ['Handling failed', 'No se ha podido atender'],
        'Fout bij accepteren betaalverzoek': ['Error accepting the payment request', 'Error al aceptar la solicitud de pago'],
        'Fout bij vrijmaken tafel': ['Error freeing up the table', 'Error al liberar la mesa'],

        // ---------- Voorraad ----------
        'Las Tapas - Voorraadbeheer': ['Las Tapas - Stock', 'Las Tapas - Existencias'],
        'Overzicht en beheer van ingrediënten en drankjes': ['Overview and management of ingredients and drinks', 'Resumen y gestión de ingredientes y bebidas'],
        'Actuele Voorraad': ['Current stock', 'Existencias actuales'],
        'Hier kun je zien hoeveel er nog op voorraad is per gerecht of product. Bij 0 stuks is het uitverkocht, tot en met 5 stuks kleurt het rood': ['Here you can see how much is left in stock per dish or product. At 0 it is sold out, up to 5 it turns red', 'Aquí puedes ver cuánto queda de cada plato o producto. Con 0 está agotado; hasta 5 se marca en rojo'],
        'Product / Gerecht': ['Product / Dish', 'Producto / Plato'],
        'Aantal op voorraad': ['Quantity in stock', 'Cantidad en existencias'],
        'Actie': ['Action', 'Acción'],
        'Acties': ['Actions', 'Acciones'],
        'Voorraad laden': ['Loading stock', 'Cargando existencias'],
        'Geen voorraadgegevens gevonden': ['No stock data found', 'No se han encontrado datos de existencias'],
        'Fout bij laden van voorraadgegevens': ['Error loading stock data', 'Error al cargar las existencias'],
        'Kon voorraad niet bijwerken': ['Could not update stock', 'No se han podido actualizar las existencias'],
        'Uitverkocht (0)': ['Sold out (0)', 'Agotado (0)'],

        // ---------- Inloggen ----------
        'Las Tapas - Medewerker Login': ['Las Tapas - Staff login', 'Las Tapas - Acceso del personal'],
        'Taberna española': ['Spanish taberna', 'Taberna española'],
        'Inloggen voor personeel': ['Staff login', 'Acceso para el personal'],
        'Gebruikersnaam': ['Username', 'Usuario'],
        'Wachtwoord': ['Password', 'Contraseña'],
        'Inloggen': ['Log in', 'Entrar'],
        'De database is niet bereikbaar. Staat WampServer aan': ['The database cannot be reached. Is WampServer running', 'No se puede acceder a la base de datos. ¿Está WampServer en marcha'],
        'Te veel foute pogingen. Probeer het over 15 minuten opnieuw': ['Too many failed attempts. Please try again in 15 minutes', 'Demasiados intentos fallidos. Inténtalo de nuevo dentro de 15 minutos'],

        // ---------- Beheer: algemeen ----------
        'Las Tapas - Beheer': ['Las Tapas - Management', 'Las Tapas - Gestión'],
        'Medewerkers': ['Staff', 'Personal'],
        'Menu & prijzen': ['Menu & prices', 'Carta y precios'],
        'Statistieken': ['Statistics', 'Estadísticas'],
        'QR-codes': ['QR codes', 'Códigos QR'],
        'Alle tafels': ['All tables', 'Todas las mesas'],
        '1e etage': ['1st floor', '1ª planta'],
        '2e etage': ['2nd floor', '2ª planta'],
        'Groot tonen': ['Show large', 'Mostrar en grande'],
        'Tafel nr': ['Table no', 'Mesa nº'],
        'Tik op een QR-code om hem schermvullend te tonen. "Printen" print de tafels die je nu ziet (alle, of één etage)': ['Tap a QR code to show it full screen. "Print" prints the tables you see now (all, or one floor)', 'Pulsa un código QR para verlo a pantalla completa. "Imprimir" imprime las mesas que ves ahora (todas o una planta)'],
        'Scan om in te loggen': ['Scan to log in', 'Escanea para entrar'],
        'Hang deze QR-code op een plek waar alleen personeel komt, bijvoorbeeld in de keuken': ['Put this QR code somewhere only staff can see it, for example in the kitchen', 'Coloca este código QR donde solo lo vea el personal, por ejemplo en la cocina'],
        'Serveerster': ['Waiter', 'Camarero/a'],
        'Opgeslagen': ['Saved', 'Guardado'],
        'Toegevoegd': ['Added', 'Añadido'],
        'Verwijderd': ['Deleted', 'Eliminado'],

        // ---------- Beheer: medewerkers ----------
        'Medewerkers Overzicht': ['Staff overview', 'Resumen del personal'],
        'ID': ['ID', 'ID'],
        'Rol': ['Role', 'Rol'],
        'Jijzelf': ['You', 'Tú'],
        'Nieuwe medewerker toevoegen': ['Add new staff member', 'Añadir nuevo empleado'],
        'Medewerker aanpassen': ['Edit staff member', 'Editar empleado'],
        'Volledige naam': ['Full name', 'Nombre completo'],
        'Gebruikersnaam (voor inlog)': ['Username (for login)', 'Usuario (para entrar)'],
        'Nieuw wachtwoord': ['New password', 'Nueva contraseña'],
        'leeg laten om niet te wijzigen': ['leave empty to keep it', 'déjalo vacío para no cambiarla'],
        'Chef (Keuken)': ['Chef (Kitchen)', 'Chef (Cocina)'],
        'Serveerster (Bediening)': ['Waiter (Service)', 'Camarero/a (Sala)'],
        'Baas (Eigenaar / Beheer)': ['Boss (Owner / Admin)', 'Jefe (Propietario / Administración)'],
        'Medewerker opslaan': ['Save staff member', 'Guardar empleado'],
        'Wijzigingen opslaan': ['Save changes', 'Guardar cambios'],
        'Medewerker toegevoegd': ['Staff member added', 'Empleado añadido'],
        'Wijzigingen opgeslagen': ['Changes saved', 'Cambios guardados'],
        'Medewerker verwijderd': ['Staff member deleted', 'Empleado eliminado'],
        'Vul een naam en gebruikersnaam in': ['Enter a name and username', 'Introduce un nombre y un usuario'],
        'Kies een wachtwoord van minimaal 4 tekens': ['Choose a password of at least 4 characters', 'Elige una contraseña de al menos 4 caracteres'],
        'Bijv. Carlos de Kok': ['E.g. Carlos the Cook', 'Ej. Carlos el Cocinero'],
        'Bijv. carlos': ['E.g. carlos', 'Ej. carlos'],

        // ---------- Beheer: menu & prijzen ----------
        'Menu': ['Menu', 'Carta'],
        'Nieuw gerecht': ['New dish', 'Nuevo plato'],
        'Instellingen': ['Settings', 'Ajustes'],
        'Kies een gerecht': ['Choose a dish', 'Elige un plato'],
        'Kies een arrangement': ['Choose a package', 'Elige un menú'],
        '(nog geen gerechten in deze categorie)': ['(no dishes in this category yet)', '(aún no hay platos en esta categoría)'],
        'nog geen gerechten in deze categorie': ['no dishes in this category yet', 'aún no hay platos en esta categoría'],
        'Prijs (€)': ['Price (€)', 'Precio (€)'],
        'betalen gasten met een arrangement zonder drank': ['paid by guests with a package without drinks', 'lo pagan los clientes con un menú sin bebidas'],
        'lager = hoger in de lijst': ['lower = higher in the list', 'menor = más arriba en la lista'],
        'Zichtbaar op het menu': ['Visible on the menu', 'Visible en la carta'],
        'Toevoegen aan menu': ['Add to menu', 'Añadir a la carta'],
        'Wijzigingen zijn direct zichtbaar voor klanten. Is iets tijdelijk op? Haal dan het vinkje "Zichtbaar op het menu" weg': ['Changes are visible to guests immediately. Is something temporarily unavailable? Untick "Visible on the menu"', 'Los cambios se ven al instante. ¿Algo está agotado temporalmente? Desmarca "Visible en la carta"'],
        'Vul de gegevens in en kies een categorie. Een prijs vul je alleen in bij drankjes. Na het toevoegen staat het gerecht meteen op het menu (en krijgt het een voorraad van 25)': ['Fill in the details and choose a category. Only drinks have a price. Once added, the dish appears on the menu straight away (with a stock of 25)', 'Rellena los datos y elige una categoría. Solo las bebidas tienen precio. Al añadirlo, el plato aparece enseguida en la carta (con 25 unidades en existencias)'],
        'Duur (minuten)': ['Duration (minutes)', 'Duración (minutos)'],
        'Prijs volwassene (€)': ['Adult price (€)', 'Precio adulto (€)'],
        'Prijs senior 65+ (€)': ['Senior 65+ price (€)', 'Precio mayores 65+ (€)'],
        'Prijs kind (€)': ['Child price (€)', 'Precio niño (€)'],
        'Drankjes inbegrepen': ['Drinks included', 'Bebidas incluidas'],
        'Nieuw arrangement': ['New package', 'Nuevo menú'],
        'Nieuw arrangement toevoegen': ['Add new package', 'Añadir nuevo menú'],
        'Arrangement toevoegen': ['Add package', 'Añadir menú'],
        'Bijv. Lunch Tapas (1u)': ['E.g. Lunch Tapas (1h)', 'Ej. Tapas de mediodía (1h)'],
        'De naam van een bestaand arrangement kan niet worden gewijzigd, omdat tafels ernaar verwijzen': ['The name of an existing package cannot be changed, because tables refer to it', 'El nombre de un menú existente no se puede cambiar porque las mesas lo usan'],
        'Arrangement definitief verwijderen': ['Delete package permanently', '¿Eliminar el menú definitivamente'],
        'Aantal minuten per keer': ['Minutes per extension', 'Minutos por ampliación'],
        'Prijs per persoon (€)': ['Price per person (€)', 'Precio por persona (€)'],
        'Ronde-limiet': ['Round limit', 'Límite por ronda'],
        'Max. gerechten per persoon': ['Max. dishes per person', 'Máx. platos por persona'],
        '0 = geen limiet': ['0 = no limit', '0 = sin límite'],
        'Duur van een ronde (minuten)': ['Round length (minutes)', 'Duración de una ronda (minutos)'],
        'Drankjes tellen niet mee voor de ronde-limiet': ['Drinks do not count towards the round limit', 'Las bebidas no cuentan para el límite por ronda'],
        'Achtergelaten tafels': ['Abandoned tables', 'Mesas abandonadas'],
        'Automatisch opruimen na ... uur zonder activiteit': ['Clean up automatically after ... hours without activity', 'Cerrar automáticamente tras ... horas sin actividad'],
        'Instellingen opslaan': ['Save settings', 'Guardar ajustes'],

        // ---------- Beheer: statistieken ----------
        'Vandaag': ['Today', 'Hoy'],
        'Afgelopen 7 dagen': ['Last 7 days', 'Últimos 7 días'],
        'Afgelopen 30 dagen': ['Last 30 days', 'Últimos 30 días'],
        'Alles': ['All', 'Todo'],
        'Omzet': ['Revenue', 'Facturación'],
        'Betaalde tafels': ['Paid tables', 'Mesas pagadas'],
        'Gemiddeld per tafel': ['Average per table', 'Media por mesa'],
        'Gemiddeld per gast': ['Average per guest', 'Media por cliente'],
        'Losse drankjes': ['Separate drinks', 'Bebidas aparte'],
        'Tijd verlengd': ['Time extended', 'Tiempo ampliado'],
        'Omzet per dag': ['Revenue per day', 'Facturación por día'],
        'Per arrangement': ['Per package', 'Por menú'],
        'Per betaalmethode': ['Per payment method', 'Por forma de pago'],
        'Populairste gerechten': ['Most popular dishes', 'Platos más populares'],
        'Populairste drankjes': ['Most popular drinks', 'Bebidas más populares'],
        'Drukste uren': ['Busiest hours', 'Horas de más actividad'],
        'Aantal bestelde gerechten en drankjes per uur': ['Number of dishes and drinks ordered per hour', 'Número de platos y bebidas pedidos por hora'],
        'Nog geen gegevens in deze periode': ['No data in this period yet', 'Aún no hay datos en este periodo'],
        'Beoordelingen van gasten': ['Guest reviews', 'Valoraciones de clientes'],
        'Nog geen beoordelingen in deze periode. Gasten kunnen een beoordeling geven nadat ze hebben betaald': ['No reviews in this period yet. Guests can leave a review after paying', 'Aún no hay valoraciones en este periodo. Los clientes pueden valorar después de pagar'],
        'Laatst afgesloten tafels': ['Recently closed tables', 'Últimas mesas cerradas'],
        'Nog geen afgesloten tafels in deze periode. Tafels komen hier zodra een serveerster ze vrijmaakt': ['No closed tables in this period yet. Tables appear here once a waiter frees them up', 'Aún no hay mesas cerradas en este periodo. Aparecen aquí cuando un camarero las libera'],
        'Afgerond': ['Closed', 'Cerrada'],
        'Niet betaald': ['Not paid', 'No pagado'],
        'Contant': ['Cash', 'Efectivo'],
        'Online': ['Online', 'Online'],
        'Automatisch opgeruimd': ['Cleaned up automatically', 'Cerrada automáticamente'],
        'Personeel': ['Staff', 'Personal'],

        // ---------- Beheer: QR-codes ----------
        'QR-codes voor de tafels': ['QR codes for the tables', 'Códigos QR de las mesas'],
        'Elke QR-code bevat een geheime code voor die tafel': ['Each QR code contains a secret code for that table', 'Cada código QR contiene un código secreto para esa mesa'],
        'Oude QR-codes (zonder code) werken niet meer': ['Old QR codes (without a code) no longer work', 'Los códigos QR antiguos (sin código) ya no funcionan'],
        'print deze nieuwe versie en vervang de oude op de tafels. De QR-code voor medewerkers staat op een aparte pagina': ['print this new version and replace the old ones on the tables. The QR code for staff is on a separate page', 'imprime esta nueva versión y sustituye los antiguos en las mesas. El código QR del personal está en otra página'],
        'QR-code medewerkers': ['Staff QR code', 'Código QR del personal'],
        'Adres van de klantpagina': ['Address of the guest page', 'Dirección de la página de clientes'],
        'QR-codes maken': ['Create QR codes', 'Crear códigos QR'],
        'Printen': ['Print', 'Imprimir'],
        'Scan om te bestellen': ['Scan to order', 'Escanea para pedir'],
        'Kopieer link': ['Copy link', 'Copiar enlace'],
        'Gekopieerd': ['Copied', 'Copiado'],
        'Kopieer deze link': ['Copy this link', 'Copia este enlace'],
        'Inloggen personeel': ['Staff login', 'Acceso del personal'],
        'Alleen voor medewerkers': ['Staff only', 'Solo para el personal'],
        'Scan met je telefoon of tablet om in te loggen als chef, serveerster of beheerder': ['Scan with your phone or tablet to log in as chef, waiter or manager', 'Escanea con tu móvil o tableta para entrar como chef, camarero o administrador'],
        'Print QR-code': ['Print QR code', 'Imprimir código QR'],
        // ---------- Sprint-aanpassingen ----------
        // ---------- Reserveren, plattegrond en menukaart ----------
        'Openingstijden': ['Opening hours', 'Horario'],
        'Gesloten': ['Closed', 'Cerrado'],
        'Op deze dag zijn we gesloten. Kies een andere dag': ['We are closed on this day. Choose another day', 'Este día estamos cerrados. Elige otro día'],
        'Op deze dag zijn we gesloten. Kies een andere datum': ['We are closed on this day. Choose another date', 'Este día estamos cerrados. Elige otra fecha'],
        'Op deze dag zijn we gesloten': ['We are closed on this day', 'Este día estamos cerrados'],
        'Nog niet te reserveren': ['Not yet bookable', 'Todavía no se puede reservar'],
        'Vorige maand': ['Previous month', 'Mes anterior'],
        'Volgende maand': ['Next month', 'Mes siguiente'],
        'Kies een geldige datum en tijd (binnen de openingstijden)': ['Choose a valid date and time (within opening hours)', 'Elige una fecha y hora válidas (dentro del horario)'],
        'Vink een dag uit als jullie dan gesloten zijn. Sluiten om 00:00 betekent 12 uur \'s nachts. Gasten kunnen reserveren vanaf de openingstijd tot (sluitingstijd − duur van een reservering)': ['Untick a day when you are closed. Closing at 00:00 means midnight. Guests can book from opening time until (closing time − length of a reservation)', 'Desmarca un día si estáis cerrados. Cerrar a las 00:00 significa medianoche. Los clientes pueden reservar desde la apertura hasta (cierre − duración de una reserva)'],
        'Komende reserveringen': ['Upcoming reservations', 'Próximas reservas'],
        'Geen reserveringen in de 14 dagen hierna': ['No reservations in the next 14 days', 'No hay reservas en los próximos 14 días'],
        'reservering': ['reservation', 'reserva'],
        'gasten': ['guests', 'clientes'],
        'vanaf': ['from', 'desde'],
        'Geen reserveringen op deze dag. Hierboven zie je de eerstvolgende dagen met reserveringen': ['No reservations on this day. Above you can see the next days with reservations', 'No hay reservas este día. Arriba ves los próximos días con reservas'],
        'Deze reservering kunnen we niet vinden. Controleer de link uit je e-mail. Heb je geannuleerd? Geannuleerde reserveringen worden na 15 minuten verwijderd': ['We cannot find this reservation. Check the link in your email. Did you cancel? Cancelled reservations are deleted after 15 minutes', 'No encontramos esta reserva. Comprueba el enlace de tu correo. ¿La has cancelado? Las reservas canceladas se borran a los 15 minutos'],
        'Wie komt er wanneer, en aan welke tafel': ['Who is coming when, and at which table', 'Quién viene, cuándo y en qué mesa'],
        'Onderdelen': ['Sections', 'Secciones'],
        'Werkplek': ['Workstation', 'Puesto'],
        'Beheer': ['Management', 'Gestión'],
        'Je bent hier': ['You are here', 'Estás aquí'],
        "Pagina's": ['Pages', 'Páginas'],
        'Allergieën': ['Allergies', 'Alergias'],
        'aangeschoven': ['pushed together', 'juntas'],
        'Welkom! We hebben je reservering gevonden': ['Welcome! We found your reservation', '¡Bienvenidos! Hemos encontrado tu reserva'],
        'Let op: je had tafel': ['Note: you had booked table', 'Atención: habías reservado la mesa'],
        'gereserveerd. Geen probleem, de bediening weet ervan': ['. No problem, the staff know about it', '. No pasa nada, el personal ya lo sabe'],
        'Kies tafels die direct naast of achter elkaar staan, dan kunnen we ze aanschuiven': ['Choose tables directly next to or behind each other so we can push them together', 'Elige mesas que estén justo al lado o detrás una de otra para poder juntarlas'],
        'Maximaal aantal personen per online reservering': ['Maximum number of people per online reservation', 'Número máximo de personas por reserva online'],
        'Groepen groter dan 4 kiezen meerdere tafels die naast of achter elkaar staan; die worden aangeschoven. Een tafel is tijdens de hele duur van een reservering bezet voor andere gasten. Het adres wordt gebruikt als je de site op de laptop zelf (localhost) opent; via ngrok vult de site het adres zelf in': ['Groups larger than 4 choose several tables next to or behind each other; these are pushed together. A table is unavailable to other guests for the whole duration of a reservation. The address is used when you open the site on the laptop itself (localhost); via ngrok the site fills in the address itself', 'Los grupos de más de 4 eligen varias mesas contiguas, que se juntan. Una mesa está ocupada para otros clientes durante toda la reserva. La dirección se usa cuando abres el sitio en el propio portátil (localhost); con ngrok el sitio la rellena solo'],
        'Begane grond (Tafels 1 - 10)': ['Ground floor (Tables 1 - 10)', 'Planta baja (Mesas 1 - 10)'],
        '1e verdieping (Tafels 11 - 26)': ['1st floor (Tables 11 - 26)', '1ª planta (Mesas 11 - 26)'],
        'Status tafels begane grond': ['Status of ground floor tables', 'Estado mesas planta baja'],
        'Status tafels 1e verdieping': ['Status of 1st floor tables', 'Estado mesas 1ª planta'],
        'Reserveer je tafel': ['Book your table', 'Reserva tu mesa'],
        'Online reserveren is op dit moment niet mogelijk. Bel ons gerust om te reserveren': ['Online booking is not possible at the moment. Feel free to call us to book', 'Ahora mismo no se puede reservar online. Llámanos para reservar'],
        'Je verplaatst reservering': ['You are changing reservation', 'Estás cambiando la reserva'],
        'Kies een nieuwe datum, tijd of tafel': ['Choose a new date, time or table', 'Elige una nueva fecha, hora o mesa'],
        'Wanneer en met hoeveel': ['When and how many people', '¿Cuándo y cuántos'],
        'Datum': ['Date', 'Fecha'],
        'Morgen': ['Tomorrow', 'Mañana'],
        'Aantal personen': ['Number of people', 'Número de personas'],
        'Hoe laat': ['What time', '¿A qué hora'],
        'nog 1 tafel': ['1 table left', 'queda 1 mesa'],
        'vol': ['full', 'completo'],
        'voorbij': ['past', 'pasado'],
        'Kies je tafel': ['Choose your table', 'Elige tu mesa'],
        'Dit is het restaurant van bovenaf gezien. Tik op een vrije (groene) tafel. Elke tafel heeft 4 plaatsen; het nummer staat onder de tafel': ['This is the restaurant seen from above. Tap a free (green) table. Every table seats 4; the number is below the table', 'Este es el restaurante visto desde arriba. Toca una mesa libre (verde). Cada mesa tiene 4 plazas; el número está debajo de la mesa'],
        'Vrij': ['Free', 'Libre'],
        'Jouw keuze': ['Your choice', 'Tu elección'],
        'Gereserveerd': ['Reserved', 'Reservada'],
        'Te klein voor jullie': ['Too small for you', 'Demasiado pequeña'],
        'Kies eerst een tijd, daarna een tafel': ['First choose a time, then a table', 'Primero elige una hora y luego una mesa'],
        'Jouw gegevens': ['Your details', 'Tus datos'],
        'We hebben deze gegevens nodig om je reservering te bevestigen en om je te bereiken als er iets verandert': ['We need these details to confirm your reservation and to reach you if anything changes', 'Necesitamos estos datos para confirmar tu reserva y contactarte si algo cambia'],
        'Telefoonnummer': ['Phone number', 'Teléfono'],
        'E-mailadres': ['Email address', 'Correo electrónico'],
        'Opmerking (optioneel)': ['Note (optional)', 'Nota (opcional)'],
        'We gebruiken je gegevens alleen voor deze reservering: je krijgt een bevestiging per e-mail, en we bellen of mailen alleen als er iets verandert': ['We only use your details for this reservation: you get a confirmation by email, and we only call or email if something changes', 'Solo usamos tus datos para esta reserva: recibes una confirmación por correo y solo te llamamos o escribimos si algo cambia'],
        'Personen': ['People', 'Personas'],
        'personen': ['people', 'personas'],
        'Reservering bevestigen': ['Confirm reservation', 'Confirmar reserva'],
        'Wijziging opslaan': ['Save change', 'Guardar cambio'],
        'Even geduld': ['One moment', 'Un momento'],
        'Je reservering is bevestigd': ['Your reservation is confirmed', '¡Tu reserva está confirmada'],
        'Je reservering is gewijzigd': ['Your reservation has been changed', '¡Tu reserva ha sido modificada'],
        'Reserveringsnummer': ['Reservation number', 'Número de reserva'],
        'We hebben een bevestiging gestuurd naar je e-mail. Daarin staat ook een link om te verplaatsen of te annuleren': ['We have sent a confirmation to your email. It also contains a link to change or cancel', 'Te hemos enviado una confirmación por correo. También incluye un enlace para cambiar o cancelar'],
        'De bevestiging kon niet worden gemaild. Bewaar daarom de link hieronder (bijvoorbeeld als bladwijzer of met een screenshot) om je reservering later te bekijken, verplaatsen of annuleren': ['The confirmation could not be emailed. So keep the link below (for example as a bookmark or a screenshot) to view, change or cancel your reservation later', 'No se pudo enviar la confirmación por correo. Guarda el enlace de abajo (por ejemplo como marcador o captura de pantalla) para ver, cambiar o cancelar tu reserva más tarde'],
        'Bekijken, verplaatsen of annuleren': ['View, change or cancel', 'Ver, cambiar o cancelar'],
        'Minder personen': ['Fewer people', 'Menos personas'],
        'Meer personen': ['More people', 'Más personas'],
        'Bijv. Maria Jansen': ['E.g. Maria Jansen', 'Ej. María García'],
        'Bijv. 06 12345678': ['E.g. 06 12345678', 'Ej. 06 12345678'],
        'jouw@email.nl': ['your@email.com', 'tu@correo.es'],
        'Bijv. kinderstoel, allergie, verjaardag': ['E.g. high chair, allergy, birthday', 'Ej. trona, alergia, cumpleaños'],
        'Reserveren - Las Tapas': ['Book a table - Las Tapas', 'Reservar - Las Tapas'],
        'Tik op een vrije (groene) tafel op de plattegrond': ['Tap a free (green) table on the floor plan', 'Toca una mesa libre (verde) en el plano'],
        'plaatsen': ['seats', 'plazas'],
        'aan het raam': ['by the window', 'junto a la ventana'],
        'midden in de zaal': ['in the middle of the room', 'en el centro del salón'],
        'bij de bar': ['by the bar', 'junto a la barra'],
        'bij de entree': ['by the entrance', 'junto a la entrada'],
        'bij de lounge': ['by the lounge', 'junto al lounge'],
        'Begane grond': ['Ground floor', 'Planta baja'],
        'Eerste verdieping': ['First floor', 'Primera planta'],
        '1e verdieping': ['1st floor', '1ª planta'],
        'Vul je naam, e-mailadres en telefoonnummer in': ['Fill in your name, email address and phone number', 'Rellena tu nombre, correo y teléfono'],
        'gereserveerd': ['reserved', 'reservada'],
        'te klein': ['too small', 'demasiado pequeña'],
        'Vergroten': ['Zoom in', 'Ampliar'],
        'Kleiner': ['Zoom out', 'Reducir'],
        'Afwas': ['Dishwashing', 'Fregadero'],
        'Koeling': ['Cooling', 'Refrigeración'],
        'Kooklijn': ['Cooking line', 'Línea de cocina'],
        'Oven / grill': ['Oven / grill', 'Horno / parrilla'],
        'Voorbereiding': ['Prep', 'Preparación'],
        'Uitgifte': ['Pass', 'Pase'],
        'Vriezer': ['Freezer', 'Congelador'],
        'Koelcel': ['Cold room', 'Cámara frigorífica'],
        'Kast': ['Cupboard', 'Armario'],
        'Trap': ['Stairs', 'Escalera'],
        'Kassa / host': ['Till / host', 'Caja / recepción'],
        'Toilet dames': ['Ladies', 'Aseo señoras'],
        'Toilet heren': ['Gents', 'Aseo caballeros'],
        'Entree': ['Entrance', 'Entrada'],
        'Kantoor': ['Office', 'Oficina'],
        'Droge opslag': ['Dry storage', 'Almacén seco'],
        'Mijn reservering': ['My reservation', 'Mi reserva'],
        'Mijn reservering - Las Tapas': ['My reservation - Las Tapas', 'Mi reserva - Las Tapas'],
        'Reservering wordt geladen': ['Loading reservation', 'Cargando reserva'],
        'Niet gevonden': ['Not found', 'No encontrada'],
        'Deze reservering kunnen we niet vinden. Controleer de link uit je e-mail': ['We cannot find this reservation. Check the link in your email', 'No encontramos esta reserva. Comprueba el enlace de tu correo'],
        'Deze reservering kunnen we niet vinden': ['We cannot find this reservation', 'No encontramos esta reserva'],
        'Nieuwe reservering maken': ['Make a new reservation', 'Hacer una nueva reserva'],
        'Jouw reservering': ['Your reservation', 'Tu reserva'],
        'Weet je zeker dat je wilt annuleren': ['Are you sure you want to cancel', '¿Seguro que quieres cancelar'],
        'Je tafel komt dan vrij voor andere gasten. Dit kun je niet ongedaan maken': ['Your table will then be released for other guests. This cannot be undone', 'Tu mesa quedará libre para otros clientes. No se puede deshacer'],
        'Ja, annuleren': ['Yes, cancel', 'Sí, cancelar'],
        'Nee, terug': ['No, go back', 'No, volver'],
        'Vragen of iets bijzonders? Bel ons gerust, we helpen je graag': ['Questions or something special? Feel free to call us, we are happy to help', '¿Preguntas o algo especial? Llámanos, te ayudamos con gusto'],
        'Bevestigd': ['Confirmed', 'Confirmada'],
        'Je bent binnen – eet smakelijk': ['You are here – enjoy your meal', '¡Ya estás aquí, que aproveche'],
        'Niet gekomen': ['No-show', 'No se presentó'],
        'Geannuleerd': ['Cancelled', 'Cancelada'],
        'Verplaatsen': ['Change', 'Cambiar'],
        'Laat bij binnenkomst je reserveringsnummer of naam zien, dan brengen we je meteen naar je tafel': ['Show your reservation number or name when you arrive and we will take you straight to your table', 'Muestra tu número de reserva o tu nombre al llegar y te llevamos directamente a tu mesa'],
        'Deze reservering is geannuleerd': ['This reservation has been cancelled', 'Esta reserva ha sido cancelada'],
        'Deze reservering kan niet meer online worden aangepast': ['This reservation can no longer be changed online', 'Esta reserva ya no se puede modificar online'],
        'Je reservering is geannuleerd. Je krijgt hiervan een bevestiging per e-mail': ['Your reservation has been cancelled. You will receive a confirmation by email', 'Tu reserva ha sido cancelada. Recibirás una confirmación por correo'],
        'Je reservering is geannuleerd': ['Your reservation has been cancelled', 'Tu reserva ha sido cancelada'],
        'Kan de server niet bereiken. Probeer het opnieuw': ['Cannot reach the server. Please try again', 'No se puede conectar con el servidor. Inténtalo de nuevo'],
        'Opmerking': ['Note', 'Nota'],
        'Reserveringen': ['Reservations', 'Reservas'],
        'Las Tapas - Reserveringen': ['Las Tapas - Reservations', 'Las Tapas - Reservas'],
        'Telefonische reservering': ['Phone reservation', 'Reserva telefónica'],
        'Vorige dag': ['Previous day', 'Día anterior'],
        'Volgende dag': ['Next day', 'Día siguiente'],
        'Zoek op naam, telefoonnummer of reserveringsnummer (bijv. R-0012)': ['Search by name, phone number or reservation number (e.g. R-0012)', 'Buscar por nombre, teléfono o número de reserva (p. ej. R-0012)'],
        'reserveringen': ['reservations', 'reservas'],
        'gasten verwacht': ['guests expected', 'clientes esperados'],
        'aangekomen': ['arrived', 'llegados'],
        'niet gekomen': ['no-shows', 'no presentados'],
        'Lijst': ['List', 'Lista'],
        'Bezettingsschema': ['Occupancy chart', 'Ocupación'],
        'Aangekomen': ['Arrived', 'Llegado'],
        'Verwacht': ['Expected', 'Esperado'],
        'raam': ['window', 'ventana'],
        'midden': ['middle', 'centro'],
        'entree': ['entrance', 'entrada'],
        'bar': ['bar', 'barra'],
        'lounge': ['lounge', 'lounge'],
        'Binnengekomen om': ['Arrived at', 'Llegó a las'],
        'Laatst aangepast door': ['Last changed by', 'Última modificación por'],
        'Terug naar verwacht': ['Back to expected', 'Volver a esperado'],
        'Reservering verplaatsen': ['Move reservation', 'Cambiar reserva'],
        'De gast krijgt automatisch een e-mail met de nieuwe gegevens': ['The guest automatically gets an email with the new details', 'El cliente recibe automáticamente un correo con los nuevos datos'],
        'Opslaan & gast mailen': ['Save & email guest', 'Guardar y enviar correo'],
        'Reservering annuleren': ['Cancel reservation', 'Cancelar reserva'],
        'Gast een e-mail sturen dat de reservering is geannuleerd': ['Email the guest that the reservation has been cancelled', 'Enviar un correo al cliente de que la reserva se ha cancelado'],
        'Zoekresultaten': ['Search results', 'Resultados'],
        'Niets gevonden. Probeer een deel van de naam of het telefoonnummer': ['Nothing found. Try part of the name or phone number', 'No se ha encontrado nada. Prueba con parte del nombre o del teléfono'],
        'Geen reserveringen op deze dag': ['No reservations on this day', 'No hay reservas este día'],
        'Gast staat op aangekomen': ['Guest marked as arrived', 'Cliente marcado como llegado'],
        'Gemarkeerd als niet gekomen': ['Marked as no-show', 'Marcado como no presentado'],
        'Status teruggezet': ['Status reset', 'Estado restablecido'],
        'Reservering verplaatst': ['Reservation moved', 'Reserva cambiada'],
        'Reservering geannuleerd': ['Reservation cancelled', 'Reserva cancelada'],
        'Kies een vrije tafel': ['Choose a free table', 'Elige una mesa libre'],
        'bezet': ['taken', 'ocupada'],
        'Kies een geldige datum en tijd': ['Choose a valid date and time', 'Elige una fecha y hora válidas'],
        'Online reserveren staat uit. Gasten kunnen nu niet via de site reserveren': ['Online booking is switched off. Guests cannot book via the site now', 'Las reservas online están desactivadas. Los clientes no pueden reservar por la web'],
        'Online reserveren staat uit. Gasten kunnen nu niet via de site reserveren. Zet het aan bij': ['Online booking is switched off. Guests cannot book via the site now. Switch it on under', 'Las reservas online están desactivadas. Los clientes no pueden reservar por la web. Actívalas en'],
        'Menu & prijzen → Instellingen': ['Menu & prices → Settings', 'Menú y precios → Ajustes'],
        'Chef-kok': ['Head chef', 'Jefe de cocina'],
        'Barman': ['Bartender', 'Barman'],
        'Alle reserveringen': ['All reservations', 'Todas las reservas'],
        'Reserveringen vandaag': ['Today\'s reservations', 'Reservas de hoy'],
        'Gast aangekomen': ['Guest arrived', 'Cliente llegado'],
        'Om': ['At', 'A las'],
        'gereserveerd voor': ['reserved for', 'reservada para'],
        'Reserveren': ['Booking', 'Reservar'],
        'Openen': ['Open', 'Abrir'],
        'Scan om online een tafel te reserveren: kies een dag, tijd en je eigen tafel op de plattegrond': ['Scan to book a table online: choose a day, time and your own table on the floor plan', 'Escanea para reservar mesa online: elige día, hora y tu propia mesa en el plano'],
        'Voor gasten': ['For guests', 'Para clientes'],
        'Zet deze QR-code bijvoorbeeld op de rekening, op het raam of op flyers. Alle reserveringen zie je bij': ['Put this QR code on the bill, the window or flyers, for example. You can see all reservations under', 'Pon este código QR, por ejemplo, en la cuenta, en la ventana o en folletos. Todas las reservas están en'],
        'Scan om te reserveren': ['Scan to book', 'Escanea para reservar'],
        'Vorige tafel': ['Previous table', 'Mesa anterior'],
        'Volgende tafel': ['Next table', 'Mesa siguiente'],
        'Plattegrond': ['Floor plan', 'Plano'],
        'QR-kaartjes': ['QR cards', 'Tarjetas QR'],
        'Bovenaanzicht van het restaurant. Tik op een tafel voor de QR-code en de link. "Printen" print de QR-kaartjes van de tafels die je nu ziet': ['Top view of the restaurant. Tap a table for the QR code and the link. "Print" prints the QR cards of the tables you see now', 'Vista desde arriba del restaurante. Toca una mesa para ver el código QR y el enlace. "Imprimir" imprime las tarjetas QR de las mesas que ves ahora'],
        'Bovenaanzicht van het restaurant. Tik op een tafel voor de QR-code en de link. "Groot tonen" laat de plattegrond schermvullend zien (vul je een tafelnummer in, dan alleen die QR-code). "Printen" print de QR-kaartjes van de tafels die je nu ziet': ['Top view of the restaurant. Tap a table for the QR code and the link. "Show large" shows the floor plan full screen (enter a table number to show only that QR code). "Print" prints the QR cards of the tables you see now', 'Vista desde arriba del restaurante. Toca una mesa para ver el código QR y el enlace. "Mostrar grande" muestra el plano a pantalla completa (escribe un número de mesa para ver solo ese código QR). "Imprimir" imprime las tarjetas QR de las mesas que ves ahora'],
        'Tafels 1 - 10 · tik op een tafel voor de QR-code': ['Tables 1 - 10 · tap a table for the QR code', 'Mesas 1 - 10 · toca una mesa para ver el código QR'],
        'Tafels 11 - 26 · tik op een tafel voor de QR-code': ['Tables 11 - 26 · tap a table for the QR code', 'Mesas 11 - 26 · toca una mesa para ver el código QR'],
        'Vorige verdieping': ['Previous floor', 'Planta anterior'],
        'Volgende verdieping': ['Next floor', 'Planta siguiente'],
        '✕ Sluiten': ['✕ Close', '✕ Cerrar'],
        'Tik op een QR-code om hem schermvullend te tonen. "Printen" print de tafels die je nu ziet (alle, of één verdieping)': ['Tap a QR code to show it full screen. "Print" prints the tables you see now (all, or one floor)', 'Toca un código QR para mostrarlo en pantalla completa. "Imprimir" imprime las mesas que ves ahora (todas o una planta)'],
        'Online reserveren': ['Online booking', 'Reservas online'],
        'Gasten kunnen online een tafel reserveren': ['Guests can book a table online', 'Los clientes pueden reservar mesa online'],
        'Uitgevinkt: de reserveerpagina laat weten dat online reserveren nu niet kan. Bestaande reserveringen blijven gewoon staan': ['Unticked: the booking page says online booking is not possible right now. Existing reservations stay as they are', 'Desmarcado: la página de reservas indica que ahora no se puede reservar online. Las reservas existentes se mantienen'],
        'Eerste starttijd': ['First start time', 'Primera hora de inicio'],
        'Laatste starttijd': ['Last start time', 'Última hora de inicio'],
        'Elke ... minuten een starttijd': ['A start time every ... minutes', 'Una hora de inicio cada ... minutos'],
        'Een reservering duurt (minuten)': ['A reservation lasts (minutes)', 'Una reserva dura (minutos)'],
        'Hoeveel dagen vooruit reserveren': ['How many days ahead guests can book', 'Con cuántos días de antelación se puede reservar'],
        'Adres van de site (voor de links in e-mails)': ['Site address (for the links in emails)', 'Dirección del sitio (para los enlaces de los correos)'],
        'Een tafel is tijdens de hele duur van een reservering bezet voor andere gasten. Het adres wordt gebruikt als je de site op de laptop zelf (localhost) opent; via ngrok vult de site het adres zelf in': ['A table is unavailable to other guests for the whole duration of a reservation. The address is used when you open the site on the laptop itself (localhost); via ngrok the site fills in the address itself', 'Una mesa está ocupada para otros clientes durante toda la reserva. La dirección se usa cuando abres el sitio en el propio portátil (localhost); con ngrok el sitio la rellena solo'],
        'De laatste starttijd moet na de eerste starttijd liggen': ['The last start time must be after the first start time', 'La última hora de inicio debe ser posterior a la primera'],
        'Koude tapas': ['Cold tapas', 'Tapas frías'],
        'Specialiteiten': ['Specialities', 'Especialidades'],
        'koud': ['cold', ''],
        'koude tapas': ['cold tapas', ''],
        'specialiteiten': ['specialities', ''],
        'Gemarineerde olijven met olijfolie, knoflook en kruiden': ['Marinated olives with olive oil, garlic and herbs', 'Aceitunas marinadas con aceite de oliva, ajo y hierbas'],
        'Geroosterd brood met verse tomaat, olijfolie en zout': ['Toasted bread with fresh tomato, olive oil and salt', 'Pan tostado con tomate fresco, aceite de oliva y sal'],
        'Selectie van Spaanse kazen zoals manchego en geitenkaas': ['Selection of Spanish cheeses such as manchego and goat cheese', 'Selección de quesos españoles como manchego y queso de cabra'],
        'Gedroogde Spaanse ham met een zoute, rijke smaak': ['Cured Spanish ham with a salty, rich flavour', 'Jamón curado español de sabor salado e intenso'],
        'Tonijnsalade met ui, tomaat en olijfolie': ['Tuna salad with onion, tomato and olive oil', 'Ensalada de atún con cebolla, tomate y aceite de oliva'],
        'Koude soep van tomaat, paprika en komkommer': ['Cold soup of tomato, pepper and cucumber', 'Sopa fría de tomate, pimiento y pepino'],
        'Dikke koude tomatensoep met brood, ei en ham': ['Thick cold tomato soup with bread, egg and ham', 'Crema fría de tomate con pan, huevo y jamón'],
        'Gemengde salade met sla, tomaat, ei en olijven': ['Mixed salad with lettuce, tomato, egg and olives', 'Ensalada variada con lechuga, tomate, huevo y aceitunas'],
        'Ansjovis gemarineerd in azijn en knoflook': ['Anchovies marinated in vinegar and garlic', 'Boquerones marinados en vinagre y ajo'],
        'Garnalen met een romige cocktailsaus': ['Prawns with a creamy cocktail sauce', 'Gambas con una cremosa salsa cóctel'],
        'Gebakken aardappelen met pittige saus': ['Fried potatoes with spicy sauce', 'Patatas fritas con salsa picante'],
        'Aardappelen met knoflooksaus': ['Potatoes with garlic sauce', 'Patatas con salsa de ajo'],
        'Garnalen gebakken in knoflook en olie': ['Prawns fried in garlic and oil', 'Gambas fritas en ajo y aceite'],
        'Gefrituurde inktvisringen': ['Deep-fried squid rings', 'Anillas de calamar fritas'],
        'Gefrituurde kroketjes gevuld met ham, kaas of kip': ['Fried croquettes filled with ham, cheese or chicken', 'Croquetas fritas rellenas de jamón, queso o pollo'],
        'Dadels omwikkeld met spek': ['Dates wrapped in bacon', 'Dátiles envueltos en beicon'],
        'Kippenvleugels met kruiden, gefrituurd of gebakken': ['Chicken wings with herbs, fried or baked', 'Alitas de pollo con especias, fritas o al horno'],
        'Kip gebakken met knoflook en olie': ['Chicken fried with garlic and oil', 'Pollo frito con ajo y aceite'],
        'Chorizoworst gekookt in cider': ['Chorizo sausage cooked in cider', 'Chorizo cocido en sidra'],
        'Chorizoworst bereid in rode wijnsaus': ['Chorizo sausage in red wine sauce', 'Chorizo en salsa de vino tinto'],
        'Kleine groene pepers gebakken met zout': ['Small green peppers fried with salt', 'Pimientos verdes pequeños fritos con sal'],
        'Champignons in knoflookolie': ['Mushrooms in garlic oil', 'Champiñones en aceite de ajo'],
        'Spaanse omelet met aardappel en ui': ['Spanish omelette with potato and onion', 'Tortilla de patatas con cebolla'],
        'Gebakken eieren met aardappelen en ham': ['Fried eggs with potatoes and ham', 'Huevos fritos con patatas y jamón'],
        'Gekruide vleesspiesjes': ['Spiced meat skewers', 'Brochetas de carne especiadas'],
        'Octopus met paprikapoeder en olijfolie': ['Octopus with paprika and olive oil', 'Pulpo con pimentón y aceite de oliva'],
        'Kleine paella met zeevruchten zoals garnalen en mosselen': ['Small paella with seafood such as prawns and mussels', 'Paella pequeña con marisco como gambas y mejillones'],
        'Kleine paella met kip en groenten': ['Small paella with chicken and vegetables', 'Paella pequeña con pollo y verduras'],
        'Kleine portie paella met vlees en vis samen': ['Small portion of paella with meat and fish together', 'Ración pequeña de paella con carne y pescado'],
        'Kleine gevulde deegpakketjes met vlees of vis': ['Small filled pastries with meat or fish', 'Empanadillas pequeñas rellenas de carne o pescado'],
        'Gefrituurde aubergine met honing': ['Fried aubergine with honey', 'Berenjena frita con miel'],
        'Gegrild varkensvlees': ['Grilled pork loin', 'Lomo de cerdo a la plancha'],
        'Kleine broodjes met verschillende belegsoorten': ['Small sandwiches with various toppings', 'Pequeños bocadillos con diferentes ingredientes'],
        'Brood met knoflooksaus': ['Bread with garlic sauce', 'Pan con salsa de ajo'],
        'Dessert met custard en een krokante suikerlaag': ['Custard dessert with a crunchy sugar crust', 'Postre de crema con una capa crujiente de azúcar'],
        'Pudding van ei en karamel': ['Egg and caramel pudding', 'Pudin de huevo y caramelo'],
        'Rijst gekookt in melk met suiker en kaneel': ['Rice cooked in milk with sugar and cinnamon', 'Arroz cocido en leche con azúcar y canela'],
        'Gefrituurd deeg met warme chocoladesaus': ['Fried dough with warm chocolate sauce', 'Masa frita con chocolate caliente'],
        'Amandeltaart uit Spanje': ['Almond cake from Spain', 'Tarta de almendra española'],
        'Gefrituurd melkgebak met suiker': ['Fried milk pudding with sugar', 'Leche frita con azúcar'],
        'Vanille-ijs': ['Vanilla ice cream', 'Helado de vainilla'],
        'Zoete custard met kaneel': ['Sweet custard with cinnamon', 'Natillas dulces con canela'],
        'Aardbeien met slagroom': ['Strawberries with whipped cream', 'Fresas con nata montada'],
        'Rode wijn, vaak stevig en vol van smaak (bij vleesgerechten)': ['Red wine, often robust and full-bodied (with meat dishes)', 'Vino tinto, a menudo con cuerpo e intenso (para platos de carne)'],
        'Witte wijn, fris en licht (bij vis en lichte tapas)': ['White wine, fresh and light (with fish and light tapas)', 'Vino blanco, fresco y ligero (para pescado y tapas ligeras)'],
        'Rosé wijn, een frisse mix tussen rood en wit': ['Rosé wine, a fresh blend between red and white', 'Vino rosado, una mezcla fresca entre tinto y blanco'],
        'Wijn gemengd met fruit, suiker en soms frisdrank': ['Wine mixed with fruit, sugar and sometimes soda', 'Vino mezclado con fruta, azúcar y a veces refresco'],
        'Rode wijn met frisdrank en ijs, een lichtere variant van sangria': ['Red wine with soda and ice, a lighter version of sangria', 'Vino tinto con gaseosa y hielo, una versión más ligera de la sangría'],
        'Bier, zoals bekende Spaanse merken (bijv. Estrella)': ['Beer, such as well-known Spanish brands (e.g. Estrella)', 'Cerveza, de marcas españolas conocidas (p. ej. Estrella)'],
        'Mineraalwater, met of zonder koolzuur': ['Mineral water, still or sparkling', 'Agua mineral, con o sin gas'],
        'Frisdranken zoals cola, sinaasappel en citroenlimonade': ['Soft drinks such as cola, orange and lemonade', 'Refrescos como cola, naranja y limón'],
        'Sinaasappelsap, vaak vers geperst': ['Orange juice, often freshly squeezed', 'Zumo de naranja, a menudo recién exprimido'],
        'Sterke zwarte koffie (espresso)': ['Strong black coffee (espresso)', 'Café solo fuerte (espresso)'],
        'Koffie met melk': ['Coffee with milk', 'Café con leche'],
        'Koffie met een klein beetje melk': ['Coffee with a little milk', 'Café con un poco de leche'],
        'Kies een datum': ['Choose a date', 'Elige una fecha'],
        'Kies een geldige datum': ['Choose a valid date', 'Elige una fecha válida'],
        'Kies een van de tijden in de lijst': ['Choose one of the times in the list', 'Elige una de las horas de la lista'],
        'Dit tijdstip is al voorbij of te kort dag. Kies een later tijdstip': ['This time has passed or is too soon. Choose a later time', 'Esta hora ya ha pasado o es demasiado pronto. Elige una hora más tarde'],
        'Kies een tafel op de plattegrond': ['Choose a table on the floor plan', 'Elige una mesa en el plano'],
        'Kies een tafel': ['Choose a table', 'Elige una mesa'],
        'Deze tafel is te klein voor jullie gezelschap. Kies een andere tafel': ['This table is too small for your group. Choose another table', 'Esta mesa es demasiado pequeña para vuestro grupo. Elige otra mesa'],
        'Deze tafel is net gereserveerd door iemand anders. Kies een andere tafel of tijd': ['This table has just been booked by someone else. Choose another table or time', 'Alguien acaba de reservar esta mesa. Elige otra mesa u hora'],
        'Vul je naam in': ['Fill in your name', 'Escribe tu nombre'],
        'Vul een geldig e-mailadres in': ['Fill in a valid email address', 'Escribe un correo electrónico válido'],
        'Vul een geldig telefoonnummer in (bijv. 06 12345678)': ['Fill in a valid phone number (e.g. 06 12345678)', 'Escribe un teléfono válido (p. ej. 06 12345678)'],
        'Online reserveren is op dit moment niet mogelijk. Bel ons gerust': ['Online booking is not possible at the moment. Feel free to call us', 'Ahora mismo no se puede reservar online. Llámanos'],
        'Je hebt al een paar reserveringen gemaakt. Probeer het later opnieuw of bel ons': ['You have already made a few reservations. Try again later or call us', 'Ya has hecho varias reservas. Inténtalo más tarde o llámanos'],
        'Deze reservering kan niet meer worden aangepast': ['This reservation can no longer be changed', 'Esta reserva ya no se puede modificar'],
        'Deze reservering is al begonnen of voorbij. Bel ons als je iets wilt veranderen': ['This reservation has already started or passed. Call us if you want to change something', 'Esta reserva ya ha empezado o ha pasado. Llámanos si quieres cambiar algo'],
        'Er ging iets mis op de server. Probeer het opnieuw': ['Something went wrong on the server. Please try again', 'Algo ha fallado en el servidor. Inténtalo de nuevo'],
        'Reservering niet gevonden': ['Reservation not found', 'Reserva no encontrada'],
        'Geen toegang: log in als medewerker': ['No access: log in as a staff member', 'Sin acceso: inicia sesión como empleado'],
        'Ongeldige aanvraag': ['Invalid request', 'Solicitud no válida'],
        'Ongeldige status': ['Invalid status', 'Estado no válido'],
        'Jullie tijd loopt nu tot': ['Your time currently runs until', 'Vuestro tiempo dura ahora hasta'],
        '1. Hoe lang?': ['1. How long?', '1. ¿Cuánto tiempo?'],
        '2. Wat willen jullie nog bestellen?': ['2. What would you still like to order?', '2. ¿Qué queréis seguir pidiendo?'],
        'Gast kiest in stappen van (minuten)': ['Guest chooses in steps of (minutes)', 'El cliente elige en pasos de (minutos)'],
        'Maximaal per keer (minuten)': ['Maximum per time (minutes)', 'Máximo por vez (minutos)'],
        'De prijzen hieronder gelden per (minuten)': ['The prices below apply per (minutes)', 'Los precios de abajo son por (minutos)'],
        'Bij "alleen drankjes" kan de gast in de extra tijd alleen drankjes bestellen, geen gerechten. De prijs rekent mee met de gekozen tijd: met de standaardinstellingen kost 15 minuten de helft en 60 minuten het dubbele van de prijs per 30 minuten': ['With "drinks only", guests can only order drinks during the extra time, no dishes. The price scales with the chosen time: with the default settings, 15 minutes costs half and 60 minutes double the price per 30 minutes', 'Con "solo bebidas", el cliente solo puede pedir bebidas durante el tiempo extra, no platos. El precio se ajusta al tiempo elegido: con la configuración estándar, 15 minutos cuestan la mitad y 60 minutos el doble del precio por 30 minutos'],
        'Eten + drinken': ['Food + drinks', 'Comida + bebida'],
        'Gerechten en drankjes bestellen': ['Order dishes and drinks', 'Pedir platos y bebidas'],
        'Alleen drankjes': ['Drinks only', 'Solo bebidas'],
        'Geen gerechten meer, wel drankjes': ['No more dishes, drinks only', 'Sin más platos, solo bebidas'],
        'We hopen dat het heeft gesmaakt! Verlengen is vandaag niet mogelijk. Jullie kunnen nu afrekenen': ['We hope you enjoyed it! Extending is not possible today. You can pay now', '¡Esperamos que os haya gustado! Hoy no es posible ampliar el tiempo. Ya podéis pagar'],
        'Verleng met': ['Extend by', 'Ampliar'],
        'tot': ['until', 'hasta'],
        'Gerechten tot': ['Dishes until', 'Platos hasta'],
        'drankjes tot': ['drinks until', 'bebidas hasta'],
        'Alleen drankjes tot': ['Drinks only until', 'Solo bebidas hasta'],
        '(de tijd voor gerechten is voorbij)': ['(the time for dishes is over)', '(el tiempo para platos ha terminado)'],
        'Alleen drankjes: haal de gerechten uit je mandje': ['Drinks only: remove the dishes from your basket', 'Solo bebidas: quita los platos de tu cesta'],
        'Tijd verlengen is op dit moment niet mogelijk. Vraag het personeel': ['Extending your time is not possible right now. Please ask the staff', 'Ahora no es posible ampliar el tiempo. Pregunta al personal'],
        'De tijd voor gerechten zit erop. Jullie hebben verlengd voor alleen drankjes, dus je kunt nu alleen drankjes bestellen': ['The time for dishes is up. You extended for drinks only, so you can now only order drinks', 'El tiempo para platos ha terminado. Habéis ampliado solo para bebidas, así que ahora solo podéis pedir bebidas'],
        'Gasten mogen hun tijd verlengen': ['Guests may extend their time', 'Los clientes pueden ampliar su tiempo'],
        'Uitgevinkt: gasten zien de knop "Tijd verlengen" niet meer. Is hun tijd om, dan kunnen ze alleen nog afrekenen': ['Unticked: guests no longer see the "Extend time" button. When their time is up, they can only pay', 'Desmarcado: los clientes ya no ven el botón "Ampliar tiempo". Cuando se acabe su tiempo, solo pueden pagar'],
        'Prijs per persoon: eten + drinken (€)': ['Price per person: food + drinks (€)', 'Precio por persona: comida + bebida (€)'],
        'Prijs per persoon: alleen drankjes (€)': ['Price per person: drinks only (€)', 'Precio por persona: solo bebidas (€)'],
        'Bij "alleen drankjes" kan de gast in de extra tijd alleen drankjes bestellen, geen gerechten': ['With "drinks only", guests can only order drinks during the extra time, no dishes', 'Con "solo bebidas", el cliente solo puede pedir bebidas durante el tiempo extra, no platos'],
        'Tik ergens op het scherm om het geluid van nieuwe orders aan te zetten': ['Tap anywhere on the screen to turn on the sound for new orders', 'Toca cualquier parte de la pantalla para activar el sonido de los pedidos nuevos'],
        'Tik ergens op het scherm om het geluid van meldingen aan te zetten': ['Tap anywhere on the screen to turn on notification sounds', 'Toca cualquier parte de la pantalla para activar el sonido de los avisos'],
        'nieuwe order': ['new order', 'pedido nuevo'],
        'nieuwe orders': ['new orders', 'pedidos nuevos'],
        'Net binnen': ['Just in', 'Recién llegado'],
        'Alles open- of dichtklappen': ['Expand or collapse all', 'Abrir o cerrar todo'],
        'Naam gewijzigd? Tafels die nu dit arrangement hebben, krijgen de nieuwe naam ook. In de statistieken blijft bij oude bezoeken de oude naam staan. Een arrangement dat nu bij een tafel in gebruik is, kun je pas verwijderen als die tafel klaar is': ['Changed the name? Tables that currently have this package get the new name too. In the statistics, old visits keep the old name. A package that is in use at a table can only be deleted once that table is finished', '¿Has cambiado el nombre? Las mesas que tienen ahora este menú reciben también el nombre nuevo. En las estadísticas, las visitas antiguas mantienen el nombre antiguo. Un menú en uso en una mesa solo se puede eliminar cuando esa mesa haya terminado'],
        'Dit is het laatste arrangement. Voeg eerst een ander arrangement toe, anders kunnen gasten niets kiezen': ['This is the last package. Add another package first, otherwise guests cannot choose anything', 'Este es el último menú. Añade primero otro menú; si no, los clientes no podrán elegir nada'],
        'Wie deed wat': ['Who did what', 'Quién hizo qué'],
        'Gekookt door:': ['Cooked by:', 'Cocinado por:'],
        'Gekookt door': ['Cooked by', 'Cocinado por'],
        'Drankjes door:': ['Drinks by:', 'Bebidas por:'],
        'Drankjes door': ['Drinks by', 'Bebidas por'],
        'Gebracht door:': ['Served by:', 'Servido por:'],
        'Gebracht door': ['Served by', 'Servido por'],
        'Geholpen door:': ['Helped by:', 'Atendido por:'],
        'Geholpen door': ['Helped by', 'Atendido por'],
        'Betaling door:': ['Payment by:', 'Cobro por:'],
        'Betaling door': ['Payment by', 'Cobro por'],
        'Afgerond door:': ['Closed by:', 'Cerrado por:'],
        'Afgerond door': ['Closed by', 'Cerrado por'],
        'Drukste dagen van de week': ['Busiest days of the week', 'Días con más clientes'],
        'Drukste dag:': ['Busiest day:', 'Día con más clientes:'],
        'Drukste dag': ['Busiest day', 'Día con más clientes'],
        'Maandag': ['Monday', 'Lunes'],
        'Dinsdag': ['Tuesday', 'Martes'],
        'Woensdag': ['Wednesday', 'Miércoles'],
        'Donderdag': ['Thursday', 'Jueves'],
        'Vrijdag': ['Friday', 'Viernes'],
        'Zaterdag': ['Saturday', 'Sábado'],
        'Zondag': ['Sunday', 'Domingo'],
        'Kortingscodes en cadeaubonnen zijn op dit moment niet beschikbaar': ['Discount codes and gift cards are not available at the moment', 'Los códigos de descuento y las tarjetas regalo no están disponibles en este momento'],
        'Kortingscodes en cadeaubonnen staan AAN': ['Discount codes and gift cards are ON', 'Códigos de descuento y tarjetas regalo: ACTIVADOS'],
        'Kortingscodes en cadeaubonnen staan UIT': ['Discount codes and gift cards are OFF', 'Códigos de descuento y tarjetas regalo: DESACTIVADOS'],
        'Gasten kunnen bij de rekening een kortingscode of cadeaubon invullen en een cadeaubon kopen': ['Guests can enter a discount code or gift card at the bill and buy a gift card', 'Los clientes pueden introducir un código de descuento o una tarjeta regalo en la cuenta y comprar una tarjeta regalo'],
        'Gasten zien geen kortingscode-veld en kunnen geen cadeaubon kopen of gebruiken. Je kunt hieronder alvast codes en bonnen klaarzetten': ['Guests see no discount code field and cannot buy or use gift cards. You can already prepare codes and cards below', 'Los clientes no ven el campo de código y no pueden comprar ni usar tarjetas regalo. Abajo ya puedes preparar códigos y tarjetas'],
        'Kortingscodes en cadeaubonnen staan AAN. Gasten zien de opties nu bij de rekening': ['Discount codes and gift cards are ON. Guests now see the options at the bill', 'Códigos y tarjetas regalo activados. Los clientes ya ven las opciones en la cuenta'],
        'Kortingscodes en cadeaubonnen staan UIT. Gasten zien de opties niet meer': ['Discount codes and gift cards are OFF. Guests no longer see the options', 'Códigos y tarjetas regalo desactivados. Los clientes ya no ven las opciones'],
        'Cadeaubonnen zijn op dit moment niet beschikbaar. Vraag het personeel als je een vraag hebt over je cadeaubon': ['Gift cards are not available at the moment. Ask the staff if you have a question about your gift card', 'Las tarjetas regalo no están disponibles en este momento. Pregunta al personal si tienes alguna duda sobre tu tarjeta'],
        'Je naam bij de beoordeling (optioneel)': ['Your name with the review (optional)', 'Tu nombre en la valoración (opcional)'],
        'Bijv. Maria': ['E.g. Maria', 'Ej. María'],
        'Je beoordeling verschijnt als': ['Your review will appear as', 'Tu valoración aparecerá como'],
        'Vul hierboven een naam in als je die erbij wilt': ['Enter a name above if you want it included', 'Escribe un nombre arriba si quieres que aparezca'],
        'Openstaande & Actieve Orders': ['Open & active orders', 'Pedidos pendientes y activos'],
        'Actief: in bereiding': ['Active: being prepared', 'Activos: en preparación'],
        'Openstaand: nog oppakken': ['Open: still to pick up', 'Pendientes: por empezar'],
        'Klaar & Onderweg': ['Ready & on the way', 'Listos y en camino'],
        'Onderweg: ben je aan het brengen': ['On the way: you are delivering', 'En camino: los estás llevando'],
        'Klaar: kan opgehaald worden': ['Ready: can be picked up', 'Listos: para recoger'],
        'Let op: drankjes betaal je apart': ['Note: drinks are paid separately', 'Atención: las bebidas se pagan aparte'],
        'Jullie arrangement is': ['Your package is', 'Vuestro menú es'],
        'zonder drankjes': ['without drinks', 'sin bebidas'],
        'Deze drankjes komen daarom op de rekening:': ['These drinks will therefore be added to the bill:', 'Por eso estas bebidas se añaden a la cuenta:'],
        'Deze drankjes komen daarom op de rekening': ['These drinks will therefore be added to the bill', 'Por eso estas bebidas se añaden a la cuenta'],
        'Drankjes in deze bestelling': ['Drinks in this order', 'Bebidas de este pedido'],
        'Totaal aan drankjes op de rekening, samen met eerdere bestellingen:': ['Total for drinks on the bill, including earlier orders:', 'Total de bebidas en la cuenta, con los pedidos anteriores:'],
        'Totaal aan drankjes op de rekening, samen met eerdere bestellingen': ['Total for drinks on the bill, including earlier orders', 'Total de bebidas en la cuenta, con los pedidos anteriores'],
        'Dit bedrag komt bij jullie rekening en betaal je bij het afrekenen': ['This amount is added to your bill and paid when you settle up', 'Este importe se añade a vuestra cuenta y se paga al final'],
        'Ja, bestellen': ['Yes, place order', 'Sí, pedir'],
        'Terug naar het mandje': ['Back to the basket', 'Volver a la cesta'],
        // ---------- Korting en cadeaubonnen ----------
        'Kortingscode of cadeaubon': ['Discount code or gift card', 'Código de descuento o tarjeta regalo'],
        'Bijv. ZOMER10 of LT-XXXX-…': ['E.g. SUMMER10 or LT-XXXX-…', 'Ej. VERANO10 o LT-XXXX-…'],
        'Toepassen': ['Apply', 'Aplicar'],
        'Even controleren...': ['Checking...', 'Comprobando...'],
        'Kortingscode toegepast': ['Discount code applied', 'Código de descuento aplicado'],
        'Cadeaubon kopen voor iemand anders': ['Buy a gift card for someone else', 'Comprar una tarjeta regalo para otra persona'],
        'Cadeaubon kopen': ['Buy a gift card', 'Comprar tarjeta regalo'],
        'Leuk voor iemand die jarig is! Kies een bedrag. De cadeaubon komt op jullie rekening; na het betalen krijg je de code': ['Nice for someone with a birthday! Choose an amount. The gift card is added to your bill; you get the code after paying', '¡Ideal para alguien que cumple años! Elige un importe. La tarjeta regalo se añade a vuestra cuenta; recibirás el código después de pagar'],
        'Of kies zelf een bedrag (€ 5 tot € 500)': ['Or choose your own amount (€5 to €500)', 'O elige tú el importe (de 5 € a 500 €)'],
        'Voor wie? (optioneel)': ['For whom? (optional)', '¿Para quién? (opcional)'],
        'Persoonlijke boodschap (optioneel)': ['Personal message (optional)', 'Mensaje personal (opcional)'],
        'Bijv. Oma Rosa': ['E.g. Grandma Rosa', 'Ej. Abuela Rosa'],
        'Bijv. Gefeliciteerd met je verjaardag!': ['E.g. Happy birthday!', 'Ej. ¡Feliz cumpleaños!'],
        'Bijv. 40': ['E.g. 40', 'Ej. 40'],
        'De naam en boodschap staan alleen op de cadeaubon en worden gewist als het tegoed op is': ['The name and message only appear on the gift card and are deleted once the balance is used up', 'El nombre y el mensaje solo aparecen en la tarjeta regalo y se borran cuando se agota el saldo'],
        'Op de rekening zetten': ['Add to the bill', 'Añadir a la cuenta'],
        'Kies een bedrag tussen € 5 en € 500': ['Choose an amount between €5 and €500', 'Elige un importe entre 5 € y 500 €'],
        'Dat is niet gelukt': ['That did not work', 'No ha funcionado'],
        'Deze cadeaubon weghalen van de rekening?': ['Remove this gift card from the bill?', '¿Quitar esta tarjeta regalo de la cuenta?'],
        'Cadeaubon weghalen': ['Remove gift card', 'Quitar tarjeta regalo'],
        'Nieuwe cadeaubon': ['New gift card', 'Nueva tarjeta regalo'],
        'Jullie cadeaubon': ['Your gift card', 'Vuestra tarjeta regalo'],
        'Jullie cadeaubonnen': ['Your gift cards', 'Vuestras tarjetas regalo'],
        'voor': ['for', 'para'],
        'Cadeaubon openen, printen of doorsturen →': ['Open, print or share the gift card →', 'Abrir, imprimir o compartir la tarjeta regalo →'],
        'Maak een foto of open de cadeaubon om hem te bewaren': ['Take a photo or open the gift card to keep it', 'Haz una foto o abre la tarjeta regalo para guardarla'],
        'Heb je de code van je cadeaubon bewaard? Na het sluiten zie je hem hier niet meer': ['Have you saved your gift card code? After closing you will no longer see it here', '¿Has guardado el código de tu tarjeta regalo? Después de cerrar ya no lo verás aquí'],
        'Je cadeaubon is hiermee op': ['Your gift card is now used up', 'Tu tarjeta regalo se agota con esto'],
        'Nog over op je cadeaubon': ['Remaining on your gift card', 'Saldo restante en tu tarjeta regalo'],
        'Je cadeaubon is nu op': ['Your gift card is now used up', 'Tu tarjeta regalo se ha agotado'],
        'Cadeaubon bekijken →': ['View gift card →', 'Ver tarjeta regalo →'],
        'Blijft over op de cadeaubon:': ['Remaining on the gift card:', 'Queda en la tarjeta regalo:'],
        'Saldo cadeaubon checken': ['Check gift card balance', 'Consultar saldo de la tarjeta regalo'],
        'Vul een kortingscode of cadeauboncode in': ['Enter a discount code or gift card code', 'Introduce un código de descuento o de tarjeta regalo'],
        'Deze code kennen we niet. Controleer de code en probeer het opnieuw': ['We do not recognise this code. Check the code and try again', 'No reconocemos este código. Compruébalo e inténtalo de nuevo'],
        'Te vaak een verkeerde code ingevuld. Probeer het over een kwartier opnieuw of vraag het personeel': ['Too many wrong codes entered. Try again in 15 minutes or ask the staff', 'Demasiados códigos incorrectos. Inténtalo dentro de 15 minutos o pregunta al personal'],
        'Deze kortingscode is niet (meer) geldig': ['This discount code is not (or no longer) valid', 'Este código de descuento no es válido (o ya no lo es)'],
        'Deze kortingscode is verlopen': ['This discount code has expired', 'Este código de descuento ha caducado'],
        'Deze kortingscode is al zo vaak gebruikt als de bedoeling was': ['This discount code has already been used the maximum number of times', 'Este código de descuento ya se ha usado el máximo de veces'],
        'Deze cadeaubon bestaat niet': ['This gift card does not exist', 'Esta tarjeta regalo no existe'],
        'Deze cadeaubon is nog niet betaald en dus nog niet geldig': ['This gift card has not been paid yet, so it is not valid yet', 'Esta tarjeta regalo aún no se ha pagado, así que todavía no es válida'],
        'Deze cadeaubon is geblokkeerd. Vraag het personeel om hulp': ['This gift card is blocked. Please ask the staff for help', 'Esta tarjeta regalo está bloqueada. Pide ayuda al personal'],
        'Het tegoed van deze cadeaubon is op': ['The balance of this gift card has been used up', 'El saldo de esta tarjeta regalo se ha agotado'],
        'Deze cadeaubon koop je nu zelf. Je kunt hem gebruiken zodra hij betaald is': ['You are buying this gift card yourself right now. You can use it once it has been paid', 'Estás comprando esta tarjeta regalo ahora. Podrás usarla cuando esté pagada'],
        'Je kunt per bezoek maximaal 5 cadeaubonnen kopen': ['You can buy up to 5 gift cards per visit', 'Puedes comprar como máximo 5 tarjetas regalo por visita'],
        'Jullie hebben de rekening al gevraagd. Vraag de serveerster als je nog iets wilt veranderen': ['You have already asked for the bill. Ask the waiter if you want to change something', 'Ya habéis pedido la cuenta. Pregunta al camarero si quieres cambiar algo'],
        'Meld je eerst opnieuw aan bij deze tafel': ['Please sign in again at this table first', 'Primero vuelve a registrarte en esta mesa'],
        'Cadeaubon': ['Gift card', 'Tarjeta regalo'],
        'Saldo checken': ['Check balance', 'Consultar saldo'],
        'Vul de code van je cadeaubon in om het tegoed te zien': ['Enter your gift card code to see the balance', 'Introduce el código de tu tarjeta regalo para ver el saldo'],
        'Geldig': ['Valid', 'Válida'],
        'Tegoed op': ['Balance used up', 'Saldo agotado'],
        'Geblokkeerd': ['Blocked', 'Bloqueada'],
        'Voor': ['For', 'Para'],
        'Een cadeautje om samen tapas te eten': ['A gift to enjoy tapas together', 'Un regalo para disfrutar de unas tapas juntos'],
        'Nog te besteden': ['Still to spend', 'Saldo disponible'],
        'Te gebruiken bij Las Tapas: vul deze code in bij de rekening. Wat je niet opmaakt, blijft op de bon staan': ['To be used at Las Tapas: enter this code at the bill. Whatever you do not spend stays on the card', 'Para usar en Las Tapas: introduce este código en la cuenta. Lo que no gastes se queda en la tarjeta'],
        'Doorsturen': ['Share', 'Compartir'],
        'Bewaar deze code goed: wie de code heeft, kan de cadeaubon gebruiken': ['Keep this code safe: anyone who has the code can use the gift card', 'Guarda bien este código: quien lo tenga puede usar la tarjeta regalo'],
        'De link naar de cadeaubon is gekopieerd': ['The link to the gift card has been copied', 'Se ha copiado el enlace a la tarjeta regalo'],
        'Te vaak geprobeerd. Probeer het over een kwartier opnieuw': ['Too many attempts. Try again in 15 minutes', 'Demasiados intentos. Inténtalo dentro de 15 minutos'],
        'De cadeaubonnen kunnen nu niet worden opgehaald. Probeer het later opnieuw': ['Gift cards cannot be retrieved right now. Please try again later', 'Ahora no se pueden consultar las tarjetas regalo. Inténtalo más tarde'],
        'Korting & cadeaubonnen': ['Discounts & gift cards', 'Descuentos y bonos'],
        'Kortingscodes': ['Discount codes', 'Códigos de descuento'],
        'Cadeaubonnen': ['Gift cards', 'Tarjetas regalo'],
        'Gasten vullen een kortingscode in bij de rekening. De korting geldt voor het eten en drinken (niet voor cadeaubonnen)': ['Guests enter a discount code at the bill. The discount applies to food and drinks (not to gift cards)', 'Los clientes introducen un código de descuento en la cuenta. El descuento se aplica a la comida y la bebida (no a las tarjetas regalo)'],
        'Code': ['Code', 'Código'],
        'Korting': ['Discount', 'Descuento'],
        'Gebruikt': ['Used', 'Usado'],
        'Geldig tot': ['Valid until', 'Válido hasta'],
        'Altijd': ['Always', 'Siempre'],
        'Uitgezet': ['Switched off', 'Desactivado'],
        'Verlopen': ['Expired', 'Caducado'],
        'Op': ['Used up', 'Agotado'],
        'Actief': ['Active', 'Activo'],
        'Uitzetten': ['Switch off', 'Desactivar'],
        'Aanzetten': ['Switch on', 'Activar'],
        'Nog geen kortingscodes. Maak er hieronder een aan': ['No discount codes yet. Create one below', 'Aún no hay códigos de descuento. Crea uno abajo'],
        'Nieuwe kortingscode': ['New discount code', 'Nuevo código de descuento'],
        'Soort korting': ['Type of discount', 'Tipo de descuento'],
        'Percentage (%)': ['Percentage (%)', 'Porcentaje (%)'],
        'Vast bedrag (€)': ['Fixed amount (€)', 'Importe fijo (€)'],
        'Hoeveel': ['How much', 'Cuánto'],
        'Omschrijving (voor jezelf)': ['Description (for yourself)', 'Descripción (para ti)'],
        'Geldig tot (optioneel)': ['Valid until (optional)', 'Válido hasta (opcional)'],
        'Hoe vaak te gebruiken': ['How many times it can be used', 'Cuántas veces se puede usar'],
        '0 = onbeperkt': ['0 = unlimited', '0 = ilimitado'],
        'Kortingscode aanmaken': ['Create discount code', 'Crear código de descuento'],
        'Bijv. ZOMER10': ['E.g. SUMMER10', 'Ej. VERANO10'],
        'Bijv. Zomeractie': ['E.g. Summer promotion', 'Ej. Promoción de verano'],
        'Bijv. 10': ['E.g. 10', 'Ej. 10'],
        'Bijv. 50': ['E.g. 50', 'Ej. 50'],
        'Bijv. Gefeliciteerd!': ['E.g. Congratulations!', 'Ej. ¡Felicidades!'],
        'Gasten kopen een cadeaubon aan tafel; hij komt op hun rekening en wordt geldig zodra die betaald is. Wat een gast niet opmaakt, blijft op de bon staan': ['Guests buy a gift card at the table; it is added to their bill and becomes valid once paid. Whatever a guest does not spend stays on the card', 'Los clientes compran una tarjeta regalo en la mesa; se añade a su cuenta y es válida en cuanto se paga. Lo que no se gasta se queda en la tarjeta'],
        'Openstaand tegoed': ['Outstanding balance', 'Saldo pendiente'],
        'Zoek op code, bijv. LT-ABCD': ['Search by code, e.g. LT-ABCD', 'Buscar por código, ej. LT-ABCD'],
        'Zoeken': ['Search', 'Buscar'],
        'Bedrag': ['Amount', 'Importe'],
        'Saldo': ['Balance', 'Saldo'],
        'Gekocht': ['Bought', 'Comprada'],
        'Nog niet betaald': ['Not paid yet', 'Aún no pagada'],
        'Wordt geldig na betaling': ['Becomes valid after payment', 'Será válida tras el pago'],
        'Blokkeren': ['Block', 'Bloquear'],
        'Deblokkeren': ['Unblock', 'Desbloquear'],
        'Geen cadeaubon gevonden met deze code': ['No gift card found with this code', 'No se ha encontrado ninguna tarjeta regalo con este código'],
        'Nog geen cadeaubonnen': ['No gift cards yet', 'Aún no hay tarjetas regalo'],
        'Cadeaubon aanmaken': ['Create gift card', 'Crear tarjeta regalo'],
        'Bijvoorbeeld als iemand aan de bar een cadeaubon koopt, of als cadeau van het restaurant. Deze bon is meteen geldig': ['For example when someone buys a gift card at the bar, or as a gift from the restaurant. This card is valid immediately', 'Por ejemplo, cuando alguien compra una tarjeta regalo en la barra o como regalo del restaurante. Esta tarjeta es válida de inmediato'],
        'Bedrag (€)': ['Amount (€)', 'Importe (€)'],
        'Voor wie (optioneel)': ['For whom (optional)', 'Para quién (opcional)'],
        'Boodschap (optioneel)': ['Message (optional)', 'Mensaje (opcional)'],
        'De kortingscode is aangepast': ['The discount code has been updated', 'Se ha actualizado el código de descuento'],
        'De kortingscode is verwijderd': ['The discount code has been deleted', 'Se ha eliminado el código de descuento'],
        'De cadeaubon is aangepast': ['The gift card has been updated', 'Se ha actualizado la tarjeta regalo'],
        'Een kortingscode heeft 3 tot 30 letters of cijfers': ['A discount code has 3 to 30 letters or digits', 'Un código de descuento tiene de 3 a 30 letras o cifras'],
        'Kies een andere code: deze lijkt te veel op een cadeauboncode': ['Choose a different code: this one looks too much like a gift card code', 'Elige otro código: se parece demasiado a un código de tarjeta regalo'],
        'Een percentage ligt tussen 1 en 100': ['A percentage is between 1 and 100', 'Un porcentaje está entre 1 y 100'],
        'Kies een kortingsbedrag tussen € 0,01 en € 1000': ['Choose a discount amount between €0.01 and €1000', 'Elige un descuento entre 0,01 € y 1000 €'],
        'Deze cadeaubon bestaat niet (meer)': ['This gift card no longer exists', 'Esta tarjeta regalo ya no existe'],
        'Ongeldige datum': ['Invalid date', 'Fecha no válida'],
    };

    // ======================= PATRONEN (teksten met getallen of namen) =======================
    // {1}, {2}, ... worden overgenomen (en zo mogelijk zelf ook vertaald).
    const P = [
        ['Tafel {1}', 'Table {1}', 'Mesa {1}'],
        ['Tafel {1}: {2}', 'Table {1}: {2}', 'Mesa {1}: {2}'],
        ['Mesa {1} (Tafel)', 'Mesa {1} (Table)', 'Mesa {1}'],
        ['Tus Pedidos · Tafel', 'Tus Pedidos · Table', 'Tus Pedidos · Mesa'],
        ['Bezet ({1})', 'Occupied ({1})', 'Ocupada ({1})'],
        ['{1} items gekozen', '{1} items chosen', '{1} artículos elegidos'],
        ['{1}x geselecteerd', '{1}x selected', '{1}x seleccionado'],
        ['Nog maar {1} over', 'Only {1} left', 'Solo quedan {1}'],
        ['Let op: Nog maar {1} over in voorraad', 'Note: only {1} left in stock', 'Atención: solo quedan {1} en existencias'],
        ['Besteld om: {1}', 'Ordered at: {1}', 'Pedido a las: {1}'],
        ['Tijd: {1}', 'Time: {1}', 'Hora: {1}'],
        ['{1} · Tijd: {2}', '{1} · Time: {2}', '{1} · Hora: {2}'],
        ['Foto van {1} groot bekijken', 'View photo of {1} large', 'Ver foto de {1} en grande'],
        ['Foto van {1} verwijderen', 'Delete photo of {1}', '¿Eliminar la foto de {1}'],
        ['Foto van {1}', 'Photo of {1}', 'Foto de {1}'],
        ['{1}e etage · Tafel {2}', 'Floor {1} · Table {2}', 'Planta {1} · Mesa {2}'],
        ['\'{1}\' staat niet op het menu', '\'{1}\' is not on the menu', '\'{1}\' no está en la carta'],
        ['Bon gemaild naar {1}', 'Receipt emailed to {1}', 'Recibo enviado a {1}'],
        ['Bon kon niet worden gemaild: {1}', 'Receipt could not be emailed: {1}', 'No se ha podido enviar el recibo: {1}'],
        ['Tafel {1} is al in gebruik door', 'Table {1} is already in use by', 'La mesa {1} ya está ocupada por'],
        ['({1} personen, {2})', '({1} people, {2})', '({1} personas, {2})'],
        ['({1} persoon, {2})', '({1} person, {2})', '({1} persona, {2})'],
        ['Status: {1}', 'Status: {1}', 'Estado: {1}'],
        ['{1} · niet inbegrepen', '{1} · not included', '{1} · no incluido'],
        ['{1} minuten', '{1} minutes', '{1} minutos'],
        ['Jullie {1} uur beginnen bij de eerste bestelling', 'Your {1} hours start with the first order', 'Vuestras {1} horas empiezan con el primer pedido'],
        ['kost {1} per persoon. Voor jullie {2} personen is dat', 'costs {1} per person. For your party of {2} that is', 'cuesta {1} por persona. Para vosotros {2} son'],
        ['kost {1} per persoon. Voor jullie {2} persoon is dat', 'costs {1} per person. For you that is', 'cuesta {1} por persona. Para ti son'],
        ['Verlengen met {1} min ({2})', 'Extend by {1} min ({2})', 'Ampliar {1} min ({2})'],
        ['Verlengd tot {1}', 'Extended until {1}', 'Ampliado hasta las {1}'],
        ['van {1} gerechten ({2} per persoon per {3} min). Drankjes tellen niet mee', 'of {1} dishes left ({2} per person per {3} min). Drinks do not count', 'de {1} platos ({2} por persona cada {3} min). Las bebidas no cuentan'],
        ['Ronde vol: max. {1} gerechten per {2} min', 'Round full: max. {1} dishes per {2} min', 'Ronda completa: máx. {1} platos cada {2} min'],
        ['Per ronde van {1} minuten kunnen jullie {2} gerechten bestellen ({3} per persoon). Vanaf {4} kan het weer. Drankjes kunnen altijd', 'Per round of {1} minutes you can order {2} dishes ({3} per person). You can order again from {4}. Drinks are always possible', 'En cada ronda de {1} minutos podéis pedir {2} platos ({3} por persona). Podréis volver a pedir a partir de las {4}. Las bebidas se pueden pedir siempre'],
        ['Per ronde van {1} minuten kunnen jullie {2} gerechten bestellen ({3} per persoon). Drankjes kunnen altijd', 'Per round of {1} minutes you can order {2} dishes ({3} per person). Drinks are always possible', 'En cada ronda de {1} minutos podéis pedir {2} platos ({3} por persona). Las bebidas se pueden pedir siempre'],
        ['Per ronde van {1} minuten kunnen jullie {2} gerechten bestellen ({3} per persoon). Deze ronde nog {4}. De volgende ronde begint om {5}', 'Per round of {1} minutes you can order {2} dishes ({3} per person). {4} left this round. The next round starts at {5}', 'En cada ronda de {1} minutos podéis pedir {2} platos ({3} por persona). Quedan {4} en esta ronda. La siguiente empieza a las {5}'],
        ['Per ronde van {1} minuten kunnen jullie {2} gerechten bestellen ({3} per persoon). Deze ronde nog {4}', 'Per round of {1} minutes you can order {2} dishes ({3} per person). {4} left this round', 'En cada ronda de {1} minutos podéis pedir {2} platos ({3} por persona). Quedan {4} en esta ronda'],
        ['Je hebt al het maximum aantal ({1}) van dit item in je mandje gezet', 'You already have the maximum number ({1}) of this item in your basket', 'Ya tienes el máximo ({1}) de este artículo en tu pedido'],
        ['Helaas, van \'{1}\' zijn er nog maar {2} op voorraad (gevraagd: {3})', 'Sorry, only {2} of \'{1}\' left in stock (requested: {3})', 'Lo sentimos, de \'{1}\' solo quedan {2} (pedidos: {3})'],
        ['\'{1}\' staat niet (meer) op het menu', '\'{1}\' is not (or no longer) on the menu', '\'{1}\' no está (ya) en la carta'],
        ['heeft je oproep geaccepteerd en komt naar tafel {1}', 'has accepted your call and is coming to table {1}', 'ha aceptado tu llamada y va a la mesa {1}'],
        ['Een serveerster heeft je oproep geaccepteerd en komt naar tafel {1}', 'A waiter has accepted your call and is coming to table {1}', 'Un camarero ha aceptado tu llamada y va a la mesa {1}'],
        ['komt naar tafel {1} om jullie online betaling te controleren', 'is coming to table {1} to check your online payment', 'va a la mesa {1} para comprobar vuestro pago online'],
        ['De serveerster komt naar tafel {1} om jullie online betaling te controleren', 'A waiter is coming to table {1} to check your online payment', 'Un camarero va a la mesa {1} para comprobar vuestro pago online'],
        ['heeft jullie verzoek geaccepteerd en is onderweg naar tafel {1} om de betaling in ontvangst te nemen', 'has accepted your request and is on the way to table {1} to collect the payment', 'ha aceptado vuestra solicitud y va de camino a la mesa {1} para cobrar'],
        ['De serveerster heeft jullie verzoek geaccepteerd en is onderweg naar tafel {1} om de betaling in ontvangst te nemen', 'A waiter has accepted your request and is on the way to table {1} to collect the payment', 'Un camarero ha aceptado vuestra solicitud y va de camino a la mesa {1} para cobrar'],
        ['{1} is al onderweg naar jullie tafel', '{1} is already on the way to your table', '{1} ya va de camino a vuestra mesa'],
        ['Deze bestelling is al geaccepteerd door {1}', 'This order has already been accepted by {1}', 'Este pedido ya lo ha aceptado {1}'],
        ['Dit betaalverzoek is al geaccepteerd door {1}', 'This payment request has already been accepted by {1}', 'Esta solicitud de pago ya la ha aceptado {1}'],
        ['Deze oproep is al geaccepteerd door {1}', 'This call has already been accepted by {1}', 'Esta llamada ya la ha aceptado {1}'],
        ['(was {1})', '(was {1})', '(era a las {1})'],
        ['Laatste activiteit: {1}', 'Last activity: {1}', 'Última actividad: {1}'],
        ['{1} min geleden', '{1} min ago', 'hace {1} min'],
        ['Geroepen om {1} ({2})', 'Called at {1} ({2})', 'Llamó a las {1} ({2})'],
        ['Tafel {1} vrijmaken omdat de gasten zijn vertrokken ZONDER te betalen{2}? Dit wordt apart bijgehouden in de statistieken', 'Free up table {1} because the guests left WITHOUT paying{2}? This is tracked separately in the statistics', '¿Liberar la mesa {1} porque los clientes se fueron SIN pagar{2}? Se registra aparte en las estadísticas'],
        ['Bevestig: heb je gecontroleerd dat de online betaling van Tafel {1} echt gelukt is? De tafel komt daarna weer vrij', 'Confirm: have you checked that the online payment for table {1} really went through? The table will then be freed up', 'Confirma: ¿has comprobado que el pago online de la mesa {1} se ha realizado? Después la mesa quedará libre'],
        ['Bevestig: is de rekening van Tafel {1} betaald? De tafel komt daarna weer vrij', 'Confirm: has the bill for table {1} been paid? The table will then be freed up', 'Confirma: ¿se ha pagado la cuenta de la mesa {1}? Después la mesa quedará libre'],
        ['Bijna op ({1})', 'Almost out ({1})', 'Casi agotado ({1})'],
        ['Voldoende ({1})', 'Enough ({1})', 'Suficiente ({1})'],
        ['Open tafel {1}', 'Open table {1}', 'Abrir mesa {1}'],
        ['{1} (verborgen)', '{1} (hidden)', '{1} (oculto)'],
        ['{1} ({2} uur)', '{1} ({2} hours)', '{1} ({2} horas)'],
        ['{1} ({2} min)', '{1} ({2} min)', '{1} ({2} min)'],
        ['Volw: {1} | 65+: {2} | Kind: {3}', 'Adult: {1} | 65+: {2} | Child: {3}', 'Adulto: {1} | 65+: {2} | Niño: {3}'],
        ['{1}× Volwassene', '{1}× Adult', '{1}× Adulto'],
        ['{1}× Senior (65+)', '{1}× Senior (65+)', '{1}× Mayor (65+)'],
        ['{1}× Kind', '{1}× Child', '{1}× Niño'],
        ['Verlenging {1} min ({2} pers.)', 'Extension {1} min ({2} pers.)', 'Ampliación {1} min ({2} pers.)'],
        ['Te betalen: {1}', 'To pay: {1}', 'A pagar: {1}'],
        ['{1} volw, {2} sen, {3} kind', '{1} adults, {2} seniors, {3} children', '{1} adultos, {2} mayores, {3} niños'],
        ['{1} ({2} tafels, {3} gasten)', '{1} ({2} tables, {3} guests)', '{1} ({2} mesas, {3} clientes)'],
        ['Contant ({1} tafels)', 'Cash ({1} tables)', 'Efectivo ({1} mesas)'],
        ['Online ({1} tafels)', 'Online ({1} tables)', 'Online ({1} mesas)'],
        ['Onbekend ({1} tafels)', 'Unknown ({1} tables)', 'Desconocido ({1} mesas)'],
        ['Vertrokken zonder betalen ({1})', 'Left without paying ({1})', 'Se fueron sin pagar ({1})'],
        ['Gemiddelde beoordeling ({1})', 'Average rating ({1})', 'Valoración media ({1})'],
        ['Betaald ({1})', 'Paid ({1})', 'Pagado ({1})'],
        ['tafel {1}', 'table {1}', 'mesa {1}'],
        ['Welkom, {1}', 'Welcome, {1}', 'Bienvenido/a, {1}'],
        ['{1} sterren', '{1} stars', '{1} estrellas'],
        ['Medewerker aanpassen: {1}', 'Edit staff member: {1}', 'Editar empleado: {1}'],
        ['Weet je zeker dat je {1} wilt verwijderen', 'Are you sure you want to delete {1}', '¿Seguro que quieres eliminar a {1}'],
        ['{1} definitief verwijderen? Tip: haal liever het vinkje Zichtbaar weg als het tijdelijk op is', 'Delete {1} permanently? Tip: untick Visible instead if it is only temporarily unavailable', '¿Eliminar {1} definitivamente? Consejo: si solo está agotado temporalmente, desmarca Visible'],
        ['De gebruikersnaam \'{1}\' is al in gebruik', 'The username \'{1}\' is already taken', 'El usuario \'{1}\' ya está en uso'],
        ['Onjuiste gebruikersnaam of wachtwoord. Nog {1} pogingen', 'Incorrect username or password. {1} attempts left', 'Usuario o contraseña incorrectos. Te quedan {1} intentos'],
        ['Onjuiste gebruikersnaam of wachtwoord. Nog {1} poging', 'Incorrect username or password. {1} attempt left', 'Usuario o contraseña incorrectos. Te queda {1} intento'],
        ['Te veel foute pogingen. Probeer het na {1} opnieuw', 'Too many failed attempts. Please try again after {1}', 'Demasiados intentos fallidos. Inténtalo de nuevo después de las {1}'],
        ['Voorbeeld (tafel 1): {1}', 'Example (table 1): {1}', 'Ejemplo (mesa 1): {1}'],        ['Korting ({1})', 'Discount ({1})', 'Descuento ({1})'],
        ['Cadeaubon {1}', 'Gift card {1}', 'Tarjeta regalo {1}'],
        ['Nieuwe cadeaubon voor {1}', 'New gift card for {1}', 'Nueva tarjeta regalo para {1}'],
        ['Blijft over op de cadeaubon: {1}', 'Remaining on the gift card: {1}', 'Queda en la tarjeta regalo: {1}'],
        ['Cadeaubon toegepast (tegoed {1})', 'Gift card applied (balance {1})', 'Tarjeta regalo aplicada (saldo {1})'],
        ['{1} geldt niet meer', '{1} is no longer valid', '{1} ya no es válido'],
        ['Cadeaubon {1} verwijderen? Er staat nog {2} op. De gast kan hem daarna niet meer gebruiken', 'Delete gift card {1}? It still has {2} on it. The guest will no longer be able to use it', '¿Eliminar la tarjeta regalo {1}? Todavía tiene {2}. El cliente ya no podrá usarla'],
        ['{1} ({2} tafels)', '{1} ({2} tables)', '{1} ({2} mesas)'],
        ['{1} ({2} tafel)', '{1} ({2} table)', '{1} ({2} mesa)'],
        ['met {1} gasten in deze periode. Aantal gasten per dag van de week (alle bezoeken, ook als er niet betaald is)', 'with {1} guests in this period. Number of guests per day of the week (all visits, including unpaid ones)', 'con {1} clientes en este periodo. Número de clientes por día de la semana (todas las visitas, también las no pagadas)'],
        ['({1} medewerkers)', '({1} staff)', '({1} empleados)'],
        ['({1} medewerker)', '({1} staff member)', '({1} empleado)'],
        ['Er bestaat al een arrangement met de naam \'{1}\'', 'A package named \'{1}\' already exists', 'Ya existe un menú llamado \'{1}\''],
        ['Arrangement {1} definitief verwijderen?', 'Delete package {1} permanently?', '¿Eliminar el menú {1} definitivamente?'],
        ['Kies wat jullie in die tijd nog willen bestellen (prijs voor {1} personen):', 'Choose what you still want to order in that time (price for {1} people):', 'Elegid qué queréis pedir en ese tiempo (precio para {1} personas):'],
        ['Kies wat jullie in die tijd nog willen bestellen (prijs voor {1} persoon):', 'Choose what you still want to order in that time (price for {1} person):', 'Elegid qué queréis pedir en ese tiempo (precio para {1} persona):'],
        ['({1} p.p.)', '({1} p.p.)', '({1} p.p.)'],
        ['Drankjes verlengd tot {1}', 'Drinks extended until {1}', 'Bebidas ampliadas hasta {1}'],
        ['Verlenging alleen drankjes {1} min ({2} pers.)', 'Extension drinks only {1} min ({2} pers.)', 'Ampliación solo bebidas {1} min ({2} pers.)'],
        ['{1} min', '{1} min', '{1} min'],
        ['Met {1} minuten extra wordt dat', 'With {1} extra minutes that becomes', 'Con {1} minutos más será'],
        ['Met {1} minuten extra kunnen jullie blijven tot', 'With {1} extra minutes you can stay until', 'Con {1} minutos más podéis quedaros hasta'],
        ['Je hebt de tafel {1} uur', 'You have the table for {1} hours', 'Tenéis la mesa {1} horas'],
        ['{1} tafels vrij', '{1} tables free', '{1} mesas libres'],
        ['Met meer dan {1} personen? Bel ons, dan regelen we samen een plek', 'More than {1} people? Call us and we will arrange a place together', '¿Más de {1} personas? Llámanos y buscamos un sitio juntos'],
        ['Tafels {1} - {2}', 'Tables {1} - {2}', 'Mesas {1} - {2}'],
        ['{1} (vandaag)', '{1} (today)', '{1} (hoy)'],
        ['Reservering van {1} op {2} om {3} annuleren', 'Cancel the reservation of {1} on {2} at {3}', '¿Cancelar la reserva de {1} el {2} a las {3}'],
        ['{1}. De gast heeft een e-mail gekregen', '{1}. The guest has received an email', '{1}. El cliente ha recibido un correo'],
        ['{1}. Let op: de e-mail kon niet worden verstuurd – bel de gast even', '{1}. Note: the email could not be sent – please call the guest', '{1}. Atención: no se pudo enviar el correo, llama al cliente'],
        ['Gereserveerd {1}', 'Reserved {1}', 'Reservada {1}'],
        ['QR-code van tafel {1} groot tonen', 'Show QR code of table {1} full screen', 'Mostrar el código QR de la mesa {1} en grande'],
        ['QR-code van tafel {1}', 'QR code of table {1}', 'Código QR de la mesa {1}'],
        ['{1} · Tafel {2}', '{1} · Table {2}', '{1} · Mesa {2}'],
        ['Je kunt maximaal {1} dagen vooruit reserveren', 'You can book up to {1} days ahead', 'Puedes reservar con un máximo de {1} días de antelación'],
        ['Online reserveren kan voor 1 tot {1} personen. Bel ons voor een grotere groep', 'Online booking is possible for 1 to {1} people. Call us for a larger group', 'Se puede reservar online para 1 a {1} personas. Llámanos para grupos más grandes'],
        ['Tafel {1} heeft maar {2} plaatsen', 'Table {1} only has {2} seats', 'La mesa {1} solo tiene {2} plazas'],
        ['Tafel {1} is op dat moment al gereserveerd', 'Table {1} is already reserved at that time', 'La mesa {1} ya está reservada a esa hora'],
        ['Tafel {1}: gereserveerd', 'Table {1}: reserved', 'Mesa {1}: reservada'],
        ['Tafel {1}: te klein', 'Table {1}: too small', 'Mesa {1}: demasiado pequeña'],
        ['Jullie zijn met {1}: daarvoor schuiven we {2} tafels aan elkaar', 'You are {1} people: we will push {2} tables together for you', 'Sois {1}: juntaremos {2} mesas para vosotros'],
        ['Gekozen: tafel {1}. Kies er nog {2} direct naast', 'Chosen: table {1}. Choose {2} more right next to it', 'Elegida: mesa {1}. Elige {2} más justo al lado'],
        ['Tik op een vrije (groene) tafel: we kiezen er {1} vrije tafel(s) naast. Tik op een tafel om hem te wisselen', 'Tap a free (green) table: we will add {1} free table(s) next to it. Tap a table to swap it', 'Toca una mesa libre (verde): añadimos {1} mesa(s) libre(s) al lado. Toca una mesa para cambiarla'],
        ['Rond tafel {1} zijn niet genoeg vrije tafels. Kies een andere plek', 'There are not enough free tables around table {1}. Choose another spot', 'No hay suficientes mesas libres alrededor de la mesa {1}. Elige otro sitio'],
        ['Met {1} personen hebben jullie {2} tafels nodig. Kies er nog {3} naast de gekozen tafel', 'With {1} people you need {2} tables. Choose {3} more next to the chosen table', 'Con {1} personas necesitáis {2} mesas. Elige {3} más al lado de la mesa elegida'],
        ['Met {1} personen zijn {2} tafels genoeg. Haal een tafel weg', 'With {1} people {2} tables are enough. Remove a table', 'Con {1} personas bastan {2} mesas. Quita una mesa'],
        ['Tafel {1} is net gereserveerd door iemand anders. Kies andere tafels of een andere tijd', 'Table {1} has just been booked by someone else. Choose other tables or another time', 'Alguien acaba de reservar la mesa {1}. Elige otras mesas u otra hora'],
        ['Groep van {1}: kies {2} tafels die naast of achter elkaar staan', 'Group of {1}: choose {2} tables next to or behind each other', 'Grupo de {1}: elige {2} mesas contiguas'],
        ['{1} · gesloten', '{1} · closed', '{1} · cerrado'],
        ['Vul bij {1} een geldige openings- en sluitingstijd in', 'Fill in a valid opening and closing time for {1}', 'Introduce una hora de apertura y cierre válida para el {1}'],
        ['Bij {1} zijn de openings- en sluitingstijd hetzelfde', 'The opening and closing time for {1} are the same', 'La hora de apertura y cierre del {1} son iguales'],
        ['({1} p.st.)', '({1} each)', '({1} c/u)'],
        ['{1} p.st', '{1} each', '{1} c/u'],
        ['Cadeaubon {1} verwijderen?', 'Delete gift card {1}?', '¿Eliminar la tarjeta regalo {1}?'],
        ['Deze cadeaubon staat nog op de rekening van tafel {1}. Verwijderen? Hij verdwijnt dan ook van die rekening', 'This gift card is still on the bill of table {1}. Delete it? It will also be removed from that bill', 'Esta tarjeta regalo aún está en la cuenta de la mesa {1}. ¿Eliminarla? También desaparecerá de esa cuenta'],
        ['Cadeaubon {1} is verwijderd', 'Gift card {1} has been deleted', 'Se ha eliminado la tarjeta regalo {1}'],
        ['Kortingscode {1} verwijderen?', 'Delete discount code {1}?', '¿Eliminar el código de descuento {1}?'],
        ['Kortingscode {1} is aangemaakt', 'Discount code {1} has been created', 'Se ha creado el código de descuento {1}'],
        ['De code {1} bestaat al', 'The code {1} already exists', 'El código {1} ya existe'],
        ['Cadeaubon {1} is aangemaakt. Klik op "Bekijken" om hem te printen', 'Gift card {1} has been created. Click "View" to print it', 'Se ha creado la tarjeta regalo {1}. Pulsa "Ver" para imprimirla'],
    ];

    // ======================= VERTAALLOGICA =======================
    let taal = 'nl';
    try { taal = localStorage.getItem('taal') || 'nl'; } catch (e) {}
    if (!TALEN[taal]) taal = 'nl';
    const index = { en: 0, es: 1 };

    const patronen = P.map(([nl, en, es]) => {
        const delen = nl.split(/(\{\d\})/);
        const volgorde = [];
        const bron = delen.map(d => {
            const m = d.match(/^\{(\d)\}$/);
            if (m) { volgorde.push(Number(m[1])); return '(.+?)'; }
            return d.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }).join('');
        const vasteTekst = nl.replace(/\{\d\}/g, '').length;
        return { re: new RegExp('^' + bron + '$', 's'), volgorde, en, es, vasteTekst };
    }).sort((a, b) => b.vasteTekst - a.vasteTekst);   // specifieke patronen eerst

    function vertaalKern(kern, diepte) {
        if (!kern) return undefined;
        if (Object.prototype.hasOwnProperty.call(W, kern)) return W[kern][index[taal]];
        if (diepte > 2) return undefined;
        for (const p of patronen) {
            const m = kern.match(p.re);
            if (!m) continue;
            let uit = p[taal];
            p.volgorde.forEach((nr, i) => {
                const waarde = m[i + 1];
                const vertaald = vertaalTekst(waarde, diepte + 1);
                uit = uit.split('{' + nr + '}').join(vertaald !== undefined ? vertaald : waarde);
            });
            return uit;
        }
        return undefined;
    }

    // Probeert de tekst zelf, en daarna zonder emoji's/leestekens aan het begin en eind
    function vertaalTekst(tekst, diepte = 0) {
        if (taal === 'nl' || !tekst || !/\p{L}/u.test(tekst)) return undefined;
        const pogingen = [
            /^(\s*)([\s\S]*?)(\s*)$/u,
            /^([^\p{L}\p{N}(]*)([\s\S]*?)([^\p{L}\p{N})]*)$/u,
            /^([^\p{L}\p{N}]*)([\s\S]*?)([^\p{L}\p{N}]*)$/u,
        ];
        for (const re of pogingen) {
            const m = tekst.match(re);
            if (!m || !m[2]) continue;
            const vertaald = vertaalKern(m[2], diepte);
            if (vertaald !== undefined) return m[1] + vertaald + m[3];
        }
        return undefined;
    }

    function vertaal(tekst) {
        const v = vertaalTekst(String(tekst));
        return v !== undefined ? v : tekst;
    }
    window.vertaal = vertaal;

    // ---- Tekstnodes ----
    const OVERSLAAN = new Set(['SCRIPT', 'STYLE', 'TEXTAREA', 'NOSCRIPT', 'CODE']);

    function doeTekstNode(node) {
        const ouder = node.parentNode;
        if (!ouder || OVERSLAAN.has(ouder.nodeName) || (ouder.closest && ouder.closest('[data-geen-vertaling]'))) return;
        // Heeft de pagina zelf de tekst veranderd? Dan is dat de nieuwe (Nederlandse) bron
        if (node.__nl === undefined || node.nodeValue !== node.__uit) node.__nl = node.nodeValue;
        const vertaald = taal === 'nl' ? undefined : vertaalTekst(node.__nl);
        const nieuw = vertaald !== undefined ? vertaald : node.__nl;
        node.__uit = nieuw;
        if (node.nodeValue !== nieuw) node.nodeValue = nieuw;
    }

    const ATTRIBUTEN = ['placeholder', 'title', 'aria-label', 'label'];
    function doeElement(el) {
        if (el.closest && el.closest('[data-geen-vertaling]')) return;
        ATTRIBUTEN.forEach(a => {
            if (!el.hasAttribute(a)) return;
            const sleutel = 'nl' + a.replace(/-./g, x => x[1].toUpperCase());
            const huidig = el.getAttribute(a);
            if (el.dataset[sleutel] === undefined || huidig !== el.__uitAttr?.[a]) el.dataset[sleutel] = huidig;
            const bron = el.dataset[sleutel];
            const vertaald = taal === 'nl' ? undefined : vertaalTekst(bron);
            const nieuw = vertaald !== undefined ? vertaald : bron;
            el.__uitAttr = Object.assign(el.__uitAttr || {}, { [a]: nieuw });
            if (huidig !== nieuw) el.setAttribute(a, nieuw);
        });
        if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) {
            if (el.dataset.nlValue === undefined) el.dataset.nlValue = el.value;
            const v = taal === 'nl' ? undefined : vertaalTekst(el.dataset.nlValue);
            el.value = v !== undefined ? v : el.dataset.nlValue;
        }
    }

    let bezig = false;
    function vertaalBoom(wortel) {
        if (!wortel) return;
        bezig = true;
        if (wortel.nodeType === Node.TEXT_NODE) {
            doeTekstNode(wortel);
        } else if (wortel.nodeType === Node.ELEMENT_NODE) {
            if (OVERSLAAN.has(wortel.nodeName)) { bezig = false; return; }
            doeElement(wortel);
            wortel.querySelectorAll('*').forEach(doeElement);
            const walker = document.createTreeWalker(wortel, NodeFilter.SHOW_TEXT);
            let n;
            while ((n = walker.nextNode())) doeTekstNode(n);
        }
        bezig = false;
    }

    let nlTitel = null;
    function vertaalTitel() {
        if (nlTitel === null || document.title !== vertaalTitel.__uit) nlTitel = document.title;
        const v = taal === 'nl' ? undefined : vertaalTekst(nlTitel);
        document.title = v !== undefined ? v : nlTitel;
        vertaalTitel.__uit = document.title;
    }

    // Nieuwe of veranderde inhoud meteen vertalen
    const observer = new MutationObserver(mutaties => {
        if (bezig) return;
        for (const m of mutaties) {
            if (m.type === 'childList') {
                m.addedNodes.forEach(vertaalBoom);
            } else if (m.type === 'characterData') {
                if (m.target.nodeValue !== m.target.__uit) vertaalBoom(m.target);
            } else if (m.type === 'attributes') {
                const el = m.target;
                if (el.getAttribute(m.attributeName) !== el.__uitAttr?.[m.attributeName]) doeElement(el);
            }
        }
    });

    // Pop-upmeldingen ook vertalen
    const origAlert = window.alert.bind(window);
    const origConfirm = window.confirm.bind(window);
    const origPrompt = window.prompt.bind(window);
    window.alert = msg => origAlert(vertaal(msg));
    window.confirm = msg => origConfirm(vertaal(msg));
    window.prompt = (msg, standaard) => origPrompt(vertaal(msg), standaard);

    // ======================= TAALKNOP =======================
    // Een knop op elke plek met id="taal-plek" of class="taal-plek" (geen plek: zwevend rechtsboven)
    function maakKiezer() {
        const plekken = document.querySelectorAll('#taal-plek, .taal-plek');
        if (plekken.length) plekken.forEach(maakEenKiezer);
        else maakEenKiezer(null);
    }

    function maakEenKiezer(plek) {
        const kiezer = document.createElement('div');
        kiezer.className = 'taal-kiezer' + (plek ? '' : ' zwevend');
        kiezer.setAttribute('data-geen-vertaling', '');
        kiezer.innerHTML = `
            <button type="button" class="taal-knop" aria-label="Taal / Language / Idioma" aria-haspopup="true">
                <span class="taal-vlag">${TALEN[taal].vlag}</span> <span class="taal-code">${taal.toUpperCase()}</span> ▾
            </button>
            <div class="taal-menu" hidden>
                ${Object.entries(TALEN).map(([code, t]) =>
                    `<button type="button" data-taal="${code}" class="${code === taal ? 'gekozen' : ''}">${t.vlag} ${t.naam}</button>`).join('')}
            </div>`;
        (plek || document.body).appendChild(kiezer);

        const knop = kiezer.querySelector('.taal-knop');
        const menu = kiezer.querySelector('.taal-menu');
        knop.addEventListener('click', e => {
            e.stopPropagation();
            menu.hidden = !menu.hidden;
            if (!menu.hidden) {
                // Het menu zweeft los boven de pagina (position: fixed), zodat een schuifbare
                // knoppenrij het niet kan afknippen. Openen naar de kant waar genoeg ruimte is.
                const r = knop.getBoundingClientRect();
                const naarRechts = r.left + 170 < window.innerWidth;
                menu.style.position = 'fixed';
                menu.style.top = Math.round(r.bottom + 6) + 'px';
                menu.style.left = naarRechts ? Math.round(Math.max(8, r.left)) + 'px' : 'auto';
                menu.style.right = naarRechts ? 'auto' : Math.round(Math.max(8, window.innerWidth - r.right)) + 'px';
            }
        });
        document.addEventListener('click', () => { menu.hidden = true; });
        // Bij scrollen of draaien van het scherm het menu sluiten (anders zweeft het op de verkeerde plek)
        window.addEventListener('scroll', e => { if (!menu.contains(e.target)) menu.hidden = true; }, true);
        window.addEventListener('resize', () => { menu.hidden = true; });
        menu.querySelectorAll('button').forEach(b => b.addEventListener('click', () => zetTaal(b.dataset.taal)));
    }

    function zetTaal(nieuw) {
        if (!TALEN[nieuw]) return;
        taal = nieuw;
        try { localStorage.setItem('taal', taal); } catch (e) {}
        document.documentElement.lang = taal;
        document.querySelectorAll('.taal-kiezer').forEach(k => {
            k.querySelector('.taal-vlag').textContent = TALEN[taal].vlag;
            k.querySelector('.taal-code').textContent = taal.toUpperCase();
            k.querySelectorAll('.taal-menu button').forEach(b => b.classList.toggle('gekozen', b.dataset.taal === taal));
            k.querySelector('.taal-menu').hidden = true;
        });
        vertaalBoom(document.body);
        vertaalTitel();
    }
    window.zetTaal = zetTaal;

    const stijl = document.createElement('style');
    stijl.textContent = `
        .taal-kiezer { position: relative; display: inline-block; font-family: 'Segoe UI', sans-serif; }
        .taal-kiezer.zwevend { position: fixed; top: 10px; right: 10px; z-index: 3000; }
        .taal-knop {
            background: rgba(255,255,255,0.92); color: #2b1d19; border: 1px solid rgba(0,0,0,0.15);
            border-radius: 20px; padding: 6px 10px; font-weight: bold; font-size: 0.8rem; cursor: pointer;
            margin: 0; line-height: 1.2; font-family: inherit; white-space: nowrap;
        }
        .taal-knop:hover { background: white; }
        .taal-menu {
            position: absolute; right: 0; top: calc(100% + 6px); background: white; border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2); overflow: hidden; z-index: 3001; min-width: 150px;
        }
        .taal-menu button {
            display: block; width: 100%; text-align: left; background: white; color: #2b1d19; border: none;
            padding: 10px 14px; font-size: 0.9rem; cursor: pointer; margin: 0; border-radius: 0; font-family: inherit; font-weight: normal;
        }
        .taal-menu button:hover { background: #fbf1e6; }
        .taal-menu button.gekozen { font-weight: bold; background: #fff3e6; }
        @media (max-width: 480px) { .taal-code { display: none; } }
        @media print { .taal-kiezer { display: none !important; } }
    `;

    function start() {
        document.head.appendChild(stijl);
        document.documentElement.lang = taal;
        maakKiezer();
        vertaalBoom(document.body);
        vertaalTitel();
        observer.observe(document.body, {
            childList: true, subtree: true, characterData: true,
            attributes: true, attributeFilter: ATTRIBUTEN,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
