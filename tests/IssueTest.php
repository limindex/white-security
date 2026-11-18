<?php
require_once __DIR__ . '/../src/bootstrap.php';

function ws_groups(): array { return json_decode(file_get_contents(__DIR__ . '/../data/groups.json'), true); }
function ws_fixture(): string { return file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'); }
/** 고친 원본을 임시 파일로 써서 Issue 로 읽고 IssueError 메시지를 돌려준다. */
function ws_issue_error(string $raw): string {
    $f = sys_get_temp_dir() . '/ws-' . bin2hex(random_bytes(4)) . '-bad.md';
    file_put_contents($f, $raw);
    try { Issue::fromFile($f, ws_groups()); }
    catch (IssueError $e) { unlink($f); return $e->getMessage(); }
    unlink($f);
    throw new Exception('IssueError 가 나야 함');
}

test('정상 fixture 를 읽는다', function () {
    $i = Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups());
    assert_same('WS-INJ-001', $i->id);
    assert_same('web', $i->group);
    assert_same('high', $i->severity);
    assert_same(false, $i->siteOnly);
    assert_same('2026-11-18', $i->checked->format('Y-m-d'));
    assert_same(['python', 'php'], $i->languages);
    assert_true($i->code['php']['bad'] !== '' && $i->code['php']['good'] !== '');
    assert_contains('prepare', $i->code['php']['good']);
    assert_contains('## 주석처럼', $i->code['python']['bad']);
    assert_same([], $i->maps['llm2026']);
    assert_same(['CWE-89'], $i->maps['cwe']);
    assert_contains('SQL', $i->sections['요약']);
    assert_same(2025, $i->sources[0]['year']);
});

function ws_mut(string $from, string $to): string { return str_replace($from, $to, ws_fixture()); }

test('id 누락', function () {
    assert_contains('-bad.md', ws_issue_error(ws_mut("id: WS-INJ-001\n", '')));
});
test('id 형식 위반', function () {
    assert_contains('id', ws_issue_error(ws_mut('WS-INJ-001', 'WS-inj-1')));
});
test('모르는 group', function () {
    assert_contains('group', ws_issue_error(ws_mut('group: web', 'group: nope')));
});
test('severity critical', function () {
    assert_contains('severity', ws_issue_error(ws_mut('severity: high', 'severity: critical')));
});
test('checked 달력에 없는 날짜', function () {
    assert_contains('checked', ws_issue_error(ws_mut('2026-11-18', '2026-13-01')));
    assert_contains('checked', ws_issue_error(ws_mut('2026-11-18', '2026-02-30')));
});
test('full 인데 필수 제목 없음', function () {
    $m = ws_issue_error(ws_mut('## 위험 배경', '## 다른 제목'));
    assert_contains('-bad.md', $m);
    assert_contains('위험 배경', $m);
});
test('depth 값 위반', function () {
    assert_contains('depth', ws_issue_error(ws_mut('depth: full', 'depth: deep')));
});
test('maps 모르는 키', function () {
    assert_contains('maps', ws_issue_error(ws_mut("  cwe: [CWE-89]\n", "  cwe: [CWE-89]\n  nist: [X]\n")));
});
test('sources url 이 https 아님 / year 가 정수 아님', function () {
    assert_contains('url', ws_issue_error(ws_mut('url: https://owasp.org/Top10/', 'url: http://owasp.org/Top10/')));
    assert_contains('year', ws_issue_error(ws_mut('year: 2025', 'year: abc')));
});
test('languages 가 코드 소제목과 다르면 오류', function () {
    assert_contains('languages', ws_issue_error(ws_mut('languages: [python, php]', 'languages: [python, js]')));
});
test('✗/✓ 코드 블록 짝이 없으면 오류', function () {
    assert_contains('php', ws_issue_error(ws_mut("✓ 안전 코드\n\n```php", "안전 코드\n\n```php")));
});
test('brief 는 세 제목만 필수', function () {
    $raw = "---\nid: WS-PPL-001\ntitle: 메신저 사칭\nslug: impersonation\ngroup: people\nseverity: low\nsite_only: true\ndepth: brief\nlanguages: []\nchecked: 2026-11-18\nsources:\n  - title: 참고\n    url: https://example.org/a\n    year: 2025\n---\n\n## 요약\n\n확인한다.\n\n## 점검 항목\n\n- 확인\n\n## 코드 예시\n\n해당 없음\n";
    $f = sys_get_temp_dir() . '/ws-brief-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($f, $raw);
    $i = Issue::fromFile($f, ws_groups());
    unlink($f);
    assert_same(true, $i->siteOnly);
    assert_same([], $i->code);
    assert_same('brief', $i->depth);
    assert_same(['owasp2025' => [], 'api2023' => [], 'llm2026' => [], 'agentic2026' => [], 'asvs5' => [], 'cwe' => [], 'kr_guide' => [], 'kr_ciip' => []], $i->maps);
});

