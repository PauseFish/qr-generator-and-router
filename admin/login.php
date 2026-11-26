<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
start_session();

if (current_user()) redirect(base_url('admin/'));

// Nog niet geïnstalleerd? Dan eerst naar setup.php
try {
    $installed = (int)db()->query('SELECT COUNT(*) FROM qr_users')->fetchColumn() > 0;
} catch (PDOException $ex) {
    if ($ex->getCode() !== '42S02') throw $ex; // 42S02 = tabel bestaat niet
    $installed = false;
}
if (!$installed) {
    if (is_file(__DIR__ . '/../setup.php')) redirect(base_url('setup.php'));
    exit('Nog geen gebruikers. Upload setup.php om een account aan te maken.');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (too_many_attempts()) {
        $error = 'Te veel mislukte pogingen. Probeer het over 15 minuten opnieuw.';
    } elseif (login(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''))) {
        redirect(base_url('admin/'));
    } else {
        record_failed_attempt();
        $error = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
}

page_header('Inloggen');
?>
<div class="narrow">
  <h1>QR Dashboard</h1>
  <p class="lead">Log in om je QR-codes te beheren.</p>
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="card">
    <?= csrf_field() ?>
    <div class="field"><label for="username">Gebruikersnaam</label>
      <input id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></div>
    <div class="field"><label for="password">Wachtwoord</label>
      <input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button type="submit">Inloggen</button>
  </form>
</div>
<?php page_footer();
