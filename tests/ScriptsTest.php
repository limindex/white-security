<?php
// scripts/ 의 CI·로컬 검사: 숨은 문자 · 버전 올림 · 금지어(로컬 전용, 가린 출력) · 코드 예시 문법
require_once __DIR__ . '/support.php';
require_once __DIR__ . '/../scripts/check-hidden-chars.php';
require_once __DIR__ . '/../scripts/check-version-bump.php';
require_once __DIR__ . '/../scripts/check-forbidden.php';
require_once __DIR__ . '/../scripts/check-code-examples.php';

/** 임시 git 저장소: scripts/ 를 복사해 그 저장소 기준으로 돌린다. 전역 훅·서명 설정의 영향을 받지 않게 한다. */
function sc_repo(): string
{
    $root = sys_get_temp_dir() . '/ws-scripts-' . bin2hex(random_bytes(4));
    mkdir($root . '/scripts', 0777, true);
    foreach (glob(__DIR__ . '/../scripts/*.php') as $f) copy($f, $root . '/scripts/' . basename($f));
    sc_git($root, 'init', '-q', '-b', 'main');
    return $root;
}
function sc_git(string $root, string ...$args): string
{
    $out = ws_git($root, '-c', 'user.name=ws-test', '-c', 'user.email=ws-test@example.invalid', '-c', 'core.hooksPath=/dev/null',
        '-c', 'commit.gpgsign=false', ...$args);
    if ($out === null) throw new Exception('git ' . implode(' ', $args) . ' 실패');
    return $out;
}
function sc_put(string $root, string $rel, string $body): void
{
    if (!is_dir(dirname("$root/$rel"))) mkdir(dirname("$root/$rel"), 0777, true);
    file_put_contents("$root/$rel", $body);
}
function sc_commit(string $root, string $msg): string
{
    sc_git($root, 'add', '-A');
    sc_git($root, 'commit', '-q', '-m', $msg);
    return trim(sc_git($root, 'rev-parse', 'HEAD'));
}
/** @return array{0:int,1:string} 종료 코드·출력 */
function sc_run(string $root, string $script, string ...$args): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/scripts/$script") . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';
    exec($cmd, $lines, $code);
    return [$code, implode("\n", $lines)];
}

// ── 숨은 문자 ──

test('숨은 문자: zero-width·bidi·BOM·태그·한글 채움 문자를 U+XXXX 로 찾고, 한글·보통 글자는 통과', function () {
    foreach ([0x200B, 0x200F, 0x202A, 0x202E, 0x2060, 0x2064, 0x2066, 0x2069, 0xFEFF, 0x3164, 0x115F, 0xE0001, 0xE007F] as $cp) {
        $hits = ws_hidden_scan("가나\n다" . mb_chr($cp, 'UTF-8') . "라\n");
        assert_same([['line' => 2, 'col' => 2, 'code' => sprintf('U+%04X', $cp)]], $hits, sprintf('U+%04X', $cp));
    }
    assert_same([], ws_hidden_scan("한글 문장과 English, 이모지 없는 보통 글. 표기만 `U+200B` 는 괜찮다.\n\t탭·줄바꿈\r\n"));
    assert_same('UTF-8 아님', ws_hidden_scan("\xff\xfe abc")[0]['code']);
});

test('숨은 문자: 저장소 검사는 파일 이름도 보고 문자 자체는 찍지 않는다', function () {
    $root = sc_repo();
    sc_put($root, 'ok.md', "괜찮은 글\n");
    sc_put($root, 'bad.md', "a\nb" . mb_chr(0x202E, 'UTF-8') . "c\n");
    sc_put($root, 'font.bin', "\0\x01" . mb_chr(0x200B, 'UTF-8'));
    sc_commit($root, 'init');
    [$code, $out] = sc_run($root, 'check-hidden-chars.php');
    assert_same(1, $code, $out);
    assert_contains('bad.md:2:2 U+202E', $out);
    assert_true(!str_contains($out, 'ok.md') && !str_contains($out, 'font.bin'), '깨끗한 파일·바이너리는 안 찍음');
    assert_true(!str_contains($out, mb_chr(0x202E, 'UTF-8')), '문자 자체는 출력하지 않음');
});

// ── 버전 올림 ──