test('sources 가 비어 있으면 오류', function () {
    assert_contains('sources', ws_issue_error(preg_replace('/^sources:\n(?:  .*\n)+/m', "sources: []\n", ws_fixture())));
    assert_contains('-bad.md', ws_issue_error(preg_replace('/^sources:\n(?:  .*\n)+/m', "sources: []\n", ws_fixture())));
});
test('site_only 아닌데 languages 가 비면 오류', function () {
    $m = ws_issue_error("---\nid: WS-PPL-002\ntitle: t\nslug: t\ngroup: web\nseverity: low\nsite_only: false\ndepth: brief\nlanguages: []\nchecked: 2026-11-18\nsources:\n  - title: s\n    url: https://example.org/a\n    year: 2025\n---\n\n## 요약\n\n요약\n\n## 점검 항목\n\n- 확인\n\n## 코드 예시\n\n없음\n");
    assert_contains('languages', $m);
});
test('site_only 는 ## 코드 예시 없이도 통과 (full·brief 모두)', function () {
    foreach (['brief', 'full'] as $depth) {
        $extra = $depth === 'full' ? "## 발생 상황\n\na\n\n## 위험 배경\n\nb\n\n## 대응 방법\n\nc\n\n## 탐지 규칙\n\nd\n\n" : '';
        $raw = "---\nid: WS-PPL-003\ntitle: t\nslug: t\ngroup: people\nseverity: low\nsite_only: true\ndepth: $depth\nlanguages: []\nchecked: 2026-11-18\nsources:\n  - title: s\n    url: https://example.org/a\n    year: 2025\n---\n\n## 요약\n\n요약\n\n$extra## 점검 항목\n\n- 확인\n";
        $f = sys_get_temp_dir() . '/ws-so-' . bin2hex(random_bytes(4)) . '.md';
        file_put_contents($f, $raw);
        $i = Issue::fromFile($f, ws_groups());
        unlink($f);
        assert_same([], $i->code);
        assert_same([], $i->languages);
    }
});

test('고정 제목: 새 이름(요약·발생 상황·…·코드 예시)만 인정, 옛 이름은 필수 제목 누락', function () {
    assert_same(['요약', '발생 상황', '위험 배경', '대응 방법', '점검 항목', '탐지 규칙', '코드 예시'], Issue::FULL_SECTIONS);
    assert_same(['요약', '점검 항목', '코드 예시'], Issue::BRIEF_SECTIONS);
    $m = ws_issue_error(ws_mut('## 요약', '## 한 줄 요약'));
    assert_contains('## 요약', $m);
    // `## 코드` 는 더 이상 코드 절이 아님 → languages 와 소제목이 어긋나거나 필수 제목 누락
    assert_contains('코드 예시', ws_issue_error(ws_mut('## 코드 예시', '## 코드')));
});
