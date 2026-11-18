<?php
const INST_JSON_8U = '{"publisher": "LimIndex", "github": "https://github.com/limindex/white-security", "marketplace": "limindex/skills", "plugin_id": "white-security@limindex", "released": false}';
require_once __DIR__ . '/support.php';

/** 정보 구조: 왼쪽 메뉴 3구역 · 경로 표시 · 새 페이지 · 설정(site.json) · 공유 메타 */
function nav_site(array $extra = [], ?string $siteJson = null): Site
{
    $root = make_root($extra);
    if ($siteJson !== null) file_put_contents($root . '/data/site.json', $siteJson);
    return new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
}

const NAV_NEW_PAGES = ['start', 'severity', 'guide', 'sources'];

test('켜진 새 페이지 4개: 200 html · 제목 · 한 줄 설명', function () {
    $s = nav_site();
    foreach (NAV_NEW_PAGES as $p) {
        $r = $s->route($p);
        assert_same(200, $r['status'], $p);
        assert_same('html', $r['type'], $p);
        $meta = Site::PAGES[$p];
        assert_contains('<h1>' . $meta['title'] . '</h1>', nw_strip($r['body']));
        assert_contains('<p class="ws-page-lead">' . Site::e($meta['lead']) . '</p>', nw_strip($r['body']));
    }
});

test('공개 화면에 "준비 중" 없음, 심각도 기준·문서 읽는 법은 실제 내용', function () {
    $s = nav_site();
    foreach (array_merge(array_keys($s->pages()), ['', 'issues/WS-INJ-001', 'nope']) as $p) {
        $b = $s->route($p)['body'];
        assert_true(!str_contains($b, 'ws-pending') && !str_contains($b, '준비 중'), "$p: 준비 중 없음");
    }
    $sev = $s->route('severity')['body'];
    assert_true(!str_contains($sev, 'class="ws-pending"'), 'severity 준비 중 아님');
    foreach (['영향', '악용 쉬움', '데이터 유출', '계정 탈취', '서비스 중단', '비용', '인증', '자동화', '판정 예'] as $w) assert_contains($w, strip_tags($sev));
    foreach (['ws-badge--high', 'ws-badge--medium', 'ws-badge--low'] as $c) assert_contains($c, $sev);
    $guide = $s->route('guide')['body'];
    assert_true(!str_contains($guide, 'class="ws-pending"'), 'guide 준비 중 아님');
    foreach (['WS-&lt;분류&gt;-&lt;번호&gt;', '요약', '발생 상황', '위험 배경', '대응 방법', '점검 항목', '탐지 규칙', '코드 예시', '출처', '게시일', '최종 확인일', '개정 이력', '적용 대상', '심각도 근거', '기준 번호'] as $w) assert_contains($w, $guide);
});

test('왼쪽 메뉴: 시작하기 · 보안 이슈(총 개수) · 참고 순서와 링크', function () {
    $b = nav_site()->route('about')['body'];
    assert_true((bool)preg_match('#<nav[^>]*class="ws-nav"[^>]*>(.*?)</nav>#s', $b, $m), 'nav');
    $nav = $m[1];
    $p1 = strpos($nav, '시작하기'); $p2 = strpos($nav, '보안 이슈'); $p3 = strpos($nav, '참고');
    assert_true($p1 !== false && $p2 > $p1 && $p3 > $p2, '구역 순서');
    foreach (['about', 'start', 'install', 'severity', 'guide', 'changelog', 'sources'] as $p) {
        assert_contains('href="/white-security/' . $p . '"', $nav);
    }
    foreach (['check', 'glossary', 'coverage'] as $p) assert_true(!str_contains($nav, 'href="/white-security/' . $p . '"'), "메뉴에 없음: $p");
    assert_true((bool)preg_match('#보안 이슈</span>\s*<span class="ws-nav-count">1</span>#u', $nav), '보안 이슈 총 개수');
    assert_true((bool)preg_match('#href="/white-security/about"[^>]*aria-current="page"#', $nav), '현재 페이지');
});

