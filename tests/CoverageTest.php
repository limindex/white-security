<?php
require_once __DIR__ . '/support.php';

/** fixture 이슈를 복제해 id·slug·maps·site_only 를 바꾼 원본 문자열 */
function cov_issue(string $id, string $slug, string $owasp, bool $siteOnly = false): string
{
    $raw = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $raw = str_replace(['id: WS-INJ-001', 'slug: sql-injection', "  owasp2025: [A05]\n", 'site_only: false'],
        ["id: $id", "slug: $slug", "  owasp2025: [$owasp]\n", 'site_only: ' . ($siteOnly ? 'true' : 'false')], $raw);
    return $raw;
}

function cov_fw(): array
{
    $e = [];
    return Coverage::loadFrameworks(__DIR__ . '/..', $e);
}

function cov_repo(array $extra, array $fw): Repository
{
    return Repository::load(make_root($extra));
}

test('Coverage: A05 는 이슈 2개, A02 는 빈칸이고 gaps 에 포함', function () {
    $repo = cov_repo(['WS-INJ-002-second.md' => cov_issue('WS-INJ-002', 'second', 'A01, A05')], []);
    $built = Coverage::build($repo, cov_fw());
    $rows = array_column($built['owasp2025'], null, 'item');
    assert_same(['WS-INJ-001', 'WS-INJ-002'], $rows['A05']['issues']);
    assert_same(['WS-INJ-002'], $rows['A01']['issues']);
    assert_same([], $rows['A02']['issues']);
    $gaps = Coverage::gaps($built);
    assert_true(in_array(['fw' => 'owasp2025', 'item' => 'A02'], $gaps, true));
    assert_true(!in_array(['fw' => 'owasp2025', 'item' => 'A05'], $gaps, true));
});

test('Coverage: 없는 항목(A99)은 CoverageError 로 파일·키·항목을 모두 나열', function () {
    $repo = cov_repo(['WS-INJ-002-second.md' => cov_issue('WS-INJ-002', 'second', 'A99, A98')], []);
    try { Coverage::build($repo, cov_fw()); throw new Exception('오류가 나야 합니다'); }
    catch (CoverageError $e) {
        assert_contains('WS-INJ-002-second.md', $e->getMessage());
        assert_contains('owasp2025', $e->getMessage());
        assert_contains('A99', $e->getMessage());
        assert_contains('A98', $e->getMessage());
        assert_same(2, count($e->problems));
    }
});

test('Coverage: 사이트 전용은 매핑된 이슈가 전부 site_only 일 때만', function () {
    $fw = cov_fw();
    $repo = cov_repo([
        'WS-INJ-002-second.md' => cov_issue('WS-INJ-002', 'second', 'A01', true),
        'WS-INJ-003-third.md' => cov_issue('WS-INJ-003', 'third', 'A02', true),
        'WS-INJ-004-fourth.md' => cov_issue('WS-INJ-004', 'fourth', 'A02', false),
    ], $fw);
    $rows = array_column(Coverage::build($repo, $fw)['owasp2025'], null, 'item');
    assert_same(true, $rows['A01']['site_only']);
    assert_same(false, $rows['A02']['site_only']);
    assert_same(false, $rows['A05']['site_only']);
});

test('Coverage: 프레임워크 파일이 없는 maps 키는 오류', function () {
    $repo = cov_repo([], []);
    try { Coverage::build($repo, []); throw new Exception('오류가 나야 합니다'); }
    catch (CoverageError $e) { assert_contains('프레임워크', $e->getMessage()); }
});

test('Coverage: ASVS 장 단위 — V1.2 는 V1 로 합쳐 센다', function () {
    $fw = [['id' => 'asvs5', 'name' => 'ASVS', 'year' => 2025, 'url' => 'https://example.org/', 'rollup' => '.', 'items' => [['id' => 'V1', 'title' => 'Encoding', 'title_ko' => '인코딩']]]];
    $fw[] = ['id' => 'owasp2025', 'name' => 'x', 'year' => 2025, 'url' => 'https://example.org/', 'items' => [['id' => 'A05', 'title' => 'Injection', 'title_ko' => '삽입']]];
    $fw[] = ['id' => 'cwe', 'name' => 'x', 'year' => 2025, 'url' => 'https://example.org/', 'items' => [['id' => 'CWE-89', 'title' => 'SQLi', 'title_ko' => 'SQL 삽입']]];
    $fw[] = ['id' => 'kr_guide', 'name' => 'x', 'year' => 2021, 'url' => 'https://example.org/', 'items' => [['id' => '입력데이터 검증 및 표현-1', 'title' => 'SQL 삽입', 'title_ko' => 'SQL 삽입']]];
    $b = Coverage::build(Repository::load(make_root()), $fw);
    assert_same(['WS-INJ-001'], $b['asvs5'][0]['issues']);
});

