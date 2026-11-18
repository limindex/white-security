<?php
require_once __DIR__ . '/support.php';

const BT_PHP = PHP_BINARY;

function bt_issue(string $id, string $slug, string $group, string $extra = '', bool $siteOnly = false): string
{
    $langs = $siteOnly ? '[]' : '[python]';
    $code = $siteOnly ? '' : "\n## 코드 예시\n\n### python\n\n✗ 취약 코드\n\n```python\nbad()\n```\n\n✓ 안전 코드\n\n```python\ngood()\n```\n";
    return "---\nid: $id\ntitle: 제목 $slug\nslug: $slug\ngroup: $group\nseverity: medium\nsite_only: " . ($siteOnly ? 'true' : 'false')
        . "\ndepth: brief\nlanguages: $langs\nmaps:\n  owasp2025: [A05]\nchecked: 2026-11-18\nsources:\n  - title: t\n    url: https://example.org/x\n    year: 2025\n---\n\n"
        . "## 요약\n\n요약 $slug.\n\n## 점검 항목\n\n- 항목 $slug\n$code$extra";
}

/** 임시 루트에 build.php 를 실행한다. @return array{0:int,1:string,2:string} 종료코드·출력·루트 */
function bt_build(array $extra = [], string $args = ''): array
{
    $root = make_root($extra);
    mkdir($root . '/issues/people', 0777, true);
    file_put_contents($root . '/issues/people/WS-PPL-001-people.md', bt_issue('WS-PPL-001', 'people', 'people', '', true));
    $cmd = escapeshellarg(BT_PHP) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root)
        . ' --out=dist --today=2026-11-18 ' . $args . ' 2>&1';
    exec($cmd, $lines, $code);
    return [$code, implode("\n", $lines), $root];
}

test('build: 쪽·API·assets·404 가 만들어진다', function () {
    [$code, $out, $root] = bt_build();
    assert_same(0, $code, $out);
    foreach (['index.html', 'issues/WS-INJ-001/index.html', 'issues/WS-PPL-001/index.html', 'coverage/index.html', '404.html',
        'api/issues.json', 'api/search.json', 'api/coverage.json', 'api/issues/WS-INJ-001.json', 'assets/css/site.css', 'assets/js/site.js'] as $f) {
        assert_true(is_file("$root/dist/$f"), $f);
    }
    assert_true(is_array(json_decode(file_get_contents("$root/dist/api/issues.json"), true)), 'issues.json');
});

test('build: dist 안 링크가 전부 dist 안 파일로 풀린다', function () {
    [$code, $out, $root] = bt_build();
    assert_same(0, $code, $out);
    $dist = "$root/dist";
    $n = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dist, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!str_ends_with($f->getPathname(), '.html')) continue;
        $html = file_get_contents($f->getPathname());
        preg_match_all('/\b(?:href|src|data-search)="([^"]*)"/', $html, $m);
        foreach ($m[1] as $u) {
            if (preg_match('#^(https?:|mailto:|\#)#', $u)) continue;
            $u = html_entity_decode($u);
            assert_true(str_starts_with($u, '/white-security/'), "base 밖 링크 $u");
            $p = rawurldecode(explode('#', substr($u, strlen('/white-security/')))[0]);
            $ok = $p === '' || is_file("$dist/$p") || is_file("$dist/" . rtrim($p, '/') . '/index.html');
            assert_true($ok, "깨진 링크 $u (" . basename($f->getPathname()) . ')');
            $n++;
        }
        assert_true(!str_contains($html, $root), '서버 경로 노출');
        assert_true(!str_contains($html, '원본 오류'), '오류 띠 노출');
    }
    assert_true($n > 20, "링크 $n 개");
});

