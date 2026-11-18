<?php
// 단일 진입점. 경로는 REQUEST_URI 의 base 뒤 부분에서 읽고(?p= 는 폴백), 파일 경로를 만드는 데 쓰지 않는다.
declare(strict_types=1);
ini_set('display_errors', '0');

const WS_BASE = '/white-security/';

try {
    require __DIR__ . '/src/bootstrap.php';

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = Site::pathFromRequest(is_string($uri) ? $uri : '', WS_BASE);
    if ($path === null || $path === 'index.php') {
        $p = $_GET['p'] ?? ($_SERVER['PATH_INFO'] ?? '');
        $path = is_string($p) ? $p : '';
    }
    $site = new Site(Repository::load(__DIR__), WS_BASE, 'live');
    $r = $site->route($path);
    http_response_code($r['status']);
    header($r['type'] === 'json' ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: text/html; charset=utf-8');
    echo $r['body'];
} catch (Throwable $e) {
    // 원인은 서버 로그에만 남기고, 화면에는 고정 문구만 보낸다.
    error_log('white-security: ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $home = htmlspecialchars(WS_BASE, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>일시적인 오류 — White Security</title><link rel="stylesheet" href="' . $home . 'assets/css/site.css"></head>'
        . '<body class="ws-fatal"><main class="ws-fatal-box"><h1>일시적인 오류</h1>'
        . '<p>페이지를 그리지 못했습니다. 잠시 뒤 다시 열어 주세요.</p>'
        . '<p><a class="ws-btn" href="' . $home . '">홈으로</a></p></main></body></html>';
}
