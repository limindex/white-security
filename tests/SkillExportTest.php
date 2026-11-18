<?php
require_once __DIR__ . '/support.php';

const SE_ROOT = __DIR__ . '/..';

/** 임시 루트에서 references 를 만들어 [폴더, 루트] 를 돌려준다. */
function se_write(array $extra = []): array
{
    $root = make_root($extra);
    $ref = $root . '/' . SkillExport::REF_DIR;
    SkillExport::write(Repository::load($root), $ref);
    return [$ref, $root];
}

test('SkillExport: 스킬은 검토 스킬 …/skills/review 하나, 규칙 파일도 그 한 곳에만(상시 스킬 없음, 11-19)', function () {
    assert_same('plugins/white-security/skills/review', SkillExport::REVIEW_DIR);
    assert_same('plugins/white-security/skills/review/references', SkillExport::REF_DIR);
    assert_true(!defined('SkillExport::SKILL_DIR') && !defined('SkillExport::RULES_START'), '상시 스킬 상수 없음');
    assert_true(!method_exists(SkillExport::class, 'rules') && !method_exists(SkillExport::class, 'checkAlwaysOn'), '상시 규칙 생성기 없음');
    assert_true(!is_dir(SE_ROOT . '/plugins/white-security/skills/white-security'), '상시 스킬 폴더 없음');
});

