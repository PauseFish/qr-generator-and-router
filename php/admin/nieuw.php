<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
require_login();

$error = null;
$label = trim((string)($_POST['label'] ?? ''));
$code = strtolower(trim((string)($_POST['code'] ?? '')));
$target = trim((string)($_POST['target_url'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!valid_code($code)) {
        $error = 'Ongeldige code. Gebruik kleine letters, cijfers, - en _ (en niet: ' . implode(', ', RESERVED_CODES) . ').';
    } elseif (!valid_url($target)) {
        $error = 'Ongeldige bestemming. Die moet met http:// of https:// beginnen.';
    } else {
        try {
            db()->prepare('INSERT INTO qr_codes (code, label, target_url, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')
                ->execute([$code, $label, $target]);
            flash('QR-code aangemaakt. Download hem hieronder als PNG of SVG.');
            redirect(base_url('admin/bewerk.php?id=' . (int)db()->lastInsertId()));
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') throw $ex;
            $error = 'De code "' . $code . '" bestaat al. Kies een andere code.';
        }
    }
}

page_header('Nieuwe QR-code', 'nieuw');
?>
<h1>Nieuwe QR-code</h1>
<p class="lead">De QR-code krijgt een vaste URL. De bestemming kun je later altijd wijzigen.</p>
<?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>

<div class="split">
  <form method="post" class="card" id="form">
    <?= csrf_field() ?>
    <div class="field">
      <label for="label">Naam</label>
      <input id="label" name="label" value="<?= e($label) ?>" placeholder="bijv. Menukaart restaurant" autocomplete="off" maxlength="255">
    </div>
    <div class="field">
      <label for="code">Code (komt in de URL)</label>
      <input id="code" name="code" value="<?= e($code) ?>" placeholder="bijv. menu" autocomplete="off" required pattern="[a-z0-9_\-]{1,64}" maxlength="64">
      <p class="hint">Kleine letters, cijfers, - en _. Kan na het aanmaken niet meer veranderen.</p>
    </div>
    <div class="field">
      <label for="target_url">Bestemming</label>
      <input id="target_url" name="target_url" type="url" value="<?= e($target) ?>" placeholder="https://…" required>
    </div>
    <div class="field">
      <label>Vaste URL in de QR-code</label>
      <input id="fixed" readonly value="<?= e($code ? qr_url($code) : '') ?>">
    </div>
    <button type="submit">QR-code aanmaken</button>
  </form>
  <div class="card qr-panel">
    <label>Voorbeeld</label>
    <div class="qr-box"><div id="preview" data-size="220"></div></div>
    <p class="hint">Na het aanmaken kun je hem downloaden als PNG of SVG.</p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var base = <?= json_encode(base_url(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
  var label = document.getElementById('label'), code = document.getElementById('code');
  var fixed = document.getElementById('fixed'), preview = document.getElementById('preview');
  var touched = code.value !== '';
  function clean(s) { return s.toLowerCase().replace(/[^a-z0-9_-]/g, ''); }
  function update() {
    fixed.value = code.value ? base + code.value : '';
    preview.dataset.qr = base + (code.value || 'voorbeeld');
    preview.style.opacity = code.value ? 1 : .25;
    QR.draw(preview);
  }
  label.addEventListener('input', function () {
    if (touched) return;
    code.value = clean(label.value.trim().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, '-')).replace(/-+/g, '-').replace(/^-|-$/g, '').slice(0, 64);
    update();
  });
  code.addEventListener('input', function () { touched = true; code.value = clean(code.value); update(); });
  update();
});
</script>
<?php page_footer();
