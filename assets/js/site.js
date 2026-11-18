/* 화이트 보안 — 언어 탭 · 복사 · 목록 검색 · 좁은 화면 목록 접기
   외부 요청 없음. 저장하는 것은 언어 탭 선택(localStorage) 하나뿐. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var LANG_KEY = 'ws-lang';

  function readLang() {
    try { return window.localStorage.getItem(LANG_KEY); } catch (e) { return null; }
  }
  function saveLang(lang) {
    try { window.localStorage.setItem(LANG_KEY, lang); } catch (e) { /* 저장 못 해도 화면은 그대로 */ }
  }

  /* ---------- 언어 탭 ---------- */
  function initTabs() {
    var lists = document.querySelectorAll('.ws-code [role="tablist"]');
    Array.prototype.forEach.call(lists, function (list) {
      var aside = list.closest('.ws-code');
      var tabs = Array.prototype.slice.call(list.querySelectorAll('button[data-lang]'));
      if (!tabs.length) return;

      function select(lang, focus) {
        tabs.forEach(function (t) {
          var on = t.getAttribute('data-lang') === lang;
          t.setAttribute('aria-selected', on ? 'true' : 'false');
          t.tabIndex = on ? 0 : -1;
          if (on && focus) t.focus();
        });
        Array.prototype.forEach.call(aside.querySelectorAll('[data-lang-panel]'), function (p) {
          p.classList.toggle('is-active', p.getAttribute('data-lang-panel') === lang);
        });
      }

      var saved = readLang();
      var has = tabs.some(function (t) { return t.getAttribute('data-lang') === saved; });
      select(has ? saved : tabs[0].getAttribute('data-lang'), false);

      list.addEventListener('click', function (ev) {
        var t = ev.target.closest('button[data-lang]');
        if (!t) return;
        var lang = t.getAttribute('data-lang');
        select(lang, false);
        saveLang(lang);
      });
      list.addEventListener('keydown', function (ev) {
        var i = tabs.indexOf(document.activeElement);
        if (i < 0) return;
        var next = null;
        if (ev.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
        else if (ev.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
        else if (ev.key === 'Home') next = tabs[0];
        else if (ev.key === 'End') next = tabs[tabs.length - 1];
        if (!next) return;
        ev.preventDefault();
        var lang = next.getAttribute('data-lang');
        select(lang, true);
        saveLang(lang);
      });
    });
  }

  /* ---------- 복사 ---------- */
  function selectNode(node) {
    var sel = window.getSelection();
    if (!sel) return;
    var range = document.createRange();
    range.selectNodeContents(node);
    sel.removeAllRanges();
    sel.addRange(range);
  }

  function flash(btn, text) {
    var label = btn.querySelector('.ws-tx') || btn;
    if (!btn.hasAttribute('data-label')) btn.setAttribute('data-label', label.textContent);
    label.textContent = text;
    btn.classList.remove('is-done');
    void btn.offsetWidth; // 애니메이션을 처음부터 다시
    btn.classList.add('is-done');
    clearTimeout(btn._wsTimer);
    btn._wsTimer = setTimeout(function () {
      label.textContent = btn.getAttribute('data-label');
      btn.classList.remove('is-done');
    }, 1800);
  }

  function initCopy() {
    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-copy]');
      if (!btn) return;
      var fig = btn.closest('figure');
      var code = fig && fig.querySelector('pre code, pre');
      if (!code) return;
      var text = code.textContent;
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () { flash(btn, '복사됨'); }, function () {
          selectNode(code); flash(btn, '선택됨');
        });
      } else {
        selectNode(code); flash(btn, '선택됨');
      }
    });
  }

  /* ---------- 좁은 화면: 목록 접기 ---------- */
  function initFold() {
    var fold = document.querySelector('.ws-nav-fold');
    if (!fold || !window.matchMedia) return fold;
    var narrow = window.matchMedia('(max-width: 759.98px)');
    function apply() { fold.open = !narrow.matches; }
    apply();
    if (narrow.addEventListener) narrow.addEventListener('change', apply);
    return fold;
  }

  /* ---------- 목록 검색 ---------- */
  function norm(s) { return (s || '').toLowerCase().normalize('NFC'); }

  function initSearch(fold) {
    var input = document.querySelector('input[data-search]');
    if (!input) return;
    var nav = input.closest('.ws-nav');
    var items = Array.prototype.slice.call(nav.querySelectorAll('li[data-id]'));
    var groups = Array.prototype.slice.call(nav.querySelectorAll('.ws-nav-group'));
    var none = nav.querySelector('.ws-nav-none');
    var status = document.getElementById('ws-q-status');
    var hay = {};
    items.forEach(function (li) {
      var g = li.closest('.ws-nav-group');
      var title = g ? g.querySelector('.ws-nav-title') : null;
      hay[li.getAttribute('data-id')] = norm(li.textContent + ' ' + (title ? title.textContent : ''));
    });
    var loaded = false;

    function load() {
      if (loaded || !window.fetch) return;
      loaded = true;
      fetch(input.getAttribute('data-search'), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
        .then(function (rows) {
          if (!Array.isArray(rows)) return;
          rows.forEach(function (row) {
            if (row && hay.hasOwnProperty(row.id)) hay[row.id] += ' ' + norm(row.title + ' ' + row.text);
          });
          if (input.value) run();
        })
        .catch(function () { /* 색인을 못 받으면 제목·id 로만 찾음 */ });
    }

    function run() {
      var words = norm(input.value).split(/\s+/).filter(Boolean);
      var shown = 0;
      items.forEach(function (li) {
        var h = hay[li.getAttribute('data-id')];
        var ok = words.every(function (w) { return h.indexOf(w) !== -1; });
        li.hidden = !ok;
        if (ok) shown++;
      });
      groups.forEach(function (g) {
        g.hidden = !g.querySelector('li[data-id]:not([hidden])');
      });
      if (none) none.hidden = !(words.length && shown === 0);
      if (status) status.textContent = words.length ? '검색 결과 ' + shown + '건' : '';
      if (words.length && fold) fold.open = true;
    }

    input.addEventListener('focus', load);
    input.addEventListener('input', function () { load(); run(); });
    input.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && input.value) {
        input.value = '';
        run();
      } else if (ev.key === 'Enter') {
        var first = nav.querySelector('li[data-id]:not([hidden]) a');
        if (first && input.value) window.location.href = first.href;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initCopy();
    initSearch(initFold());
  });
})();