test('Coverage: 프레임워크 파일 읽기 — 필수 필드 검사', function () {
    $root = make_root();
    $dir = $root . '/data/frameworks';
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/bad.json', json_encode(['id' => 'bad', 'name' => 'x', 'items' => []]));
    $errs = [];
    Coverage::loadFrameworks($root, $errs);
    assert_true(count($errs) >= 1);
    assert_contains('bad.json', implode("\n", $errs));
});

test('실제 data/frameworks: 9개 파일, url·year·항목 필수, 항목 id 유일', function () {
    $errs = [];
    $fws = Coverage::loadFrameworks(__DIR__ . '/..', $errs);
    assert_same([], $errs);
    assert_same(Issue::MAP_KEYS, array_column($fws, 'id'));
    $counts = ['owasp2025' => 10, 'api2023' => 10, 'llm2026' => 10, 'agentic2026' => 10, 'owasp_mobile2024' => 10, 'asvs5' => 17, 'cwe' => 25, 'kr_guide' => 49, 'kr_ciip' => 21];
    foreach ($fws as $fw) {
        assert_same($counts[$fw['id']], count($fw['items']), $fw['id']);
        assert_true(preg_match('#^https://#', $fw['url']) === 1, $fw['id'] . ' url');
        assert_same(count($fw['items']), count(array_unique(array_column($fw['items'], 'id'))), $fw['id'] . ' 중복');
        foreach ($fw['items'] as $it) assert_true($it['title'] !== '' && $it['title_ko'] !== '', $fw['id'] . ' ' . $it['id']);
    }
});

test('Coverage 화면: 표·집계·빈칸 배지, 이스케이프', function () {
    $repo = Repository::load(make_root());
    $site = new Site($repo, '/white-security/', 'live');
    $b = $site->route('coverage')['body'];
    assert_contains('<span class="ws-badge ws-badge--covered">대응</span>', $b);
    assert_contains('빈칸', $b);
    assert_contains('/white-security/issues/WS-INJ-001', $b);
    assert_contains('OWASP Top 10:2025', $b);
    assert_true(substr_count($b, '<table') >= 8, '프레임워크마다 표');
});

test('api/coverage.json: 형식', function () {
    $site = new Site(Repository::load(make_root()), '/white-security/', 'live');
    $a = json_decode($site->route('api/coverage.json')['body'], true);
    assert_same(['frameworks', 'gaps'], array_keys($a));
    $f = $a['frameworks'][0];
    foreach (['id', 'name', 'year', 'url', 'items'] as $k) assert_true(array_key_exists($k, $f), $k);
    foreach (['id', 'title', 'title_ko', 'issues', 'site_only'] as $k) assert_true(array_key_exists($k, $f['items'][0]), $k);
    assert_true(isset($a['gaps'][0]['fw'], $a['gaps'][0]['item']));
});

test('존재하지 않는 항목은 오류 띠에 나오고 화면은 살아 있다', function () {
    $root = make_root(['WS-INJ-002-second.md' => cov_issue('WS-INJ-002', 'second', 'A99')]);
    $r = (new Site(Repository::load($root), '/white-security/', 'live'))->route('coverage');
    assert_same(200, $r['status']);
    assert_contains('A99', $r['body']);
    assert_contains('원본 오류', $r['body']);
    assert_true(!str_contains($r['body'], $root));
});

test('mapLabel: 프레임워크 파일의 이름을 쓴다', function () {
    assert_same('OWASP Top 10:2025', Site::mapLabel('owasp2025'));
    assert_same('modelx', Site::mapLabel('modelx'));
});

/** fixture 를 복제해 maps.cwe 만 바꾼다 */
function cov_cwe_issue(string $id, string $slug, string $cwe): string
{
    $raw = cov_issue($id, $slug, 'A05');
    return str_replace("  cwe: [CWE-89]\n", "  cwe: [$cwe]\n", $raw);
}