test('SkillExport: 이슈 파일 — 중립 머리 주석·기준은 CWE 번호만(맨 끝 기본 심각도 줄)·보고 형식 줄 없음·영문 병기 표지', function () {
    [$ref] = se_write();
    $f = file_get_contents("$ref/web/WS-INJ-001.md");
    assert_true(str_starts_with($f, "<!-- generated from white-security issues; do not edit -->\n"), '머리 주석');
    assert_true(!str_contains($f, '고치세요') && !str_contains($f, 'issues/ 원본'), '지시처럼 읽히는 머리 없음');
    assert_contains("# WS-INJ-001 SQL 삽입\n", $f);
    // 순서: 제목 → 탐지 규칙 → 점검 항목 → 검토 주의 → 코드 목록 → 기본 심각도·CWE(맨 끝 한 줄)
    $pos = array_map(fn($k) => strpos($f, $k), ['## 탐지 규칙', '## 점검 항목', 'Code examples', '기본 심각도:']);
    foreach ($pos as $n => $p) assert_true(is_int($p) && ($n === 0 || $p > $pos[$n - 1]), '절 순서 ' . json_encode($pos));
    $lines = explode("\n", rtrim($f, "\n"));
    assert_same('기본 심각도: 위험 · CWE-89', end($lines), '맨 끝 한 줄 = 기본 심각도 · CWE 번호');
    // 다른 기준 번호(OWASP·ASVS·국내 가이드)는 사이트에만 — 발동 1회 예산
    foreach (['기준:', 'OWASP', 'ASVS', '국내 보안약점'] as $s) assert_true(!str_contains($f, $s), "$s 없음");
    foreach (['## 점검 항목', '## 탐지 규칙'] as $s) assert_contains($s, $f);
    foreach (['## 발생 상황', '## 요약', '## 위험 배경', '## 대응 방법', '## 코드 예시', '보고 형식', '[위험|주의|참고] <id>'] as $s) assert_true(!str_contains($f, $s), $s);
    assert_contains('- **보고 예**: `[위험] WS-INJ-001', $f);
    assert_contains('- **고칠 방법**: 값만 자리표시자로 바꿔야 한다.', $f);
    assert_true(!str_contains($f, '## 검토 주의'), '검토 주의 칸이 없으면 절도 없음');
    // 검토 주의는 탐지 규칙 칸에서 빠져 점검 항목 뒤 별도 절로(고칠 방법은 탐지 규칙에 남음)
    $fx = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    [$ref3] = se_write(['WS-INJ-007-x.md' => str_replace(['WS-INJ-001', "- **고칠 방법**:"], ['WS-INJ-007', "- **검토 주의**: 정수 변환 뒤 연결은 참고로만.\n- **고칠 방법**:"], $fx)]);
    $g = file_get_contents("$ref3/web/WS-INJ-007.md");
    assert_true(!str_contains($g, '- **검토 주의**'), '탐지 규칙 칸에 검토 주의 없음');
    assert_contains("\n## 검토 주의\n\n정수 변환 뒤 연결은 참고로만.\n", $g);
    assert_true(strpos($g, '## 검토 주의') > strpos($g, '## 점검 항목'), '검토 주의는 점검 항목 뒤');
    assert_true(strpos($g, '- **고칠 방법**') < strpos($g, '## 점검 항목'), '고칠 방법은 탐지 규칙 안');
    // 코드 블록 안의 ✗ 로 시작하는 줄은 건드리지 않는다
    [$ref2] = se_write(['WS-INJ-002-x.md' => str_replace(['WS-INJ-001', '## 주석처럼 보이는 줄'], ['WS-INJ-002', '✗ 코드 속 줄'], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'))]);
    $py = file_get_contents("$ref2/web/WS-INJ-002.code.python.md");
    assert_contains("✗ 코드 속 줄\n", $py);
    assert_true(!str_contains($py, '✗ 코드 속 줄 (vulnerable'), '코드 블록 안 표지 불변');
});

test('SkillExport: 전체 INDEX 없음, 묶음별 INDEX = 이름 + "코드 신호 → 이슈 id: 위험 조건" (요약 문장·제목·when 없음)', function () {
    [$ref] = se_write();
    assert_true(!is_file("$ref/INDEX.md"), '전체 INDEX 없음');
    $idx = file_get_contents("$ref/web/INDEX.md");
    assert_true(str_starts_with($idx, "<!-- generated from white-security issues; do not edit -->\n"), '머리');
    assert_contains("# web · 웹 애플리케이션\n", $idx);
    assert_true(!str_contains($idx, 'When:'), 'when 줄 없음(라우팅 표에 목적이 있음)');
    // grade_hint 가 없으면 등급 첫 단계(위험) 문장의 첫 문장, 위험이면 단계 이름은 붙이지 않는다
    assert_true((bool)preg_match('/^- `cursor\.execute\(` `db\.query\(` `->query\(` → WS-INJ-001: \S[^\n]*$/mu', $idx), $idx);
    assert_true(!str_contains($idx, 'SQL 삽입'), '제목 없음(등급 조건이 대신 말함)');
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
    assert_contains("\n| web | ", $block);
    assert_true(!preg_match('/^\| (people|auth) \|/m', $block), '이슈 있는 묶음만');
    $md = "---\nname: x\n---\n# 위\n\n" . SkillExport::ROUTING_START . "\n옛 표\n" . SkillExport::ROUTING_END . "\n\n# 아래\n";
    $out = SkillExport::injectRouting($md, $block);
    assert_same("---\nname: x\n---\n# 위\n\n" . $block . "\n\n# 아래\n", $out);
    assert_same($out, SkillExport::injectRouting($out, $block), '두 번 넣어도 같음');
    foreach (["# 표시 없음\n", SkillExport::ROUTING_END . "\n" . SkillExport::ROUTING_START . "\n", SkillExport::ROUTING_START . SkillExport::ROUTING_START . SkillExport::ROUTING_END] as $bad) {
        try { SkillExport::injectRouting($bad, $block); throw new Exception('거절해야 함'); }
        catch (SkillExportError $e) { assert_contains('SKILL.md', $e->getMessage()); }
    }
});

test('build: 검토 SKILL.md 에 라우팅 표, review.md 에 링크 규칙, 에이전트에 절차를 끼워 넣고, 표시가 없거나 파일이 없으면 빌드 실패', function () {
    $root = make_root();
    $dir = $root . '/' . SkillExport::REVIEW_DIR;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l, $c);
    assert_same(0, $c, implode("\n", $l));
    assert_contains("\n| web | ", file_get_contents("$dir/SKILL.md"));
    assert_contains(SkillExport::TEXT['links_plain'], file_get_contents($root . '/' . SkillExport::REF_DIR . '/review.md')); // site.json 없음 → id 만
    $agent = file_get_contents($root . '/' . SkillExport::AGENT_FILE);
    foreach (['## Trust and safety rules', '## Routing table', '| web | ', '## 0. Scope', SkillExport::TEXT['links_plain']] as $x) assert_contains($x, $agent);
    // site.json origin 이 있으면 보고서 id 가 문서 사이트 이슈 주소로 링크된다(--origin 미리보기 값은 쓰지 않음)
    file_put_contents($root . '/data/site.json', json_encode(['origin' => 'https://docs.example.org']));
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 --origin=https://preview.example.com 2>&1', $lo, $co);
    assert_same(0, $co, implode("\n", $lo));
    assert_contains('https://docs.example.org/white-security/issues/WS-INJ-001', file_get_contents($root . '/' . SkillExport::AGENT_FILE));
    assert_true(!str_contains(file_get_contents($root . '/' . SkillExport::REF_DIR . '/review.md'), 'preview.example.com'), '--origin 은 플러그인에 쓰지 않음');
    unlink($root . '/data/site.json');
    // 에이전트 표시가 없으면 실패
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, "---\nname: r\n---\n표시 없음\n");
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $la, $ca);
    assert_same(1, $ca, '에이전트 표시 없으면 실패');
    assert_contains('검토 에이전트', implode("\n", $la));
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, SkillExport::REVIEWER_START . "\n" . SkillExport::REVIEWER_END . "\n");
    $keepReview = file_get_contents("$dir/SKILL.md");
    file_put_contents("$dir/SKILL.md", "# 표시 없음\n");
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l2, $c2);
    assert_same(1, $c2);
    assert_contains('SKILL.md', implode("\n", $l2));
    file_put_contents("$dir/SKILL.md", $keepReview);
    unlink("$dir/SKILL.md");
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l3, $c3);
    assert_same(1, $c3, 'SKILL.md 없으면 실패');
    assert_contains('SKILL.md 가 없습니다', implode("\n", $l3));
});

