<?php
require_once __DIR__ . '/support.php';

/** 점검 기준 대응표: 기준별 미터 막대(대응 n / 전체 m) → 기준별 표(기본 접힘) */
function cp_body(): string
{
    return (new Site(Repository::load(make_root()), '/white-security/', 'live', new DateTimeImmutable('2026-11-18')))->route('coverage')['body'];
}

test('대응표: 기준마다 미터 한 줄, 막대는 수치를 담은 role=img', function () {
    $b = cp_body();
    $n = count(Repository::load(make_root())->frameworks());
    assert_true((bool)preg_match('#<ol class="ws-meters"[^>]*>(.*?)</ol>#s', $b, $m), '미터 목록');
    assert_same($n, preg_match_all('#<li class="ws-meter">#', $m[1]), '기준 수만큼');
    assert_same($n, preg_match_all('#<span class="ws-meter-bar" role="img" aria-label="[^"]+: 항목 \d+개 중 대응 \d+, 문서만\(스킬 점검 없음\) \d+, 빈칸 \d+">#u', $m[1]), '막대 이름');
    assert_true((bool)preg_match('#ws-meter-bar.*?OWASP Top 10.*?대응 1,#su', $m[1]), 'fixture 대응(A05) 1건');
    assert_true((bool)preg_match('#<span class="ws-meter-fill ws-meter-fill--covered ws-w-\d+"></span>#', $m[1]), '덮음 막대 폭 클래스');
    assert_true(!str_contains($m[1], 'style='), 'style 속성 없음');
    assert_true((bool)preg_match('#<a href="\#fw-owasp2025">#', $m[1]), '표로 가는 링크');
});

test('대응표: 범례(대응·문서만·빈칸)와 전체 요약 줄(구분선은 CSS, <i> 없음), 국내 기준 풀이', function () {
    $b = cp_body();
    assert_true((bool)preg_match('#<ul class="ws-legend"[^>]*>.*?>대응<.*?문서만\(스킬 점검 없음\).*?빈칸.*?</ul>#su', $b), '범례');
    assert_true((bool)preg_match('#class="ws-cov-line ws-seps">(.*?)</p>#su', $b, $m), '요약 줄');
    assert_true(!str_contains($m[1], '<i>'), '요약 줄 구분선은 CSS');
    assert_contains(Site::KR_NOTE, nw_strip($b));
    foreach (['덮음', '사이트 전용'] as $w) assert_true(!str_contains(strip_tags($b), $w), "옛 용어 없음: $w");
});

test('대응표: 국내 기준(행안부·주통기)이 맨 위, 이름은 짧은 이름', function () {
    $b = cp_body();
    preg_match_all('#<li class="ws-meter">\s*<span class="ws-meter-name"><a href="\#fw-([a-z_0-9]+)">([^<]+)</a>#u', $b, $m);
    assert_same(['kr_guide', 'kr_ciip'], array_slice($m[1], 0, 2));
    assert_same(['행안부 구현단계 보안약점 49개', '주통기 웹 취약점 21개'], array_slice($m[2], 0, 2));
    $j = json_decode((new Site(Repository::load(make_root()), '/white-security/', 'live'))->route('api/coverage.json')['body'], true);
    assert_same(['kr_guide', 'kr_ciip'], array_slice(array_column($j['frameworks'], 'id'), 0, 2));
});

test('site.css: 접기 막대 글자(펼치기·개수)는 한 줄', function () {
    $css = file_get_contents(__DIR__ . '/../assets/css/site.css');
    foreach (['.ws-fold-count {', '.ws-fold-hint {'] as $sel) {
        assert_true((bool)preg_match('#' . preg_quote($sel, '#') . '[^}]*white-space: nowrap#', $css), $sel);
    }
});

test('대응표: 기준별 표는 기본 접힘 details, 원문 대조를 마친 기준엔 확인 중 표시 없음', function () {
    $b = cp_body();
    assert_true((bool)preg_match('#<section class="ws-sec ws-fold" id="fw-owasp2025">\s*<details>\s*<summary>#', $b), '접힘 표');
    assert_true(!str_contains($b, '<details open>'), '기본 접힘');
    assert_true(!str_contains($b, '일부 항목 출처 확인 중') && !str_contains($b, '원문 대조 전 항목 포함'), '모든 기준 원문 대조 완료 → 확인 중 표시 없음(미확인 표시는 BuildTest 에서 임시 사본으로 시험)');
    assert_true(substr_count($b, '<table') >= 8, '표 8개');
});

test('site.css: 대응표 색은 토큰만, 빨강 계열 없음', function () {
    $css = file_get_contents(__DIR__ . '/../assets/css/site.css');
    preg_match_all('#\.ws-meter[^{]*\{([^}]*)\}#', $css, $m);
    $rules = implode("\n", $m[1]);
    assert_true(!preg_match('/#[0-9a-f]{3,6}\b|oklch\(/i', $rules), '미터 규칙에 날 색값 없음');
    assert_true(!str_contains($rules, '--high'), '위험 빨강 미사용');
});