test('왼쪽 메뉴: 이슈가 있는 묶음만 접힘(details)·개수, 빈 묶음은 숨김, 현재 이슈 묶음만 펼침', function () {
    $s = nav_site();
    $home = $s->route('')['body'];
    assert_same(1, preg_match_all('#<details class="ws-nav-grp"#', $home), '이슈 있는 묶음만');
    assert_same(0, preg_match_all('#<details class="ws-nav-grp"[^>]*\bopen\b#', $home), '홈에서는 모두 접힘');
    assert_true((bool)preg_match('#<summary[^>]*>\s*<span class="ws-nav-grp-name">웹 애플리케이션</span>\s*<span class="ws-nav-count">1</span>#u', $home), '묶음 개수');
    assert_true(!str_contains($home, 'AI 활용') && !str_contains($home, 'id="grp-ai-use"'), '빈 묶음은 메뉴·홈에서 숨김');
    $issue = $s->route('issues/WS-INJ-001')['body'];
    assert_same(1, preg_match_all('#<details class="ws-nav-grp"[^>]*\bopen\b#', $issue), '펼친 묶음 1개');
    assert_true((bool)preg_match('#<details class="ws-nav-grp" data-group="web" open>#', $issue), '현재 묶음 펼침');
});

test('경로 표시: 모든 페이지(홈 제외)에 nav[aria-label=경로] ol, 마지막은 aria-current', function () {
    $s = nav_site();
    foreach (array_merge(array_keys($s->pages()), ['issues/WS-INJ-001']) as $p) {
        $b = $s->route($p)['body'];
        assert_true((bool)preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $b, $m), "$p 경로");
        assert_contains('<a href="/white-security/">홈</a>', $m[1]);
        assert_true((bool)preg_match('#<li aria-current="page"><span>[^<]+</span></li>\s*$#u', trim($m[1])), "$p 마지막 aria-current");
    }
    $issue = $s->route('issues/WS-INJ-001')['body'];
    preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $issue, $m);
    assert_contains('보안 이슈', $m[1]);
    assert_contains('<a href="/white-security/#grp-web">웹 애플리케이션</a>', $m[1]);
    assert_contains('<li aria-current="page"><span>WS-INJ-001</span></li>', $m[1]);
    $sev = $s->route('severity')['body'];
    preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $sev, $m);
    assert_contains('<li><span>시작하기</span></li>', $m[1]);
    assert_true(!str_contains($s->route('')['body'], 'class="ws-crumbs"'), '홈에는 경로 없음');
});

test('상단: 브랜드·검색(단축키 /)·발행 주체와 GitHub 는 설정값이 있을 때만', function () {
    $b = nav_site()->route('')['body'];
    assert_true((bool)preg_match('#<header class="ws-top">.*?White Security.*?바이브 코딩 보안 가이드.*?</header>#s', $b), '브랜드');
    assert_true((bool)preg_match('#<header class="ws-top">.*?<input[^>]*type="search"[^>]*data-search="/white-security/api/search.json".*?</header>#s', $b), '상단 검색');
    assert_true((bool)preg_match('#<kbd[^>]*>/</kbd>#', $b), '단축키 표시');
    assert_true(!str_contains($b, 'ws-publisher'), '발행 주체 기본 빈 값');
    assert_true(!str_contains($b, 'ws-gh'), 'GitHub 기본 없음');
    $s = nav_site([], '{"publisher": "테스트 발행처", "github": "https://github.com/example/repo", "marketplace": "example/skills", "plugin_id": "repo@example"}');
    $b = $s->route('')['body'];
    assert_contains('<span class="ws-publisher">by 테스트 발행처</span>', $b);
    assert_contains('<span>테스트 발행처 발행</span>', $b);
    assert_true((bool)preg_match('#<a class="ws-gh" href="https://github.com/example/repo"#', $b), 'GitHub 링크');
    assert_contains('npx skills add example/repo', $s->route('install')['body']);
});

test('site.json: 형식 오류는 오류 띠로, 기본값으로 계속', function () {
    $s = nav_site([], '{"publisher": 3, "github": "http://x"}');
    $errs = implode("\n", $s->repo->errors());
    assert_contains('data/site.json', $errs);
    $b = $s->route('')['body'];
    assert_true(!str_contains($b, 'ws-publisher') && !str_contains($b, 'ws-gh'), '잘못된 값은 쓰지 않음');
    assert_same('', nav_site([], '{깨짐')->setting('publisher'));
});

