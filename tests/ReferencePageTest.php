<?php
require_once __DIR__ . '/support.php';

/** 8D2: 개정 이력·출처·404·모바일 메뉴 단추·검색 결과 틀 */
function ref_site(array $extra = [], ?string $siteJson = null): Site
{
    $root = make_root($extra);
    if ($siteJson !== null) file_put_contents($root . '/data/site.json', $siteJson);
    return new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
}

function ref_issue(string $id, string $date, string $url): string
{
    // 검증을 통과하는 fixture(INJ-001)를 바탕으로 번호·날짜·개정·출처만 바꿈
    $raw = (string)file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $raw = str_replace(['id: WS-INJ-001', 'slug: sql-injection'], ["id: $id", 'slug: s-' . strtolower($id)], $raw);
    $raw = (string)preg_replace('/^published: .*$/m', "published: $date", $raw);
    $raw = (string)preg_replace('/^checked: .*$/m', "checked: $date", $raw);
    $raw = (string)preg_replace('/^revisions:\n(?:  .*\n)+/m', "revisions:\n  - date: $date\n    note: 첫 작성 $id\n", $raw);
    return (string)preg_replace('/^sources:\n(?:  .*\n)+/m', "sources:\n  - title: 같은 자료\n    url: $url\n    year: 2025\n", $raw);
}

test('개정 이력: 모든 문서 개정을 날짜별로 묶고 최신 날짜가 먼저', function () {
    $s = ref_site(['WS-ZZZ-001-a.md' => ref_issue('WS-ZZZ-001', '2026-10-05', 'https://owasp.org/Top10/')]);
    $rows = $s->allRevisions();
    assert_same(3, count($rows), '개정 3건(INJ 2 + ZZZ 1)');
    assert_same(['2026-11-18', '2026-10-05', '2026-09-01'], array_column($rows, 'date'));
    $b = $s->route('changelog')['body'];
    assert_same(3, preg_match_all('#<h3 class="ws-revday-h"#', $b), '날짜 묶음 3개');
    assert_true(strpos($b, 'datetime="2026-11-18"') < strpos($b, 'datetime="2026-10-05"'), '최신 날짜 먼저');
    assert_contains('첫 작성 WS-ZZZ-001', $b);
    assert_contains('<span class="ws-fold-count">3건</span>', $b);
    assert_true(!str_contains($b, '기록 없음') && !str_contains($b, '스킬 버전'), 'CHANGELOG.md 가 없으면 버전 절 없음(자리표시 없음)');
    assert_true(!str_contains($b, '준비 중'), '준비 중 없음');
});

test('개정 이력: CHANGELOG.md 의 버전 절을 스킬 버전으로 보여 줌', function () {
    $root = make_root();
    file_put_contents($root . '/CHANGELOG.md', "# Changelog\n\n설명\n\n## 0.2.0\n\n- 둘째 판 `x`를 고침\n\n## 0.1.0\n\n- 첫 판\n");
    $s = new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    assert_same(['0.2.0', '0.1.0'], array_column($s->repo->versions(), 'version'));
    $b = nw_strip($s->route('changelog')['body']);
    assert_contains('<h2 id="ws-ver-h">스킬 버전</h2>', $b);
    assert_true(strpos($b, '>0.2.0</h3>') < strpos($b, '>0.1.0</h3>'), '최신 버전 먼저');
    assert_contains('<li>첫 판</li>', $b);
    $real = Repository::load(dirname(__DIR__))->versions();
    assert_same('0.1.0', end($real)['version'], '실제 CHANGELOG 0.1.0');
});

