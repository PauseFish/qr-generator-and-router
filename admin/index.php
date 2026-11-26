<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $st = db()->prepare('SELECT * FROM qr_codes WHERE code LIKE ? OR label LIKE ? OR target_url LIKE ? ORDER BY created_at DESC, id DESC');
    $st->execute([$like, $like, $like]);
} else {
    $st = db()->query('SELECT * FROM qr_codes ORDER BY created_at DESC, id DESC');
}
$codes = $st->fetchAll();
$total = (int)db()->query('SELECT COUNT(*) FROM qr_codes')->fetchColumn();

page_header('Alle QR-codes', 'overzicht');
?>
<h1>Alle QR-codes</h1>
<p class="lead">Elke QR-code heeft een vaste URL. Klik op <strong>Bewerken</strong> om de bestemming te wijzigen.</p>

<div class="toolbar">
  <form method="get" class="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Zoeken op naam, code of bestemming…">
  </form>
  <span class="muted"><?= $total ?> <?= $total === 1 ? 'QR-code' : 'QR-codes' ?></span>
  <span class="spacer"></span>
  <a class="btn" href="<?= e(base_url('admin/nieuw.php')) ?>">+ Nieuwe QR-code</a>
</div>

<?php if (!$total): ?>
  <div class="card empty">Nog geen QR-codes. <a href="<?= e(base_url('admin/nieuw.php')) ?>">Maak je eerste QR-code aan</a>.</div>
<?php elseif (!$codes): ?>
  <div class="card empty">Geen resultaten voor “<?= e($q) ?>”. <a href="<?= e(base_url('admin/')) ?>">Alles tonen</a></div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($codes as $c):
      $edit = base_url('admin/bewerk.php?id=' . (int)$c['id']);
      $url = qr_url($c['code']); ?>
    <div class="card tile">
      <div class="tile-head">
        <a class="qr-box" href="<?= e($edit) ?>" title="Bewerken"><div data-qr="<?= e($url) ?>" data-size="76"></div></a>
        <div>
          <h3><?= e($c['label'] ?: $c['code']) ?></h3>
          <span class="mono muted"><?= e($c['code']) ?></span>
        </div>
      </div>
      <div class="dest" title="<?= e($c['target_url']) ?>">→ <a href="<?= e($c['target_url']) ?>" target="_blank" rel="noopener"><?= e($c['target_url']) ?></a></div>
      <div class="muted small"><?= (int)$c['scan_count'] ?> scans</div>
      <div class="actions">
        <a class="btn" href="<?= e($edit) ?>">Bewerken</a>
        <button class="ghost" data-download="png" data-url="<?= e($url) ?>" data-name="<?= e($c['code']) ?>">PNG</button>
        <button class="ghost" data-download="svg" data-url="<?= e($url) ?>" data-name="<?= e($c['code']) ?>">SVG</button>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_footer();