test('공유 메타: 페이지별 title·description·og, 이슈는 article', function () {
    $s = nav_site();
    $b = $s->route('severity')['body'];
    assert_contains('<title>심각도 기준 — White Security 바이브 코딩 보안 가이드</title>', $b);
    assert_contains('<title>White Security — 바이브 코딩 보안 가이드</title>', $s->route('')['body']);
    assert_contains('<meta name="description" content="' . Site::e(Site::PAGES['severity']['lead']) . '">', $b);
    assert_contains('<meta property="og:title" content="심각도 기준 — White Security 바이브 코딩 보안 가이드">', $b);
    assert_contains('<meta property="og:type" content="website">', $b);
    assert_contains('<meta property="og:locale" content="ko_KR">', $b);
    $i = $s->route('issues/WS-INJ-001')['body'];
    assert_contains('<meta property="og:type" content="article">', $i);
    assert_true((bool)preg_match('#<meta name="description" content="사용자 입력이 SQL#u', $i), '이슈 설명 = 요약');
    assert_contains('rel="icon" href="/white-security/assets/img/favicon.svg"', $i);
    assert_true(!str_contains($i, 'og:url'), 'origin 없으면 og:url 없음');
    $o = nav_site([], '{"origin": "https://docs.example.org"}')->route('severity')['body'];
    assert_contains('<link rel="canonical" href="https://docs.example.org/white-security/severity">', $o);
    assert_contains('<meta property="og:url" content="https://docs.example.org/white-security/severity">', $o);
});

test('홈: 한 줄 소개 → 시작점 3갈래 → 최근 개정 → 묶음 색인 순서(점검 기준 대응 줄 없음)', function () {
    $b = nav_site()->route('')['body'];
    $pos = [];
    foreach (['class="ws-home-intro"', 'class="ws-paths"', 'class="ws-recent"', 'class="ws-grp-index"'] as $k) {
        $pos[$k] = strpos($b, $k);
        assert_true($pos[$k] !== false, $k);
    }
    assert_true($pos['class="ws-home-intro"'] < $pos['class="ws-paths"'] && $pos['class="ws-paths"'] < $pos['class="ws-recent"'] && $pos['class="ws-recent"'] < $pos['class="ws-grp-index"'], '순서');
    assert_true(!str_contains($b, 'ws-cov-line') && !str_contains($b, 'href="/white-security/coverage"'), '홈에 점검 기준 대응 줄 없음');
    preg_match('#class="ws-paths"(.*?)</section>\s*<section class="ws-home-sec"#s', $b, $m);
    foreach (['/white-security/start', '/white-security/guide', '/white-security/install'] as $h) assert_contains('href="' . $h . '"', $m[1]);
    assert_true(!str_contains($m[1], '/white-security/check'), 'check 꺼짐: 시작점에 없음');
    assert_same(3, preg_match_all('#<h3[^>]*>#', $m[1]), '시작점 3개');
});

test('404: 디자인된 안내와 주요 링크', function () {
    $b = nav_site()->route('nope')['body'];
    assert_contains('찾을 수 없음', $b);
    foreach (['/white-security/', '/white-security/start', '/white-security/install'] as $h) assert_contains('href="' . $h . '"', $b);
});

test('페이지 스위치: 기본은 check·glossary 꺼짐 → 404, 메뉴·홈·경로·내보내기 목록에서 빠짐', function () {
    $s = nav_site();
    foreach (['check', 'glossary'] as $p) assert_same(404, $s->route($p)['status'], $p);
    assert_true(!isset($s->pages()['check']) && !isset($s->pages()['glossary']) && isset($s->pages()['start']), 'pages()');
    $body = $s->route('start')['body'];
    assert_true(!str_contains($body, 'href="/white-security/check"') && !str_contains($body, 'href="/white-security/glossary"'), '링크 없음');
    assert_true(!str_contains($s->route('guide')['body'], 'href="/white-security/glossary"'), '관련 문서에서도 빠짐');
});

