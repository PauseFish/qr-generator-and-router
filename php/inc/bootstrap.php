<?php
// Gedeelde code: configuratie, database, sessie, login, CSRF en hulpfuncties.

declare(strict_types=1);

// Nooit technische foutdetails aan bezoekers tonen; wel loggen (zie het foutlogboek in DirectAdmin).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $ex) {
    error_log('[qr-dashboard] ' . $ex);
    if (!headers_sent()) http_response_code(500);
    echo 'Er ging iets mis. Probeer het later opnieuw.';
});

if (!is_file(__DIR__ . '/../config.php')) {
    http_response_code(500);
    exit('config.php ontbreekt. Kopieer config.sample.php naar config.php en vul je databasegegevens in.');
}
$CONFIG = require __DIR__ . '/../config.php';
$CONFIG['base_url'] = rtrim($CONFIG['base_url'], '/') . '/';

const CODE_PATTERN = '/^[a-z0-9_-]{1,64}$/';
// Mappen/bestanden in de root die geen QR-code mogen heten
const RESERVED_CODES = ['admin', 'assets', 'vendor', 'inc', 'setup', 'index', 'config', 'schema'];

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . $CONFIG['db_host'] . ';dbname=' . $CONFIG['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('qrdash');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    global $CONFIG;
    return $CONFIG['base_url'] . ltrim($path, '/');
}

function qr_url(string $code): string
{
    return base_url($code);
}

function redirect(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

function valid_url(string $u): bool
{
    if (!filter_var($u, FILTER_VALIDATE_URL)) return false;
    $scheme = strtolower((string)parse_url($u, PHP_URL_SCHEME));
    return $scheme === 'http' || $scheme === 'https';
}

function valid_code(string $c): bool
{
    return (bool)preg_match(CODE_PATTERN, $c) && !in_array($c, RESERVED_CODES, true);
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Ongeldig formulier (verlopen sessie). Ga terug en probeer het opnieuw.');
    }
}

// ---------- Login ----------
function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $u = current_user();
    if ($u) {
        // Uitloggen als het account inmiddels is verwijderd
        $st = db()->prepare('SELECT 1 FROM qr_users WHERE id = ?');
        $st->execute([$u['id']]);
        if (!$st->fetchColumn()) { $_SESSION = []; $u = null; }
    }
    if (!$u) redirect(base_url('admin/login.php'));
    return $u;
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function too_many_attempts(): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM qr_login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([client_ip()]);
    return (int)$st->fetchColumn() >= 10;
}

function record_failed_attempt(): void
{
    db()->prepare('INSERT INTO qr_login_attempts (ip, attempted_at) VALUES (?, NOW())')->execute([client_ip()]);
    db()->exec('DELETE FROM qr_login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
}

function login(string $username, string $password): bool
{
    $st = db()->prepare('SELECT id, username, password_hash FROM qr_users WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        // Constante tijd, ook als de gebruiker niet bestaat
        if (!$row) password_verify($password, password_hash('dummy', PASSWORD_DEFAULT));
        return false;
    }
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE qr_users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$row['id'], 'username' => $row['username']];
    return true;
}

// ---------- Flash-meldingen ----------
function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------- QR-codes ----------
function find_code_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM qr_codes WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function format_dt(?string $dt): string
{
    if (!$dt) return '–';
    return date('d-m-Y H:i', strtotime($dt));
}
