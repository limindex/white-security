<?php
// 숨은 문자 검사: git 이 추적하는 모든 텍스트 파일(과 파일 이름)에서 보이지 않는 문자를 찾는다.
// zero-width(U+200B–U+200F)·bidi 제어(U+202A–U+202E, U+2066–U+2069, Trojan Source)·보이지 않는 연산자(U+2060–U+2064)·BOM(U+FEFF)
// ·태그 문자(U+E0000–U+E007F)·한글 채움 문자(U+115F, U+1160, U+3164, U+FFA0, 빈 식별자에 쓰임). 한글 음절·자모는 허용.
// 사용: php scripts/check-hidden-chars.php   (걸리면 파일:줄:칸 U+XXXX 를 찍고 종료 코드 1, 문자 자체는 찍지 않는다)
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

const WS_HIDDEN_RE = '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}\x{115F}\x{1160}\x{3164}\x{FFA0}\x{E0000}-\x{E007F}]/u';

/**
 * 한 텍스트에서 숨은 문자 위치. UTF-8 이 아니면 [['line' => 0, ...]] 하나.
 * @return list<array{line:int,col:int,code:string}>
 */
function ws_hidden_scan(string $text): array
{
    if (!mb_check_encoding($text, 'UTF-8')) return [['line' => 0, 'col' => 0, 'code' => 'UTF-8 아님']];
    $hits = [];
    foreach (explode("\n", $text) as $i => $line) {
        if (!preg_match_all(WS_HIDDEN_RE, $line, $m, PREG_OFFSET_CAPTURE)) continue;
        foreach ($m[0] as [$ch, $off]) {
            $hits[] = ['line' => $i + 1, 'col' => mb_strlen(substr($line, 0, $off)) + 1, 'code' => sprintf('U+%04X', mb_ord($ch, 'UTF-8'))];
        }
    }
    return $hits;
}

function ws_hidden_main(): int
{
    $root = ws_root();
    $n = 0; $bad = 0; $skipped = 0;
    foreach (ws_files($root) as $rel) {
        foreach (ws_hidden_scan($rel) as $h) { echo "$rel: 파일 이름에 {$h['code']}\n"; $bad++; }
        $path = $root . '/' . $rel;
        if (is_link($path) || !is_file($path)) continue;
        $bytes = (string)file_get_contents($path);
        if (ws_is_binary($bytes)) { $skipped++; continue; }
        $n++;
        foreach (ws_hidden_scan($bytes) as $h) {
            echo $h['line'] === 0 ? "$rel: {$h['code']}\n" : "$rel:{$h['line']}:{$h['col']} {$h['code']}\n";
            $bad++;
        }
    }
    echo "숨은 문자: 텍스트 파일 {$n}개 검사(바이너리 {$skipped}개 제외), {$bad}건\n";
    return $bad > 0 ? 1 : 0;
}

if (ws_is_main(__FILE__)) exit(ws_hidden_main());
