<?php
// 정적 사이트(dist/)와 스킬 참고 파일(plugins/white-security/skills/white-security/references, SKILL.md 라우팅 표)을 만든다. 사용: php build.php [--out=dist] [--base=/white-security/] [--today=YYYY-MM-DD] [--origin=https://도메인]
// --origin 이 없으면 data/site.json 의 origin 을 쓰고, 둘 다 비어 있으면 sitemap.xml·canonical 을 만들지 않는다.
// --root 는 테스트용(원본 폴더 지정). 같은 입력이면 같은 출력이 나온다.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/src/bootstrap.php';

function bail(string $msg): never { fwrite(STDERR, "오류: $msg\n"); exit(1); }

$opt = ['out' => 'dist', 'base' => '/white-security/', 'today' => date('Y-m-d'), 'root' => __DIR__, 'origin' => null];
foreach (array_slice($argv, 1) as $a) {
    if (!preg_match('/^--(out|base|today|root|origin)=(.*)$/s', $a, $m)) bail("알 수 없는 옵션 $a");
    $opt[$m[1]] = $m[2];
}
$root = realpath($opt['root']) ?: bail('--root 폴더를 찾을 수 없습니다');
$today = DateTimeImmutable::createFromFormat('!Y-m-d', $opt['today']);
if (!$today || $today->format('Y-m-d') !== $opt['today']) bail('--today 는 YYYY-MM-DD 여야 합니다');
if (!preg_match('#^/[A-Za-z0-9._~/-]*$#', $opt['base'])) bail('--base 는 / 로 시작하는 경로여야 합니다');
if ($opt['origin'] !== null && !Site::validOrigin($opt['origin'])) bail('--origin 은 https://도메인 형식(끝 / 없이)이어야 합니다');

// 출력 폴더: 프로젝트 안이나 시스템 임시 폴더 아래의 "하위" 폴더만 허용
if (trim($opt['out']) === '') bail('--out 이 비어 있습니다');
$out = str_starts_with($opt['out'], '/') ? $opt['out'] : $root . '/' . $opt['out'];
$parts = [];
foreach (explode('/', $out) as $seg) {
    if ($seg === '' || $seg === '.') continue;
    if ($seg === '..') { array_pop($parts); continue; }
    $parts[] = $seg;
}
$out = '/' . implode('/', $parts);
$tmp = realpath(sys_get_temp_dir()) ?: '/tmp';
$under = fn(string $p, string $base) => str_starts_with($p, rtrim($base, '/') . '/');
// 심볼릭 링크로 빠져나가지 못하게, 이미 있는 가장 가까운 상위 폴더를 실경로로 바꿔 비교한다.
$probe = $out;
while (!file_exists($probe) && $probe !== '/') $probe = dirname($probe);
$real = realpath($probe) . substr($out, strlen($probe));
// 프로젝트 안이면 dist(와 그 하위)만, 프로젝트 밖이면 시스템 임시 폴더 하위만 허용한다.
$ok = ($real === $root || $under($real, $root))
    ? ($real === $root . '/dist' || $under($real, $root . '/dist'))
    : $under($real, $tmp);
if (!$ok) bail('--out 은 프로젝트의 dist 폴더 안이나 임시 폴더 아래의 하위 폴더여야 합니다');
if ($real === $root || $under($root, $real)) bail('--out 이 프로젝트 폴더 자신이거나 그 상위 폴더입니다');
// 비어 있지 않은 폴더는 이전 빌드가 남긴 표시 파일이 있을 때만 비운다.
const BUILD_MARK = '.white-security-build';
if (is_dir($real) && count(scandir($real)) > 2 && !is_file($real . '/' . BUILD_MARK)) {
    bail('--out 폴더가 비어 있지 않고 이전 빌드 표시(' . BUILD_MARK . ')가 없어 지우지 않습니다');
}

