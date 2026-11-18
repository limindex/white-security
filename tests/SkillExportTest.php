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

test('SkillExport: 스킬은 …/skills/white-security 하나, 규칙 파일도 그 한 곳에만(옛 review 이름·상시 스킬 없음, 11-19)', function () {
    assert_same('plugins/white-security/skills/white-security', SkillExport::SKILL_DIR);
    assert_same('plugins/white-security/skills/white-security/references', SkillExport::REF_DIR);
    assert_true(!defined('SkillExport::REVIEW_DIR') && !defined('SkillExport::RULES_START') && !defined('SkillExport::ROUTING_START'), '옛 상수 없음');
    foreach (['rules', 'checkAlwaysOn', 'routing', 'routingTable', 'injectRouting', 'gradeHint', 'topSignals', 'injectLinks'] as $m) assert_true(!method_exists(SkillExport::class, $m), "옛 생성기 $m 없음");
    assert_true(!is_dir(SE_ROOT . '/plugins/white-security/skills/review'), '옛 review 폴더 없음');
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

test('SkillExport: INDEX 파일 없음(11-19 가벼운 안내) — references 는 이슈 파일과 코드 파일뿐, site_only·이슈 없는 묶음 없음', function () {
    [$ref] = se_write();
    assert_true(!is_file("$ref/INDEX.md") && !is_file("$ref/web/INDEX.md"), 'INDEX 없음');
    $files = SkillExport::render(Repository::load(make_root()));
    foreach (array_keys($files) as $rel) assert_true((bool)preg_match('#^[a-z0-9-]+/WS-[A-Z]+-\d{3}(\.code\.[a-z0-9-]+)?\.md$#', $rel), $rel);
    assert_true(!is_dir("$ref/people"), 'site_only 묶음 없음');
    assert_true(!is_dir("$ref/auth"), '이슈 없는 묶음 폴더 없음');
});
test('SkillExport: 이슈 id 목록 — 묶음마다 한 줄 `<group>: <WS- 뺀 id> <짧은 제목>; …`, 짧은 제목은 영어 업계 용어', function () {
    $repo = Repository::load(make_root());
    $block = SkillExport::catalogue($repo);
    assert_true(str_starts_with($block, SkillExport::IDS_START . "\n") && str_ends_with($block, SkillExport::IDS_END), '표시');
    assert_true((bool)preg_match('/^web: INJ-001 /m', $block), $block);
    assert_true(!preg_match('/^(people|auth):/m', $block), '이슈 있는 묶음만');
    assert_true(!str_contains($block, 'WS-'), 'WS- 접두어 뺌');
    // 짧은 제목: title_en 우선, 괄호 묶음은 모두 뗌. 영어가 안 남으면 한국어 풀이로 물러남(실제 이슈는 아래 테스트가 막음)
    $fx = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $mk = fn(string $id, string $title, string $en = '') => Repository::load(make_root(["$id-x.md" => preg_replace('/^title: .*$/m', "title: $title" . ($en !== '' ? "\ntitle_en: $en" : ''), str_replace('WS-INJ-001', $id, $fx), 1)]));
    $pick = fn(Repository $r, string $id) => SkillExport::shortTitle(array_values(array_filter($r->all(), fn($i) => $i->id === $id))[0]);
    assert_same('Path Traversal', $pick($mk('WS-INJ-005', 'Path Traversal (경로 조작)'), 'WS-INJ-005'));
    assert_same('SQL Injection', $pick($mk('WS-INJ-006', 'SQL 인젝션 (SQLi)', 'SQL Injection (SQLi)'), 'WS-INJ-006'));
    assert_same('XXE · Billion Laughs', $pick($mk('WS-INJ-007', 'XXE · Billion Laughs', 'XXE (XML External Entity) · Billion Laughs'), 'WS-INJ-007'));
    assert_same('SSRF', $pick($mk('WS-INJ-008', 'SSRF (서버사이드 요청 위조)', 'SSRF (Server-Side Request Forgery)'), 'WS-INJ-008'));
    assert_same('SQL 삽입', $pick($mk('WS-INJ-009', 'SQL 삽입 (SQLi)'), 'WS-INJ-009'), '영어 용어 없음 → 예전 방식(괄호 앞 한국어)');
    assert_same('A, B', $pick($mk('WS-INJ-010', 'A; B (가)'), 'WS-INJ-010'), '구분자 ; → ,');
});
test('SkillExport: 실제 SKILL.md 이슈 id 목록에는 한국어가 없다(영어 용어가 없는 제목은 title_en 필요)', function () {
    $s = file_get_contents(SE_ROOT . '/' . SkillExport::SKILL_DIR . '/SKILL.md');
    $a = strpos($s, SkillExport::IDS_START); $b = strpos($s, SkillExport::IDS_END);
    assert_true($a !== false && $b > $a, '표시');
    $block = substr($s, $a + strlen(SkillExport::IDS_START), $b - $a - strlen(SkillExport::IDS_START));
    assert_true(!preg_match('/[\x{AC00}-\x{D7A3}]/u', $block), '한국어가 남은 항목: ' . implode(' | ', array_filter(preg_split('/; |\n/', $block), fn($x) => preg_match('/[\x{AC00}-\x{D7A3}]/u', $x))));
});
test('SkillExport: 감시 목록 — advisories.md 항목마다 Watch 줄 + CVE + 만료일, 줄·항목 수 규칙 어기면 실패', function () {
    $md = "# W\n\nintro `- Watch:` not an entry\n\n## A — CVE-2025-1111 (x)\n- Published: 2025-01-01\n- Watch: `a` < 2 → 2.\n- Expires: 2027-01-31\n\n## B — CVE-2026-22222\n- Watch: `b` < 3 → 3\n- Expires: 2027-02-28\n";
    $b = SkillExport::watchList($md);
    assert_same(SkillExport::WATCH_START . "\n- `a` < 2 → 2 (CVE-2025-1111, until 2027-01-31)\n- `b` < 3 → 3 (CVE-2026-22222, until 2027-02-28)\n" . SkillExport::WATCH_END, $b);
    $bad = [
        str_replace("- Watch: `b` < 3 → 3\n", '', $md) => 'Watch',
        str_replace("- Expires: 2027-02-28\n", '', $md) => 'Expires',
        str_replace('— CVE-2026-22222', '', $md) => 'CVE',
        "# empty\n" => '항목',
        str_repeat("## X — CVE-2025-0001\n- Watch: x\n- Expires: 2027-01-01\n", SkillExport::WATCH_MAX + 1) => '상한',
    ];
    foreach ($bad as $in => $why) {
        try { SkillExport::watchList($in); throw new Exception("실패해야 함: $why"); }
        catch (SkillExportError $e) { assert_contains('advisories.md', $e->getMessage()); }
    }
    // 실제 감시 목록은 상한 안이고 항목마다 Watch 줄이 있다
    assert_true(substr_count(SkillExport::watchList(file_get_contents(SE_ROOT . '/' . SkillExport::REF_DIR . '/advisories.md')), "\n- ") >= 1);
});

test('SkillExport: SKILL.md 생성 — 세 표시 사이만 바꾸고(두 번 넣어도 같음), 표시가 틀리거나 상한을 넘으면 실패', function () {
    $root = make_root();
    $repo = Repository::load($root);
    $skill = file_get_contents($root . '/' . SkillExport::SKILL_DIR . '/SKILL.md');
    $adv = file_get_contents($root . '/' . SkillExport::REF_DIR . '/advisories.md');
    $out = SkillExport::buildSkill($skill, $repo, $adv, '');
    assert_contains("\nweb: INJ-001 ", $out);
    assert_contains('(CVE-2025-0001, until 2027-03-31)', $out);
    assert_contains(SkillExport::TEXT['links_plain'], $out);
    assert_same($out, SkillExport::buildSkill($out, $repo, $adv, ''), '두 번 넣어도 같음');
    foreach ([SkillExport::IDS_START, SkillExport::WATCH_END, SkillExport::LINKS_START] as $marker) {
        try { SkillExport::buildSkill(str_replace($marker, '', $skill), $repo, $adv, ''); throw new Exception('거절해야 함'); }
        catch (SkillExportError $e) { assert_contains('SKILL.md', $e->getMessage()); }
    }
    try { SkillExport::buildSkill($skill . str_repeat('x', 4 * SkillExport::SKILL_MAX_TOKENS), $repo, $adv, ''); throw new Exception('상한'); }
    catch (SkillExportError $e) { assert_contains((string)SkillExport::SKILL_MAX_TOKENS, $e->getMessage()); }
});

test('SkillExport: 검토 에이전트 블록 — SKILL.md 의 검토 절만 그대로, 보고 틀 코드 블록 안의 ## 는 절로 보지 않음, 절이 없으면 실패', function () {
    $root = make_root();
    $skill = SkillExport::buildSkill(file_get_contents($root . '/' . SkillExport::SKILL_DIR . '/SKILL.md'), Repository::load($root), file_get_contents($root . '/' . SkillExport::REF_DIR . '/advisories.md'), '');
    $b = SkillExport::reviewerBlock($skill);
    foreach (SkillExport::REVIEWER_SECTIONS as $h) assert_contains("## $h", $b);
    assert_contains("```\n## 위험 (<n>)\n```", $b);
    assert_true(!str_contains($b, '## After the report'), '보고 뒤 단계는 메인 몫');
    assert_true(strpos($b, '## Scope') < strpos($b, '## Report (Korean)'), '순서');
    try { SkillExport::reviewerBlock(str_replace('## Safety rules', '## Safety', $skill)); throw new Exception('실패해야 함'); }
    catch (SkillExportError $e) { assert_contains('Safety rules', $e->getMessage()); }
});
test('build: SKILL.md 에 감시 목록·id 목록·링크 규칙, 에이전트에 검토 절을 끼워 넣고, 표시가 없거나 파일이 없으면 빌드 실패', function () {
    $root = make_root();
    $dir = $root . '/' . SkillExport::SKILL_DIR;
    $run = function (string $extra = '') use ($root): array {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(SE_ROOT . '/build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18' . $extra . ' 2>&1', $l, $c);
        return [$c, implode("\n", $l)];
    };
    [$c, $o] = $run();
    assert_same(0, $c, $o);
    $skill = file_get_contents("$dir/SKILL.md");
    foreach (["\nweb: INJ-001 ", '(CVE-2025-0001, until 2027-03-31)', SkillExport::TEXT['links_plain']] as $x) assert_contains($x, $skill); // site.json 없음 → id 만
    $agent = file_get_contents($root . '/' . SkillExport::AGENT_FILE);
    foreach (['## Scope', '## Issue ids', "\nweb: INJ-001 ", '## Report (Korean)', SkillExport::TEXT['links_plain']] as $x) assert_contains($x, $agent);
    // site.json origin 이 있으면 보고서 id 가 문서 사이트 이슈 주소로 링크된다(--origin 미리보기 값은 쓰지 않음)
    file_put_contents($root . '/data/site.json', json_encode(['origin' => 'https://docs.example.org']));
    [$co, $oo] = $run(' --origin=https://preview.example.com');
    assert_same(0, $co, $oo);
    assert_contains('https://docs.example.org/white-security/issues/WS-INJ-001', file_get_contents($root . '/' . SkillExport::AGENT_FILE));
    assert_true(!str_contains(file_get_contents("$dir/SKILL.md"), 'preview.example.com'), '--origin 은 플러그인에 쓰지 않음');
    unlink($root . '/data/site.json');
    // 에이전트 표시가 없으면 실패
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, "---\nname: r\n---\n표시 없음\n");
    [$ca, $la] = $run();
    assert_same(1, $ca, '에이전트 표시 없으면 실패');
    assert_contains('검토 에이전트', $la);
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, SkillExport::REVIEWER_START . "\n" . SkillExport::REVIEWER_END . "\n");
    // advisories.md 가 없으면 실패
    $adv = $root . '/' . SkillExport::REF_DIR . '/advisories.md';
    $keepAdv = file_get_contents($adv);
    unlink($adv);
    [$cv, $lv] = $run();
    assert_same(1, $cv);
    assert_contains('advisories.md 가 없습니다', $lv);
    file_put_contents($adv, $keepAdv);
    $keep = file_get_contents("$dir/SKILL.md");
    file_put_contents("$dir/SKILL.md", "# 표시 없음\n");
    [$c2, $l2] = $run();
    assert_same(1, $c2);
    assert_contains('SKILL.md', $l2);
    file_put_contents("$dir/SKILL.md", $keep);
    unlink("$dir/SKILL.md");
    [$c3, $l3] = $run();
    assert_same(1, $c3, 'SKILL.md 없으면 실패');
    assert_contains('SKILL.md 가 없습니다', $l3);
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
});
test('저장소: 스킬 폴더의 LICENSE.txt — 본문 CC BY-SA 4.0 · 코드 블록 MIT · 저작권자 · 원본 저장소 · 각색 내용은 CC BY-SA', function () {
    $site = json_decode(file_get_contents(SE_ROOT . '/data/site.json'), true);
    foreach ([SkillExport::SKILL_DIR] as $dir) {
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

test('SkillExport: 보고서 링크 규칙 — origin 없으면 id 만, 있으면 문서 사이트 이슈 주소, 그 밖의 링크·이미지 금지', function () {
    assert_contains(SkillExport::TEXT['links_plain'], SkillExport::links(''));
    $b = SkillExport::links('https://limindex.github.io');
    assert_contains('[WS-INJ-001](https://limindex.github.io/white-security/issues/WS-INJ-001)', $b);
    assert_contains('No other links or images', $b);
    assert_same('', SkillExport::issueUrlBase(''));
    try { SkillExport::links('http://bad'); throw new Exception('실패해야 함'); } catch (SkillExportError $e) { assert_contains('origin', $e->getMessage()); }
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
