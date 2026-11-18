<?php
// 버전 올림 검사: 기준 커밋 이후 plugins/white-security/ 아래가 바뀌었으면
// plugin.json 의 version 이 기준보다 커야 하고, CHANGELOG.md 에 "## <그 버전>" 제목이 있어야 한다(버전이 그대로면 사용자에게 업데이트가 가지 않는다).
// 사용: php scripts/check-version-bump.php <기준 커밋>   (예: origin/main, PR 의 base SHA, push 의 before SHA)
// 기준이 비었거나 0000…(첫 push·새 브랜치)이거나 이 저장소에 없으면 건너뛴다(종료 코드 0). 비교는 커밋된 내용(HEAD)끼리 한다.
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

const WS_PLUGIN_DIR = 'plugins/white-security/';
const WS_PLUGIN_JSON = 'plugins/white-security/.claude-plugin/plugin.json';
const WS_SEMVER_RE = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-[0-9A-Za-z.-]+)?$/';

/** plugin.json 내용에서 version. 없거나 형식이 틀리면 null */
function ws_plugin_version(?string $json): ?string
{
    if ($json === null) return null;
    $j = json_decode($json, true);
    return is_array($j) && is_string($j['version'] ?? null) ? $j['version'] : null;
}

/**
 * 판정만(테스트용). $changed 가 비면 통과.
 * @param list<string> $changed 기준 이후 바뀐 플러그인 파일 @return list<string> 오류(없으면 통과)
 */
function ws_version_errors(array $changed, ?string $old, ?string $new, string $changelog): array
{
    if ($changed === []) return [];
    if ($new === null || !preg_match(WS_SEMVER_RE, $new)) return [WS_PLUGIN_JSON . ' 의 version 이 x.y.z 형식이 아닙니다'];
    $err = [];
    if ($old !== null && version_compare($new, $old, '<=')) {
        $err[] = "플러그인 파일이 바뀌었는데 version 이 그대로이거나 낮습니다($old → $new). " . WS_PLUGIN_JSON . ' 의 version 을 올리세요';
    }
    if (!preg_match('/^## ' . preg_quote($new, '/') . '(?:[ \t].*)?$/m', $changelog)) {
        $err[] = "CHANGELOG.md 에 \"## $new\" 제목이 없습니다";
    }
    return $err;
}

function ws_version_main(array $argv): int
{
    $root = ws_root();
    $base = trim($argv[1] ?? '');
    if ($base === '' || preg_match('/^0+$/', $base)) { echo "버전 검사: 기준 커밋 없음(첫 push·새 브랜치) — 건너뜀\n"; return 0; }
    if (str_starts_with($base, '-')) { fwrite(STDERR, "기준 커밋 이름이 올바르지 않습니다\n"); return 2; }
    $baseSha = ws_git($root, 'rev-parse', '--verify', '--quiet', '--end-of-options', $base . '^{commit}');
    $head = ws_git($root, 'rev-parse', '--verify', '--quiet', 'HEAD^{commit}');
    if ($baseSha === null || $head === null) { echo "버전 검사: 기준 커밋을 이 저장소에서 찾을 수 없음 — 건너뜀\n"; return 0; }
    [$baseSha, $head] = [trim($baseSha), trim($head)];
    $diff = ws_git($root, 'diff', '--name-only', '-z', $baseSha, $head, '--', WS_PLUGIN_DIR);
    if ($diff === null) { fwrite(STDERR, "git diff 실패\n"); return 2; }
    $changed = array_values(array_filter(explode("\0", $diff), fn($f) => $f !== ''));
    if ($changed === []) { echo "버전 검사: 플러그인 변경 없음 — 통과\n"; return 0; }
    $old = ws_plugin_version(ws_git($root, 'show', $baseSha . ':' . WS_PLUGIN_JSON));
    $new = ws_plugin_version(ws_git($root, 'show', $head . ':' . WS_PLUGIN_JSON));
    $errors = ws_version_errors($changed, $old, $new, (string)ws_git($root, 'show', $head . ':CHANGELOG.md'));
    echo '버전 검사: 플러그인 파일 ' . count($changed) . '개 변경, version ' . ($old ?? '(없음)') . ' → ' . ($new ?? '(없음)') . "\n";
    foreach ($errors as $e) echo "  오류: $e\n";
    return $errors === [] ? 0 : 1;
}

if (ws_is_main(__FILE__)) exit(ws_version_main($argv));
