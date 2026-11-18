<?php
require_once __DIR__ . '/support.php';

const ST_KEYS = ['python', 'fastapi', 'django', 'flask', 'js', 'ts', 'nextjs', 'react', 'express', 'php', 'laravel', 'supabase', 'firebase', 'react-native', 'expo', 'flutter', 'kotlin', 'swift', 'shell', 'sql', 'json', 'yaml', 'toml', 'dockerfile', 'nginx'];

/** 가짜 이슈: 코드 예시 키 목록 $keys 로 만든 원본 */
function st_issue(array $keys, string $id = 'WS-INJ-009', string $slug = 'stack-fixture'): string
{
    $code = '';
    foreach ($keys as $k) $code .= "\n### $k\n\n✗ 취약 코드\n\n```\nbad_$k()\n```\n\n✓ 안전 코드\n\n```\ngood_$k()\n```\n";
    $raw = str_replace(['id: WS-INJ-001', 'slug: sql-injection'], ["id: $id", "slug: $slug"], file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md'));
    $raw = preg_replace('/^languages:.*$/m', 'languages: [' . implode(', ', $keys) . ']', $raw);
    return preg_replace('/\n## 코드 예시\n.*\z/s', "\n## 코드 예시\n$code", $raw);
}

function st_error(array $keys): string
{
    $f = sys_get_temp_dir() . '/ws-' . bin2hex(random_bytes(4)) . '-stack.md';
    file_put_contents($f, st_issue($keys));
    try { Issue::fromFile($f, json_decode(file_get_contents(__DIR__ . '/../data/groups.json'), true)); }
    catch (IssueError $e) { unlink($f); return $e->getMessage(); }
    unlink($f);
    throw new Exception('IssueError 가 나야 함');
}

test('stacks.json: 허용 키 25개, 각 항목 key·label·lang·stack, stack 은 groups 에 있음', function () {
    $errs = [];
    $d = Stacks::loadFile(__DIR__ . '/../data/stacks.json', $errs);
    assert_same([], $errs);
    assert_same(ST_KEYS, array_column($d['stacks'], 'key'));
    $groups = array_column($d['groups'], 'id');
    foreach ($d['stacks'] as $s) {
        assert_true($s['label'] !== '' && $s['lang'] !== '', $s['key']);
        assert_true(in_array($s['stack'], $groups, true), $s['key'] . ' stack');
    }
    assert_same('Laravel', Stacks::label('laravel'));
    assert_same('Next.js', Stacks::label('nextjs'));
    assert_same('typescript', Stacks::lang('nextjs'));
    assert_same('dart', Stacks::lang('flutter'));
    assert_same('모르는키', Stacks::label('모르는키'));
});

test('stacks.json 검증: 깨진 항목·중복·없는 stack 은 오류로 남기고 그 항목만 뺀다', function () {
    $f = sys_get_temp_dir() . '/ws-stacks-' . bin2hex(random_bytes(4)) . '.json';
    file_put_contents($f, json_encode(['groups' => [['id' => 'a', 'name' => 'A']], 'stacks' => [
        ['key' => 'ok', 'label' => 'OK', 'lang' => 'php', 'stack' => 'a'],
        ['key' => 'ok', 'label' => 'dup', 'lang' => 'php', 'stack' => 'a'],
        ['key' => 'Bad Key', 'label' => 'x', 'lang' => 'php', 'stack' => 'a'],
        ['key' => 'orphan', 'label' => 'x', 'lang' => 'php', 'stack' => 'zz'],
        ['key' => 'nolang', 'label' => 'x', 'stack' => 'a'],
    ]]));
    $errs = [];
    $d = Stacks::loadFile($f, $errs);
    unlink($f);
    assert_same(['ok'], array_column($d['stacks'], 'key'));
    assert_same(4, count($errs));
    assert_contains('data/stacks.json', $errs[0]);
    $errs = [];
    Stacks::loadFile('/nonexistent/stacks.json', $errs);
    assert_same(1, count($errs));
});

test('Issue: 허용 키(새 스택 포함)는 languages 와 ### 제목으로 쓸 수 있다', function () {
    $f = sys_get_temp_dir() . '/ws-' . bin2hex(random_bytes(4)) . '-ok.md';
    file_put_contents($f, st_issue(ST_KEYS));
    $i = Issue::fromFile($f, json_decode(file_get_contents(__DIR__ . '/../data/groups.json'), true));
    unlink($f);
    assert_same(ST_KEYS, $i->languages);
    assert_same(ST_KEYS, array_keys($i->code));
});

test('Issue: 모르는 코드 키는 파일 이름과 함께 오류', function () {
    $m = st_error(['python', 'cobol']);
    assert_contains('-stack.md', $m);
    assert_contains('cobol', $m);
});

test('Issue: ### 제목에만 모르는 키가 있어도 오류(languages 와 일치해도 허용 목록 밖이면 거부)', function () {
    assert_contains('Python', st_error(['Python']));
});

test('maps: owasp_mobile2024 키와 기준 파일 — M1~M10, 공식 출처 주소, 확인 못 한 항목 없음', function () {
    assert_true(in_array('owasp_mobile2024', Issue::MAP_KEYS, true));
    $errs = [];
    $fws = array_column(Coverage::loadFrameworks(__DIR__ . '/..', $errs), null, 'id');
    assert_same([], $errs);
    $fw = $fws['owasp_mobile2024'];
    assert_same(2024, $fw['year']);
    assert_contains('owasp.org/www-project-mobile-top-10', $fw['url']);
    assert_same(['M1', 'M2', 'M3', 'M4', 'M5', 'M6', 'M7', 'M8', 'M9', 'M10'], array_column($fw['items'], 'id'));
    assert_same('Improper Credential Usage', $fw['items'][0]['title']);
    assert_same('Insufficient Cryptography', $fw['items'][9]['title']);
    assert_true(mb_strlen($fw['about']) <= 140 && str_contains($fw['about'], ' — '), 'about 풀이');
    assert_same('OWASP Mobile 2024', Site::mapLabel('owasp_mobile2024'));
});

test('mobile 묶음: groups.json 에 있고(이슈 0개) 메뉴·홈·스킬 라우팅에는 안 보인다', function () {
    $groups = array_column(json_decode(file_get_contents(__DIR__ . '/../data/groups.json'), true), null, 'id');
    $m = $groups['mobile'];
    assert_same(false, $m['site_only']);
    assert_contains('React Native·Expo·Flutter', $m['when']);
    assert_true(preg_match('/^[\x20-\x7E]+$/', $m['route']) === 1, 'route 는 영문');
    assert_true($m['order'] > $groups['supply']['order'] && $m['order'] < $groups['people']['order'], '순서');
    $orders = array_column($groups, 'order');
    assert_same(count($orders), count(array_unique($orders)), '순서 중복 없음');
    $root = make_root();
    $repo = Repository::load($root);
    assert_same([], $repo->errors());
    $s = new Site($repo, '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    assert_true(!str_contains($s->route('')['body'], '모바일 앱'), '홈·메뉴에 안 보임');
    assert_true(!isset(SkillExport::render($repo)['mobile/INDEX.md']), '스킬에 안 들어감');
});

test('이슈 화면: 코드 탭 이름은 label(Laravel·Next.js), 탭 7개도 모두 탭·패널로 나온다', function () {
    $keys = ['python', 'fastapi', 'ts', 'nextjs', 'php', 'laravel', 'supabase'];
    $root = make_root(['WS-INJ-009-stack-fixture.md' => st_issue($keys)]);
    $s = new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    $b = $s->route('issues/WS-INJ-009')['body'];
    assert_same(7, preg_match_all('#role="tab"#', $b));
    assert_same(7, preg_match_all('#role="tabpanel"#', $b));
    foreach (['Laravel', 'Next.js', 'FastAPI', 'TypeScript', 'Supabase'] as $l) assert_true((bool)preg_match('#<span class="ws-tx">' . preg_quote($l, '#') . '</span></button>#', $b), $l);
    assert_contains('data-lang="nextjs"', $b);
    assert_contains('class="lang-typescript"', $b);
    assert_contains('class="lang-php"', $b);
});

test('SkillExport: 코드 파일 <ID>.code.<key>.md(하이픈 키 포함), 코드 목록 줄에 label, INDEX 머리에 스택 목록', function () {
    $keys = ['python', 'react-native', 'laravel'];
    $root = make_root(['WS-INJ-009-stack-fixture.md' => st_issue($keys)]);
    $files = SkillExport::render(Repository::load($root));
    foreach ($keys as $k) assert_true(isset($files["web/WS-INJ-009.code.$k.md"]), $k);
    $main = $files['web/WS-INJ-009.md'];
    assert_contains('`WS-INJ-009.code.laravel.md` (Laravel)', $main);
    assert_contains('`WS-INJ-009.code.react-native.md` (React Native)', $main);
    $idx = $files['web/INDEX.md'];
    assert_true((bool)preg_match('/^Stacks: .*Laravel.*$/m', $idx), 'INDEX 스택 줄');
    assert_contains('React Native', $idx);
    assert_same(1, preg_match_all('/^Stacks: /m', $idx), '한 줄');
    assert_true(SkillExport::estimateTokens($idx) <= SkillExport::INDEX_MAX_TOKENS);
    assert_contains('# WS-INJ-009 코드 예시 — Laravel', $files['web/WS-INJ-009.code.laravel.md']);
});

test('지원 스택 블록: 이슈가 있는 스택만, 소개·설치 페이지 아래, "예정"·"준비 중" 없음', function () {
    $root = make_root(['WS-INJ-009-stack-fixture.md' => st_issue(['python', 'php', 'laravel'])]);
    $s2 = new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    foreach (['about', 'install'] as $p) {
        $b = $s2->route($p)['body'];
        assert_contains('지원 스택', $b);
        assert_true((bool)preg_match('#class="ws-sec ws-stacks".*Laravel#su', $b), "$p Laravel");
        foreach (['FastAPI', 'Supabase', 'Flutter', 'Expo', '예정', '준비 중'] as $w) assert_true(!str_contains($b, $w), "$p: $w 없음");
        assert_true(strpos($b, 'ws-stacks') > strpos($b, '<h2>'), "$p 아래");
    }
    $stacks = $s2->supportedStacks();
    assert_same(['Python', 'PHP'], array_column($stacks, 'name'));
    assert_same(['Laravel'], $stacks[1]['items']);
    // 이슈가 하나도 없으면 블록 자체를 그리지 않는다
    $none = make_root();
    foreach (glob($none . '/issues/web/*.md') as $f) unlink($f);
    $empty = new Site(Repository::load($none), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    assert_same([], $empty->supportedStacks());
    assert_true(!str_contains($empty->route('about')['body'], 'ws-stacks'), '빈 블록 없음');
});
