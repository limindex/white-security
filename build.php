<?php
// 정적 사이트(dist/)와 스킬 파일(plugins/white-security/skills/review/references, 검토 SKILL.md 라우팅 표, 검토 에이전트 절차)을 만든다. 사용: php build.php [--out=dist] [--base=/white-security/] [--today=YYYY-MM-DD] [--origin=https://도메인]
// --origin 이 없으면 data/site.json 의 origin 을 쓰고, 둘 다 비어 있으면 sitemap.xml·canonical 을 만들지 않는다.
// .well-known/security.txt 는 data/site.json 의 github·security_expires 로 만들고(origin 이 있으면 Canonical 포함), 만료가 지났으면 빌드하지 않는다.
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
// 검토 스킬(REVIEW_DIR): 라우팅 표, references/review.md: 문서 링크 규칙.
$reviewSkillPath = $root . '/' . SkillExport::REVIEW_DIR . '/SKILL.md';
$refDir = $root . '/' . SkillExport::REF_DIR;
$agentPath = $root . '/' . SkillExport::AGENT_FILE;
$reviewPath = $refDir . '/review.md';
try {
    $skillFiles = SkillExport::render($repo);
    foreach ([$reviewSkillPath, $agentPath, $reviewPath] as $need) {
        if (!is_file($need)) throw new SkillExportError(substr($need, strlen($root) + 1) . ' 가 없습니다(손으로 쓰는 파일이고, 빌드는 그 안의 표시 사이만 갱신합니다)');
    }
    $reviewSkillMd = SkillExport::injectRouting((string)file_get_contents($reviewSkillPath), SkillExport::routing($repo));
    // 보고서의 이슈 링크는 data/site.json 의 origin(공개 주소)으로만 만든다(--origin 미리보기 빌드가 플러그인을 바꾸지 않게)
    $siteJson = is_file($root . '/data/site.json') ? json_decode((string)file_get_contents($root . '/data/site.json'), true) : [];
    $reviewMd = SkillExport::injectLinks((string)file_get_contents($reviewPath), SkillExport::links((string)($siteJson['origin'] ?? '')));
    // 검토 에이전트: 신뢰·등급 규칙·라우팅 표·검토 절차를 본문에 넣는다(작업 폴더 밖 SKILL.md·review.md 를 읽지 않아도 되게)
    $agentMd = SkillExport::injectReviewer((string)file_get_contents($agentPath), SkillExport::reviewerBlock($reviewSkillMd, $reviewMd, $repo));
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

// /.well-known/security.txt (RFC 9116): github·security_expires 가 있을 때만. 만료가 지났으면 빌드하지 않는다(1년마다 data/site.json 갱신).
$securityTxt = $site->securityTxt();
if ($securityTxt !== null) {
    // 날짜(UTC)로 비교: 만료일 당일 빌드도 막는다
    if (Site::securityExpires($site->setting('security_expires'))->format('Y-m-d') <= $opt['today']) {
        bail('data/site.json 의 security_expires 가 빌드 날짜 이전이거나 같습니다(만료). 1년 뒤로 갱신하세요');
    }
    $files[Site::SECURITY_TXT] = $securityTxt;
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

$refs = SkillExport::write($repo, $refDir, $skillFiles);
file_put_contents($reviewSkillPath, $reviewSkillMd);
file_put_contents($reviewPath, $reviewMd);
file_put_contents($agentPath, $agentMd);
echo count($files) . "개 쪽과 API, assets " . count($list) . "개를 {$opt['out']} 에 만들었습니다.\n";
if ($origin === '') echo "origin 이 비어 sitemap.xml 은 만들지 않았습니다 (--origin=https://도메인 또는 data/site.json).\n";
if ($securityTxt === null) echo "data/site.json 에 github·security_expires 가 없어 .well-known/security.txt 는 만들지 않았습니다.\n";
echo "스킬 참고 파일 " . count($refs) . "개를 " . SkillExport::REF_DIR . " 에 만들었습니다.\n";
$rdesc = SkillExport::description($reviewSkillMd);
echo '검토 SKILL.md 라우팅 표를 갱신했습니다(추정 약 ' . SkillExport::estimateTokens($reviewSkillMd) . '토큰, description ' . mb_strlen($rdesc) . '자 약 ' . SkillExport::estimateTokens($rdesc) . "토큰).\n";
echo '검토 에이전트 절차 블록을 갱신했습니다(추정 약 ' . SkillExport::estimateTokens($agentMd) . "토큰).\n";
