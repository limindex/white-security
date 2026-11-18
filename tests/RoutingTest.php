<?php
require_once __DIR__ . '/support.php';

function mk_site(string $mode = 'live', array $extra = []): Site
{
    return new Site(Repository::load(make_root($extra)), '/white-security/', $mode);
}

test('route: 홈·이슈·정적 페이지 200 html', function () {
    $s = mk_site();
    foreach (['', 'issues/WS-INJ-001', 'coverage', 'install', 'changelog', 'about'] as $p) {
        $r = $s->route($p);
        assert_same(200, $r['status'], $p);
        assert_same('html', $r['type'], $p);
    }
    assert_contains('SQL 삽입', $s->route('issues/WS-INJ-001')['body']);
});

test('route: 없는 경로·경로 탈출은 404', function () {
    $s = mk_site();
    foreach (['issues/../../_notes/design', 'issues/WS-NOPE-999', 'nope', 'src/Issue.php', 'issues/WS-INJ-001/x', 'api/issues/..%2f.json'] as $p) {
        assert_same(404, $s->route($p)['status'], $p);
    }
    assert_same('html', $s->route('nope')['type']);
});

test('route: api/issues.json', function () {
    $r = mk_site()->route('api/issues.json');
    assert_same(200, $r['status']);
    assert_same('json', $r['type']);
    $a = json_decode($r['body'], true);
    assert_same(1, count($a));
    foreach (['id', 'title', 'group', 'severity', 'maps', 'url'] as $k) assert_true(array_key_exists($k, $a[0]), $k);
    assert_same('/white-security/issues/WS-INJ-001', $a[0]['url']);
});

test('route: api/issues/<id>.json', function () {
    $r = mk_site()->route('api/issues/WS-INJ-001.json');
    assert_same(200, $r['status']);
    $a = json_decode($r['body'], true);
    assert_true(isset($a['code']['php']['good']));
    assert_same('2026-11-18', $a['checked']);
    assert_true(isset($a['sections']['요약']));
    assert_same(404, mk_site()->route('api/issues/WS-NOPE-999.json')['status']);
});

test('route: api/search.json · api/coverage.json', function () {
    $s = mk_site();
    $a = json_decode($s->route('api/search.json')['body'], true);
    foreach (['id', 'title', 'group', 'severity', 'url', 'text'] as $k) assert_true(isset($a[0][$k]), $k);
    $c = $s->route('api/coverage.json');
    assert_same(200, $c['status']);
    assert_true(is_array(json_decode($c['body'], true)));
});

test('route: 오류 띠는 live 에만, 이스케이프됨', function () {
    $extra = ['WS-BAD-001-<b>.md' => "---\nid: nope\n---\n"];
    $live = mk_site('live', $extra)->route('');
    assert_contains('WS-BAD-001-&lt;b&gt;.md', $live['body']);
    assert_true(!str_contains($live['body'], '<b>.md'));
    $static = mk_site('static', $extra)->route('');
    assert_true(!str_contains($static['body'], 'WS-BAD-001'));
});

test('url: base 붙이기', function () {
    assert_same('/white-security/issues/x', mk_site()->url('issues/x'));
    assert_same('/white-security/', mk_site('static')->url(''));
});

test('HTML 은 이스케이프되고 인라인 script/style 없음', function () {
    $b = mk_site()->route('issues/WS-INJ-001')['body'];
    assert_true(!preg_match('/<script(?![^>]*\bsrc=)/i', $b));
    assert_true(!preg_match('/\sstyle=/i', $b));
});

test('404 본문에 요청 경로가 나오지 않음', function () {
    $s = mk_site();
    foreach (['zz-unique-probe', 'issues/WS-ZZZ-999', 'issues/<script>zz-probe2', 'api/zz-probe3.json'] as $p) {
        $r = $s->route($p);
        assert_same(404, $r['status'], $p);
        foreach (['zz-unique-probe', 'WS-ZZZ-999', 'zz-probe2', 'zz-probe3'] as $needle) assert_true(!str_contains($r['body'], $needle), "$p: $needle");
    }
});

test('pathFromRequest: base 뒤 경로만, 쿼리 제거, 한 번만 디코딩', function () {
    $b = '/white-security/';
    assert_same('issues/WS-INJ-001', Site::pathFromRequest('/white-security/issues/WS-INJ-001?p=about', $b));
    assert_same('', Site::pathFromRequest('/white-security/', $b));
    assert_same('', Site::pathFromRequest('/white-security', $b));
    assert_same('', Site::pathFromRequest('/white-security/?p=about', $b));
    assert_same('api/search.json', Site::pathFromRequest('/white-security/api/search.json', $b));
    assert_same('issues/WS-INJ-001', Site::pathFromRequest('/white-security/issues%2FWS-INJ-001', $b));
    assert_same('issues/%2e%2e', Site::pathFromRequest('/white-security/issues/%252e%252e', $b), '두 번 디코딩 금지');
    assert_same(null, Site::pathFromRequest('/other/issues/x', $b));
    assert_same(null, Site::pathFromRequest('/white-securityX/about', $b));
    assert_same(null, Site::pathFromRequest('', $b));
});
