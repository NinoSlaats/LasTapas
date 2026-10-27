<?php
session_start();
// Beveiliging: Alleen chefs én de baas hebben toegang tot de voorraad
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['chef', 'bar', 'baas'], true)) {
    header("Location: login.html"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Tapas - Voorraadbeheer</title>
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
        .btn-terug {
            background: #2980b9;
            color: white;
            margin-right: 5px;
        }
        .btn-terug:hover { background: #2471a3; }
        .btn-uitlog {
            background: #c0392b;
            color: white;
        }
        .btn-uitlog:hover { opacity: 0.85; }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #fcf8f2;
            color: var(--inkt);
            font-weight: bold;
        }

        tr:hover {
            background-color: #fcf8f2;
        }

        .badge-uitverkocht {
            background: #78281f;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .badge-laag {
            background: #fadbd8;
            color: #c0392b;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .badge-voldoende {
            background: #eafaf1;
            color: #27ae60;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .btn-aanpassen {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-aanpassen:hover { opacity: 0.85; }
        
        input[type="number"] {
            width: 70px;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        #voorraad-tabel-container {
            overflow-x: auto;
        }

        /* ===== Responsive: tablet/telefoon ===== */
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
            .card { padding: 15px; }
        }

        @media (max-width: 480px) {
            .header-title h1 { font-size: 1.3rem; }
            th, td { padding: 8px 10px; font-size: 0.85rem; }
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
        }
    </style>
    <script src="taal.js?v=20261007c" defer></script>
</head>
<body>

    <header>
        <div class="header-title">
            <h1>📦 Las Tapas - Voorraadbeheer</h1>
            <p>Overzicht en beheer van ingrediënten en drankjes</p>
        </div>
        <div class="user-info">
            <div class="user-details">
                <span>Ingelogd als: <strong><?php echo htmlspecialchars($_SESSION['gebruiker']); ?></strong></span>
            </div>
            <div>
                <span id="taal-plek" style="margin-right: 5px;"></span>
                <?php require_once __DIR__ . '/navigatie.php'; toonNavigatie(); ?>
            </div>
        </div>
    </header>
    <?php toonSectieTabs(); ?>

    <div class="container">
        <div class="card">
            <h2>Actuele Voorraad</h2>
            <p>Hier kun je zien hoeveel er nog op voorraad is per gerecht of product. Bij 0 stuks is het uitverkocht, tot en met 5 stuks kleurt het rood.</p>
            
            <div id="voorraad-tabel-container">
                <p>Voorraad laden...</p>
            </div>
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

        function laadVoorraad() {
            fetch('voorraad_api.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('voorraad-tabel-container');
                    
                    if (!data || data.length === 0) {
                        container.innerHTML = '<p>Geen voorraadgegevens gevonden.</p>';
                        return;
                    }

                    let html = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Product / Gerecht</th>
                                    <th>Aantal op voorraad</th>
                                    <th>Status</th>
                                    <th>Actie</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.forEach(item => {
                        let statusBadge = '';
                        if (item.aantal === 0) {
                            statusBadge = `<span class="badge-uitverkocht">❌ Uitverkocht (0)</span>`;
                        } else if (item.aantal <= 5) {
                            statusBadge = `<span class="badge-laag">⚠️ Bijna op (${item.aantal})</span>`;
                        } else {
                            statusBadge = `<span class="badge-voldoende">Voldoende (${item.aantal})</span>`;
                        }

                        html += `
                            <tr>
                                <td><strong>${String(item.naam).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}</strong></td>
                                <td>
                                    <input type="number" id="aantal-${item.id}" value="${item.aantal}" min="0" oninput="valideerMin(this)">
                                </td>
                                <td>${statusBadge}</td>
                                <td>
                                    <button class="btn-aanpassen" onclick="updateVoorraad(${item.id})">Opslaan</button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table>`;
                    container.innerHTML = html;
                })
                .catch(err => {
                    console.error('Fout bij ophalen voorraad:', err);
                    document.getElementById('voorraad-tabel-container').innerHTML = '<p style="color: red;">Fout bij laden van voorraadgegevens.</p>';
                });
        }

        function valideerMin(input) {
            if (input.value < 0) {
                input.value = 0;
            }
        }

        function updateVoorraad(id) {
            let inputElement = document.getElementById(`aantal-${id}`);
            let nieuwAantal = parseInt(inputElement.value);

            if (isNaN(nieuwAantal) || nieuwAantal < 0) {
                nieuwAantal = 0;
                inputElement.value = 0;
            }

            fetch('voorraad_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actie: 'update', id: id, aantal: nieuwAantal })
            })
            .then(res => res.json())
            .then(data => {
                if (data.succes) {
                    laadVoorraad();
                } else {
                    alert('Kon voorraad niet bijwerken.');
                }
            })
            .catch(err => console.error('Fout bij updaten:', err));
        }

        laadVoorraad();
    </script>
</body>
</html>