test('저장소: 생성물이 원본과 일치(php build.php 후 커밋), 옛 skills/ 폴더 없음', function () {
    assert_true(!is_dir(SE_ROOT . '/skills'), '옛 skills/ 폴더');
    $ref = SE_ROOT . '/' . SkillExport::REF_DIR;
    $want = SkillExport::render(Repository::load(SE_ROOT));
    $have = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ref, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $have[substr($f->getPathname(), strlen($ref) + 1)] = file_get_contents($f->getPathname());
    foreach (SkillExport::HAND_FILES as $hand) { assert_true(isset($have[$hand]), "손으로 쓰는 $hand"); unset($have[$hand]); }
    ksort($have); ksort($want);
    assert_same(array_keys($want), array_keys($have), '파일 목록 (php build.php 실행 필요)');
    foreach ($want as $rel => $text) assert_true($have[$rel] === $text, "$rel 이 원본과 다름 (php build.php 실행 필요)");
    assert_true(!isset($have['INDEX.md']), '전체 INDEX 없음');
});

test('저장소: 검토 스킬 폴더의 LICENSE.txt — 본문 CC BY-SA 4.0 · 코드 블록 MIT · 저작권자 · 원본 저장소 · 각색 내용은 CC BY-SA', function () {
    $site = json_decode(file_get_contents(SE_ROOT . '/data/site.json'), true);
    foreach ([SkillExport::REVIEW_DIR] as $dir) {
        $f = SE_ROOT . '/' . $dir . '/LICENSE.txt';
        assert_true(is_file($f), "$dir/LICENSE.txt");
        $t = file_get_contents($f);
        foreach (['CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 'MIT License', 'Lim Ho Jae (LimIndex)', $site['github'], 'OWASP',
            'Permission is hereby granted, free of charge'] as $s) assert_contains($s, $t);
    }
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
    assert_contains("# WS-INJ-001 코드 예시 — Python\n", $py);
    assert_contains("✗ 취약 코드 (vulnerable example — do not use)\n", $py);
    assert_contains("✓ 안전 코드 (safe example)\n", $py);
    assert_contains('cur.execute("SELECT * FROM u WHERE id=%s", (uid,))', $py);
    assert_true(!str_contains($py, '$db->query'), 'php 코드 없음');
    assert_contains('$st = $db->prepare', file_get_contents("$ref/web/WS-INJ-001.code.php.md"));
});

