<?php
// Paginakop en -voet voor het beheergedeelte.

function page_header(string $title, string $active = ''): void
{
    $user = current_user();
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    $nav = [
        'overzicht' => ['admin/', 'Alle QR-codes'],
        'nieuw' => ['admin/nieuw.php', 'Nieuwe QR-code'],
        'account' => ['admin/account.php', 'Account'],
    ];
    ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> – QR Dashboard</title>
  <link rel="stylesheet" href="<?= e(base_url('assets/style.css')) ?>">
</head>
<body>
<?php if ($user): ?>
<header class="topbar"><div class="topbar-inner">
  <a class="brand" href="<?= e(base_url('admin/')) ?>">QR Dashboard</a>
  <nav class="nav">
    <?php foreach ($nav as $key => [$href, $label]): ?>
      <a href="<?= e(base_url($href)) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <span class="spacer"></span>
  <form method="post" action="<?= e(base_url('admin/logout.php')) ?>" class="logout">
    <?= csrf_field() ?>
    <span class="muted"><?= e($user['username']) ?></span>
    <button class="ghost" type="submit">Uitloggen</button>
  </form>
</div></header>
<?php endif; ?>
<main>
<?php if ($f = take_flash()): ?>
  <div class="flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endif;
}

function page_footer(): void
{
    ?>
</main>
<script src="<?= e(base_url('vendor/qrcode.js')) ?>"></script>
<script src="<?= e(base_url('assets/qr.js')) ?>"></script>
</body>
</html>
<?php
}
