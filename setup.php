<?php
// Eenmalige installatie: maakt de tabellen aan en het eerste beheerdersaccount.
// Werkt alleen zolang er nog geen gebruikers zijn. Verwijder dit bestand daarna.

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
start_session();

$error = null;
try {
    // Tabellen aanmaken (CREATE TABLE IF NOT EXISTS, dus veilig om vaker te draaien)
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        db()->exec($stmt);
    }
    $hasUsers = (int)db()->query('SELECT COUNT(*) FROM qr_users')->fetchColumn() > 0;
} catch (PDOException $ex) {
    error_log('[qr-dashboard] setup: ' . $ex->getMessage());
    http_response_code(500);
    exit('Kan geen verbinding maken met de database. Controleer db_host, db_name, db_user en db_pass in config.php.');
}

if (!$hasUsers && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $repeat = (string)($_POST['repeat'] ?? '');
    // Bewijs dat je toegang tot de server hebt: alleen wie config.php kent, kan het eerste account maken
    if (too_many_attempts()) {
        $error = 'Te veel mislukte pogingen. Probeer het over 15 minuten opnieuw.';
    } elseif (!hash_equals((string)$CONFIG['db_pass'], (string)($_POST['db_pass'] ?? ''))) {
        record_failed_attempt();
        $error = 'Het databasewachtwoord klopt niet met config.php.';
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $username)) {
        $error = 'Gebruikersnaam: 3–64 tekens (letters, cijfers, _ . @ -).';
    } elseif (strlen($password) < 10) {
        $error = 'Wachtwoord moet minstens 10 tekens zijn.';
    } elseif ($password !== $repeat) {
        $error = 'De wachtwoorden komen niet overeen.';
    } else {
        db()->prepare('INSERT INTO qr_users (username, password_hash) VALUES (?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        login($username, $password);
        flash('Installatie voltooid. Verwijder nu setup.php van je server.', 'warn');
        redirect(base_url('admin/'));
    }
}

page_header('Installatie');
?>
<div class="narrow">
  <h1>Installatie</h1>
  <?php if ($hasUsers): ?>
    <div class="card">
      <p>De installatie is al voltooid. <strong>Verwijder setup.php van je server.</strong></p>
      <a class="btn" href="<?= e(base_url('admin/')) ?>">Naar het dashboard</a>
    </div>
  <?php else: ?>
    <p class="lead">De databasetabellen zijn aangemaakt. Maak nu je beheerdersaccount aan.</p>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card">
      <?= csrf_field() ?>
      <div class="field"><label for="db_pass">Databasewachtwoord (uit config.php)</label>
        <input id="db_pass" name="db_pass" type="password" required autocomplete="off">
        <p class="hint">Ter controle dat jij de eigenaar van deze installatie bent.</p></div>
      <div class="field"><label for="username">Gebruikersnaam</label>
        <input id="username" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></div>
      <div class="field"><label for="password">Wachtwoord (minstens 10 tekens)</label>
        <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
      <div class="field"><label for="repeat">Herhaal wachtwoord</label>
        <input id="repeat" name="repeat" type="password" required minlength="10" autocomplete="new-password"></div>
      <button type="submit">Account aanmaken</button>
    </form>
  <?php endif; ?>
</div>
<?php page_footer();
