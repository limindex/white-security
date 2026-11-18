<?php
require_once __DIR__ . '/../src/bootstrap.php';

test('머리말 예시 전체를 파싱한다', function () {
    $raw = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    [$fm, $body] = FrontMatter::split($raw);
    assert_same('WS-INJ-001', $fm['id']);
    assert_same(false, $fm['site_only']);
    assert_same(['python', 'php'], $fm['languages']);
    assert_same(['CWE-89'], $fm['maps']['cwe']);
    assert_same([], $fm['maps']['api2023']);
    assert_same(['입력데이터 검증 및 표현-1'], $fm['maps']['kr_guide']);
    assert_same('2026-11-18', $fm['checked']);
    assert_same(2025, $fm['sources'][0]['year']);
    assert_same('https://owasp.org/Top10/', $fm['sources'][0]['url']);
    assert_same(2, count($fm['sources']));
    assert_true(str_starts_with(ltrim($body), '## 한 줄 요약'));
});

test('블록 목록·따옴표·주석', function () {
    $raw = "---\na: \"x: y # z\"  # 주석\nb:\n  - one\n  - 'two'\nc: 3\nd: null\n---\n본문";
    [$fm, $body] = FrontMatter::split($raw);
    assert_same('x: y # z', $fm['a']);
    assert_same(['one', 'two'], $fm['b']);
    assert_same(3, $fm['c']);
    assert_same(null, $fm['d']);
    assert_same('본문', $body);
});

test('탭 들여쓰기는 줄 번호와 함께 오류', function () {
    $raw = "---\nid: X\nmaps:\n\tcwe: [A]\n---\n본문";
    try { FrontMatter::split($raw); throw new Exception('오류가 나야 함'); }
    catch (FrontMatterError $e) { assert_contains('4', $e->getMessage()); assert_contains('탭', $e->getMessage()); }
});

test('지원하지 않는 문법과 닫히지 않은 머리말은 오류', function () {
    foreach (["---\na: {b: 1}\n---\n", "---\na: 1\n", "본문만 있음", "---\na: [1, 2\n---\n"] as $raw) {
        try { FrontMatter::split($raw); throw new Exception('오류가 나야 함: ' . $raw); }
        catch (FrontMatterError $e) { assert_true(true); }
    }
});