test('출처·라이선스: 라이선스 공식 링크 · 인용 원칙 · 인용 자료는 주소 기준 한 번만', function () {
    $s = ref_site(['WS-ZZZ-001-a.md' => ref_issue('WS-ZZZ-001', '2026-10-05', 'https://owasp.org/Top10/')]);
    $refs = $s->sourceIndex();
    assert_same(2, count($refs), '중복 주소는 한 번');
    $owasp = array_values(array_filter($refs, fn($r) => $r['url'] === 'https://owasp.org/Top10/'))[0];
    assert_same(['WS-INJ-001', 'WS-ZZZ-001'], $owasp['ids']);
    assert_same('owasp.org', $owasp['host']);
    $b = $s->route('sources')['body'];
    foreach (['https://creativecommons.org/licenses/by-sa/4.0/deed.ko', 'https://opensource.org/license/mit', 'https://openfontlicense.org/'] as $u) assert_contains('href="' . $u . '"', $b);
    assert_contains('<h2 id="ws-cite-h">인용 원칙</h2>', $b);
    assert_contains('<span class="ws-fold-count">2건</span>', $b);
    assert_contains('href="/white-security/issues/WS-ZZZ-001"', $b);
});

test('404: 상태 숫자 라벨 없이 제목·안내·주요 문서(켜진 쪽만)', function () {
    $r = ref_site()->route('nope');
    assert_same(404, $r['status']);
    $b = $r['body'];
    assert_true(!str_contains($b, 'ws-code-num'), '404 라벨 없음');
    assert_contains('<h1>찾을 수 없음</h1>', $b);
    assert_contains('주요 문서', $b);
    foreach (['/white-security/', '/white-security/start', '/white-security/install', '/white-security/guide'] as $h) assert_contains('href="' . $h . '"', $b);
    $off = ref_site([], '{"pages": {"guide": false}}')->route('nope')['body'];
    assert_true(!str_contains($off, 'href="/white-security/guide"'), '꺼진 쪽은 빠짐');
});

test('좁은 화면 메뉴 단추: 메뉴를 가리키고 닫힌 상태로 시작, GitHub 는 설정 있을 때만 메뉴 안에', function () {
    $b = ref_site()->route('about')['body'];
    assert_true((bool)preg_match('#<button class="ws-menu-btn" type="button" aria-controls="ws-menu" aria-expanded="false" aria-label="문서 메뉴">#', $b), '메뉴 단추');
    assert_contains('<details class="ws-side" id="ws-menu" open>', $b);
    assert_true(!str_contains($b, 'ws-nav-gh'), 'GitHub 없음');
    $g = ref_site([], '{"github": "https://github.com/example/repo"}')->route('about')['body'];
    assert_contains('<p class="ws-nav-gh"><a href="https://github.com/example/repo"', $g);
});

test('검색 결과 틀: 결과 없음·더 있음 안내 자리', function () {
    $b = ref_site()->route('')['body'];
    assert_contains('<p class="ws-results-none" hidden>', $b);
    assert_contains('<p class="ws-results-more" hidden></p>', $b);
});

test('개정 이력: 같은 날 \'최초 작성\' 여러 건은 "N개 문서 첫 게시" 한 줄(접힘 안에 문서 목록), 한 건이면 그대로', function () {
    $mk = fn(string $id, string $date) => str_replace('note: 첫 작성 ' . $id, 'note: 최초 작성', ref_issue($id, $date, 'https://owasp.org/' . $id));
    $s = ref_site(['WS-ZZZ-001-a.md' => $mk('WS-ZZZ-001', '2026-10-05'), 'WS-ZZZ-002-a.md' => $mk('WS-ZZZ-002', '2026-10-05'), 'WS-ZZZ-003-a.md' => $mk('WS-ZZZ-003', '2026-10-06')]);
    $b = nw_strip($s->route('changelog')['body']);
    preg_match('#<section class="ws-revday" aria-labelledby="rev-2026-10-05">(.*?)</section>#s', $b, $d5);
    assert_true((bool)preg_match('#<details class="ws-subfold ws-revfirst">\s*<summary><span class="ws-tx">2개 문서 첫 게시</span>#u', $d5[1]), '묶은 한 줄');
    assert_true(!str_contains($d5[1], 'class="ws-recent'), '개별 줄 없음');
    assert_same(2, substr_count($d5[1], '<li><a href="/white-security/issues/WS-ZZZ-00'), '펼친 목록 2건');
    preg_match('#<section class="ws-revday" aria-labelledby="rev-2026-10-06">(.*?)</section>#s', $b, $d6);
    assert_true(!str_contains($d6[1], '첫 게시') && str_contains($d6[1], '최초 작성'), '한 건이면 그대로');
});
