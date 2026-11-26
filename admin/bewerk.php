<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$qr = find_code_by_id($id);

if (!$qr) {
    http_response_code(404);
    page_header('Niet gevonden', 'overzicht');
    echo '<div class="card empty">Deze QR-code bestaat niet (meer). <a href="' . e(base_url('admin/')) . '">Terug naar het overzicht</a></div>';
    page_footer();
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db()->prepare('DELETE FROM qr_codes WHERE id = ?')->execute([$id]);
        flash('QR-code "' . $qr['code'] . '" is verwijderd.');
        redirect(base_url('admin/'));
    }

    if ($action === 'reset_stats') {
        db()->prepare('UPDATE qr_codes SET scan_count = 0, last_scan_at = NULL WHERE id = ?')->execute([$id]);
        flash('Scanteller op 0 gezet.');
        redirect(base_url('admin/bewerk.php?id=' . $id));
    }

    $label = trim((string)($_POST['label'] ?? ''));
    $target = trim((string)($_POST['target_url'] ?? ''));
    if (!valid_url($target)) {
        $error = 'Ongeldige bestemming. Die moet met http:// of https:// beginnen.';
        $qr['label'] = $label;
        $qr['target_url'] = $target;
    } else {
        db()->prepare('UPDATE qr_codes SET label = ?, target_url = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$label, $target, $id]);
        flash('Opgeslagen. De QR-code stuurt nu door naar de nieuwe bestemming.');
        redirect(base_url('admin/bewerk.php?id=' . $id));
    }
}

$url = qr_url($qr['code']);
page_header($qr['label'] ?: $qr['code'], 'overzicht');
?>
<p class="back"><a href="<?= e(base_url('admin/')) ?>">← Alle QR-codes</a></p>
<h1><?= e($qr['label'] ?: $qr['code']) ?></h1>
<p class="lead mono">Code: <?= e($qr['code']) ?></p>
<?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>

<div class="split">
  <div>
    <form method="post" class="card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <div class="field">
        <label for="label">Naam</label>
        <input id="label" name="label" value="<?= e($qr['label']) ?>" placeholder="Naam" maxlength="255" autocomplete="off">
      </div>
      <div class="field">
        <label for="target_url">Bestemming</label>
        <input id="target_url" name="target_url" type="url" value="<?= e($qr['target_url']) ?>" required>
        <p class="hint">Hier stuurt de QR-code naartoe. Wijzigingen zijn direct live.</p>
      </div>
      <div class="field">
        <label>Vaste URL in de QR-code (verandert nooit)</label>
        <input readonly value="<?= e($url) ?>">
      </div>
      <button type="submit">Wijzigingen opslaan</button>
    </form>

    <div class="card">
      <h2>Statistieken</h2>
      <dl class="stats">
        <div><dt>Scans</dt><dd><?= (int)$qr['scan_count'] ?></dd></div>
        <div><dt>Laatste scan</dt><dd><?= e(format_dt($qr['last_scan_at'])) ?></dd></div>
        <div><dt>Aangemaakt</dt><dd><?= e(format_dt($qr['created_at'])) ?></dd></div>
        <div><dt>Laatst gewijzigd</dt><dd><?= e(format_dt($qr['updated_at'])) ?></dd></div>
      </dl>
      <div class="actions">
        <form method="post" data-confirm="Scanteller van &quot;<?= e($qr['code']) ?>&quot; op 0 zetten?">
          <?= csrf_field() ?><input type="hidden" name="action" value="reset_stats">
          <button class="ghost" type="submit">Teller resetten</button>
        </form>
        <form method="post" data-confirm="QR-code &quot;<?= e($qr['code']) ?>&quot; verwijderen?&#10;&#10;Geprinte exemplaren werken daarna niet meer.">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <button class="danger" type="submit">QR-code verwijderen</button>
        </form>
      </div>
    </div>
  </div>

  <div class="card qr-panel">
    <div class="qr-box"><div data-qr="<?= e($url) ?>" data-size="220"></div></div>
    <div class="actions">
      <button data-download="png" data-url="<?= e($url) ?>" data-name="<?= e($qr['code']) ?>">PNG</button>
      <button class="ghost" data-download="svg" data-url="<?= e($url) ?>" data-name="<?= e($qr['code']) ?>">SVG</button>
    </div>
    <div class="actions">
      <button class="ghost" data-copy="<?= e($url) ?>">Kopieer URL</button>
      <a class="btn ghost" href="<?= e($url) ?>" target="_blank" rel="noopener">Test</a>
    </div>
  </div>
</div>
<?php page_footer();
