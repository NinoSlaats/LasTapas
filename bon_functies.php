<?php
/*
 * Las Tapas - bon_functies.php
 * Maakt de bon (rekening) van een tafel: als HTML voor e-mail en printen, en als platte tekst.
 * De bon komt in de taal die de gast op zijn telefoon had gekozen (nl, en of es).
 */
require_once __DIR__ . '/gedeeld.php';
require_once __DIR__ . '/mailer.php';

const BON_TEKSTEN = [
    'nl' => [
        'titel' => 'Bon', 'datum' => 'Datum', 'tafel' => 'Tafel', 'naam' => 'Naam', 'arrangement' => 'Arrangement',
        'totaal' => 'Totaal', 'betaald' => 'Betaald', 'contant' => 'contant', 'online' => 'online',
        'bedankt' => '¡Muchas gracias! Bedankt voor jullie bezoek. Graag tot ziens bij Las Tapas.',
        'onderwerp' => 'Jullie bon van Las Tapas', 'btw' => 'Prijzen inclusief btw.',
        'cadeau_titel' => 'Cadeaubon', 'cadeau_voor' => 'voor %s', 'cadeau_rest' => 'Nog over op je cadeaubon', 'cadeau_op' => 'Je cadeaubon is hiermee op',
        'cadeau_uitleg' => 'Geldig zodra de rekening betaald is. Vul de code bij je volgende bezoek in bij de rekening.',
    ],
    'en' => [
        'titel' => 'Receipt', 'datum' => 'Date', 'tafel' => 'Table', 'naam' => 'Name', 'arrangement' => 'Package',
        'totaal' => 'Total', 'betaald' => 'Paid', 'contant' => 'cash', 'online' => 'online',
        'bedankt' => '¡Muchas gracias! Thank you for your visit. We hope to see you again at Las Tapas.',
        'onderwerp' => 'Your receipt from Las Tapas', 'btw' => 'Prices include VAT.',
        'cadeau_titel' => 'Gift card', 'cadeau_voor' => 'for %s', 'cadeau_rest' => 'Remaining on your gift card', 'cadeau_op' => 'Your gift card is now used up',
        'cadeau_uitleg' => 'Valid once the bill has been paid. Enter the code at the bill on your next visit.',
    ],
    'es' => [
        'titel' => 'Recibo', 'datum' => 'Fecha', 'tafel' => 'Mesa', 'naam' => 'Nombre', 'arrangement' => 'Menú',
        'totaal' => 'Total', 'betaald' => 'Pagado', 'contant' => 'en efectivo', 'online' => 'online',
        'bedankt' => '¡Muchas gracias por vuestra visita! Esperamos veros pronto en Las Tapas.',
        'onderwerp' => 'Vuestro recibo de Las Tapas', 'btw' => 'Precios con IVA incluido.',
        'cadeau_titel' => 'Tarjeta regalo', 'cadeau_voor' => 'para %s', 'cadeau_rest' => 'Saldo restante en tu tarjeta regalo', 'cadeau_op' => 'Tu tarjeta regalo se ha agotado',
        'cadeau_uitleg' => 'Válida una vez pagada la cuenta. Introduce el código en la cuenta en tu próxima visita.',
    ],
];

function bonEuro($bedrag) {
    return ((float)$bedrag < 0 ? '- ' : '') . '€ ' . number_format(abs((float)$bedrag), 2, ',', '.');
}

// Alle gegevens die op de bon komen
function bonGegevens($t, $taal) {
    $taal = isset(BON_TEKSTEN[$taal]) ? $taal : 'nl';
    return [
        'taal' => $taal,
        'tk' => BON_TEKSTEN[$taal],
        'rekening' => berekenRekening($t, $taal),
        'datum' => date('d-m-Y H:i'),
        'methode' => $t['betaalmethode'] ?: 'contant',
        'cadeaubonnen' => empty($t['sessie']) ? [] : alleRijen(q(
            "SELECT code, bedrag, voor_naam FROM cadeaubonnen WHERE tafel_sessie = ? AND status IN ('open', 'actief') ORDER BY id",
            "s", [$t['sessie']])),
    ];
}

// Blok met de codes van nieuw gekochte cadeaubonnen (HTML)
function bonCadeauHtml($g) {
    $tk = $g['tk'];
    $html = '';
    foreach ($g['cadeaubonnen'] as $b) {
        $voor = $b['voor_naam'] !== '' ? ' ' . esc(sprintf($tk['cadeau_voor'], $b['voor_naam'])) : '';
        $html .= '<tr><td style="padding:0 22px 14px;"><div style="border:2px dashed #e9a23b;border-radius:10px;padding:12px;text-align:center;background:#fff8e7;">'
               . '<div style="font-size:13px;color:#786a63;">🎁 ' . esc($tk['cadeau_titel']) . ' ' . bonEuro($b['bedrag']) . $voor . '</div>'
               . '<div style="font-family:Consolas,monospace;font-size:20px;font-weight:bold;letter-spacing:2px;color:#a8232b;margin:6px 0;">' . esc(toonCadeauCode($b['code'])) . '</div>'
               . '<div style="font-size:11px;color:#786a63;">' . esc($tk['cadeau_uitleg']) . '</div></div></td></tr>';
    }
    return $html;
}

