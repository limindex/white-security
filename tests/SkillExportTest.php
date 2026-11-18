<?php
require_once __DIR__ . '/support.php';

const SE_ROOT = __DIR__ . '/..';

/** 임시 루트에서 references 를 만들어 [폴더, 루트] 를 돌려준다. */
function se_write(array $extra = []): array
{
    $root = make_root($extra);
    $ref = $root . '/' . SkillExport::SKILL_DIR . '/references';
    SkillExport::write(Repository::load($root), $ref);
    return [$ref, $root];
}

test('SkillExport: 스킬 폴더는 plugins/white-security/skills/white-security', function () {
    assert_same('plugins/white-security/skills/white-security', SkillExport::SKILL_DIR);
});

test('SkillExport: 이슈 파일 — 중립 머리 주석·기준 이름은 frameworks 의 short/name·보고 형식 줄 없음·영문 병기 표지', function () {
    [$ref] = se_write();
    $f = file_get_contents("$ref/web/WS-INJ-001.md");
    assert_true(str_starts_with($f, "<!-- generated from white-security issues; do not edit -->\n"), '머리 주석');
    assert_true(!str_contains($f, '고치세요') && !str_contains($f, 'issues/ 원본'), '지시처럼 읽히는 머리 없음');
    assert_contains("# WS-INJ-001 SQL 삽입\n", $f);
    assert_contains("심각도: 위험\n", $f);
    // 판본 연도가 붙은 이름(owasp2025 은 short 가 없어 name), 접두어 중복 없음, 번호 뒤 항목 이름
    assert_contains('OWASP Top 10:2025 A05(삽입)', $f);
    assert_contains('ASVS 5.0 V1.2', $f);
    assert_contains('· CWE-89(SQL 삽입)', $f);
    assert_true(!str_contains($f, 'CWE Top 25 CWE-89'), 'CWE 접두어 중복 없음');
    assert_contains('국내 보안약점 49 입력데이터 검증 및 표현-1(SQL 삽입)', $f);
    foreach (['## 점검 항목', '## 탐지 규칙'] as $s) assert_contains($s, $f);
    foreach (['## 발생 상황', '## 요약', '## 위험 배경', '## 대응 방법', '## 코드 예시', '보고 형식', '[위험|주의|참고] <id>'] as $s) assert_true(!str_contains($f, $s), $s);
    assert_contains('- **보고 예**: `[위험] WS-INJ-001', $f);
    assert_contains('- **고칠 방법**: 값만 자리표시자로 바꿔야 한다.', $f);
    // 코드 블록 안의 ✗ 로 시작하는 줄은 건드리지 않는다
    [$ref2] = se_write(['WS-INJ-002-x.md' => str_replace(['WS-INJ-001', '## 주석처럼 보이는 줄'], ['WS-INJ-002', '✗ 코드 속 줄'], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'))]);
    $py = file_get_contents("$ref2/web/WS-INJ-002.code.python.md");
    assert_contains("✗ 코드 속 줄\n", $py);
    assert_true(!str_contains($py, '✗ 코드 속 줄 (vulnerable'), '코드 블록 안 표지 불변');
});

test('SkillExport: 전체 INDEX 없음, 묶음별 INDEX = 이름·when·코드 신호 → 이슈 id (요약 문장 없음)', function () {
    [$ref] = se_write();
    assert_true(!is_file("$ref/INDEX.md"), '전체 INDEX 없음');
    $idx = file_get_contents("$ref/web/INDEX.md");
    assert_true(str_starts_with($idx, "<!-- generated from white-security issues; do not edit -->\n"), '머리');
    assert_contains("# web · 웹 애플리케이션\n", $idx);
    assert_contains("When: 입력을 받아 DB·화면·파일·명령으로 넘기는 웹 기능\n", $idx);
    assert_contains('- `cursor.execute(` `db.query(` `->query(` → WS-INJ-001 SQL 삽입', $idx);
    assert_true(!str_contains($idx, '사용자 입력이 SQL 문장에'), '요약 문장 없음');
    // 이슈 파일은 같은 폴더에 있다
    preg_match_all('/→ (WS-[A-Z]+-\d{3})/', $idx, $m);
    foreach ($m[1] as $id) assert_true(is_file("$ref/web/$id.md"), $id);
    assert_true(!is_dir("$ref/people"), 'site_only 묶음 없음');
    assert_true(!is_dir("$ref/auth"), '이슈 없는 묶음은 INDEX 도 없음');
});

test('SkillExport: 묶음에 route 가 없으면 render 실패(묶음 이름과 이유)', function () {
    $root = make_root();
    file_put_contents($root . '/data/groups.json', '[{"id":"web","name":"웹 기본","order":5}]');
    try { SkillExport::render(Repository::load($root)); }
    catch (SkillExportError $e) { assert_contains('web', $e->getMessage()); assert_contains('route', $e->getMessage()); return; }
    throw new Exception('SkillExportError 가 나야 함');
});

test('SkillExport: 라우팅 표 — 묶음당 한 줄(route → INDEX 경로), SKILL.md 표시 사이만 바꾼다', function () {
    $repo = Repository::load(make_root());
    $block = SkillExport::routing($repo);
    assert_true(str_starts_with($block, SkillExport::ROUTING_START . "\n"), '시작 표시');
    assert_true(str_ends_with($block, SkillExport::ROUTING_END), '끝 표시');
    assert_contains('| `references/web/INDEX.md` |', $block);
    assert_true(!str_contains($block, 'references/people/') && !str_contains($block, 'references/auth/'), '이슈 있는 묶음만');
    $md = "---\nname: x\n---\n# 위\n\n" . SkillExport::ROUTING_START . "\n옛 표\n" . SkillExport::ROUTING_END . "\n\n# 아래\n";
    $out = SkillExport::injectRouting($md, $block);
    assert_same("---\nname: x\n---\n# 위\n\n" . $block . "\n\n# 아래\n", $out);
    assert_same($out, SkillExport::injectRouting($out, $block), '두 번 넣어도 같음');
    foreach (["# 표시 없음\n", SkillExport::ROUTING_END . "\n" . SkillExport::ROUTING_START . "\n", SkillExport::ROUTING_START . SkillExport::ROUTING_START . SkillExport::ROUTING_END] as $bad) {
        try { SkillExport::injectRouting($bad, $block); throw new Exception('거절해야 함'); }
        catch (SkillExportError $e) { assert_contains('SKILL.md', $e->getMessage()); }
    }
});

test('build: SKILL.md 가 있으면 라우팅 표를 끼워 넣고, 표시가 없으면 빌드 실패', function () {
    $root = make_root();
    $dir = $root . '/' . SkillExport::SKILL_DIR;
    mkdir($dir, 0777, true);
    file_put_contents("$dir/SKILL.md", "# s\n" . SkillExport::ROUTING_START . "\n" . SkillExport::ROUTING_END . "\n");
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l, $c);
    assert_same(0, $c, implode("\n", $l));
    assert_contains('references/web/INDEX.md', file_get_contents("$dir/SKILL.md"));
    file_put_contents("$dir/SKILL.md", "# 표시 없음\n");
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l2, $c2);
    assert_same(1, $c2);
    assert_contains('SKILL.md', implode("\n", $l2));
});

test('저장소: 생성물이 원본과 일치(php build.php 후 커밋), 옛 skills/ 폴더 없음', function () {
    assert_true(!is_dir(SE_ROOT . '/skills'), '옛 skills/ 폴더');
    $ref = SE_ROOT . '/' . SkillExport::SKILL_DIR . '/references';
    $want = SkillExport::render(Repository::load(SE_ROOT));
    $have = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ref, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $have[substr($f->getPathname(), strlen($ref) + 1)] = file_get_contents($f->getPathname());
    ksort($have); ksort($want);
    assert_same(array_keys($want), array_keys($have), '파일 목록 (php build.php 실행 필요)');
    foreach ($want as $rel => $text) assert_true($have[$rel] === $text, "$rel 이 원본과 다름 (php build.php 실행 필요)");
    assert_true(!isset($have['INDEX.md']), '전체 INDEX 없음');
});

test('저장소: 스킬 LICENSE.txt — 본문 CC BY-SA 4.0 · 코드 블록 MIT · 저작권자 · 원본 저장소 · 각색 내용은 CC BY-SA', function () {
    $f = SE_ROOT . '/' . SkillExport::SKILL_DIR . '/LICENSE.txt';
    assert_true(is_file($f), 'LICENSE.txt');
    $t = file_get_contents($f);
    $site = json_decode(file_get_contents(SE_ROOT . '/data/site.json'), true);
    foreach (['CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 'MIT License', 'Lim Ho Jae (LimIndex)', $site['github'], 'OWASP',
        'Permission is hereby granted, free of charge'] as $s) assert_contains($s, $t);
});

test('토큰 추정: 한글 음절은 1개씩, 나머지는 바이트/4 (보수적, 올림)', function () {
    assert_same(0, SkillExport::estimateTokens(''));
    assert_same(1, SkillExport::estimateTokens('abcd'));
    assert_same(2, SkillExport::estimateTokens('abcde'));
    assert_same(3, SkillExport::estimateTokens('한국어'));
    assert_same(4, SkillExport::estimateTokens('한국어 ab'));   // 3 + ceil(3/4)
    assert_same(2, SkillExport::estimateTokens('·—'));          // 한글 아닌 비ASCII 도 바이트/4: 5바이트 → 2
});

test('SkillExport: 코드 예시는 언어별 파일로 분리, 이슈 파일에는 목록 한 줄만', function () {
    [$ref] = se_write();
    $f = file_get_contents("$ref/web/WS-INJ-001.md");
    assert_true(!str_contains($f, '## 코드 예시') && !str_contains($f, '```'), '이슈 파일에 코드 없음');
    assert_contains('`WS-INJ-001.code.python.md`', $f);
    assert_contains('`WS-INJ-001.code.php.md`', $f);
    $py = file_get_contents("$ref/web/WS-INJ-001.code.python.md");
    assert_true(str_starts_with($py, SkillExport::HEADER), '머리');
    assert_contains("# WS-INJ-001 코드 예시 — python\n", $py);
    assert_contains("✗ 취약 코드 (vulnerable example — do not use)\n", $py);
    assert_contains("✓ 안전 코드 (safe example)\n", $py);
    assert_contains('cur.execute("SELECT * FROM u WHERE id=%s", (uid,))', $py);
    assert_true(!str_contains($py, '$db->query'), 'php 코드 없음');
    assert_contains('$st = $db->prepare', file_get_contents("$ref/web/WS-INJ-001.code.php.md"));
});

test('SkillExport: INDEX 는 이슈당 신호를 찾는 곳 순서대로 앞 8개만(작성자가 핵심 신호를 앞에), 600토큰 넘으면 실패', function () {
    $sigs = ['abcd', 'x.y', 'longer_name_1', 'aaaa', 'bbbbbbbb', 'c.c(', 'dddd', 'eeee', 'ffff', 'g-g', 'hhhhhhhhhhhh'];
    $where = '- **찾는 곳**: ' . implode(', ', array_map(fn($s) => "`$s`", $sigs));
    [$ref] = se_write(['WS-INJ-005-x.md' => str_replace(['WS-INJ-001', '- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)'], ['WS-INJ-005', $where], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'))]);
    $idx = file_get_contents("$ref/web/INDEX.md");
    preg_match('/^- (.*) → WS-INJ-005/m', $idx, $m);
    preg_match_all('/`([^`]+)`/', $m[1], $got);
    assert_same(array_slice($sigs, 0, 8), $got[1], '원래 순서 앞 8개');
    assert_same(array_slice($sigs, 0, 8), SkillExport::topSignals($sigs));
    assert_same(['a.b', 'c.d'], SkillExport::topSignals(['a.b', 'c.d']));
    // 상한 초과
    $root = make_root();
    file_put_contents($root . '/data/groups.json', json_encode([['id' => 'web', 'name' => '웹', 'order' => 5, 'route' => 'sql', 'when' => str_repeat('아주 긴 설명 ', 300)]], JSON_UNESCAPED_UNICODE));
    try { SkillExport::render(Repository::load($root)); throw new Exception('실패해야 함'); }
    catch (SkillExportError $e) { assert_contains('web/INDEX.md', $e->getMessage()); assert_contains('600', $e->getMessage()); }
});

test('SkillExport: INDEX 제목 — 한국어 풀이만 떼고, 약어만 남으면 title_en 필수', function () {
    $fx = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $acr = str_replace(['WS-INJ-001', 'title: SQL 삽입'], ['WS-INJ-006', 'title: SQLi (SQL 삽입)'], $fx);
    try { SkillExport::render(Repository::load(make_root(['WS-INJ-006-x.md' => $acr]))); throw new Exception('실패해야 함'); }
    catch (SkillExportError $e) { assert_contains('title_en', $e->getMessage()); assert_contains('WS-INJ-006', $e->getMessage()); }
    [$ref] = se_write(['WS-INJ-006-x.md' => str_replace('title: SQLi (SQL 삽입)', "title: SQLi (SQL 삽입)\ntitle_en: SQLi (SQL Injection)", $acr)]);
    assert_contains('→ WS-INJ-006 SQLi (SQL Injection)', file_get_contents("$ref/web/INDEX.md"));
});
