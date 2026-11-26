<?php
// Alleen voor lokaal ontwikkelen met de ingebouwde PHP-server:
//   php -S localhost:8000 dev/router.php
// Doet hetzelfde als .htaccess (die de ingebouwde server niet leest).
// Hoeft niet geüpload te worden; op de server blokkeert .htaccess deze map.

$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/(\.git|\.gitignore|inc/|dev/|config\.php|config\.sample\.php|schema\.sql|README\.md)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
if (is_file($root . $path)) return false;
if (is_dir($root . $path) && is_file(rtrim($root . $path, '/') . '/index.php')) {
    if (substr($path, -1) !== '/') { header('Location: ' . $path . '/'); exit; }
    chdir($root . $path);
    require $root . $path . 'index.php';
    return true;
}
if (preg_match('#^/([A-Za-z0-9_-]+)/?$#', $path, $m)) {
    $_GET['c'] = $m[1];
    require $root . '/index.php';
    return true;
}
http_response_code(404);
echo 'Niet gevonden';