test('build: 스킬 references — 플러그인 폴더에 묶음별 INDEX·이슈 파일, site_only 제외, 전체 INDEX 없음', function () {
    [$code, $out, $root] = bt_build();
    assert_same(0, $code, $out);
    $ref = "$root/" . SkillExport::SKILL_DIR . '/references';
    assert_true(!is_dir("$root/skills"), '옛 위치에 만들지 않음');
    $f = file_get_contents("$ref/web/WS-INJ-001.md");
    assert_true(str_starts_with($f, SkillExport::HEADER), '머리 주석');
    foreach (['## 점검 항목', '## 탐지 규칙'] as $s) assert_contains($s, $f);
    foreach (['## 발생 상황', '## 요약', '## 위험 배경', '## 대응 방법', '## 코드 예시'] as $s) assert_true(!str_contains($f, $s), $s);
    assert_contains('cur.execute("SELECT * FROM u WHERE id=%s", (uid,))', file_get_contents("$ref/web/WS-INJ-001.code.python.md"));
    assert_true(!is_file("$ref/people/WS-PPL-001.md") && !is_dir("$ref/people"), 'site_only 제외');
    assert_true(is_file("$ref/web/INDEX.md") && !is_file("$ref/INDEX.md"), '묶음 INDEX 만');
    assert_contains('plugins/white-security/skills/white-security/references', $out);
    assert_contains('SKILL.md 라우팅 표를 갱신했습니다', $out);
    assert_contains('description', $out); // 항상 로드되는 설명의 길이·토큰 추정을 알린다
});

test('build: 같은 입력이면 출력이 바이트까지 같다', function () {
    [$c1, $o1, $root] = bt_build();
    assert_same(0, $c1, $o1);
    $hash = function (string $root): array {
        $h = [];
        foreach ([$root . '/dist', $root . '/plugins'] as $base) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) if ($f->isFile()) $h[substr($f->getPathname(), strlen($root))] = md5_file($f->getPathname());
        }
        ksort($h);
        return $h;
    };
    $a = $hash($root);
    exec(escapeshellarg(BT_PHP) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root) . ' --out=dist --today=2026-11-18 2>&1', $l, $c2);
    assert_same(0, $c2);
    assert_same($a, $hash($root));
});

test('build: 원본 오류가 있으면 종료코드 1 이고 dist 를 만들지 않는다', function () {
    [$code, $out, $root] = bt_build(['WS-BAD-001-bad.md' => "---\nid: nope\n---\n"]);
    assert_same(1, $code);
    assert_contains('오류', $out);
    assert_true(!is_dir("$root/dist"), 'dist 없음');
});

test('build: 위험한 out 은 거부하고 지우지 않는다', function () {
    foreach (['/', '..', '/etc', '../..', '.', '', 'src', 'issues', 'lib', '.superpowers', '_notes', 'assets'] as $o) {
        $root = make_root();
        file_put_contents("$root/keep.txt", 'x');
        exec(escapeshellarg(BT_PHP) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root) . ' --out=' . escapeshellarg($o) . ' 2>&1', $l, $c);
        assert_same(1, $c, "out=$o");
        assert_true(is_file("$root/keep.txt"), "out=$o 파일 보존");
    }
});

test('build: 확인 안 된 표준 항목은 적용 범위에 안내가 나오고 JSON 에 verified 가 실린다', function () {
    [$code, $out, $root] = bt_build();
    assert_same(0, $code, $out);
    $cov = file_get_contents("$root/dist/coverage/index.html");
    assert_contains('일부 항목 출처 확인 중', $cov);
    $j = json_decode(file_get_contents("$root/dist/api/coverage.json"), true);
    $by = [];
    foreach ($j['frameworks'] as $fw) {
        $by[$fw['id']] = $fw;
        foreach ($fw['items'] as $it) assert_true(is_bool($it['verified']), 'verified bool');
    }
    assert_same(false, $by['agentic2026']['items'][0]['verified']);
    assert_same(true, $by['owasp2025']['items'][0]['verified']);
});

test('build: 표시 없는 비어 있지 않은 임시 폴더와 링크로 우회한 out 은 거부', function () {
    $root = make_root();
    mkdir("$root/lib"); file_put_contents("$root/lib/keep.txt", 'x');
    mkdir("$root/src"); file_put_contents("$root/src/keep.txt", 'x');
    $dir = sys_get_temp_dir() . '/ws-out-' . bin2hex(random_bytes(4));
    mkdir($dir); file_put_contents("$dir/sentinel.txt", 'x');
    $link = sys_get_temp_dir() . '/ws-link-' . bin2hex(random_bytes(4));
    symlink("$root/src", $link);
    foreach ([$dir => "$dir/sentinel.txt", $link => "$root/src/keep.txt"] as $o => $sentinel) {
        exec(escapeshellarg(BT_PHP) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root) . ' --out=' . escapeshellarg($o) . ' 2>&1', $l, $c);
        assert_same(1, $c, $o);
        assert_true(is_file($sentinel), "보존 $o");
    }
    assert_true(is_file("$root/lib/keep.txt"));
    // 표시가 생긴 뒤에는 같은 폴더를 다시 빌드할 수 있다
    $fresh = sys_get_temp_dir() . '/ws-out-' . bin2hex(random_bytes(4));
    foreach ([1, 2] as $n) {
        exec(escapeshellarg(BT_PHP) . ' ' . escapeshellarg(__DIR__ . '/../build.php') . ' --root=' . escapeshellarg($root) . ' --out=' . escapeshellarg($fresh) . ' 2>&1', $l, $c);
        assert_same(0, $c, "빌드 $n");
    }
});