test('CWE: Top 25 밖 번호(CWE-611)는 유효, JSON maps·칩 링크에 나오고 표·빈칸에는 없다', function () {
    $root = make_root(['WS-XML-009-xxe.md' => cov_cwe_issue('WS-XML-009', 'xxe', 'CWE-89, CWE-611')]);
    $repo = Repository::load($root);
    assert_same([], $repo->errors());
    assert_same(['CWE-89', 'CWE-611'], $repo->find('WS-XML-009')->maps['cwe']);
    $built = Coverage::build($repo, cov_fw());
    $items = array_column($built['cwe'], 'item');
    assert_true(!in_array('CWE-611', $items, true), 'CWE-611 은 표 행이 아님');
    assert_same(25, count($items));
    $rows = array_column($built['cwe'], null, 'item');
    assert_same(['WS-INJ-001', 'WS-XML-009'], $rows['CWE-89']['issues']);
    foreach (Coverage::gaps($built) as $g) assert_true($g['item'] !== 'CWE-611', '빈칸 목록에 CWE-611 없음');
    $site = new Site($repo, '/white-security/', 'live');
    $api = json_decode($site->route('api/issues/WS-XML-009.json')['body'], true);
    assert_same(['CWE-89', 'CWE-611'], $api['maps']['cwe']);
    $page = $site->route('issues/WS-XML-009')['body'];
    assert_contains('href="https://cwe.mitre.org/data/definitions/611.html"', $page);
    assert_contains('href="https://cwe.mitre.org/data/definitions/89.html"', $page);
});

test('CWE: 형식이 틀린 번호(CWE-abc)는 원본 오류', function () {
    $repo = Repository::load(make_root(['WS-XML-009-xxe.md' => cov_cwe_issue('WS-XML-009', 'xxe', 'CWE-abc')]));
    $errs = implode("\n", $repo->errors());
    assert_contains('WS-XML-009-xxe.md', $errs);
    assert_contains('CWE-abc', $errs);
    assert_same(null, $repo->find('WS-XML-009'));
});

test('CWE: cwe 가 아닌 키의 없는 항목은 여전히 오류', function () {
    $repo = cov_repo(['WS-INJ-002-second.md' => cov_issue('WS-INJ-002', 'second', 'A99')], []);
    try { Coverage::build($repo, cov_fw()); throw new Exception('오류가 나야 합니다'); }
    catch (CoverageError $e) { assert_contains('A99', $e->getMessage()); }
});

test('Site::cweUrl: CWE 번호만 링크', function () {
    assert_same('https://cwe.mitre.org/data/definitions/1427.html', Site::cweUrl('cwe', 'CWE-1427'));
    assert_same(null, Site::cweUrl('cwe', 'CWE-x'));
    assert_same(null, Site::cweUrl('owasp2025', 'A01'));
});

test('지원 스택 밖(data/out_of_scope.json): 빈칸과 따로 표시, gaps 에서 빠짐, 잘못된 항목·이슈 있는 항목은 오류', function () {
    $root = make_root();
    file_put_contents($root . '/data/out_of_scope.json', json_encode(['owasp2025' => ['A02' => 'C·C++ 메모리 약점', 'A05' => '이슈 있음', 'Z99' => '없는 항목'], 'nope' => ['X' => 'y']], JSON_UNESCAPED_UNICODE));
    $repo = Repository::load($root);
    $rows = array_column($repo->coverage()['owasp2025'], null, 'item');
    assert_same('C·C++ 메모리 약점', $rows['A02']['out_of_scope']);
    assert_true(!in_array(['fw' => 'owasp2025', 'item' => 'A02'], Coverage::gaps($repo->coverage()), true), 'gaps 에서 빠짐');
    $errs = implode("\n", $repo->errors());
    foreach (['모르는 기준 `nope`', '없는 항목 `Z99`', '`A05` 에 대응 이슈가 있어'] as $w) assert_contains($w, $errs);
    $b = nw_strip((new Site($repo, '/white-security/', 'static', new DateTimeImmutable('2026-11-18')))->route('coverage')['body']);
    assert_contains('<span class="ws-badge ws-badge--oos">지원 스택 밖</span><span class="ws-cov-en">C·C++ 메모리 약점</span>', $b);
    assert_contains('ws-meter-fill--oos', $b);
});

test('실제 data/out_of_scope.json: ASVS V17·CWE 메모리 6·kr_guide 입력데이터-16 이 지원 스택 밖', function () {
    $repo = Repository::load(dirname(__DIR__));
    assert_same([], array_values(array_filter($repo->errors(), fn($e) => str_contains($e, 'out_of_scope'))));
    $oos = [];
    foreach ($repo->coverage() as $fw => $rows) foreach ($rows as $r) if ($r['out_of_scope'] !== null) $oos[] = "$fw:{$r['item']}";
    sort($oos);
    assert_same(['asvs5:V17', 'cwe:CWE-120', 'cwe:CWE-121', 'cwe:CWE-122', 'cwe:CWE-125', 'cwe:CWE-416', 'cwe:CWE-787', 'kr_guide:입력데이터 검증 및 표현-16', 'kr_guide:코드오류-3'], $oos);
});