// HTML-bon (werkt in e-mailprogramma's: alleen tabellen en inline stijlen)
function bonHtml($t, $taal = 'nl') {
    $g = bonGegevens($t, $taal);
    $tk = $g['tk'];
    $regels = '';
    foreach ($g['rekening']['regels'] as $r) {
        $regels .= '<tr><td style="padding:6px 0;border-bottom:1px solid #f0e6da;">' . esc($r['omschrijving']) . '</td>'
                 . '<td style="padding:6px 0;border-bottom:1px solid #f0e6da;text-align:right;white-space:nowrap;">' . bonEuro($r['bedrag']) . '</td></tr>';
        if (($r['soort'] ?? '') === 'cadeau_ingezet') {
            $regels .= '<tr><td colspan="2" style="padding:2px 0 6px;font-size:12px;color:#1e8449;border-bottom:1px solid #f0e6da;">🎁 '
                     . ($r['saldo_na'] > 0 ? esc($tk['cadeau_rest']) . ': <strong>' . bonEuro($r['saldo_na']) . '</strong>' : esc($tk['cadeau_op']))
                     . '</td></tr>';
        }
    }
    $info = function ($label, $waarde) {
        return '<tr><td style="padding:2px 0;color:#786a63;">' . esc($label) . '</td><td style="padding:2px 0;text-align:right;">' . esc($waarde) . '</td></tr>';
    };
    return '<div style="background:#fbf5ea;padding:20px 10px;font-family:Segoe UI,Arial,sans-serif;color:#2b1d19;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:420px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;">'
        . '<tr><td style="background:#a8232b;color:#ffffff;text-align:center;padding:22px 16px 16px;">'
        . '<div style="font-family:Georgia,serif;font-size:26px;font-weight:bold;">🍷 Las Tapas</div>'
        . '<div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;opacity:.85;margin-top:4px;">Taberna española</div></td></tr>'
        . '<tr><td style="height:8px;background:#1f4e8c;background-image:repeating-linear-gradient(90deg,#1f4e8c 0 8px,#fdf6e3 8px 16px);"></td></tr>'
        . '<tr><td style="padding:18px 22px 6px;">'
        . '<div style="font-family:Georgia,serif;font-size:20px;color:#a8232b;font-weight:bold;margin-bottom:10px;">' . esc($tk['titel']) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">'
        . $info($tk['datum'], $g['datum'])
        . $info($tk['tafel'], (string)intval($t['tafel']))
        . $info($tk['naam'], $t['gast_naam'])
        . $info($tk['arrangement'], $t['pakket'])
        . '</table></td></tr>'
        . '<tr><td style="padding:10px 22px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">'
        . $regels
        . '<tr><td style="padding:10px 0 4px;font-weight:bold;font-size:17px;">' . esc($tk['totaal']) . '</td>'
        . '<td style="padding:10px 0 4px;text-align:right;font-weight:bold;font-size:17px;">' . bonEuro($g['rekening']['totaal']) . '</td></tr>'
        . '<tr><td colspan="2" style="font-size:12px;color:#786a63;">' . esc($tk['betaald']) . ' ' . esc($tk[$g['methode']] ?? $g['methode']) . ' · ' . esc($tk['btw']) . '</td></tr>'
        . '</table></td></tr>'
        . bonCadeauHtml($g)
        . '<tr><td style="padding:14px 22px 22px;text-align:center;font-size:13px;color:#5f6f2f;">' . esc($tk['bedankt']) . '</td></tr>'
        . '</table></div>';
}

// Platte-tekstversie (voor e-mailprogramma's zonder HTML)
function bonTekst($t, $taal = 'nl') {
    $g = bonGegevens($t, $taal);
    $tk = $g['tk'];
    $r = "LAS TAPAS - Taberna española\n" . strtoupper($tk['titel']) . "\n\n"
       . "{$tk['datum']}: {$g['datum']}\n{$tk['tafel']}: " . intval($t['tafel']) . "\n{$tk['naam']}: {$t['gast_naam']}\n{$tk['arrangement']}: {$t['pakket']}\n\n";
    foreach ($g['rekening']['regels'] as $regel) {
        $r .= str_pad($regel['omschrijving'], 32) . ' ' . bonEuro($regel['bedrag']) . "\n";
        if (($regel['soort'] ?? '') === 'cadeau_ingezet') {
            $r .= '  ' . ($regel['saldo_na'] > 0 ? $tk['cadeau_rest'] . ': ' . bonEuro($regel['saldo_na']) : $tk['cadeau_op']) . "\n";
        }
    }
    $r .= str_repeat('-', 44) . "\n" . str_pad($tk['totaal'], 32) . ' ' . bonEuro($g['rekening']['totaal']) . "\n\n"
        . $tk['betaald'] . ' ' . ($tk[$g['methode']] ?? $g['methode']) . "\n\n";
    foreach ($g['cadeaubonnen'] as $b) {
        $r .= $tk['cadeau_titel'] . ' ' . bonEuro($b['bedrag']) . ($b['voor_naam'] !== '' ? ' ' . sprintf($tk['cadeau_voor'], $b['voor_naam']) : '')
            . ': ' . toonCadeauCode($b['code']) . "\n" . $tk['cadeau_uitleg'] . "\n\n";
    }
    $r .= $tk['bedankt'] . "\n";
    return $r;
}

// Bon per e-mail naar de gast sturen. Geeft [gelukt, melding] terug.
function stuurBonPerMail($t) {
    $taal = $t['bon_taal'] ?? 'nl';
    $tk = BON_TEKSTEN[$taal] ?? BON_TEKSTEN['nl'];
    return stuurMail($t['bon_email'], $tk['onderwerp'] . ' (' . $tk['tafel'] . ' ' . intval($t['tafel']) . ')',
                     bonHtml($t, $taal), bonTekst($t, $taal));
}