test('페이지 스위치: site.json pages 로 켜고 끔, 모르는 이름·값은 오류 띠', function () {
    $s = nav_site([], '{"pages": {"check": true, "sources": false}}');
    assert_same(200, $s->route('check')['status']);
    assert_same(404, $s->route('sources')['status']);
    $home = $s->route('')['body'];
    assert_true((bool)preg_match('#class="ws-paths".*href="/white-security/check"#s', $home), '켜면 홈 시작점에 내 사이트 점검');
    assert_true(!str_contains($home, 'href="/white-security/sources"'), '꺼진 sources 는 푸터에서도 빠짐');
    $nav = nav_site([], '{"pages": {"check": true}}')->route('about')['body'];
    assert_contains('href="/white-security/check"', $nav);
    $e = implode("\n", nav_site([], '{"pages": {"nope": true, "start": "yes"}}')->repo->errors());
    assert_contains('모르는 페이지 `nope`', $e);
    assert_contains('pages.start', $e);
});

test('처음이라면: data/start.json 의 이슈 10건을 순서대로 — 번호·제목 링크·심각도·한 줄 이유', function () {
    $json = '[{"id": "WS-INJ-001", "reason": "검색어가 SQL 로 실행되는 문제"}]';
    $root = make_root();
    file_put_contents($root . '/data/start.json', $json);
    $s = new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    assert_same([], $s->repo->errors());
    $b = $s->route('start')['body'];
    assert_true((bool)preg_match('#<ol class="ws-readlist">\s*<li><a href="/white-security/issues/WS-INJ-001">.*?ws-readlist-title.*?SQL.*?ws-readlist-why">검색어가 SQL 로 실행되는 문제.*?WS-INJ-001.*?ws-badge--high#su', $b), '줄 구성');
    foreach (['먼저 알아 둘 것', '읽는 순서', '다음 단계', 'href="/white-security/guide"', 'href="/white-security/severity"', 'href="/white-security/install"'] as $w) assert_contains($w, $b);
    assert_true(!str_contains($b, '읽음'), '읽음 표시 없음(8F)');
});

test('처음이라면: start.json 의 없는 id·빈 이유·겹침은 오류', function () {
    foreach ([
        ['[{"id": "WS-NOPE-999", "reason": "x"}]', 'WS-NOPE-999'],
        ['[{"id": "WS-INJ-001", "reason": " "}]', 'id·reason'],
        ['[{"id": "WS-INJ-001", "reason": "a"}, {"id": "WS-INJ-001", "reason": "b"}]', '두 번 나옵니다'],
        ['{"x": 1}', '목록'],
    ] as [$json, $want]) {
        $root = make_root();
        file_put_contents($root . '/data/start.json', $json);
        assert_contains($want, implode("\n", Repository::load($root)->errors()));
    }
});

test('실제 data/start.json: 이슈 10건, 모두 존재, 이유는 한 줄', function () {
    $repo = Repository::load(__DIR__ . '/..');
    $ids = array_map(fn($r) => $r['issue']->id, $repo->start());
    assert_same(['WS-SEC-001', 'WS-AUTH-001', 'WS-INJ-001', 'WS-XSS-001', 'WS-UPL-001', 'WS-SUP-001', 'WS-AGT-006', 'WS-AGT-001', 'WS-PI-001', 'WS-LLM-004'], $ids);
    assert_same([], array_filter($repo->errors(), fn($e) => str_contains($e, 'start.json')));
    foreach ($repo->start() as $r) assert_true(mb_strlen($r['reason']) <= 60 && !str_contains($r['reason'], "\n"), $r['issue']->id);
});

test('기준 번호 칩: 기준 한 줄 풀이(about)가 칩 안에 있고 aria-describedby 로 연결, title 속성 없음', function () {
    $b = nav_site()->route('issues/WS-INJ-001')['body'];
    preg_match_all('#<li class="ws-chip"([^>]*)>(.*?)</li>#su', $b, $m, PREG_SET_ORDER);
    assert_true(count($m) >= 2, '칩');
    foreach ($m as [, $attr, $inner]) {
        assert_true((bool)preg_match('#id="(ws-tip-\d+)"#', $inner, $id), '풀이 id');
        assert_contains('role="tooltip"', $inner);
        assert_contains('aria-describedby="' . $id[1] . '"', $attr . $inner);
        assert_true(!str_contains($inner . $attr, 'title='), 'title 속성 없음');
    }
    assert_contains('OWASP Top 10:2025 — 웹 보안 국제 비영리 단체 OWASP가 정리한 가장 흔한 웹 취약점 10가지', $b);
    assert_true((bool)preg_match('#<li class="ws-chip" tabindex="0" aria-describedby=#', $b), '링크 없는 칩은 키보드로 포커스');
    $ids = [];
    preg_match_all('#id="(ws-tip-\d+)"#', $b, $all);
    assert_same(count($all[1]), count(array_unique($all[1])), '풀이 id 중복 없음');
});