test('SkillExport: INDEX 는 이슈당 신호를 찾는 곳 순서대로 앞 INDEX_MAX_SIGNALS 개만(작성자가 핵심 신호를 앞에), 600토큰 넘으면 실패', function () {
    $n = SkillExport::INDEX_MAX_SIGNALS;
    assert_same(4, SkillExport::INDEX_MIN_SIGNALS, '줄여도 신호 4개 이상');
    assert_same(600, SkillExport::INDEX_MAX_TOKENS, '묶음 INDEX 상한 600토큰 유지');
    $sigs = ['abcd', 'x.y', 'longer_name_1', 'aaaa', 'bbbbbbbb', 'c.c(', 'dddd', 'eeee', 'ffff', 'g-g', 'hhhhhhhhhhhh'];
    $where = '- **찾는 곳**: ' . implode(', ', array_map(fn($s) => "`$s`", $sigs));
    [$ref] = se_write(['WS-INJ-005-x.md' => str_replace(['WS-INJ-001', '- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)'], ['WS-INJ-005', $where], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'))]);
    $idx = file_get_contents("$ref/web/INDEX.md");
    preg_match('/^- (.*) → WS-INJ-005/m', $idx, $m);
    preg_match_all('/`([^`]+)`/', $m[1], $got);
    assert_same(array_slice($sigs, 0, $n), $got[1], "원래 순서 앞 {$n}개");
    assert_same(array_slice($sigs, 0, $n), SkillExport::topSignals($sigs));
    assert_same(['a.b', 'c.d'], SkillExport::topSignals(['a.b', 'c.d']));
    // 앞서 실은 신호를 품은 신호는 같은 곳에 걸리므로 건너뛰고 뒤 신호가 자리를 얻는다
    assert_same(['yaml.load(', 'x.y'], SkillExport::topSignals(['yaml.load(', 'yaml.load(s)', 'x.y']));
    $many = ['aaaa', 'aaaa.1', 'aaaa.2', 'b.b', 'c.c', 'd.d', 'e.e', 'f.f', 'g.g', 'h.h', 'i.i'];
    assert_same(array_slice(['aaaa', 'b.b', 'c.c', 'd.d', 'e.e', 'f.f', 'g.g', 'h.h'], 0, $n), SkillExport::topSignals($many));
    // 이슈가 많아 600토큰을 넘는 묶음은 신호 수를 줄여(최소 INDEX_MIN_SIGNALS) 상한 안에 넣는다
    $extra = [];
    foreach (range(10, 18) as $k) {
        $w = '- **찾는 곳**: ' . implode(', ', array_map(fn($j) => "`signal_name_{$k}_{$j}(`", range(1, 8)));
        $extra["WS-INJ-1{$k}-x.md"] = str_replace(['WS-INJ-001', '- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)'], ["WS-INJ-1{$k}", $w], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'));
    }
    $big = SkillExport::render(Repository::load(make_root($extra)))['web/INDEX.md'];
    assert_true(SkillExport::estimateTokens($big) <= SkillExport::INDEX_MAX_TOKENS, '상한 안');
    preg_match('/^- (.*) → WS-INJ-110:/m', $big, $bm);
    $cnt = preg_match_all('/`[^`]+`/', $bm[1]);
    assert_true($cnt < 8 && $cnt >= SkillExport::INDEX_MIN_SIGNALS, "줄인 신호 수 $cnt");
    // 상한 초과(최소 신호 수로도 넘음)
    $root = make_root();
    file_put_contents($root . '/data/groups.json', json_encode([['id' => 'web', 'name' => str_repeat('아주 긴 이름 ', 300), 'order' => 5, 'route' => 'sql']], JSON_UNESCAPED_UNICODE));
    try { SkillExport::render(Repository::load($root)); throw new Exception('실패해야 함'); }
    catch (SkillExportError $e) { assert_contains('web/INDEX.md', $e->getMessage()); assert_contains('600', $e->getMessage()); }
});

test('SkillExport: INDEX 등급 조건 — grade_hint 우선, 없으면 등급 첫 단계 첫 문장(괄호 뺌), 첫 단계가 위험이 아니면 단계 이름', function () {
    $fx = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $hint = str_replace(['WS-INJ-001', "severity: high\n"], ['WS-INJ-006', "severity: high\ngrade_hint: \"검색어가 SQL 문자열에\"\n"], $fx);
    $idx = SkillExport::render(Repository::load(make_root(['WS-INJ-006-x.md' => $hint])))['web/INDEX.md'];
    assert_true((bool)preg_match('/→ WS-INJ-006: 검색어가 SQL 문자열에$/mu', $idx), $idx);
    // 위험 단계가 없으면 `주의: ` 를 붙이고, 자동 문장은 괄호 풀이·끝 마침표를 뺀다
    $warn = (string)preg_replace('/^- \*\*등급\*\*: .*$/mu', '- **등급**: 주의: 쿼리에 값을 붙임(예: f-string). 둘째 문장. / 참고: 기타.', str_replace('WS-INJ-001', 'WS-INJ-007', $fx));
    $repo = Repository::load(make_root(['WS-INJ-007-x.md' => $warn]));
    $i = array_values(array_filter($repo->byGroup()['web'], fn($x) => $x->id === 'WS-INJ-007'))[0];
    assert_same('주의: 쿼리에 값을 붙임', SkillExport::gradeHint($i));
});

test('SkillExport: 보고서 링크 규칙 — origin 없으면 id 만, 있으면 문서 사이트 이슈 주소, 그 밖의 링크·이미지 금지', function () {
    assert_contains(SkillExport::TEXT['links_plain'], SkillExport::links(''));
    $b = SkillExport::links('https://limindex.github.io');
    assert_contains('[WS-INJ-001](https://limindex.github.io/white-security/issues/WS-INJ-001)', $b);
    assert_contains('No other links or images', $b);
    assert_same('', SkillExport::issueUrlBase(''));
    try { SkillExport::links('http://bad'); throw new Exception('실패해야 함'); } catch (SkillExportError $e) { assert_contains('origin', $e->getMessage()); }
});

test('저장소: 바이브 스택 핵심 신호가 묶음 INDEX 에 실린다(검토가 grep 으로 찾게)', function () {
    $ref = SE_ROOT . '/' . SkillExport::REF_DIR;
    $need = [
        'auth' => ['use server', 'function POST(', 'middleware.ts'],
        'deploy' => ['sk-', 'AKIA', 'ghp_', 'xoxb-', '-----BEGIN', '.env*', 'NEXT_PUBLIC_'],
        'vibe' => ['extraKnownMarketplaces', 'enabledPlugins', '.claude/skills/', '.mcp.json'],
        'supply' => ['yarnPath', '_authToken=${', 'postinstall'],
        'data' => ['service_role'],
    ];
    foreach ($need as $g => $sigs) {
        $idx = file_get_contents("$ref/$g/INDEX.md");
        foreach ($sigs as $sig) assert_contains('`' . $sig . '`', $idx);
    }
});

test('저장소: 예시 도메인은 예약 이름만(RFC 2606 — example.com·example.org·example.net·*.example)', function () {
    $files = [];
    foreach (glob(SE_ROOT . '/issues/*/*.md') as $f) $files[] = $f;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SE_ROOT . '/plugins', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if (str_ends_with($f->getFilename(), '.md')) $files[] = $f->getPathname();
    $n = 0;
    foreach ($files as $f) {
        preg_match_all('/(?<![\w.-])((?:[a-z0-9-]+\.)*[a-z0-9-]*(?:example|evil|attacker)[a-z0-9-]*(?:\.[a-z0-9-]+)*\.(?:com|net|org|io|ai|dev|app|co|kr|xyz|me|info|site|example|evil))\b/i', file_get_contents($f), $m);
        foreach ($m[1] as $host) {
            $n++;
            assert_true((bool)preg_match('/(^|\.)(example\.(com|org|net)|[a-z0-9-]+\.example|example)$/i', $host), basename($f) . ": 등록 가능한 예시 도메인 $host");
        }
    }
    assert_true($n > 50, "예시 도메인 $n 개");
});
