/* QR-codes tekenen en downloaden in de browser. Vereist vendor/qrcode.js.
 *  <div data-qr="https://…" data-size="120"></div>          → tekent de QR-code
 *  <button data-download="png|svg" data-url="…" data-name="menu">  → download
 *  <button data-copy="…">                                     → kopieert tekst
 *  <form data-confirm="Weet je het zeker?">                   → bevestiging
 */
(function () {
  function makeQr(text) { var q = qrcode(0, 'M'); q.addData(text); q.make(); return q; }

  function canvasFor(text, cssSize) {
    var q = makeQr(text), n = q.getModuleCount(), quiet = 2, total = n + quiet * 2;
    var dpr = window.devicePixelRatio || 1, px = Math.round(cssSize * dpr);
    var cv = document.createElement('canvas'); cv.width = cv.height = px;
    cv.style.width = cv.style.height = cssSize + 'px';
    var ctx = cv.getContext('2d'), cell = px / total;
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, px, px); ctx.fillStyle = '#000';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++)
      if (q.isDark(r, c)) ctx.fillRect(Math.floor((c + quiet) * cell), Math.floor((r + quiet) * cell), Math.ceil(cell), Math.ceil(cell));
    return cv;
  }

  function download(name, href) { var a = document.createElement('a'); a.href = href; a.download = name; document.body.appendChild(a); a.click(); a.remove(); }

  function downloadPng(text, name) {
    var q = makeQr(text), n = q.getModuleCount(), quiet = 4, s = 20, px = (n + quiet * 2) * s;
    var cv = document.createElement('canvas'); cv.width = cv.height = px;
    var ctx = cv.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, px, px); ctx.fillStyle = '#000';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (q.isDark(r, c)) ctx.fillRect((c + quiet) * s, (r + quiet) * s, s, s);
    download('qr-' + name + '.png', cv.toDataURL('image/png'));
  }

  function downloadSvg(text, name) {
    var q = makeQr(text), n = q.getModuleCount(), quiet = 4, t = n + quiet * 2, d = '';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (q.isDark(r, c)) d += 'M' + (c + quiet) + ' ' + (r + quiet) + 'h1v1h-1z';
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + t + ' ' + t + '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="' + d + '" fill="#000"/></svg>';
    download('qr-' + name + '.svg', URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' })));
  }

  function draw(elm) {
    elm.innerHTML = '';
    if (elm.dataset.qr) elm.appendChild(canvasFor(elm.dataset.qr, parseInt(elm.dataset.size || '120', 10)));
  }

  window.QR = { draw: draw };
  document.querySelectorAll('[data-qr]').forEach(draw);

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-download],[data-copy]');
    if (!b) return;
    if (b.dataset.download === 'png') downloadPng(b.dataset.url, b.dataset.name);
    else if (b.dataset.download === 'svg') downloadSvg(b.dataset.url, b.dataset.name);
    else if (b.dataset.copy != null) {
      navigator.clipboard.writeText(b.dataset.copy).then(function () {
        var old = b.textContent; b.textContent = 'Gekopieerd!';
        setTimeout(function () { b.textContent = old; }, 1500);
      });
    }
  });

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.dataset.confirm && !confirm(f.dataset.confirm)) ev.preventDefault();
  });
})();
