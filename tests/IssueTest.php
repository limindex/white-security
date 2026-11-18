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
    assert_same(['owasp2025' => [], 'api2023' => [], 'llm2026' => [], 'agentic2026' => [], 'owasp_mobile2024' => [], 'asvs5' => [], 'cwe' => [], 'kr_guide' => [], 'kr_ciip' => []], $i->maps);
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

test('문서 메타: published·revisions·applies_to·severity_reason 를 읽는다', function () {
    $i = Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups());
    assert_same('2026-09-01', $i->published->format('Y-m-d'));
    assert_same([['date' => '2026-11-18', 'note' => '본문 고정 제목 변경'], ['date' => '2026-09-01', 'note' => '최초 작성']], $i->revisions);
    assert_same(['Python', 'PHP', '모든 웹 애플리케이션'], $i->appliesTo);
    assert_contains('입력 한 줄로 악용 가능', $i->severityReason);
});

test('문서 메타: 없으면 published=checked · 빈 목록 · null', function () {
    $raw = preg_replace('/^(published|severity_reason|applies_to): .*\n/m', '', ws_fixture());
    $raw = preg_replace('/^revisions:\n(?:  .*\n)+/m', '', $raw);
    $f = sys_get_temp_dir() . '/ws-meta-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($f, $raw);
    $i = Issue::fromFile($f, ws_groups());
    unlink($f);
    assert_same('2026-11-18', $i->published->format('Y-m-d'));
    assert_same([], $i->revisions);
    assert_same([], $i->appliesTo);
    assert_same(null, $i->severityReason);
});

test('문서 메타: 형식 오류', function () {
    assert_contains('published', ws_issue_error(ws_mut('published: 2026-09-01', 'published: 2026-09-31')));
    assert_contains('published', ws_issue_error(ws_mut('published: 2026-09-01', 'published: 2026-12-01')));
    assert_contains('revisions', ws_issue_error(ws_mut("  - date: 2026-11-18\n    note: 본문 고정 제목 변경\n", "  - date: 2026-11-18\n")));
    assert_contains('revisions', ws_issue_error(ws_mut('  - date: 2026-09-01', '  - date: 2026-9-1')));
    assert_contains('revisions', ws_issue_error(ws_mut('  - date: 2026-11-18', '  - date: 2026-08-01')));   // 최신이 위가 아님
    assert_contains('revisions', ws_issue_error(ws_mut('  - date: 2026-09-01', '  - date: 2026-08-01')));   // 게시일보다 이름
    assert_contains('revisions', ws_issue_error(ws_mut("revisions:\n", "revisions: 최초\nx_unused:\n")));
    assert_contains('applies_to', ws_issue_error(ws_mut('applies_to: [Python, PHP, 모든 웹 애플리케이션]', 'applies_to: Python')));
    assert_contains('applies_to', ws_issue_error(ws_mut('applies_to: [Python, PHP, 모든 웹 애플리케이션]', 'applies_to: [Python, Python]')));
    assert_contains('severity_reason', ws_issue_error(ws_mut('severity_reason: 데이터베이스', 'severity_reason: "줄\\n바꿈" #')));
    assert_contains('severity_reason', ws_issue_error(ws_mut('severity_reason: 데이터베이스 전체 유출·변조로 이어지는 영향, 입력 한 줄로 악용 가능', 'severity_reason: 3')));
});

// ── 탐지 규칙 칸 구조 (모든 이슈 같은 형식, 스킬 INDEX 의 코드 신호 원천) ──
test('탐지 규칙: 정상 형식을 칸별로 읽고 찾는 곳의 백틱 코드를 신호로 뽑는다', function () {
    $i = Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups());
    $d = $i->detection;
    assert_same(['cursor.execute(', 'db.query(', '->query('], $d['signals']);
    assert_contains('쿼리 조립 지점', $d['where']);
    assert_same(2, count($d['checks']));
    assert_same('값 이어 붙이기', $d['checks'][0]['name']);
    assert_same('요청 값을 쿼리 문자열에 이어 붙이는 경우.', $d['checks'][0]['flag']);
    assert_same('매개변수 바인딩을 쓰는 경우.', $d['checks'][0]['skip']);
    assert_same(['위험', '주의', '참고'], array_keys($d['grade']));
    assert_same('DB 오류 원문 노출.', $d['grade']['참고']);
    assert_contains('WS-INJ-001 app/search.py:18', $d['fields']['보고 예']);
    assert_same('값만 자리표시자로 바꿔야 한다.', $d['fields']['고칠 방법']);
});

