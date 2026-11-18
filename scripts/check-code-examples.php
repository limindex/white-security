<?php
// 코드 예시 문법 검사: issues/**/*.md 의 ```php·```python 블록이 문법상 읽히는지 본다(실행하지 않는다).
// PHP: PHP 파서(token_get_all + TOKEN_PARSE)로. <?php 로 시작하는 블록은 그대로 엄격히. 그 밖은 문서용 조각으로 보고
//      빈 줄로 나눈 덩어리마다 "파일·클래스 본문·메서드 체인(->…)·배열 항목(설정 파일)" 중 하나로 읽히면 통과(여러 파일을 한 블록에 보인 예시).
//      Blade 템플릿({{ }}·@지시어)은 PHP 가 아니므로 건너뛴다.
// Python: python3 -I 의 ast.parse 로(실행 없음). python3 가 없으면 Python 은 건너뛴다.
// 사용: php scripts/check-code-examples.php   (걸리면 파일:줄 [언어] 이유, 종료 코드 1)
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

/**
 * 마크다운의 펜스 코드 블록(언어·코드·여는 펜스 줄 번호).
 * @param list<string> $langs @return list<array{lang:string,code:string,line:int}>
 */
function ws_code_blocks(string $md, array $langs): array
{
    $out = [];
    $re = '/^```(' . implode('|', array_map(fn($l) => preg_quote($l, '/'), $langs)) . ')[ \t]*\n(.*?)^```[ \t]*$/ms';
    preg_match_all($re, $md, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($m as $b) $out[] = ['lang' => $b[1][0], 'code' => $b[2][0], 'line' => substr_count($md, "\n", 0, $b[0][1]) + 1];
    return $out;
}

/** PHP 파서로 읽히는지(실행하지 않음) */
function ws_php_parses(string $src): bool
{
    try { token_get_all($src, TOKEN_PARSE); return true; } catch (ParseError) { return false; }
}

/** 조각 한 덩어리: 파일·클래스 본문·메서드 체인·배열 항목 중 하나로 읽히면 참 */
function ws_php_piece_ok(string $s): bool
{
    foreach (["<?php\n$s\n", "<?php\nclass WsSnippet {\n$s\n}\n", "<?php\n\$ws\n$s\n;\n", "<?php\nreturn [\n$s\n];\n"] as $src) {
        if (ws_php_parses($src)) return true;
    }
    return false;
}

/** Blade 템플릿 조각인지 */
function ws_is_blade(string $code): bool
{
    return !str_contains($code, '<?php') && (bool)preg_match('/\{\{|\{!!|^\s*@(?:vite|csrf|method|if|elseif|else|endif|foreach|endforeach|extends|section|endsection|yield|include|auth|guest|php)\b/m', $code);
}

/** PHP 예시 판정: 'ok'·'blade'(건너뜀)·'fail' */
function ws_php_snippet(string $code): string
{
    if (ws_is_blade($code)) return 'blade';
    if (preg_match('/\A\s*<\?php\b/', $code)) return ws_php_parses($code) ? 'ok' : 'fail';
    // 빈 줄로 나눈 덩어리를 앞에서부터 쌓다가 읽히면 끊는다(빈 줄이 든 클래스·함수도 한 덩어리로 모인다)
    $acc = '';
    foreach (preg_split('/\n[ \t]*\n/', $code) as $para) {
        $acc = $acc === '' ? $para : "$acc\n\n$para";
        if (ws_php_piece_ok($acc)) $acc = '';
    }
    return trim($acc) === '' ? 'ok' : 'fail';
}

/**
 * Python 예시를 한 번에 ast.parse(실행 없음). python3 가 없으면 null.
 * @param list<string> $codes @return array<int,string>|null 실패한 번호 => 이유
 */
function ws_python_check(array $codes): ?array
{
    if ($codes === []) return [];
    $py = 'import ast,json,sys' . "\n" . 'r={}' . "\n" . 'for i,c in enumerate(json.load(sys.stdin)):' . "\n"
        . '  try: ast.parse(c)' . "\n" . '  except SyntaxError as e: r[i]="%s (%s줄)"%(e.msg,e.lineno)' . "\n" . 'print(json.dumps(r))';
    $p = @proc_open(['python3', '-I', '-c', $py], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    if (!is_resource($p)) return null;
    fwrite($pipes[0], json_encode($codes, JSON_UNESCAPED_UNICODE));
    fclose($pipes[0]);
    $out = (string)stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    if (proc_close($p) !== 0) return null;
    $r = json_decode($out, true);
    return is_array($r) ? $r : null;
}

function ws_code_main(): int
{
    $root = ws_root();
    $php = $blade = 0; $fails = []; $py = [];
    foreach (ws_files($root) as $rel) {
        if (!preg_match('#^issues/.+\.md$#', $rel)) continue;
        foreach (ws_code_blocks((string)file_get_contents($root . '/' . $rel), ['php', 'python']) as $b) {
            if ($b['lang'] === 'python') { $py[] = $b + ['file' => $rel]; continue; }
            $r = ws_php_snippet($b['code']);
            if ($r === 'blade') { $blade++; continue; }
            $php++;
            if ($r === 'fail') $fails[] = "{$rel}:{$b['line']} [php] PHP 문법으로 읽히지 않음(조각이면 빈 줄로 나눈 덩어리마다 파일·클래스 본문·메서드 체인·배열 항목이어야 함)";
        }
    }
    $res = ws_python_check(array_column($py, 'code'));
    if ($res === null) echo "python3 를 실행하지 못해 Python 예시 " . count($py) . "개는 건너뜀\n";
    else foreach ($res as $i => $why) $fails[] = "{$py[$i]['file']}:{$py[$i]['line']} [python] $why";
    foreach ($fails as $f) echo "$f\n";
    echo "코드 예시: PHP {$php}개(Blade {$blade}개 제외)·Python " . ($res === null ? 0 : count($py)) . '개 검사, ' . count($fails) . "건\n";
    return $fails === [] ? 0 : 1;
}

if (ws_is_main(__FILE__)) exit(ws_code_main());
