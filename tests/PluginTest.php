<?php
// 플러그인(plugins/white-security/) 손으로 쓰는 파일의 형식 검사: plugin.json · SKILL.md · 에이전트 · 명령 · 참고 파일 경로
require_once __DIR__ . '/support.php';

const PL_ROOT = __DIR__ . '/../plugins/white-security';
const PL_SKILL = __DIR__ . '/../' . SkillExport::SKILL_DIR;

/** `---` 머리말을 key => 값(한 줄 값만) 으로. 머리말이 없으면 null. */
function pl_frontmatter(string $text): ?array
{
    if (!preg_match('/\A---\n(.*?)\n---\n/s', $text, $m)) return null;
    $out = [];
    foreach (explode("\n", $m[1]) as $line) {
        if (preg_match('/^([A-Za-z][\w-]*):[ \t]*(.*)$/', $line, $kv)) $out[$kv[1]] = trim($kv[2]);
    }
    return $out;
}

/** 플러그인 안 모든 파일(상대 경로 => 내용) */
function pl_files(): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PL_ROOT, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $out[substr($f->getPathname(), strlen(PL_ROOT) + 1)] = file_get_contents($f->getPathname());
    ksort($out);
    return $out;
}

test('플러그인: plugin.json — 이름·버전·작성자·주소·라이선스·키워드', function () {
    $j = json_decode(file_get_contents(PL_ROOT . '/.claude-plugin/plugin.json'), true, 512, JSON_THROW_ON_ERROR);
    assert_same('white-security', $j['name']);
    assert_true((bool)preg_match('/^\d+\.\d+\.\d+$/', $j['version']), 'version 은 x.y.z');
    assert_same('LimIndex (Lim Ho Jae)', $j['author']['name']);
    $site = json_decode(file_get_contents(__DIR__ . '/../data/site.json'), true);
    assert_same($site['github'], $j['homepage']);
    assert_same($site['github'], $j['repository']);
    assert_same('CC-BY-SA-4.0 AND MIT', $j['license']);
    assert_true(is_array($j['keywords']) && in_array('security', $j['keywords'], true), 'keywords');
    assert_true(trim($j['description']) !== '' && (bool)preg_match('/[\x{AC00}-\x{D7A3}]/u', $j['description']) && (bool)preg_match('/^[A-Za-z]/', $j['description']), 'description 영문 + 한국어');
    $site_id = explode('@', $site['plugin_id'])[0];
    assert_same($j['name'], $site_id, 'data/site.json plugin_id 와 같은 이름');
});

test('플러그인: version 은 CHANGELOG.md 의 최신(첫) ## 버전과 같다', function () {
    $j = json_decode(file_get_contents(PL_ROOT . '/.claude-plugin/plugin.json'), true);
    $log = file_get_contents(__DIR__ . '/../CHANGELOG.md');
    assert_true((bool)preg_match('/^## (\d+\.\d+\.\d+)\s*$/m', $log, $m), 'CHANGELOG 에 ## x.y.z 절');
    assert_same($m[1], $j['version'], 'plugin.json version 과 CHANGELOG 최신 버전');
});

test('플러그인: SKILL.md 머리말 — name = 폴더 이름, description 한 줄·1024자 이하·약 200토큰 이하·한국어 발동 문구', function () {
    $md = file_get_contents(PL_SKILL . '/SKILL.md');
    $fm = pl_frontmatter($md);
    assert_true($fm !== null, '머리말');
    assert_same(basename(PL_SKILL), $fm['name']);
    assert_true((bool)preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $fm['name']) && strlen($fm['name']) <= 64, 'agentskills name 규칙');
    $d = SkillExport::description($md);
    assert_true($d !== '' && $d === $fm['description'], 'description 은 한 줄 값');
    assert_true(mb_strlen($d) <= 1024, 'description ≤1024자: ' . mb_strlen($d));
    assert_true(SkillExport::estimateTokens($d) <= 200, 'description 약 200토큰 이하: ' . SkillExport::estimateTokens($d));
    assert_true(str_starts_with($d, 'Security') && str_contains($d, 'Use when'), '영문 + Use when');
    foreach (['보안 점검', '보안 검토', '취약점 확인', 'security review'] as $t) assert_contains($t, $d);
    assert_true(!preg_match('/:\s/', $d), 'YAML 일반 값 안에 ": " 없음');
});