test('탐지 규칙: 백틱 두 개짜리 코드와 중복 신호', function () {
    $raw = ws_mut('- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)', '- **찾는 곳**: `` exec(`npm i ${n}`) `` 와 `cursor.execute(`, 다시 `cursor.execute(`');
    $f = sys_get_temp_dir() . '/ws-det-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($f, $raw);
    $i = Issue::fromFile($f, ws_groups());
    unlink($f);
    assert_same(['exec(`npm i ${n}`)', 'cursor.execute('], $i->detection['signals']);
});

test('탐지 규칙: 형식 위반은 파일명과 이유로 거절', function () {
    $where = "- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)\n";
    $c1 = "- **검사 1 — 값 이어 붙이기**: 지적할 때: 요청 값을 쿼리 문자열에 이어 붙이는 경우. / 지적하지 않을 때: 매개변수 바인딩을 쓰는 경우.\n";
    $grade = "- **등급**: 위험: 외부 값이 쿼리에 그대로 들어가는 경우. / 주의: 값의 출처를 끝까지 따라가지 못한 경우. / 참고: DB 오류 원문 노출.\n";
    $cases = [
        '찾는 곳' => ws_mut($where, ''),                                                      // 찾는 곳 없음
        '코드 신호' => ws_mut($where, "- **찾는 곳**: 쿼리 조립 지점\n"),                        // 백틱 신호 없음
        '검사' => preg_replace('/^- \*\*검사 \d.*\n/m', '', ws_fixture()),                    // 검사 0개
        '등급' => ws_mut($grade, ''),                                                         // 등급 없음
        '지적하지 않을 때' => ws_mut(' / 지적하지 않을 때: 매개변수 바인딩을 쓰는 경우.', ''),       // 짝 누락
        '번호' => ws_mut('**검사 2 — 식별자 자리**', '**검사 3 — 식별자 자리**'),                // 번호 건너뜀
        '순서' => ws_mut($c1 . '- **검사 2', $c1 . $grade . '- **검사 2'),                      // 등급이 검사보다 앞(그리고 두 번)
        '형식' => ws_mut("- **고칠 방법**: 값만 자리표시자로 바꿔야 한다.\n", "- **고칠 방법**: 값만 바꿔야 한다.\n- 소속 없는 불릿\n"),
        '모르는 칸' => ws_mut('- **고칠 방법**:', '- **고치는 법**:'),
        '두 번' => ws_mut("- **고칠 방법**: 값만 자리표시자로 바꿔야 한다.\n", "- **고칠 방법**: a\n- **고칠 방법**: b\n"),
        '위험·주의·참고' => ws_mut('위험: 외부 값이 쿼리에 그대로 들어가는 경우. / 주의:', '주의: 외부 값. / 위험:'),
    ];
    foreach ($cases as $why => $raw) {
        $m = ws_issue_error($raw);
        assert_contains('-bad.md', $m);
        assert_contains('탐지 규칙', $m);
        assert_contains($why, $m);
    }
});

test('탐지 규칙: site_only 가 아니면 brief 라도 필수, site_only 는 형식 검사 안 함', function () {
    $brief = "---\nid: WS-INJ-009\ntitle: t\nslug: t\ngroup: web\nseverity: low\nsite_only: false\ndepth: brief\nlanguages: [python]\nchecked: 2026-11-18\nsources:\n  - title: s\n    url: https://example.org/a\n    year: 2025\n---\n\n## 요약\n\n요약\n\n## 점검 항목\n\n- 확인\n\n## 코드 예시\n\n### python\n\n✗ 취약 코드\n\n```python\nbad()\n```\n\n✓ 안전 코드\n\n```python\ngood()\n```\n";
    assert_contains('탐지 규칙', ws_issue_error($brief));
    // site_only full: 자유 형식 탐지 규칙 그대로 통과 (위 site_only 테스트가 "d" 한 줄로 확인), detection 은 null
    $raw = "---\nid: WS-PPL-004\ntitle: t\nslug: t\ngroup: people\nseverity: low\nsite_only: true\ndepth: full\nlanguages: []\nchecked: 2026-11-18\nsources:\n  - title: s\n    url: https://example.org/a\n    year: 2025\n---\n\n## 요약\n\n요약\n\n## 발생 상황\n\na\n\n## 위험 배경\n\nb\n\n## 대응 방법\n\nc\n\n## 탐지 규칙\n\n자유 형식\n\n## 점검 항목\n\n- 확인\n";
    $f = sys_get_temp_dir() . '/ws-so-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($f, $raw);
    $i = Issue::fromFile($f, ws_groups());
    unlink($f);
    assert_same(null, $i->detection);
});