test('버전 검사(판정): 플러그인이 바뀌면 version 을 올리고 CHANGELOG 에 그 버전 제목', function () {
    $cl = "# Changelog\n\n## 0.2.0 — 2026-12-01\n\n- x\n\n## 0.1.0\n";
    assert_same([], ws_version_errors([], '0.1.0', '0.1.0', ''), '플러그인 변경 없음');
    assert_same([], ws_version_errors(['plugins/white-security/x.md'], '0.1.0', '0.2.0', $cl));
    assert_same([], ws_version_errors(['plugins/white-security/x.md'], null, '0.1.0', $cl), '기준에 plugin.json 이 없으면 CHANGELOG 만');
    assert_same(1, count(ws_version_errors(['p'], '0.1.0', '0.1.0', $cl)), '그대로');
    assert_same(1, count(ws_version_errors(['p'], '0.2.0', '0.1.0', $cl)), '낮춤');
    assert_contains('## 0.2.1', ws_version_errors(['p'], '0.2.0', '0.2.1', $cl)[0]);
    assert_same(1, count(ws_version_errors(['p'], '0.1.0', '0.1.1', "## 0.1.10\n")), '0.1.10 은 0.1.1 제목이 아님');
    assert_same(1, count(ws_version_errors(['p'], '0.1.0', 'v0.2', $cl)), 'x.y.z 형식');
    assert_same(1, count(ws_version_errors(['p'], '0.1.0', null, $cl)), 'version 없음');
});

test('버전 검사(git): 기준 이후 플러그인 변경에 버전·CHANGELOG 를 요구, 첫 push·모르는 기준은 건너뜀', function () {
    $root = sc_repo();
    sc_put($root, 'plugins/white-security/.claude-plugin/plugin.json', '{"name": "white-security", "version": "0.1.0"}');
    sc_put($root, 'plugins/white-security/skills/review/SKILL.md', "v1\n");
    sc_put($root, 'CHANGELOG.md', "## 0.1.0\n");
    $base = sc_commit($root, 'v0.1.0');

    sc_put($root, 'README.md', "plugin 밖만 바뀜\n");
    sc_commit($root, 'docs');
    [$code, $out] = sc_run($root, 'check-version-bump.php', $base);
    assert_same(0, $code, $out);
    assert_contains('플러그인 변경 없음', $out);

    sc_put($root, 'plugins/white-security/skills/review/SKILL.md', "v2\n");
    sc_commit($root, 'skill');
    [$code, $out] = sc_run($root, 'check-version-bump.php', $base);
    assert_same(1, $code, $out);
    assert_contains('version 이 그대로', $out);

    sc_put($root, 'plugins/white-security/.claude-plugin/plugin.json', '{"name": "white-security", "version": "0.1.1"}');
    sc_commit($root, 'bump');
    [$code, $out] = sc_run($root, 'check-version-bump.php', $base);
    assert_same(1, $code, $out);
    assert_contains('"## 0.1.1"', $out);

    sc_put($root, 'CHANGELOG.md', "## 0.1.1\n\n- skill\n\n## 0.1.0\n");
    sc_commit($root, 'changelog');
    [$code, $out] = sc_run($root, 'check-version-bump.php', $base);
    assert_same(0, $code, $out);

    foreach (['', str_repeat('0', 40), str_repeat('ab', 20)] as $skip) {
        [$code, $out] = sc_run($root, 'check-version-bump.php', $skip);
        assert_same(0, $code, $out);
        assert_contains('건너뜀', $out);
    }
    [$code] = sc_run($root, 'check-version-bump.php', '--output=/tmp/x');
    assert_same(2, $code, '옵션 모양 기준은 거부');
});

// ── 금지어 ──

test('금지어(판정): 대소문자 무시·한글, 첫 글자만 남기고 가림, 경로 속 낱말도 가림', function () {
    $words = ['acme', '비밀회사'];
    assert_same([['line' => 2, 'mask' => 'a***'], ['line' => 3, 'mask' => '비***']], ws_forbidden_hits("ok\nhello ACME\n(주)비밀회사 문서\n", $words));
    assert_same('docs/a***-notes.md', ws_forbidden_redact('docs/Acme-notes.md', $words));
    $f = tempnam(sys_get_temp_dir(), 'wsf');
    file_put_contents($f, "# 주석\n\n  Acme  \nacme\n비밀회사\r\n");
    assert_same(['acme', '비밀회사'], ws_forbidden_load($f));
    assert_same(null, ws_forbidden_load($f . '-none'));
});

