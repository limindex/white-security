<?php
// 단일 진입점. 경로는 ?p= (없으면 PATH_INFO)에서만 읽고, 파일 경로를 만드는 데 쓰지 않는다.
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';

$path = $_GET['p'] ?? ($_SERVER['PATH_INFO'] ?? '');
if (!is_string($path)) $path = '';
$site = new Site(Repository::load(__DIR__), '/white-security/', 'live');
$r = $site->route($path);
http_response_code($r['status']);
header($r['type'] === 'json' ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: text/html; charset=utf-8');
echo $r['body'];
