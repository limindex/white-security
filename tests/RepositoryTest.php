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
