<?php
// scripts/ 공용 도우미: git 을 셸 없이 실행, 추적 파일 목록, 바이너리 판별. CLI 전용.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/** 저장소 루트(scripts/ 의 상위) */
function ws_root(): string { return dirname(__DIR__); }

/**
 * git 을 셸 없이 실행한다(인자 배열 → 따옴표·변수 펼침 없음). 실패하면 null.
 * 한글 경로가 8진 이스케이프로 바뀌지 않게 quotePath 를 끄고, 저장소 설정의 fsmonitor 명령이 돌지 않게 막는다.
 */
function ws_git(string $root, string ...$args): ?string
{
    $cmd = ['git', '-C', $root, '-c', 'core.quotePath=false', '-c', 'core.fsmonitor=false', ...$args];
    $p = proc_open($cmd, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    if (!is_resource($p)) return null;
    $out = (string)stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    return proc_close($p) === 0 ? $out : null;
}

/**
 * git 이 추적하는 파일(상대 경로). $untracked 면 무시되지 않은 새 파일(곧 커밋될 수 있는 것)도 넣는다.
 * @return list<string>
 */
function ws_files(string $root, bool $untracked = false): array
{
    $out = ws_git($root, 'ls-files', '-z');
    if ($out === null) throw new RuntimeException('git ls-files 실패: git 저장소 안에서 실행하세요');
    if ($untracked) $out .= (string)ws_git($root, 'ls-files', '-z', '--others', '--exclude-standard');
    $files = array_values(array_unique(array_filter(explode("\0", $out), fn($f) => $f !== '')));
    sort($files);
    return $files;
}

/** NUL 바이트가 있으면 바이너리(글꼴·이미지)로 본다 */
function ws_is_binary(string $bytes): bool { return str_contains($bytes, "\0"); }

/** 직접 실행됐는지(테스트가 require 할 때는 main 을 돌리지 않는다) */
function ws_is_main(string $file): bool
{
    $argv0 = $_SERVER['argv'][0] ?? '';
    return $argv0 !== '' && realpath($argv0) === realpath($file);
}
