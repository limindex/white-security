<?php
// 금지어 검사(로컬 전용, push 전에 실행): php scripts/check-forbidden.php [--list=<목록 파일>] [--range=<커밋 범위>]
// 목록은 저장소에 올리지 않는 scripts/forbidden.txt(.gitignore)에만 둔다. 한 줄에 낱말 하나, 빈 줄·# 줄은 무시, 대소문자 무시.
// `!` 로 시작하는 줄은 허용 문구: 그 문구를 지운 뒤 금지어를 찾는다(예: 공개 주소처럼 금지어를 포함한 정해진 표기 하나만 허용).
// 검사 대상: git 이 추적하는 파일과 무시되지 않은 새 파일(내용·파일 이름), 그리고 범위 안 커밋의 메시지·작성자·커미터(기본: HEAD 까지 전체 이력).
// 출력은 "파일:줄 f***" 처럼 낱말 첫 글자만 남기고 가린다. 목록 내용·걸린 줄은 어디에도 찍지 않는다.
// 목록 파일이 없으면(CI 등) 아무 내용 없이 건너뛴다(종료 코드 0). 걸리면 종료 코드 1.
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

/** 목록 파일 → 소문자 낱말 목록. 읽을 수 없으면 null. @return list<string>|null */
function ws_forbidden_load(string $file): ?array
{
    if (!is_file($file) || !is_readable($file)) return null;
    $words = [];
    foreach (preg_split('/\R/u', (string)file_get_contents($file)) as $line) {
        $w = trim($line);
        if ($w === '' || str_starts_with($w, '#')) continue;
        $words[] = mb_strtolower($w, 'UTF-8');
    }
    return array_values(array_unique($words));
}

/** 가린 표시: 첫 글자 + *** (길이도 드러내지 않는다) */
function ws_forbidden_mask(string $word): string { return mb_substr($word, 0, 1, 'UTF-8') . '***'; }

/** 찍기 전에 파일 이름 속 금지어도 가린다(경로를 찍다가 낱말이 새지 않게) @param list<string> $words */
function ws_forbidden_redact(string $s, array $words): string
{
    foreach ($words as $w) if (!str_starts_with($w, '!')) $s = (string)preg_replace('/' . preg_quote($w, '/') . '/iu', ws_forbidden_mask($w), $s);
    return $s;
}

/**
 * 텍스트에서 걸린 줄 번호와 가린 낱말.
 * @param list<string> $words @return list<array{line:int,mask:string}>
 */
function ws_forbidden_hits(string $text, array $words): array
{
    $hits = [];
    $allow = [];
    foreach ($words as $w) if (str_starts_with($w, '!') && strlen($w) > 1) $allow[] = substr($w, 1);
    foreach (explode("\n", $text) as $i => $line) {
        $low = str_replace($allow, ' ', mb_strtolower($line, 'UTF-8'));
        foreach ($words as $w) if (!str_starts_with($w, '!') && str_contains($low, $w)) $hits[] = ['line' => $i + 1, 'mask' => ws_forbidden_mask($w)];
    }
    return $hits;
}

function ws_forbidden_main(array $argv): int
{
    $root = ws_root();
    $opt = ['list' => $root . '/scripts/forbidden.txt', 'range' => 'HEAD'];
    foreach (array_slice($argv, 1) as $a) {
        if (!preg_match('/^--(list|range)=(.+)$/s', $a, $m)) { fwrite(STDERR, "알 수 없는 옵션\n"); return 2; }
        $opt[$m[1]] = $m[2];
    }
    $words = ws_forbidden_load($opt['list']);
    if ($words === null) { echo "금지어 검사: 로컬 목록이 없어 건너뜀(push 전에 로컬에서 실행)\n"; return 0; }
    if ($words === []) { echo "금지어 검사: 목록이 비어 있음 — 건너뜀\n"; return 0; }
    $listReal = realpath($opt['list']);
    $bad = 0; $n = 0;
    foreach (ws_files($root, true) as $rel) {
        $path = $root . '/' . $rel;
        if (realpath($path) === $listReal) continue;
        $shown = ws_forbidden_redact($rel, $words);
        foreach (ws_forbidden_hits($rel, $words) as $h) { echo "$shown: 파일 이름 {$h['mask']}\n"; $bad++; }
        if (is_link($path) || !is_file($path)) continue;
        $bytes = (string)file_get_contents($path);
        if (ws_is_binary($bytes)) continue;
        $n++;
        foreach (ws_forbidden_hits($bytes, $words) as $h) { echo "$shown:{$h['line']} {$h['mask']}\n"; $bad++; }
    }
    // 커밋 메시지·작성자·커미터(이름·이메일)도 push 되면 공개된다
    if (str_starts_with($opt['range'], '-')) { fwrite(STDERR, "범위 이름이 올바르지 않습니다\n"); return 2; }
    $log = ws_git($root, 'log', '--format=%H%x00%an <%ae>%n%cn <%ce>%n%B%x1e', '--end-of-options', $opt['range']);
    $commits = 0;
    foreach ($log === null ? [] : explode("\x1e", $log) as $rec) {
        $rec = ltrim($rec, "\n");
        if ($rec === '' || !str_contains($rec, "\0")) continue;
        [$sha, $body] = explode("\0", $rec, 2);
        $commits++;
        $seen = [];
        foreach (ws_forbidden_hits($body, $words) as $h) {
            if (isset($seen[$h['mask']])) continue;
            $seen[$h['mask']] = true;
            echo 'commit ' . substr($sha, 0, 10) . ": {$h['mask']}\n";
            $bad++;
        }
    }
    if ($log === null) echo "  (커밋 범위를 읽지 못해 커밋 검사는 건너뜀)\n";
    echo "금지어 검사: 파일 {$n}개·커밋 {$commits}개, {$bad}건\n";
    return $bad > 0 ? 1 : 0;
}

if (ws_is_main(__FILE__)) exit(ws_forbidden_main($argv));