test('금지어(저장소): 목록이 없으면 중립 문구로 건너뜀, 있으면 파일·파일 이름·커밋을 가려서 보고(낱말 원문 0회)', function () {
    $root = sc_repo();
    sc_put($root, '.gitignore', "scripts/forbidden.txt\n");
    sc_put($root, 'clean.md', "괜찮음\n");
    sc_put($root, 'docs/zorbex-plan.md', "line1\nZorbex 내부 자료\n");
    sc_commit($root, 'add zorbex notes');
    sc_put($root, 'new.txt', "zorbex 새 파일\n"); // 아직 커밋 안 한 새 파일도 본다

    [$code, $out] = sc_run($root, 'check-forbidden.php');
    assert_same(0, $code, $out);
    assert_same("금지어 검사: 로컬 목록이 없어 건너뜀(push 전에 로컬에서 실행)", $out, 'CI 에서는 이 한 줄만');

    sc_put($root, 'scripts/forbidden.txt', "# 목록\nzorbex\n");
    [$code, $out] = sc_run($root, 'check-forbidden.php');
    assert_same(1, $code, $out);
    foreach (['docs/z***-plan.md: 파일 이름 z***', 'docs/z***-plan.md:2 z***', 'new.txt:1 z***', ': z***'] as $s) assert_contains($s, $out);
    assert_true(stripos($out, 'zorbex') === false, '낱말 원문이 출력에 없음');
    assert_true(!str_contains($out, 'forbidden.txt'), '목록 파일 자신은 검사·출력하지 않음');
});

test('금지어: 실제 저장소에서 scripts/forbidden.txt 는 git 이 무시하고 추적하지 않는다', function () {
    $root = __DIR__ . '/..';
    assert_true(ws_git($root, 'check-ignore', '-q', 'scripts/forbidden.txt') !== null, '.gitignore 에 있음');
    assert_same('', (string)ws_git($root, 'ls-files', 'scripts/forbidden.txt'), '추적하지 않음');
});

// ── 코드 예시 ──

test('코드 예시(PHP): 완전한 파일·클래스 본문·여러 파일 조각·설정 배열·체인 통과, Blade 건너뜀, 문법 오류는 실패', function () {
    $ok = [
        "<?php\n\$x = 1;\necho \$x;\n",
        "\$stmt = \$pdo->prepare('SELECT 1');\n",
        "public function show(Request \$r)\n{\n    return \$r->user();\n}\n",
        "// routes/web.php\nRoute::get('/', fn () => 1);\n\n// UserController\npublic function me()\n{\n    return 1;\n}\n",
        "class A\n{\n    public function a() {}\n\n    private function b() {}\n}\n\npublic function c() {}\n",
        "// config/session.php\n'secure' => true,\n'http_only' => true,\n",
        "// bootstrap/app.php\n->withMiddleware(function (\$m) {\n    \$m->x();\n})\n",
    ];
    foreach ($ok as $c) assert_same('ok', ws_php_snippet($c), $c);
    assert_same('blade', ws_php_snippet("{{-- x --}}\n@vite('a.js')\n"));
    foreach (["<?php\nfoo(;\n", "if (\$a {\n    b();\n}\n", "public function x(\n{\n}\n", "\$a = [1, 2;\n"] as $c) assert_same('fail', ws_php_snippet($c), $c);
});

test('코드 예시: 펜스 블록 줄 번호, Python 은 ast.parse 로 오류를 찾음(실행 없음)', function () {
    $md = "# t\n\n```php\n\$a = 1;\n```\n\n```python\nx = 1\n```\n\n```js\nno\n```\n";
    $b = ws_code_blocks($md, ['php', 'python']);
    assert_same([['lang' => 'php', 'code' => "\$a = 1;\n", 'line' => 3], ['lang' => 'python', 'code' => "x = 1\n", 'line' => 7]], $b);
    $r = ws_python_check(["x = 1\n", "def f(:\n    pass\n", "import os\nos.system('touch /tmp/ws-should-not-run')\n"]);
    if ($r === null) return; // python3 가 없는 환경
    assert_same([1], array_keys($r));
    assert_true(!file_exists('/tmp/ws-should-not-run'), '실행하지 않음');
});
