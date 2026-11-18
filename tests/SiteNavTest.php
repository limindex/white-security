<?php
require_once __DIR__ . '/support.php';

/** 정보 구조: 왼쪽 메뉴 3구역 · 경로 표시 · 새 페이지 · 설정(site.json) · 공유 메타 */
function nav_site(array $extra = [], ?string $siteJson = null): Site
{
    $root = make_root($extra);
    if ($siteJson !== null) file_put_contents($root . '/data/site.json', $siteJson);
    return new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
}

const NAV_NEW_PAGES = ['start', 'check', 'severity', 'guide', 'glossary', 'sources'];

test('새 페이지 6개: 200 html · 제목 · 한 줄 설명', function () {
    $s = nav_site();
    foreach (NAV_NEW_PAGES as $p) {
        $r = $s->route($p);
        assert_same(200, $r['status'], $p);
        assert_same('html', $r['type'], $p);
        $meta = Site::PAGES[$p];
        assert_contains('<h1>' . $meta['title'] . '</h1>', $r['body']);
        assert_contains('<p class="ws-page-lead">' . Site::e($meta['lead']) . '</p>', $r['body']);
    }
});

test('자리표시 페이지는 "준비 중" 구역, 심각도 기준·문서 읽는 법은 실제 내용', function () {
    $s = nav_site();
    foreach (['start', 'check', 'glossary', 'sources'] as $p) assert_contains('class="ws-pending"', $s->route($p)['body']);
    $sev = $s->route('severity')['body'];
    assert_true(!str_contains($sev, 'class="ws-pending"'), 'severity 준비 중 아님');
    foreach (['영향', '악용 쉬움', '데이터 유출', '계정 탈취', '서비스 중단', '비용', '인증', '자동화', '판정 예'] as $w) assert_contains($w, $sev);
    foreach (['ws-badge--high', 'ws-badge--medium', 'ws-badge--low'] as $c) assert_contains($c, $sev);
    $guide = $s->route('guide')['body'];
    assert_true(!str_contains($guide, 'class="ws-pending"'), 'guide 준비 중 아님');
    foreach (['WS-&lt;분류&gt;-&lt;번호&gt;', '요약', '발생 상황', '위험 배경', '대응 방법', '점검 항목', '탐지 규칙', '코드 예시', '출처', '게시일', '최종 확인일', '다음 검토 예정일', '개정 이력', '적용 대상', '심각도 근거', '기준 번호'] as $w) assert_contains($w, $guide);
});

test('왼쪽 메뉴: 시작하기 · 보안 이슈(총 개수) · 참고 순서와 링크', function () {
    $b = nav_site()->route('about')['body'];
    assert_true((bool)preg_match('#<nav[^>]*class="ws-nav"[^>]*>(.*?)</nav>#s', $b, $m), 'nav');
    $nav = $m[1];
    $p1 = strpos($nav, '시작하기'); $p2 = strpos($nav, '보안 이슈'); $p3 = strpos($nav, '참고');
    assert_true($p1 !== false && $p2 > $p1 && $p3 > $p2, '구역 순서');
    foreach (['about', 'start', 'install', 'check', 'severity', 'guide', 'glossary', 'coverage', 'changelog', 'sources'] as $p) {
        assert_contains('href="/white-security/' . $p . '"', $nav);
    }
    assert_true((bool)preg_match('#보안 이슈</span>\s*<span class="ws-nav-count">1</span>#u', $nav), '보안 이슈 총 개수');
    assert_true((bool)preg_match('#href="/white-security/about"[^>]*aria-current="page"#', $nav), '현재 페이지');
});

test('왼쪽 메뉴: 묶음 10개 전부 접힘(details)·개수, 현재 이슈 묶음만 펼침', function () {
    $s = nav_site();
    $home = $s->route('')['body'];
    assert_same(10, preg_match_all('#<details class="ws-nav-grp"#', $home), '묶음 수');
    assert_same(0, preg_match_all('#<details class="ws-nav-grp"[^>]*\bopen\b#', $home), '홈에서는 모두 접힘');
    assert_true((bool)preg_match('#<summary[^>]*>\s*<span class="ws-nav-grp-name">웹 애플리케이션</span>\s*<span class="ws-nav-count">1</span>#u', $home), '묶음 개수');
    assert_true((bool)preg_match('#<summary[^>]*>\s*<span class="ws-nav-grp-name">AI 활용</span>\s*<span class="ws-nav-count">0</span>#u', $home), '빈 묶음 0');
    $issue = $s->route('issues/WS-INJ-001')['body'];
    assert_same(1, preg_match_all('#<details class="ws-nav-grp"[^>]*\bopen\b#', $issue), '펼친 묶음 1개');
    assert_true((bool)preg_match('#<details class="ws-nav-grp" data-group="web" open>#', $issue), '현재 묶음 펼침');
});

