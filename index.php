<?php
// Router: https://qr.pausefish.com/<code>  →  doorsturen naar de ingestelde bestemming.
// Zonder code → naar het beheergedeelte.

require __DIR__ . '/inc/bootstrap.php';

$code = strtolower(trim((string)($_GET['c'] ?? ''), "/ \t\n\r"));

if ($code === '') {
    redirect(base_url('admin/'));
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex');

$row = null;
if (preg_match(CODE_PATTERN, $code)) {
    $st = db()->prepare('SELECT id, target_url FROM qr_codes WHERE code = ?');
    $st->execute([$code]);
    $row = $st->fetch() ?: null;
}

if ($row && valid_url($row['target_url'])) {
    db()->prepare('UPDATE qr_codes SET scan_count = scan_count + 1, last_scan_at = NOW() WHERE id = ?')->execute([$row['id']]);
    // 302 (tijdelijk), zodat browsers de bestemming niet onthouden en wijzigingen direct werken
    header('Location: ' . $row['target_url'], true, 302);
    exit;
}

if (!empty($CONFIG['fallback_url']) && valid_url($CONFIG['fallback_url'])) {
    header('Location: ' . $CONFIG['fallback_url'], true, 302);
    exit;
}

http_response_code(404);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR-code niet gevonden</title>
  <style>
    body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; padding: 16px; box-sizing: border-box; background: #f5f6f8; color: #1c1f24; text-align: center; }
    @media (prefers-color-scheme: dark) { body { background: #121418; color: #e8eaee; } }
  </style>
</head>
<body>
  <div>
    <h1>QR-code niet gevonden</h1>
    <p>Deze QR-code is niet (meer) actief.</p>
  </div>
</body>
</html>
