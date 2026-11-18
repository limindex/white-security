<?php
// 의존성 없는 테스트 러너. 사용: php tests/run.php [디렉터리|파일]
$GLOBALS['__tests'] = [];
function test(string $name, callable $fn): void { $GLOBALS['__tests'][] = [$name, $fn]; }
function assert_true($cond, string $msg = ''): void {
    if (!$cond) throw new Exception($msg !== '' ? $msg : '참이어야 합니다');
}
function assert_same($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new Exception(($msg !== '' ? $msg . ': ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function assert_contains(string $needle, string $haystack): void {
    if (!str_contains($haystack, $needle)) throw new Exception("'$needle' 이(가) 없습니다");
}

$target = $argv[1] ?? __DIR__;
$files = is_file($target) ? [$target] : (is_dir($target) ? glob(rtrim($target, '/') . '/*Test.php') : []);
if (!$files) {
    fwrite(STDERR, "테스트 파일이 없습니다: $target\n");
    echo "0 passed, 0 failed (테스트 파일이 없습니다: $target)\n";
    exit(1);
}
foreach ($files as $f) require $f;

$pass = 0; $fail = 0;
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try { $fn(); $pass++; echo "  ok   $name\n"; }
    catch (Throwable $e) { $fail++; echo "  FAIL $name\n       " . $e->getMessage() . "\n"; }
}
echo "$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