test('경로 표시: 모든 페이지(홈 제외)에 nav[aria-label=경로] ol, 마지막은 aria-current', function () {
    $s = nav_site();
    foreach (array_merge(array_keys(Site::PAGES), ['issues/WS-INJ-001']) as $p) {
        $b = $s->route($p)['body'];
        assert_true((bool)preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $b, $m), "$p 경로");
        assert_contains('<a href="/white-security/">홈</a>', $m[1]);
        assert_true((bool)preg_match('#<li aria-current="page">[^<]+</li>\s*$#u', trim($m[1])), "$p 마지막 aria-current");
    }
    $issue = $s->route('issues/WS-INJ-001')['body'];
    preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $issue, $m);
    assert_contains('보안 이슈', $m[1]);
    assert_contains('<a href="/white-security/#grp-web">웹 애플리케이션</a>', $m[1]);
    assert_contains('<li aria-current="page">WS-INJ-001</li>', $m[1]);
    $sev = $s->route('severity')['body'];
    preg_match('#<nav class="ws-crumbs" aria-label="경로"><ol>(.*?)</ol></nav>#s', $sev, $m);
    assert_contains('<li>시작하기</li>', $m[1]);
    assert_true(!str_contains($s->route('')['body'], 'class="ws-crumbs"'), '홈에는 경로 없음');
});

test('상단: 브랜드·검색(단축키 /)·발행 주체와 GitHub 는 설정값이 있을 때만', function () {
    $b = nav_site()->route('')['body'];
    assert_true((bool)preg_match('#<header class="ws-top">.*?화이트 보안.*?White Security.*?</header>#s', $b), '브랜드');
    assert_true((bool)preg_match('#<header class="ws-top">.*?<input[^>]*type="search"[^>]*data-search="/white-security/api/search.json".*?</header>#s', $b), '상단 검색');
    assert_true((bool)preg_match('#<kbd[^>]*>/</kbd>#', $b), '단축키 표시');
    assert_true(!str_contains($b, 'ws-publisher'), '발행 주체 기본 빈 값');
    assert_true(!str_contains($b, 'ws-gh'), 'GitHub 기본 없음');
    $s = nav_site([], '{"publisher": "테스트 발행처", "github": "https://github.com/example/repo"}');
    $b = $s->route('')['body'];
    assert_contains('<span class="ws-publisher">테스트 발행처</span>', $b);
    assert_true((bool)preg_match('#<a class="ws-gh" href="https://github.com/example/repo"#', $b), 'GitHub 링크');
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
    assert_contains('<title>심각도 기준 · 화이트 보안</title>', $b);
    assert_contains('<meta name="description" content="' . Site::e(Site::PAGES['severity']['lead']) . '">', $b);
    assert_contains('<meta property="og:title" content="심각도 기준 · 화이트 보안">', $b);
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

test('홈: 한 줄 소개 → 시작점 3갈래 → 최근 개정 → 점검 기준 요약 순서', function () {
    $b = nav_site()->route('')['body'];
    $pos = [];
    foreach (['class="ws-home-intro"', 'class="ws-paths"', 'class="ws-recent"', 'class="ws-cov-line"'] as $k) {
        $pos[$k] = strpos($b, $k);
        assert_true($pos[$k] !== false, $k);
    }
    assert_true($pos['class="ws-home-intro"'] < $pos['class="ws-paths"'] && $pos['class="ws-paths"'] < $pos['class="ws-recent"'] && $pos['class="ws-recent"'] < $pos['class="ws-cov-line"'], '순서');
    preg_match('#class="ws-paths"(.*?)</section>#s', $b, $m);
    foreach (['/white-security/start', '/white-security/check', '/white-security/install'] as $h) assert_contains('href="' . $h . '"', $m[1]);
    assert_same(3, preg_match_all('#<h3[^>]*>#', $m[1]), '시작점 3개');
});

test('404: 디자인된 안내와 주요 링크', function () {
    $b = nav_site()->route('nope')['body'];
    assert_contains('찾을 수 없음', $b);
    foreach (['/white-security/', '/white-security/start', '/white-security/coverage'] as $h) assert_contains('href="' . $h . '"', $b);
});
