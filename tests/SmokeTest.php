<?php
// 러너가 실패를 제대로 감지하는지 확인한다 (자식 프로세스로 실패하는 테스트를 돌려 본다).
test('러너가 실패를 감지하고 종료코드 1을 낸다', function () {
    $dir = sys_get_temp_dir() . '/ws-runner-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/BrokenTest.php', "<?php\ntest('일부러 실패하는 테스트', fn() => assert_same(1, 2));\n");
    $out = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/run.php') . ' ' . escapeshellarg($dir) . ' 2>&1', $out, $code);
    unlink($dir . '/BrokenTest.php');
    rmdir($dir);
    assert_same(1, $code, '종료코드');
    assert_contains('일부러 실패하는 테스트', implode("\n", $out));
    assert_contains('1 failed', implode("\n", $out));
});

test('통과하는 테스트만 있으면 종료코드 0이다', function () {
    $dir = sys_get_temp_dir() . '/ws-runner-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/OkTest.php', "<?php\ntest('통과', fn() => assert_true(true));\n");
    $out = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/run.php') . ' ' . escapeshellarg($dir) . ' 2>&1', $out, $code);
    unlink($dir . '/OkTest.php');
    rmdir($dir);
    assert_same(0, $code, '종료코드');
    assert_contains('1 passed', implode("\n", $out));
});

test('대상에 Test 파일이 없거나 경로가 없으면 종료코드 1이다', function () {
    $dir = sys_get_temp_dir() . '/ws-runner-' . bin2hex(random_bytes(4));
    mkdir($dir);
    foreach ([$dir, $dir . '/none'] as $target) {
        $out = [];
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/run.php') . ' ' . escapeshellarg($target) . ' 2>&1', $out, $code);
        assert_same(1, $code, '종료코드');
        assert_contains('테스트 파일이 없습니다', implode("\n", $out));
    }
    rmdir($dir);
});