test('SkillExport: 사라진 이슈의 생성 파일만 지우고 직접 둔 파일은 남긴다', function () {
    $root = make_root();
    $ref = "$root/refs";
    SkillExport::write(Repository::load($root), $ref);
    assert_true(is_file("$ref/web/WS-INJ-001.md"));
    mkdir("$ref/old"); file_put_contents("$ref/old/WS-OLD-001.md", SkillExport::HEADER . "# old\n");
    file_put_contents("$ref/web/NOTES.md", "직접 쓴 글\n");
    unlink("$root/issues/web/WS-INJ-001-sql-injection.md");
    SkillExport::write(Repository::load($root), $ref);
    assert_true(!is_file("$ref/web/WS-INJ-001.md"), '사라진 이슈 파일');
    assert_true(!is_dir("$ref/old"), '빈 폴더');
    assert_true(is_file("$ref/web/NOTES.md"), '직접 쓴 파일');
    assert_true(!is_file("$ref/web/INDEX.md"), '이슈가 없어진 묶음의 INDEX');
});

test('SkillExport: groups.json 에 when 이 없으면 INDEX 는 기본 문장을 쓴다', function () {
    $root = make_root();
    file_put_contents($root . '/data/groups.json', '[{"id":"web","name":"웹 기본","order":5,"route":"sql"},{"id":"auth","name":"로그인·권한","order":6,"when":"  "}]');
    $ref = "$root/" . SkillExport::SKILL_DIR . '/references';
    SkillExport::write(Repository::load($root), $ref);
    $idx = file_get_contents("$ref/web/INDEX.md");
    assert_contains("When: 「웹 기본」 상황에서 코드를 쓰거나 고칠 때\n", $idx);
    assert_same(null, Repository::load($root)->groups()[1]['when']);
});

test('build: origin 이 있으면 sitemap.xml(절대 주소, 쪽만), 없으면 만들지 않음', function () {
    [$code, $out, $root] = bt_build();
    assert_same(0, $code, $out);
    assert_true(!is_file("$root/dist/sitemap.xml"), 'origin 없으면 sitemap 없음');
    assert_contains('sitemap.xml', $out);
    foreach (['start', 'severity', 'guide', 'sources', 'coverage'] as $p) assert_true(is_file("$root/dist/$p/index.html"), $p);
    foreach (['check', 'glossary'] as $p) assert_true(!file_exists("$root/dist/$p"), "꺼진 페이지는 내보내지 않음: $p");
    [$code, $out, $root] = bt_build([], '--origin=https://docs.example.org');
    assert_same(0, $code, $out);
    $xml = file_get_contents("$root/dist/sitemap.xml");
    assert_true(str_starts_with($xml, '<?xml version="1.0" encoding="UTF-8"?>'), 'xml 머리');
    assert_contains('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
    assert_contains('<loc>https://docs.example.org/white-security/</loc>', $xml);
    assert_contains('<loc>https://docs.example.org/white-security/severity</loc>', $xml);
    assert_contains('<loc>https://docs.example.org/white-security/issues/WS-INJ-001</loc>', $xml);
    assert_contains('<lastmod>2026-11-18</lastmod>', $xml);
    foreach (['/check<', '/glossary<'] as $p) assert_true(!str_contains($xml, $p), "sitemap 에 꺼진 페이지 없음: $p");
    assert_true(!str_contains($xml, 'api/'), 'API 제외');
    assert_true(!str_contains($xml, '404'), '404 제외');
    $html = file_get_contents("$root/dist/severity/index.html");
    assert_contains('<link rel="canonical" href="https://docs.example.org/white-security/severity">', $html);
    [$code, $out] = bt_build([], '--origin=http://bad');
    assert_same(1, $code, 'http origin 거부');
});