test('data/frameworks: 기준 파일 모두 about 한 줄 풀이', function () {
    $repo = Repository::load(__DIR__ . '/..');
    assert_same([], array_filter($repo->errors(), fn($e) => str_contains($e, 'frameworks')));
    foreach ($repo->frameworks() as $fw) {
        assert_true(is_string($fw['about'] ?? null) && mb_strlen($fw['about']) <= 140, $fw['id']);
        assert_contains(' — ', $fw['about']);
    }
});

test('소개: 점검 기준 대응표 링크 한 줄과 사용 방법(한마디 → 확실하게 → 자동 점검)', function () {
    $b = nw_strip(nav_site()->route('about')['body']);
    assert_true((bool)preg_match('#9개 기준에 맞춰 작성.*href="/white-security/coverage"[^>]*>점검 기준 대응표#su', $b), '대응표 링크');
    $p1 = strpos($b, '보안 점검해 줘</code> 한마디'); $p2 = strpos($b, '확실하게 실행하려면'); $p3 = strpos($b, '별도 요청 없이 자동 점검');
    assert_true($p1 !== false && $p2 > $p1 && $p3 > $p2, '사용법 순서');
});

test('홈 스킬 설치 카드: 한마디 안내 · 자동 점검 한 줄', function () {
    $b = nav_site()->route('')['body'];
    assert_contains('“보안 점검해 줘” 한마디', $b);
    assert_contains('코드 작성 중 위험한 코드는 자동 점검', $b);
});

test('홈 시작점: 처음이라면 카드는 start.json 앞 3건 제목 링크 + 10건 모두 보기, 스킬 설치 카드에 부분 command 없음, 카드 안 링크 중첩 없음', function () {
    $rows = [];
    foreach (['WS-INJ-001'] as $id) $rows[] = '{"id": "' . $id . '", "reason": "검색어가 SQL 로 실행되는 문제"}';
    $root = make_root();
    file_put_contents($root . '/data/start.json', '[' . implode(',', $rows) . ']');
    file_put_contents($root . '/data/site.json', INST_JSON_8U);
    $s = new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    $b = $s->route('')['body'];
    preg_match('#class="ws-paths"(.*?)</section>\s*<section class="ws-home-sec"#s', $b, $m);
    $p = $m[1];
    assert_true((bool)preg_match('#ws-path--lead.*?<ol class="ws-path-steps">\s*<li><a href="/white-security/issues/WS-INJ-001">SQL 삽입.*?href="/white-security/start">10건 모두 보기#su', $p), '앞 3건 + 모두 보기');
    assert_true(!str_contains($p, '문서 번호와 절 구성'), '문서 읽는 법 카드와 겹치는 단계 없음');
    assert_true(!str_contains($p, '/plugin install') && !str_contains($p, 'ws-path-cmd'), '부분 command 없음');
    assert_contains('설치 안내 4단계', $p);
    assert_same(0, preg_match('#<a[^>]*>(?:(?!</a>).)*<a[ >]#s', $p), '링크 중첩 없음');
});

test('처음이라면 목록: 문서 번호는 제목 블록 안(이유 아래 작은 글씨), 별도 칸 없음', function () {
    $root = make_root();
    file_put_contents($root . '/data/start.json', '[{"id": "WS-INJ-001", "reason": "x"}]');
    $b = (new Site(Repository::load($root), '/white-security/', 'live'))->route('start')['body'];
    assert_true((bool)preg_match('#<span class="ws-readlist-main"><span class="ws-readlist-title">.*?</span><span class="ws-readlist-why">x</span><span class="ws-id">WS-INJ-001</span></span>#su', $b), '제목 블록 안 id');
});