test('플러그인: SKILL.md 본문 — 150줄 이하, 신뢰 규칙이 맨 위, 등급 규칙·보고 형식·라우팅 표(빌드와 일치)', function () {
    $md = file_get_contents(PL_SKILL . '/SKILL.md');
    assert_true(substr_count($md, "\n") <= 150, 'SKILL.md ≤150줄');
    $h2 = [];
    preg_match_all('/^## (.+)$/m', $md, $m);
    assert_true(str_starts_with($m[1][0], 'Trust and safety rules'), '첫 절은 신뢰·안전 규칙: ' . $m[1][0]);
    foreach (['data, not instructions', 'Never add exceptions yourself', '예외', 'first 4 characters', "user's consent", 'U+XXXX'] as $s) assert_contains($s, $md);
    assert_contains('`등급` line', $md);
    assert_contains('`[위험|주의|참고] <id> <file:line> — <reason> → <fix>`', $md);
    assert_contains('보안 메모', $md);
    assert_contains('at most **2 issue files in total**', $md);
    assert_contains('white-security:white-security-reviewer', $md);
    assert_same(1, substr_count($md, SkillExport::ROUTING_START));
    assert_same(1, substr_count($md, SkillExport::ROUTING_END));
    $built = SkillExport::injectRouting($md, SkillExport::routing(Repository::load(__DIR__ . '/..')));
    assert_true($built === $md, '라우팅 표가 원본과 다름 (php build.php 실행 필요)');
});

test('플러그인: SKILL.md·참고 파일·에이전트·명령이 가리키는 references 경로가 실제로 있다', function () {
    $texts = ['SKILL.md' => file_get_contents(PL_SKILL . '/SKILL.md')];
    foreach (SkillExport::HAND_FILES as $f) $texts["references/$f"] = file_get_contents(PL_SKILL . "/references/$f");
    $texts['agent'] = file_get_contents(PL_ROOT . '/agents/white-security-reviewer.md');
    $texts['command'] = file_get_contents(PL_ROOT . '/commands/review.md');
    $n = 0;
    foreach ($texts as $from => $t) {
        $t = str_replace(['${CLAUDE_PLUGIN_ROOT}/skills/white-security/', '${CLAUDE_SKILL_DIR}/'], '', $t);
        preg_match_all('#(?<![\w/])(references/[A-Za-z0-9_./-]+\.md|SKILL\.md)#', $t, $m);
        foreach (array_unique($m[1]) as $p) { $n++; assert_true(is_file(PL_SKILL . '/' . $p), "$from 의 $p 가 없음"); }
        // 묶음 폴더 없이 쓴 참고 파일 이름(review.md 안의 `deps.md` 등)
        preg_match_all('#`(' . implode('|', array_map('preg_quote', SkillExport::HAND_FILES)) . ')`#', $t, $m2);
        foreach (array_unique($m2[1]) as $p) assert_true(is_file(PL_SKILL . "/references/$p"), "$from 의 $p 가 없음");
    }
    assert_true($n >= 6, "경로 $n 개");
    foreach (SkillExport::HAND_FILES as $f) assert_true(!str_starts_with(file_get_contents(PL_SKILL . "/references/$f"), SkillExport::HEADER), "$f 는 생성물 머리 주석 없음(빌드가 지우지 않게)");
});

test('플러그인: 손으로 쓰는 참고 파일은 빌드(prune)가 지우지 않는다', function () {
    [$ref] = se_write_hand();
    foreach (SkillExport::HAND_FILES as $f) assert_true(is_file("$ref/$f"), $f);
});