test('탐지 규칙 신호: 너무 짧거나 흔한 낱말은 파일:줄과 함께 거절, 금지 목록은 한 곳', function () {
    assert_true(in_array('system', Issue::SIGNAL_STOPLIST, true) && in_array('update(', Issue::SIGNAL_STOPLIST, true), '금지 목록 상수');
    foreach (['`system`' => 'system', '`gs`' => 'gs', '`update(`' => 'update(', '`innerHTML`' => 'innerHTML', '`use`' => 'use'] as $code => $sig) {
        $m = ws_issue_error(ws_mut('`db.query(`', "`db.query(`, $code"));
        assert_true((bool)preg_match('/-bad\.md:\d+: 탐지 규칙/u', $m), "파일:줄 — $m");
        assert_contains("`$sig`", $m);
    }
    // 4자 이상이거나 ( . : / = - _ 중 하나가 있으면 통과
    foreach (['`zipfile`', '`a.b`', '`x=`', '`gs -`'] as $ok) Issue::fromFile(ws_tmp(ws_mut('`db.query(`', "`db.query(`, $ok")), ws_groups());
    assert_true(Issue::signalProblem('gs') !== null && Issue::signalProblem('gs -') === null && Issue::signalProblem('Wand(') === null);
    // 금지 낱말의 일부·2자 이하 호출 조각은 거절, 더 긴 패턴은 통과
    assert_true(Issue::signalProblem('dd(') !== null && Issue::signalProblem('ad(') !== null && Issue::signalProblem('earch(') !== null);
    assert_true(Issue::signalProblem('var_dump(') === null && Issue::signalProblem('{!! $') === null && Issue::signalProblem('findOne(req.body') === null);
});

test('title_en: 선택, 한 줄 문자열', function () {
    $i = Issue::fromFile(ws_tmp(ws_mut("title: SQL 삽입\n", "title: SQL 삽입\ntitle_en: SQL Injection\n")), ws_groups());
    assert_same('SQL Injection', $i->titleEn);
    assert_same(null, Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups())->titleEn);
    assert_contains('title_en', ws_issue_error(ws_mut("title: SQL 삽입\n", "title: SQL 삽입\ntitle_en: 3\n")));
});

test('grade_hint: 선택, 한 줄 문자열, 40자 이하, site_only 이슈엔 금지', function () {
    $i = Issue::fromFile(ws_tmp(ws_mut("severity: high\n", "severity: high\ngrade_hint: \"검색어가 SQL 문자열에\"\n")), ws_groups());
    assert_same('검색어가 SQL 문자열에', $i->gradeHint);
    assert_same(null, Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups())->gradeHint);
    assert_contains('grade_hint', ws_issue_error(ws_mut("severity: high\n", "severity: high\ngrade_hint: 3\n")));
    $long = ws_issue_error(ws_mut("severity: high\n", "severity: high\ngrade_hint: \"" . str_repeat('가', Issue::GRADE_HINT_MAX + 1) . "\"\n"));
    assert_contains('grade_hint', $long);
    assert_contains((string)Issue::GRADE_HINT_MAX, $long);
});

test('always_on: 선택, high 만, "`찾는 곳 신호` → 행동." 형식, 120자 이하', function () {
    $ok = "always_on: \"`cursor.execute(` → 값은 자리표시자로 넘겨야 한다.\"\n";
    $i = Issue::fromFile(ws_tmp(ws_mut("severity: high\n", "severity: high\n" . $ok)), ws_groups());
    assert_same('`cursor.execute(` → 값은 자리표시자로 넘겨야 한다.', $i->alwaysOn);
    assert_same(null, Issue::fromFile(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md', ws_groups())->alwaysOn);
    $bad = [
        'severity: medium' => ["severity: medium\n" . $ok, 'high'],
        '찾는 곳에 없는 신호' => ["severity: high\nalways_on: \"`jwt.decode(` → 서명을 확인해야 한다.\"\n", '찾는 곳'],
        '화살표 없음' => ["severity: high\nalways_on: \"`cursor.execute(` 값은 자리표시자로.\"\n", '→'],
        '신호 없이 글만' => ["severity: high\nalways_on: \"쿼리 → 값은 자리표시자로 넘겨야 한다.\"\n", '신호'],
        '마침표 없음' => ["severity: high\nalways_on: \"`cursor.execute(` → 값은 자리표시자로\"\n", '마침표'],
        '너무 김' => ["severity: high\nalways_on: \"`cursor.execute(` → " . str_repeat('가', Issue::ALWAYS_ON_MAX) . ".\"\n", (string)Issue::ALWAYS_ON_MAX],
    ];
    foreach ($bad as $why => [$repl, $want]) {
        $e = ws_issue_error(ws_mut("severity: high\n", $repl));
        assert_true(str_contains($e, 'always_on') && str_contains($e, $want), "$why: $e");
    }
});

/** 원본 문자열을 임시 파일로 쓰고 경로를 돌려준다. */
function ws_tmp(string $raw): string {
    $f = sys_get_temp_dir() . '/ws-ok-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($f, $raw);
    return $f;
}
