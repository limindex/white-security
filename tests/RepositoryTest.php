<?php
require_once __DIR__ . '/support.php';

test('Repository: fixture 로드', function () {
    $repo = Repository::load(make_root());
    assert_same(1, count($repo->all()));
    assert_same([], $repo->errors());
    assert_same('WS-INJ-001', $repo->find('WS-INJ-001')->id);
    assert_same(null, $repo->find('WS-NOPE-999'));
    assert_same(['web'], array_keys($repo->byGroup()));
});

test('Repository: 깨진 파일이 섞여도 나머지는 로드', function () {
    $repo = Repository::load(make_root(['WS-BAD-001-broken.md' => "---\nid: nope\n---\n본문"]));
    assert_same(1, count($repo->all()));
    assert_same(1, count($repo->errors()));
    assert_contains('WS-BAD-001-broken.md', $repo->errors()[0]);
});

test('Repository: 검색 색인은 300자 이하 text', function () {
    $idx = Repository::load(make_root())->searchIndex();
    assert_same(1, count($idx));
    foreach (['id', 'title', 'group', 'severity', 'text'] as $k) assert_true(isset($idx[0][$k]), $k);
    assert_true(mb_strlen($idx[0]['text']) <= 300);
    assert_contains('쿼리에 문자열 연결이 없는가', $idx[0]['text']);
});

test('Repository: issues 폴더가 없어도 빈 저장소', function () {
    $root = sys_get_temp_dir() . '/ws-test-' . bin2hex(random_bytes(4));
    mkdir($root . '/data', 0777, true);
    copy(__DIR__ . '/../data/groups.json', $root . '/data/groups.json');
    assert_same([], Repository::load($root)->all());
});

test('Repository: groups.json 없음·깨짐·항목 누락은 errors() 에 기록', function () {
    $cases = [
        'missing' => null,
        'badjson' => '{깨짐',
        'notlist' => '{"id":"web"}',
        'noname' => '[{"id":"web","order":1}]',
        'noorder' => '[{"id":"web","name":"웹 기본"}]',
        'noid' => '[{"name":"웹 기본","order":1}]',
    ];
    foreach ($cases as $name => $json) {
        $root = make_root();
        if ($json === null) unlink($root . '/data/groups.json');
        else file_put_contents($root . '/data/groups.json', $json);
        $errs = Repository::load($root)->errors();
        assert_true(count($errs) >= 1, "$name: 오류 없음");
        assert_contains('groups.json', $errs[0]);
        foreach ($errs as $e) assert_true(!str_contains($e, $root), "$name: 서버 경로 노출");
    }
});

test('Repository: 정상 groups.json 이면 묶음 오류 없음, 틀린 항목만 빠짐', function () {
    $root = make_root();
    file_put_contents($root . '/data/groups.json', '[{"id":"web","name":"웹 기본","order":5},{"id":"x"}]');
    $repo = Repository::load($root);
    assert_same(['web'], array_column($repo->groups(), 'id'));
    assert_same(1, count($repo->errors()));
    assert_same(1, count($repo->all()));
});

test('묶음 이름·설명: 명사형 공식 이름, 설명은 "~할 때" 없이 명사형', function () {
    $g = json_decode(file_get_contents(__DIR__ . '/../data/groups.json'), true);
    $names = array_column($g, 'name', 'id');
    assert_same([
        'ai-use' => 'AI 활용', 'vibe' => 'AI 코딩 도구', 'fetch' => '외부 데이터 수집·처리', 'scraped' => '자동 수집 대응',
        'web' => '웹 애플리케이션', 'auth' => '인증·인가', 'deploy' => '설정·배포', 'data' => '개인정보 보호',
        'supply' => '소프트웨어 공급망', 'mobile' => '모바일 앱', 'people' => '사용자 보안',
    ], $names);
    foreach ($g as $row) {
        assert_true(is_string($row['when'] ?? null) && trim($row['when']) !== '', "{$row['id']} when");
        assert_true(!preg_match('/(때|요|다)\.?$/u', $row['when']), "{$row['id']} when 명사형: {$row['when']}");
    }
    // ai-use 는 AI API 호출(LLM-003·004)과 답변 활용(LLM-006)까지 포괄
    $ai = array_column($g, 'when', 'id')['ai-use'];
    assert_contains('AI API', $ai);
    assert_contains('답변', $ai);
});

test('실제 이슈: 전부 오류 없이 읽히고 문서 메타가 채워져 있다', function () {
    $repo = Repository::load(__DIR__ . '/..');
    assert_same([], $repo->errors());
    assert_true(count($repo->all()) >= 25, '이슈 수');
    foreach ($repo->all() as $i) {
        assert_true($i->revisions !== [], "{$i->id} revisions");
        assert_true($i->appliesTo !== [], "{$i->id} applies_to");
        assert_true(is_string($i->severityReason) && $i->severityReason !== '', "{$i->id} severity_reason");
        assert_true($i->published <= $i->checked, "{$i->id} published");
    }
});
