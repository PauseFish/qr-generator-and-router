/* Gedeelde logica voor alle dashboard-pagina's. Vereist vendor/qrcode.js. */
window.App = (function () {
  var root = new URL('../', document.currentScript.src).href; // basis van de site
  var LS = 'qrdash:';
  var store = {
    get: function (k) { try { return localStorage.getItem(LS + k); } catch (e) { return null; } },
    set: function (k, v) { try { v == null || v === '' ? localStorage.removeItem(LS + k) : localStorage.setItem(LS + k, v); } catch (e) {} }
  };

  // ---------- Instellingen ----------
  var guessOwner = '', guessRepo = '';
  if (/\.github\.io$/i.test(location.hostname)) {
    guessOwner = location.hostname.split('.')[0];
    guessRepo = new URL(root).pathname.split('/').filter(Boolean)[0] || location.hostname;
  }
  var cfg = {
    token: store.get('token') || '',
    owner: store.get('owner') || guessOwner,
    repo: store.get('repo') || guessRepo,
    branch: store.get('branch') || 'main',
    base: store.get('base') || root + 'r/'
  };
  function saveCfg(next) { Object.keys(next).forEach(function (k) { cfg[k] = String(next[k] || '').trim(); store.set(k, cfg[k]); }); }

  // ---------- Data ----------
  var sha = null;
  function norm(v) {
    if (typeof v === 'string') return { url: v, label: '' };
    if (v && typeof v === 'object') return { url: String(v.url || ''), label: String(v.label || '') };
    return { url: '', label: '' };
  }
  function parse(json) { var out = {}, d = JSON.parse(json || '{}'); Object.keys(d).forEach(function (k) { out[k] = norm(d[k]); }); return out; }
  function serialize(obj) {
    var out = {};
    Object.keys(obj).forEach(function (k) { out[k] = obj[k].label ? { url: obj[k].url, label: obj[k].label } : obj[k].url; });
    return JSON.stringify(out, null, 2) + '\n';
  }
  function b64decode(s) { var bin = atob(s.replace(/\s/g, '')), b = new Uint8Array(bin.length); for (var i = 0; i < bin.length; i++) b[i] = bin.charCodeAt(i); return new TextDecoder().decode(b); }
  function b64encode(s) { var b = new TextEncoder().encode(s), bin = ''; for (var i = 0; i < b.length; i++) bin += String.fromCharCode(b[i]); return btoa(bin); }

  function api(path, opts) {
    opts = opts || {};
    opts.headers = { 'Accept': 'application/vnd.github+json', 'Authorization': 'Bearer ' + cfg.token, 'X-GitHub-Api-Version': '2022-11-28' };
    opts.cache = 'no-store';
    return fetch('https://api.github.com/repos/' + encodeURIComponent(cfg.owner) + '/' + encodeURIComponent(cfg.repo) + path, opts).then(function (r) {
      if (r.ok) return r.json();
      return r.json().catch(function () { return {}; }).then(function (body) {
        var m = { 401: 'Token ongeldig of verlopen.', 403: 'Token heeft geen schrijfrechten (Contents: Read and write).', 404: 'Repository, branch of links.json niet gevonden, of het token heeft geen toegang.', 409: 'Conflict: het bestand is net gewijzigd. Probeer opnieuw.', 422: 'GitHub weigerde de wijziging.' }[r.status];
        throw new Error(m || ('GitHub-fout ' + r.status + (body.message ? ': ' + body.message : '')));
      });
    });
  }
  function fetchRemote() {
    return api('/contents/links.json?ref=' + encodeURIComponent(cfg.branch)).then(function (f) { sha = f.sha; return parse(b64decode(f.content)); });
  }
  // Met token: direct uit GitHub (altijd actueel). Zonder: de gepubliceerde links.json.
  function load() {
    if (cfg.token) return fetchRemote();
    return fetch(root + 'links.json?t=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { if (!r.ok) throw new Error('links.json niet gevonden.'); return r.text(); }).then(parse);
  }
  // Haalt de nieuwste versie op, past mutate toe en commit het resultaat.
  function commit(message, mutate) {
    if (!cfg.token) return Promise.reject(new Error('Koppel eerst GitHub via Instellingen om te kunnen opslaan.'));
    return fetchRemote().then(function (fresh) {
      mutate(fresh);
      return api('/contents/links.json', { method: 'PUT', body: JSON.stringify({ message: message, content: b64encode(serialize(fresh)), sha: sha, branch: cfg.branch }) })
        .then(function (res) { sha = res.content.sha; return fresh; });
    });
  }

  // ---------- QR ----------
  function fixedUrl(code) { var b = cfg.base.trim(); if (b && !/\/$/.test(b)) b += '/'; return b + '?' + code; }
  function makeQr(text) { var q = qrcode(0, 'M'); q.addData(text); q.make(); return q; }
  function qrCanvas(text, cssSize) {
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
  function downloadPng(code) {
    var q = makeQr(fixedUrl(code)), n = q.getModuleCount(), quiet = 4, s = 20, px = (n + quiet * 2) * s;
    var cv = document.createElement('canvas'); cv.width = cv.height = px;
    var ctx = cv.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, px, px); ctx.fillStyle = '#000';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (q.isDark(r, c)) ctx.fillRect((c + quiet) * s, (r + quiet) * s, s, s);
    download('qr-' + code + '.png', cv.toDataURL('image/png'));
  }
  function downloadSvg(code) {
    var q = makeQr(fixedUrl(code)), n = q.getModuleCount(), quiet = 4, t = n + quiet * 2, d = '';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (q.isDark(r, c)) d += 'M' + (c + quiet) + ' ' + (r + quiet) + 'h1v1h-1z';
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + t + ' ' + t + '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="' + d + '" fill="#000"/></svg>';
    download('qr-' + code + '.svg', URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' })));
  }

  // ---------- UI-hulpjes ----------
  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') e.textContent = attrs[k];
      else if (k === 'on') Object.keys(attrs.on).forEach(function (ev) { e.addEventListener(ev, attrs.on[ev]); });
      else e.setAttribute(k, attrs[k]);
    });
    (children || []).forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return e;
  }
  function validUrl(u) { try { var x = new URL(u); return x.protocol === 'https:' || x.protocol === 'http:'; } catch (e) { return false; } }
  function validCode(c) { return /^[a-z0-9_-]+$/.test(c); }
  function cleanCode(c) { return String(c || '').toLowerCase().replace(/[^a-z0-9_-]/g, ''); }

  var toastTimer;
  function toast(msg, err) {
    var t = document.getElementById('toast');
    if (!t) { t = el('div', { id: 'toast' }); document.body.appendChild(t); }
    t.textContent = msg; t.className = 'show' + (err ? ' err' : '');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.className = ''; }, err ? 5000 : 2500);
  }
  function busy(btn, on, label) {
    if (on) { btn.dataset.label = btn.textContent; btn.textContent = label || 'Bezig…'; btn.disabled = true; }
    else { btn.textContent = btn.dataset.label || btn.textContent; btn.disabled = false; }
  }

  var pages = { overzicht: '', nieuw: 'nieuw/', instellingen: 'instellingen/' };
  function url(page, params) { return root + (pages[page] != null ? pages[page] : page) + (params ? '?' + new URLSearchParams(params) : ''); }

  // Navigatiebalk + waarschuwing als er geen token is
  function chrome(active) {
    var nav = el('nav', { class: 'nav' }, [
      el('a', { href: url('overzicht'), class: active === 'overzicht' ? 'active' : '', text: 'Alle QR-codes' }),
      el('a', { href: url('nieuw'), class: active === 'nieuw' ? 'active' : '', text: 'Nieuwe QR-code' }),
      el('a', { href: url('instellingen'), class: active === 'instellingen' ? 'active' : '', text: 'Instellingen' })
    ]);
    var pill = el('a', { href: url('instellingen'), class: 'pill ' + (cfg.token ? 'ok' : 'ro'), text: cfg.token ? 'Gekoppeld' : 'Alleen lezen' });
    var bar = el('header', { class: 'topbar' }, [el('div', { class: 'topbar-inner' }, [el('a', { class: 'brand', href: url('overzicht'), text: 'QR Dashboard' }), nav, el('span', { class: 'spacer' }), pill])]);
    document.body.insertBefore(bar, document.body.firstChild);
    if (!cfg.token && active !== 'instellingen') {
      var main = document.querySelector('main');
      main.insertBefore(el('div', { class: 'banner' }, ['Je kunt nu alleen kijken en downloaden. ', el('a', { href: url('instellingen'), text: 'Koppel GitHub' }), ' om QR-codes aan te maken of te wijzigen.']), main.firstChild);
    }
  }

  return {
    root: root, cfg: cfg, saveCfg: saveCfg, load: load, commit: commit, fetchRemote: fetchRemote,
    fixedUrl: fixedUrl, qrCanvas: qrCanvas, downloadPng: downloadPng, downloadSvg: downloadSvg,
    el: el, validUrl: validUrl, validCode: validCode, cleanCode: cleanCode, toast: toast, busy: busy, url: url, chrome: chrome
  };
})();
