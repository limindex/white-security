<?php
// index.php 를 임시 사본에서 자식 프로세스로 돌려 본다 (실제 issues/ 는 건드리지 않음).

/** 프로젝트를 임시 폴더로 복사하고 fixture 를 issues/ 에 넣는다. */
function idx_copy(): string
{
    $src = realpath(__DIR__ . '/..');
    $dst = sys_get_temp_dir() . '/ws-idx-' . bin2hex(random_bytes(4));
    mkdir($dst);
    foreach (['index.php'] as $f) copy("$src/$f", "$dst/$f");
    foreach (['src', 'lib', 'data', 'templates'] as $d) {
        mkdir("$dst/$d");
        foreach (glob("$src/$d/*") as $f) if (is_file($f)) copy($f, "$dst/$d/" . basename($f));
    }
    mkdir("$dst/issues/web", 0777, true);
    foreach (glob(__DIR__ . '/fixtures/issues/web/*.md') as $f) copy($f, "$dst/issues/web/" . basename($f));
    return $dst;
}

/** REQUEST_URI·$_GET 을 넣고 index.php 를 실행한 출력 */
function idx_run(string $root, string $uri, array $get = []): string
{
    $boot = $root . '/_boot.php';
    file_put_contents($boot, '<?php $_SERVER["REQUEST_URI"] = ' . var_export($uri, true) . '; $_GET = ' . var_export($get, true) . '; require ' . var_export($root . '/index.php', true) . ';');
    $out = [];
    // display_errors=1 로 띄워도 index.php 가 스스로 끄는지 본다. 로그는 파일로(화면 출력과 분리).
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -d log_errors=1 -d error_log=' . escapeshellarg($root . '/error.log') . ' ' . escapeshellarg($boot) . ' 2>&1', $out);
    return implode("\n", $out);
}

test('index.php: REQUEST_URI 로 경로를 정하고 ?p= 로 덮어쓸 수 없음', function () {
    $root = idx_copy();
    $out = idx_run($root, '/white-security/issues/WS-INJ-001?p=about', ['p' => 'about']);
    assert_contains('SQL 삽입', $out);
    assert_contains('class="ws-doc"', $out);
});

test('index.php: base 밖이거나 index.php 직접이면 ?p= 로 폴백', function () {
    $root = idx_copy();
    assert_contains('SQL 삽입', idx_run($root, '/elsewhere', ['p' => 'issues/WS-INJ-001']));
    assert_contains('SQL 삽입', idx_run($root, '/white-security/index.php?p=issues/WS-INJ-001', ['p' => 'issues/WS-INJ-001']));
    assert_contains('White Security', idx_run($root, '/white-security/index.php'));
});

test('index.php: 예외는 고정 500 페이지, 경로·메시지 노출 없음', function () {
    $root = idx_copy();
    file_put_contents($root . '/templates/home.php', '<?php throw new RuntimeException("zz-secret-message " . __FILE__);');
    $out = idx_run($root, '/white-security/');
    assert_contains('일시적인 오류', $out);
    assert_true(!str_contains($out, 'zz-secret-message'), '메시지 노출');
    assert_true(!str_contains($out, $root), '경로 노출');
    assert_true(!str_contains($out, 'Fatal'), 'PHP 오류 출력');
    assert_contains('zz-secret-message', (string)@file_get_contents($root . '/error.log'));
});
