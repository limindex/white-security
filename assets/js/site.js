/* White Security — 언어 탭 · 복사 · 상단 검색(단축키 /) · 절 주소 복사 · 점검 항목 저장 · 접힘 열기 · 인쇄 · 좁은 화면 메뉴
   외부 요청 없음. 브라우저에 저장하는 것은 언어 탭 선택과 점검 항목 체크 상태(localStorage)뿐. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var each = function (list, fn) { Array.prototype.forEach.call(list, fn); };

  function store(key, value) {
    try {
      if (value === undefined) return window.localStorage.getItem(key);
      if (value === null) window.localStorage.removeItem(key);
      else window.localStorage.setItem(key, value);
    } catch (e) { /* 저장 못 해도 화면은 그대로 */ }
    return null;
  }

  function announce(text) {
    var live = document.getElementById('ws-live');
    if (!live) return;
    live.textContent = '';
    setTimeout(function () { live.textContent = text; }, 30);
  }

  function copyText(text, done, fail) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, fail);
    } else {
      fail();
    }
  }

  /* ---------- 언어 탭 ---------- */
  var LANG_KEY = 'ws-lang';
  function initTabs() {
    each(document.querySelectorAll('.ws-code [role="tablist"]'), function (list) {
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
        each(aside.querySelectorAll('[data-lang-panel]'), function (p) {
          p.classList.toggle('is-active', p.getAttribute('data-lang-panel') === lang);
        });
        reveal();
      }

      // 좁은 화면의 한 줄 탭: 고른 탭이 보이게 가로로만 옮기고, 끝에 닿으면 흐림 단서를 없앰
      function reveal() {
        var on = list.querySelector('[aria-selected="true"]');
        if (on && list.scrollWidth > list.clientWidth) {
          var l = on.offsetLeft - list.offsetLeft, r = l + on.offsetWidth;
          if (l < list.scrollLeft) list.scrollLeft = l - 4;
          else if (r > list.scrollLeft + list.clientWidth) list.scrollLeft = r - list.clientWidth + 36;
        }
        edge();
      }
      function edge() {
        list.classList.toggle('is-end', list.scrollLeft + list.clientWidth >= list.scrollWidth - 2);
      }
      list.addEventListener('scroll', edge, { passive: true });
      window.addEventListener('resize', edge);

      var saved = store(LANG_KEY);
      var has = tabs.some(function (t) { return t.getAttribute('data-lang') === saved; });
      select(has ? saved : tabs[0].getAttribute('data-lang'), false);

      list.addEventListener('click', function (ev) {
        var t = ev.target.closest('button[data-lang]');
        if (!t) return;
        select(t.getAttribute('data-lang'), false);
        store(LANG_KEY, t.getAttribute('data-lang'));
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
        select(next.getAttribute('data-lang'), true);
        store(LANG_KEY, next.getAttribute('data-lang'));
      });
    });
  }

  /* ---------- 범용 탭 (설치 페이지 등): [data-tabs] 안 role=tab ↔ role=tabpanel ---------- */
  function initPanelTabs() {
    each(document.querySelectorAll('[data-tabs]'), function (box) {
      var tabs = Array.prototype.slice.call(box.querySelectorAll('[role="tab"]'));
      if (!tabs.length) return;
      function select(t, focus) {
        tabs.forEach(function (x) {
          var on = x === t;
          x.setAttribute('aria-selected', on ? 'true' : 'false');
          x.tabIndex = on ? 0 : -1;
          var panel = document.getElementById(x.getAttribute('aria-controls'));
          if (panel) panel.hidden = !on;
        });
        if (focus) t.focus();
      }
      select(tabs[0], false);
      box.addEventListener('click', function (ev) {
        var t = ev.target.closest('[role="tab"]');
        if (t && tabs.indexOf(t) !== -1) select(t, false);
      });
      box.addEventListener('keydown', function (ev) {
        var i = tabs.indexOf(document.activeElement);
        if (i < 0) return;
        var next = null;
        if (ev.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
        else if (ev.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
        else if (ev.key === 'Home') next = tabs[0];
        else if (ev.key === 'End') next = tabs[tabs.length - 1];
        if (!next) return;
        ev.preventDefault();
        select(next, true);
      });
    });
  }

  /* ---------- 복사 (코드·프롬프트·명령) ---------- */
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
      copyText(code.textContent, function () { flash(btn, '복사됨'); announce('복사됨'); }, function () {
        selectNode(code); flash(btn, '선택됨'); announce('선택됨 · 직접 복사 필요');
      });
    });
  }

  /* ---------- 절 주소 복사 ---------- */
  function initAnchors() {
    document.addEventListener('click', function (ev) {
      var a = ev.target.closest('a.ws-anchor');
      if (!a) return;
      ev.preventDefault();
      var id = a.getAttribute('href').slice(1);
      var url = window.location.href.split('#')[0] + '#' + id;
      if (window.history && history.replaceState) history.replaceState(null, '', '#' + id);
      copyText(url, function () {
        a.classList.add('is-done');
        clearTimeout(a._wsTimer);
        a._wsTimer = setTimeout(function () { a.classList.remove('is-done'); }, 1800);
        announce('절 주소 복사됨');
      }, function () { window.location.hash = id; });
    });
  }

  /* ---------- 점검 항목: 이슈별 체크 상태 ---------- */
  function initChecklists() {
    each(document.querySelectorAll('.ws-checklist[data-check]'), function (ul) {
      var key = 'ws-check:' + ul.getAttribute('data-check');
      var boxes = Array.prototype.slice.call(ul.querySelectorAll('input[type="checkbox"]'));
      var status = document.querySelector('[data-check-status="' + ul.getAttribute('data-check') + '"]');
      var count = status && status.querySelector('[data-check-count]');
      var saved = [];
      try { saved = JSON.parse(store(key) || '[]'); } catch (e) { saved = []; }
      boxes.forEach(function (b, i) { b.checked = saved.indexOf(i + 1) !== -1; });

      function update(save) {
        var on = [];
        boxes.forEach(function (b, i) { if (b.checked) on.push(i + 1); });
        if (count) count.textContent = String(on.length);
        if (save) store(key, on.length ? JSON.stringify(on) : null);
      }
      update(false);
      ul.addEventListener('change', function () { update(true); });
      var reset = status && status.querySelector('[data-check-reset]');
      if (reset) reset.addEventListener('click', function () {
        boxes.forEach(function (b) { b.checked = false; });
        update(true);
        announce('점검 항목 초기화');
      });
    });
  }

  /* ---------- 접힌 절: 주소의 #절 이 가리키면 펼침 ---------- */
  function openTarget() {
    var id = '';
    try { id = decodeURIComponent(window.location.hash.slice(1)); } catch (e) { return; }
    if (!id) return;
    var el = document.getElementById(id);
    if (!el) return;
    var d = el.matches('details') ? el : (el.querySelector(':scope > details') || el.closest('details'));
    if (d && !d.open) { d.open = true; el.scrollIntoView(); }
  }

  /* ---------- 인쇄: 접힌 구역을 모두 펼쳤다가 되돌림 ---------- */
  function initPrint() {
    var closed = [];
    window.addEventListener('beforeprint', function () {
      closed = [];
      each(document.querySelectorAll('details:not([open])'), function (d) { closed.push(d); d.open = true; });
    });
    window.addEventListener('afterprint', function () {
      closed.forEach(function (d) { d.open = false; });
      closed = [];
    });
  }

  /* ---------- 좁은 화면: 머리의 메뉴 단추 → 머리 아래 덮는 메뉴(포커스 가둠 · 뒤 스크롤 잠금 · Esc 닫기) ----------
     JS 없으면 메뉴는 본문 위에 펼친 채(기존 details) 그대로. */
  function initMenu() {
    var menu = document.getElementById('ws-menu');
    var btn = document.querySelector('.ws-menu-btn');
    if (!menu || !window.matchMedia) return;
    var narrow = window.matchMedia('(max-width: 899.98px)');
    var main = document.getElementById('ws-main');
    var root = document.documentElement;
    var behind = [main, document.querySelector('.ws-skip'), document.querySelector('.ws-foot'), document.querySelector('.ws-alert')].filter(Boolean);
    var isOpen = false;

    function focusables() {
      return Array.prototype.filter.call(menu.querySelectorAll('a[href], summary'), function (el) {
        return el.getClientRects().length > 0;
      });
    }
    function setOpen(on, returnFocus) {
      isOpen = on;
      menu.open = on;
      root.classList.toggle('ws-menu-open', on);
      if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      // 열린 동안: 메뉴는 모달 대화 상자, 뒤쪽(본문·바닥글·건너뛰기 링크·오류 띠)은 inert
      if (on) {
        menu.setAttribute('role', 'dialog');
        menu.setAttribute('aria-modal', 'true');
        menu.setAttribute('aria-label', '문서 메뉴');
      } else {
        menu.removeAttribute('role');
        menu.removeAttribute('aria-modal');
        menu.removeAttribute('aria-label');
      }
      behind.forEach(function (el) { el.inert = on; });
      if (on) {
        var cur = menu.querySelector('[aria-current="page"]') || focusables()[0];
        if (cur) { cur.focus({ preventScroll: true }); cur.scrollIntoView({ block: 'center' }); }
      } else if (returnFocus && btn) {
        btn.focus();
      }
    }
    function apply() {
      if (narrow.matches) { if (!isOpen) menu.open = false; }
      else { if (isOpen) setOpen(false, false); menu.open = true; }
    }
    apply();
    if (narrow.addEventListener) narrow.addEventListener('change', apply);
    if (!btn) return;

    btn.addEventListener('click', function () { setOpen(!isOpen, false); });
    menu.addEventListener('click', function (ev) {
      var a = ev.target.closest('a[href]');
      if (!isOpen || !a) return;
      setOpen(false, false);
      // 같은 쪽 안 이동(#…)이면 포커스를 그 절 제목으로(없으면 메뉴 단추로) 옮겨 body 에 남지 않게
      if (a.pathname !== window.location.pathname || !a.hash) return;
      setTimeout(function () {
        var id = '';
        try { id = decodeURIComponent(a.hash.slice(1)); } catch (e) { id = ''; }
        var t = id ? document.getElementById(id) : null;
        var h = t && (t.matches('h1, h2, h3') ? t : t.querySelector('h1, h2, h3'));
        var f = h || t;
        if (f) {
          if (!f.hasAttribute('tabindex')) f.setAttribute('tabindex', '-1');
          f.focus({ preventScroll: true });
        } else {
          btn.focus();
        }
      }, 0);
    });
    document.addEventListener('keydown', function (ev) {
      if (!isOpen) return;
      if (ev.key === 'Escape') { ev.preventDefault(); setOpen(false, true); return; }
      if (ev.key !== 'Tab') return;
      var list = focusables();
      if (!list.length) return;
      var first = list[0], last = list[list.length - 1], a = document.activeElement;
      if (!ev.shiftKey && a === last) { ev.preventDefault(); btn.focus(); }
      else if (ev.shiftKey && a === btn) { ev.preventDefault(); last.focus(); }
      else if (ev.shiftKey && a === first) { ev.preventDefault(); btn.focus(); }
      else if (a !== btn && !menu.contains(a)) { ev.preventDefault(); btn.focus(); }
    });
    // 검색 칸을 쓰면 메뉴는 닫음
    var q = document.getElementById('ws-q');
    if (q) q.addEventListener('focus', function () { if (isOpen) setOpen(false, false); });
  }

  /* ---------- 상단 검색: 콤보박스 ---------- */
  function norm(s) { return (s || '').toLowerCase().normalize('NFC'); }

  function initSearch() {
    var input = document.getElementById('ws-q');
    if (!input) return;
    var pop = document.getElementById('ws-q-pop');
    var list = document.getElementById('ws-q-list');
    var none = pop.querySelector('.ws-results-none');
    var more = pop.querySelector('.ws-results-more');
    var status = document.getElementById('ws-q-status');
    var MAX = 8;
    var rows = [];
    var byUrl = {};
    var active = -1;
    var shown = [];

    // 기본 목록: 왼쪽 메뉴의 이슈와 페이지 (색인을 못 받아도 제목으로 찾음)
    each(document.querySelectorAll('.ws-nav-grp'), function (g) {
      var gname = g.querySelector('.ws-nav-grp-name');
      each(g.querySelectorAll('li a'), function (a) {
        var name = a.querySelector('.ws-nav-name');
        var r = { title: name ? name.textContent : a.textContent, url: a.getAttribute('href'), sub: gname ? gname.textContent : '', id: '', sev: '' };
        r.hay = norm(r.title + ' ' + r.sub);
        rows.push(r);
        byUrl[r.url] = r;
      });
    });
    each(document.querySelectorAll('.ws-nav-pages a'), function (a) {
      var r = { title: a.textContent, url: a.getAttribute('href'), sub: '안내 문서', id: '', sev: '' };
      r.hay = norm(r.title);
      rows.push(r);
    });

    var loaded = false;
    function load() {
      if (loaded || !window.fetch) return;
      loaded = true;
      fetch(input.getAttribute('data-search'), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
        .then(function (data) {
          if (!Array.isArray(data)) return;
          data.forEach(function (d) {
            var r = d && byUrl[d.url];
            if (!r) return;
            r.id = d.id || '';
            r.sev = d.severity || '';
            r.hay = norm(r.title + ' ' + r.id + ' ' + r.sub + ' ' + (d.text || ''));
            r.head = norm(r.title + ' ' + r.id);
          });
          if (input.value) run();
        })
        .catch(function () { /* 색인을 못 받으면 제목으로만 찾음 */ });
    }

    var SEV = { high: '위험', medium: '주의', low: '참고' };

    function el(tag, cls, text) {
      var n = document.createElement(tag);
      if (cls) n.className = cls;
      n.textContent = text;
      return n;
    }

    // 제목 속 검색 낱말 표시(글자만 다루고 HTML 은 만들지 않음)
    function mark(node, text, words) {
      var low = norm(text), marks = [];
      words.forEach(function (w) {
        if (w.length < 2) return; // 한 글자는 표시가 너무 많아 생략
        var i = low.indexOf(w);
        while (i !== -1) { marks.push([i, i + w.length]); i = low.indexOf(w, i + w.length); }
      });
      marks.sort(function (a, b) { return a[0] - b[0]; });
      var pos = 0;
      marks.forEach(function (m) {
        if (m[0] < pos) return;
        if (m[0] > pos) node.appendChild(document.createTextNode(text.slice(pos, m[0])));
        node.appendChild(el('mark', '', text.slice(m[0], m[1])));
        pos = m[1];
      });
      if (pos < text.length) node.appendChild(document.createTextNode(text.slice(pos)));
    }

    function setActive(i) {
      var opts = list.children;
      if (active >= 0 && opts[active]) { opts[active].classList.remove('is-active'); opts[active].setAttribute('aria-selected', 'false'); }
      active = i;
      if (i >= 0 && opts[i]) {
        opts[i].classList.add('is-active');
        opts[i].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', opts[i].id);
        opts[i].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    function open(on) {
      pop.hidden = !on;
      input.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (!on) setActive(-1);
    }

    function run() {
      var words = norm(input.value).split(/\s+/).filter(Boolean);
      setActive(-1);
      list.textContent = '';
      if (!words.length) { open(false); status.textContent = ''; return; }
      var hits = rows.filter(function (r) { return words.every(function (w) { return r.hay.indexOf(w) !== -1; }); });
      hits.sort(function (a, b) {
        var ah = words.every(function (w) { return (a.head || a.hay).indexOf(w) !== -1; }) ? 0 : 1;
        var bh = words.every(function (w) { return (b.head || b.hay).indexOf(w) !== -1; }) ? 0 : 1;
        return ah - bh;
      });
      shown = hits.slice(0, MAX);
      shown.forEach(function (r, i) {
        var li = document.createElement('li');
        li.id = 'ws-opt-' + i;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.className = 'ws-result';
        var t = document.createElement('span');
        t.className = 'ws-result-title';
        mark(t, r.title, words);
        var s = document.createElement('span');
        s.className = 'ws-result-meta';
        if (r.id) s.appendChild(el('span', 'ws-id', r.id));
        if (r.sub) s.appendChild(el('span', '', r.sub));
        if (SEV[r.sev]) s.appendChild(el('span', 'ws-badge ws-badge--' + r.sev, SEV[r.sev]));
        li.appendChild(t);
        li.appendChild(s);
        li.addEventListener('mousedown', function (ev) { ev.preventDefault(); window.location.href = r.url; });
        list.appendChild(li);
      });
      none.hidden = shown.length > 0;
      more.hidden = hits.length <= MAX;
      more.textContent = hits.length > MAX ? hits.length + '건 중 ' + MAX + '건 표시 · 낱말을 더해 좁히기' : '';
      open(true);
      status.textContent = '검색 결과 ' + hits.length + '건' + (hits.length > MAX ? ' 중 ' + MAX + '건 표시' : '');
    }

    input.addEventListener('focus', function () { load(); if (input.value) run(); });
    input.addEventListener('input', function () { load(); run(); });
    input.addEventListener('blur', function () { setTimeout(function () { open(false); }, 120); });
    input.addEventListener('keydown', function (ev) {
      var n = shown.length;
      if (ev.key === 'ArrowDown' && n) { ev.preventDefault(); if (pop.hidden) run(); setActive((active + 1) % n); }
      else if (ev.key === 'ArrowUp' && n) { ev.preventDefault(); setActive(active <= 0 ? n - 1 : active - 1); }
      else if (ev.key === 'Enter') {
        var r = shown[active >= 0 ? active : 0];
        if (r && input.value) { ev.preventDefault(); window.location.href = r.url; }
      } else if (ev.key === 'Escape') {
        if (!pop.hidden) { ev.preventDefault(); open(false); }
        else if (input.value) { input.value = ''; run(); }
        else input.blur();
      }
    });

    // 단축키 / : 입력 칸 밖에서 누르면 검색으로
    document.addEventListener('keydown', function (ev) {
      if (ev.key !== '/' || ev.ctrlKey || ev.metaKey || ev.altKey) return;
      var t = ev.target;
      if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
      ev.preventDefault();
      input.focus();
      input.select();
    });
  }

  /* ---------- 긴 덩어리 줄바꿈 보정 ----------
     CSS balance 는 6줄까지만 동작함. 7줄 이상이면 pretty 로 바꾸고, 그래도 마지막 줄이 폭의 30% 미만이면
     줄 수가 늘지 않는 범위에서 폭을 1%씩(최대 14%) 줄여 마지막 줄을 늘림. 글자는 바꾸지 않음. */
  var LONG_SEL = '.ws-checklist label, .ws-summary p, .ws-sec p:not([class]), .ws-sec :is(ul,ol):not([class]) > li, .ws-fold-body p, .ws-fold-body li, .ws-deflist dd, .ws-step-desc, .ws-readlist-why, .ws-sevwhy dd';
  function lines(el) {
    var g = document.createRange(); g.selectNodeContents(el);
    var mids = [], l = Infinity, r = -Infinity, last = null;
    Array.prototype.forEach.call(g.getClientRects(), function (x) {
      if (x.width < 0.5 || x.height < 4) return;
      var m = x.top + x.height / 2;
      if (!mids.some(function (y) { return Math.abs(y - m) < 6; })) mids.push(m);
    });
    if (!mids.length) return { n: 0, ratio: 1 };
    var bottom = Math.max.apply(null, mids);
    Array.prototype.forEach.call(g.getClientRects(), function (x) {
      if (x.width < 0.5 || x.height < 4 || Math.abs(x.top + x.height / 2 - bottom) >= 6) return;
      l = Math.min(l, x.left); r = Math.max(r, x.right);
    });
    var w = el.getBoundingClientRect().width;
    return { n: mids.length, ratio: w ? (r - l) / w : 1 };
  }
  function fixLong(scope) {
    var list = scope ? scope.querySelectorAll(LONG_SEL) : document.querySelectorAll('main ' + LONG_SEL.split(', ').join(', main '));
    each(list, function (el) {
      el.style.textWrap = ''; el.style.maxInlineSize = '';
      if (!el.getClientRects().length || el.closest('details:not([open])')) return;
      var a = lines(el);
      if (a.n < 7) return;
      el.style.textWrap = 'pretty';
      a = lines(el);
      if (a.ratio >= 0.3) return;
      var best = null;
      for (var p = 99; p >= 86; p--) {
        el.style.maxInlineSize = p + '%';
        var b = lines(el);
        if (b.n !== a.n) break;
        if (b.ratio >= 0.3) { best = p; break; }
      }
      el.style.maxInlineSize = best ? best + '%' : '';
    });
  }
  function initWrap() {
    if (!window.CSS || !CSS.supports('text-wrap', 'pretty')) return;
    var run = function () { fixLong(); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(run); else run();
    var t = null, w = window.innerWidth;
    window.addEventListener('resize', function () {
      if (window.innerWidth === w) return; // 휴대폰 주소창 높이 변화는 무시
      w = window.innerWidth;
      clearTimeout(t); t = setTimeout(run, 150);
    });
    // 접힘을 펴면 그 안쪽만 다시 계산
    document.addEventListener('toggle', function (ev) {
      var d = ev.target;
      if (d.open && d.closest && d.closest('main')) fixLong(d);
    }, true);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initWrap();
    initMenu();
    initTabs();
    initPanelTabs();
    initCopy();
    initAnchors();
    initChecklists();
    initSearch();
    initPrint();
    openTarget();
    window.addEventListener('hashchange', openTarget);
  });
})();
