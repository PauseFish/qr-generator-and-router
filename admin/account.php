<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
$user = require_login();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $current = (string)($_POST['current'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM qr_users WHERE id = ?');
    $st->execute([$user['id']]);
    $hash = (string)$st->fetchColumn();

    if ($action === 'password') {
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify($current, $hash)) $error = 'Je huidige wachtwoord klopt niet.';
        elseif (strlen($new) < 10) $error = 'Nieuw wachtwoord moet minstens 10 tekens zijn.';
        elseif ($new !== (string)($_POST['repeat'] ?? '')) $error = 'De nieuwe wachtwoorden komen niet overeen.';
        else {
            db()->prepare('UPDATE qr_users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('Wachtwoord gewijzigd.');
            redirect(base_url('admin/account.php'));
        }
    } elseif ($action === 'add_user') {
        $name = trim((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['new_user_password'] ?? '');
        if (!password_verify($current, $hash)) $error = 'Je huidige wachtwoord klopt niet.';
        elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $name)) $error = 'Gebruikersnaam: 3–64 tekens (letters, cijfers, _ . @ -).';
        elseif (strlen($pw) < 10) $error = 'Wachtwoord moet minstens 10 tekens zijn.';
        else {
            try {
                db()->prepare('INSERT INTO qr_users (username, password_hash) VALUES (?, ?)')->execute([$name, password_hash($pw, PASSWORD_DEFAULT)]);
                flash('Gebruiker "' . $name . '" toegevoegd.');
                redirect(base_url('admin/account.php'));
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23000') throw $ex;
                $error = 'Die gebruikersnaam bestaat al.';
            }
        }
    } elseif ($action === 'delete_user') {
        $delId = (int)($_POST['user_id'] ?? 0);
        if ($delId === $user['id']) $error = 'Je kunt jezelf niet verwijderen.';
        else {
            db()->prepare('DELETE FROM qr_users WHERE id = ?')->execute([$delId]);
            flash('Gebruiker verwijderd.');
            redirect(base_url('admin/account.php'));
        }
    }
}

$users = db()->query('SELECT id, username, created_at FROM qr_users ORDER BY username')->fetchAll();

page_header('Account', 'account');
?>
<h1>Account</h1>
<p class="lead">Ingelogd als <strong><?= e($user['username']) ?></strong>.</p>
<?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>

<div class="split even">
  <form method="post" class="card">
    <h2>Wachtwoord wijzigen</h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="field"><label for="current">Huidig wachtwoord</label><input id="current" name="current" type="password" required autocomplete="current-password"></div>
    <div class="field"><label for="new">Nieuw wachtwoord (minstens 10 tekens)</label><input id="new" name="new" type="password" required minlength="10" autocomplete="new-password"></div>
    <div class="field"><label for="repeat">Herhaal nieuw wachtwoord</label><input id="repeat" name="repeat" type="password" required minlength="10" autocomplete="new-password"></div>
    <button type="submit">Wachtwoord wijzigen</button>
  </form>

  <div class="card">
    <h2>Gebruikers</h2>
    <ul class="users">
      <?php foreach ($users as $u): ?>
        <li>
          <span><?= e($u['username']) ?><?= (int)$u['id'] === $user['id'] ? ' <span class="muted">(jij)</span>' : '' ?></span>
          <?php if ((int)$u['id'] !== $user['id']): ?>
            <form method="post" data-confirm="Gebruiker &quot;<?= e($u['username']) ?>&quot; verwijderen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button class="danger small" type="submit">Verwijderen</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <details>
      <summary>Gebruiker toevoegen</summary>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_user">
        <div class="field"><label for="nu">Gebruikersnaam</label><input id="nu" name="username" required autocomplete="off"></div>
        <div class="field"><label for="np">Wachtwoord (minstens 10 tekens)</label><input id="np" name="new_user_password" type="password" required minlength="10" autocomplete="new-password"></div>
        <div class="field"><label for="cp">Jouw huidige wachtwoord (ter bevestiging)</label><input id="cp" name="current" type="password" required autocomplete="current-password"></div>
        <button type="submit">Toevoegen</button>
      </form>
    </details>
  </div>
</div>
<?php page_footer();
