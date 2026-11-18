<?php
// 테스트용 임시 저장소 루트를 만든다. 실제 issues/ 는 건드리지 않는다.
require_once __DIR__ . '/../src/bootstrap.php';
function make_root(array $extra = []): string
{
    $root = sys_get_temp_dir() . '/ws-test-' . bin2hex(random_bytes(4));
    mkdir($root . '/data', 0777, true);
    mkdir($root . '/issues/web', 0777, true);
    copy(__DIR__ . '/../data/groups.json', $root . '/data/groups.json');
    mkdir($root . '/data/frameworks', 0777, true);
    foreach (glob(__DIR__ . '/../data/frameworks/*.json') as $f) copy($f, $root . '/data/frameworks/' . basename($f));
    foreach (glob(__DIR__ . '/fixtures/issues/web/*.md') as $f) copy($f, $root . '/issues/web/' . basename($f));
    foreach ($extra as $name => $content) file_put_contents($root . '/issues/web/' . $name, $content);
    return $root;
}
