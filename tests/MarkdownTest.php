<?php
require_once __DIR__ . '/../src/bootstrap.php';

test('스크립트 태그는 이스케이프된다', function () {
    $h = Markdown::render('<script>alert(1)</script>');
    assert_true(!str_contains($h, '<script>'), $h);
    assert_contains('&lt;script&gt;', $h);
});
test('img onerror 가 태그 속성으로 남지 않는다', function () {
    $h = Markdown::render('<img src=x onerror=alert(1)>');
    assert_true(!str_contains($h, '<img'), $h);
    assert_contains('&lt;img', $h);
});
test('javascript: 링크는 무력화된다', function () {
    $h = Markdown::render('[x](javascript:alert(1))');
    assert_true(!str_contains($h, 'href="javascript:'), $h);
});
test('코드 블록은 lang-x 클래스와 이스케이프를 유지한다', function () {
    $h = Markdown::render("```php\n\$a = '<b>' . \$x;\n```");
    assert_contains('<pre><code class="lang-php">', $h);
    assert_contains('&lt;b&gt;', $h);
    assert_true(!str_contains($h, 'language-'), $h);
});
test('기본 마크다운', function () {
    $h = Markdown::render("## 제목\n\n- 가\n- 나\n\n**굵게**");
    assert_contains('<h2>제목</h2>', $h);
    assert_contains('<li>가</li>', $h);
    assert_contains('<strong>굵게</strong>', $h);
});