$repo = Repository::load($root);
if ($repo->errors()) {
    fwrite(STDERR, "원본 오류가 있어 빌드하지 않습니다:\n");
    foreach ($repo->errors() as $e) fwrite(STDERR, "  - $e\n");
    exit(1);
}
// 스킬 파일도 먼저 만들어 본다(실패하면 dist·references 를 건드리지 않는다).
$skillDir = $root . '/' . SkillExport::SKILL_DIR;
$skillMdPath = $skillDir . '/SKILL.md';
try {
    $skillFiles = SkillExport::render($repo);
    if (!is_file($skillMdPath)) throw new SkillExportError(SkillExport::SKILL_DIR . '/SKILL.md 가 없습니다(스킬 본체는 손으로 쓰는 파일이고, 빌드는 그 안의 라우팅 표만 갱신합니다)');
    $skillMd = SkillExport::injectRouting((string)file_get_contents($skillMdPath), SkillExport::routing($repo));
} catch (SkillExportError $e) {
    bail($e->getMessage());
}
$site = new Site($repo, $opt['base'], 'static', $today, $opt['origin']);
$origin = $site->setting('origin');

// 경로 목록 (정렬해 같은 순서로)
$paths = ['', ...array_keys($site->pages()), 'api/issues.json', 'api/search.json', 'api/coverage.json'];
foreach ($repo->all() as $i) { $paths[] = 'issues/' . $i->id; $paths[] = 'api/issues/' . $i->id . '.json'; }
sort($paths);

function rrmdir(string $d): void
{
    if (!is_dir($d)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($d);
}
function put(string $path, string $body): void
{
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    file_put_contents($path, $body);
}

// 먼저 전부 렌더해 보고(실패하면 dist 를 건드리지 않는다), 그다음 비우고 쓴다.
$files = [];
foreach ($paths as $p) {
    $r = $site->route($p);
    if ($r['status'] !== 200) bail("$p 가 {$r['status']} 입니다");
    $files[str_ends_with($p, '.json') ? $p : ($p === '' ? 'index.html' : $p . '/index.html')] = $r['body'];
}
$files['404.html'] = $site->route('__not-found__')['body'];

// sitemap.xml: 공개 주소(origin)가 있을 때만. 쪽만 넣고 API·404 는 뺀다. 이슈는 최종 확인일, 나머지는 빌드 날짜.
if ($origin !== '') {
    $checked = [];
    foreach ($repo->all() as $i) $checked['issues/' . $i->id] = $i->checked->format('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($paths as $p) {
        if (str_starts_with($p, 'api/')) continue;
        $loc = htmlspecialchars($origin . $site->url($p), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= "  <url><loc>$loc</loc><lastmod>" . ($checked[$p] ?? $opt['today']) . "</lastmod></url>\n";
    }
    $files['sitemap.xml'] = $xml . "</urlset>\n";
}

rrmdir($out);
foreach ($files as $rel => $body) put($out . '/' . $rel, $body);
// GitHub Pages 가 _ 로 시작하는 파일을 처리하지 않게 한다
put($out . '/.nojekyll', '');
put($out . '/' . BUILD_MARK, "생성 폴더: 다음 빌드 때 비워집니다\n");
$assets = __DIR__ . '/assets';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assets, FilesystemIterator::SKIP_DOTS));
$list = [];
foreach ($it as $f) if ($f->isFile()) $list[] = $f->getPathname();
sort($list);
foreach ($list as $f) put($out . '/assets/' . substr($f, strlen($assets) + 1), (string)file_get_contents($f));

$refs = SkillExport::write($repo, $skillDir . '/references', $skillFiles);
file_put_contents($skillMdPath, $skillMd);
echo count($files) . "개 쪽과 API, assets " . count($list) . "개를 {$opt['out']} 에 만들었습니다.\n";
if ($origin === '') echo "origin 이 비어 sitemap.xml 은 만들지 않았습니다 (--origin=https://도메인 또는 data/site.json).\n";
echo "스킬 참고 파일 " . count($refs) . "개를 " . SkillExport::SKILL_DIR . "/references 에 만들었습니다.\n";
$desc = SkillExport::description($skillMd);
echo 'SKILL.md 라우팅 표를 갱신했습니다(추정 약 ' . SkillExport::estimateTokens($skillMd) . '토큰, description ' . mb_strlen($desc) . '자 약 ' . SkillExport::estimateTokens($desc) . "토큰).\n";