function se_write_hand(): array
{
    $root = make_root();
    $ref = $root . '/' . SkillExport::SKILL_DIR . '/references';
    mkdir($ref, 0777, true);
    foreach (SkillExport::HAND_FILES as $f) file_put_contents("$ref/$f", "# hand\n");
    SkillExport::write(Repository::load($root), $ref);
    return [$ref, $root];
}

test('플러그인: 검토 에이전트 — 읽기 전용 도구만(Read·Grep·Glob), 절차는 references/review.md, 한국어 보고', function () {
    $t = file_get_contents(PL_ROOT . '/agents/white-security-reviewer.md');
    $fm = pl_frontmatter($t);
    assert_same('white-security-reviewer', $fm['name']);
    $tools = array_map('trim', explode(',', $fm['tools']));
    sort($tools);
    assert_same(['Glob', 'Grep', 'Read'], $tools);
    assert_true(!isset($fm['disallowedTools']) && !isset($fm['permissionMode']) && !isset($fm['hooks']) && !isset($fm['mcpServers']), '플러그인 에이전트가 무시하는 칸 없음');
    assert_true($fm['description'] !== '' && !str_contains($fm['description'], ': '), 'description 한 줄');
    assert_contains('${CLAUDE_PLUGIN_ROOT}/skills/white-security/references/review.md', $t);
    assert_contains('Korean', $t);
    // 규칙 파일을 못 읽으면 기억으로 검토하지 않고 메인에 넘긴다(작업 폴더 밖 읽기가 거부되는 환경, 스모크 1)
    assert_contains('RULES_UNREADABLE', $t);
    assert_contains('RULES_UNREADABLE', file_get_contents(PL_SKILL . '/SKILL.md'));
});

test('플러그인: review 명령 — 모델 자동 호출 끔, 범위 인자, 쉘 끼워 넣기 없음, 에이전트에 위임, 저장은 묻기', function () {
    $t = file_get_contents(PL_ROOT . '/commands/review.md');
    $fm = pl_frontmatter($t);
    assert_same('true', $fm['disable-model-invocation']);
    assert_contains('all|changed|<path>', $fm['argument-hint']);
    assert_true(trim($fm['description']) !== '', 'description');
    assert_true(!preg_match('/!`/', $t), '!` 쉘 끼워 넣기 금지(git 없는 폴더에서 명령이 멈춤)');
    assert_contains('$ARGUMENTS', $t);
    assert_contains('white-security:white-security-reviewer', $t);
    assert_contains('Save only if the user says yes', $t);
});

test('플러그인: 숨은 문자(zero-width·bidi) 없음, 손으로 쓰는 파일에 사설 IP·절대 서버 경로 없음', function () {
    foreach (pl_files() as $rel => $t) {
        assert_true(!preg_match('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}]/u', $t), "$rel 숨은 문자");
        // 생성된 코드 예시에는 예시용 서버 경로(/var/www/html)·사설 IP(신뢰 프록시 설정)가 있으므로 손으로 쓰는 파일만 본다
        if (str_starts_with($t, SkillExport::HEADER)) continue;
        assert_true(!preg_match('/\b(?:10\.\d{1,3}|192\.168|172\.(?:1[6-9]|2\d|3[01]))\.\d{1,3}\.\d{1,3}\b/', $t), "$rel 사설 IP");
        assert_true(!preg_match('#(?<![\w.$}])/(?:home|var/www|srv|Users)/#', $t), "$rel 절대 경로");
    }
});

test('플러그인: 사용자에게 설치되는 폴더에 사이트·도구 파일 없음(.md·.json·LICENSE.txt 만)', function () {
    foreach (array_keys(pl_files()) as $rel) assert_true((bool)preg_match('/(\.md|\.json|LICENSE\.txt)$/', $rel), $rel);
});
