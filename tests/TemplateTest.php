<?php
require_once __DIR__ . '/support.php';

/** fixture 하나(checked 2026-11-18)로 만든 사이트. $today 로 '오늘'을 고정한다. */
function tpl_site(?string $today = '2026-11-18', array $extra = []): Site
{
    return new Site(Repository::load(make_root($extra)), '/white-security/', 'live', $today === null ? null : new DateTimeImmutable($today));
}

/** @return list<string> 모든 화면 경로 */
function tpl_paths(): array
{
    return ['', 'issues/WS-INJ-001', ...array_keys(tpl_site()->pages()), 'nope'];
}

test('이슈 화면: 심각도 배지·id·기준 번호·언어 탭', function () {
    $b = tpl_site()->route('issues/WS-INJ-001')['body'];
    assert_contains('class="ws-badge ws-badge--high"', $b);
    assert_contains('WS-INJ-001', $b);
    assert_contains('CWE-89', $b);
    assert_contains('data-lang="php"', $b);
    assert_contains('data-lang="python"', $b);
    assert_contains('위험', $b);
});

test('이슈 화면: 3단 구조(nav.ws-nav · article.ws-doc · aside.ws-code)', function () {
    $b = tpl_site()->route('issues/WS-INJ-001')['body'];
    assert_true((bool)preg_match('/<nav[^>]*class="ws-nav"/', $b), 'nav.ws-nav');
    assert_true((bool)preg_match('/<article[^>]*class="ws-doc"/', $b), 'article.ws-doc');
    assert_true((bool)preg_match('/<aside[^>]*class="ws-code"/', $b), 'aside.ws-code');
    assert_true((bool)preg_match('/<input[^>]*type="search"/', $b), '검색 입력');
    assert_contains('data-search="/white-security/api/search.json"', $b);
});

test('이슈 화면: 언어마다 ✗/✓ 코드 블록과 복사 버튼, 코드는 이스케이프', function () {
    $b = tpl_site()->route('issues/WS-INJ-001')['body'];
    assert_same(2, substr_count($b, '✗ 취약 코드'), '✗ 블록 수');
    assert_same(2, substr_count($b, '✓ 안전 코드'), '✓ 블록 수');
    assert_same(4, substr_count($b, 'data-copy'), '복사 버튼 수');
    assert_true((bool)preg_match('#<aside[^>]*class="ws-code".*?<h2 id="code-h">코드 예시</h2>#s', $b), '코드 칸 제목');
    assert_contains('취약 코드 복사', $b);
    assert_contains('안전 코드 복사', $b);
    assert_contains('$db-&gt;query(&quot;SELECT * FROM u WHERE id=&quot; . $id);', $b);
    assert_contains('## 주석처럼 보이는 줄', $b);
    assert_true(!str_contains($b, '$db->query('), '이스케이프 안 된 코드');
});

test('이슈 화면: 본문 절·출처·최종 확인일, 현재 이슈 강조', function () {
    $b = tpl_site()->route('issues/WS-INJ-001')['body'];
    foreach (['발생 상황', '위험 배경', '대응 방법', '점검 항목', '탐지 규칙', '출처', '최종 확인'] as $h) assert_contains($h, $b);
    assert_contains('2026-11-18', $b);
    assert_contains('href="https://cwe.mitre.org/data/definitions/89.html"', $b);
    assert_true((bool)preg_match('#<a[^>]*href="/white-security/issues/WS-INJ-001"[^>]*aria-current="page"#', $b), '현재 이슈 aria-current');
});

test('홈: 약속 문구·시작점(스킬 설치)·묶음 색인·최근 개정', function () {
    $b = tpl_site()->route('')['body'];
    assert_contains('AI 코딩 보안 점검 에이전트', $b);
    assert_contains('흔한 실수를 크게 줄', $b);
    assert_contains('보안 감사를 대신하지 않', $b);
    assert_true(!preg_match('/완벽|보장/u', $b), '과장 문구');
    assert_true((bool)preg_match('#<a[^>]*href="/white-security/install"[^>]*>.*?스킬 설치#su', $b), '스킬 설치 시작점');
    assert_true((bool)preg_match('#<li id="grp-web">.*?웹 애플리케이션#su', $b), '묶음 색인 앵커');
    assert_contains('최근 개정', $b);
    assert_true((bool)preg_match('#class="ws-recent".*WS-INJ-001.*본문 고정 제목 변경#s', $b), '최근 개정에 fixture 와 변경 요지');
});

test('홈: 최근 개정은 이슈마다 최신 개정 1건, 최대 5건, 날짜 최신순', function () {
    $extra = [];
    foreach (range(2, 8) as $n) {
        $raw = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
        $raw = str_replace(['WS-INJ-001', '- date: 2026-11-18'], [sprintf('WS-INJ-%03d', $n), sprintf('- date: 2026-10-%02d', $n)], $raw);
        $extra[sprintf('WS-INJ-%03d-x.md', $n)] = $raw;
    }
    $b = tpl_site('2026-11-18', $extra)->route('')['body'];
    assert_true((bool)preg_match('#<ol class="ws-recent">(.*?)</ol>#s', $b, $m), 'ws-recent 목록');
    preg_match_all('#WS-INJ-\d{3}#', $m[1], $ids);
    assert_same(['WS-INJ-001', 'WS-INJ-008', 'WS-INJ-007', 'WS-INJ-006', 'WS-INJ-005'], array_values(array_unique($ids[0])));
    assert_same(5, substr_count($m[1], '<li>'), '5건');
});

test('180일 재확인 폐기: 오래돼도 "확인 필요" 배지·다음 검토 예정일 없음, 최종 확인일만 표시', function () {
    foreach (['2026-11-18', '2027-05-18', '2030-01-01'] as $today) {
        $s = tpl_site($today);
        foreach (['', 'issues/WS-INJ-001', 'guide', 'about'] as $p) {
            $b = $s->route($p)['body'];
            foreach (['ws-badge--stale', '확인 필요', '다음 검토', '180일'] as $w) assert_true(!str_contains($b, $w), "$today $p: $w");
        }
        assert_contains('최종 확인', $s->route('issues/WS-INJ-001')['body']);
    }
    assert_true(!method_exists('Site', 'isStale') && !method_exists('Site', 'nextReview') && !defined('Site::STALE_DAYS'), '계산 코드 제거');
});

test('모든 화면: href/src 는 base · # · https:// 로만', function () {
    $s = tpl_site();
    foreach (tpl_paths() as $p) {
        $b = $s->route($p)['body'];
        preg_match_all('/\s(?:href|src)="([^"]*)"/', $b, $m);
        assert_true(count($m[1]) > 0, "$p 링크 없음");
        foreach ($m[1] as $u) {
            assert_true(str_starts_with($u, '/white-security/') || str_starts_with($u, '#') || str_starts_with($u, 'https://'), "$p: $u");
        }
    }
});

test('모든 화면: 인라인 style 속성 없음, script 는 src 있는 것만', function () {
    $s = tpl_site();
    foreach (tpl_paths() as $p) {
        $b = $s->route($p)['body'];
        assert_true(!str_contains($b, 'style="'), "$p style=");
        assert_true(!preg_match('/<style/i', $b), "$p <style>");
        assert_true(!preg_match('/<script(?![^>]*\bsrc=)/i', $b), "$p 인라인 script");
        assert_true(!preg_match('/\son[a-z]+=/i', $b), "$p on* 속성");
        assert_true(!preg_match('/완벽|보장/u', $b), "$p 과장 문구");
        assert_contains('<script src="/white-security/assets/js/site.js"', $b);
        assert_contains('href="/white-security/assets/css/site.css"', $b);
    }
});

test('site.css: 한쪽 색 띠(border-left 등) 없음', function () {
    $css = file_get_contents(__DIR__ . '/../assets/css/site.css');
    assert_true($css !== false && $css !== '', 'site.css 있음');
    foreach (['border-left', 'border-inline-start', 'border-right', 'border-inline-end'] as $w) assert_true(!str_contains($css, $w), $w);
    assert_true(!preg_match('/https?:\/\//', preg_replace('#/\*.*?\*/#s', '', $css)), '외부 URL 없음');
    assert_contains('text-box', $css);
});

test('글꼴·자산 파일과 라이선스 존재', function () {
    $css = file_get_contents(__DIR__ . '/../assets/css/site.css');
    preg_match_all('/url\("?([^")]+)"?\)/', $css, $m);
    assert_true(count($m[1]) > 0, '@font-face url');
    foreach ($m[1] as $u) assert_true(is_file(__DIR__ . '/../assets/css/' . $u), "자산 없음: $u");
    assert_true(count(glob(__DIR__ . '/../assets/fonts/*LICENSE*') ?: []) + count(glob(__DIR__ . '/../assets/fonts/OFL*') ?: []) > 0, '글꼴 라이선스');
});

test('site.js: localStorage 만 쓰고 쿠키·외부 요청 없음', function () {
    $js = file_get_contents(__DIR__ . '/../assets/js/site.js');
    assert_true($js !== false && $js !== '', 'site.js 있음');
    assert_contains('localStorage', $js);
    assert_true(!str_contains($js, 'document.cookie'), '쿠키');
    assert_true(!preg_match('#https?://#', $js), '외부 주소');
    assert_true(!preg_match('/\beval\s*\(|new Function/', $js), 'eval');
});

test('적용 범위: 빈 표라도 표 구조를 그린다', function () {
    $b = tpl_site()->route('coverage')['body'];
    assert_contains('<table', $b);
    assert_contains('<thead', $b);
});

test('오류 띠: live 에서 groups.json 문제도 보인다', function () {
    $root = make_root();
    file_put_contents($root . '/data/groups.json', '{깨짐');
    $b = (new Site(Repository::load($root), '/white-security/', 'live'))->route('')['body'];
    assert_contains('data/groups.json', $b);
    assert_true(!str_contains($b, $root), '서버 경로 노출');
});

test('이슈 화면: 게시일·최종 확인일·적용 대상·심각도 근거·개정 이력', function () {
    $b = tpl_site()->route('issues/WS-INJ-001')['body'];
    assert_true((bool)preg_match('#<article[^>]*class="ws-doc".*게시.*2026-09-01.*최종 확인.*2026-11-18.*</article>#s', $b), '게시일·최종 확인일');
    assert_true(!str_contains($b, '다음 검토'), '다음 검토 예정일 폐기');
    assert_contains('적용 대상', $b);
    assert_contains('모든 웹 애플리케이션', $b);
    assert_contains('심각도 근거', $b);
    assert_contains('입력 한 줄로 악용 가능', $b);
    assert_true((bool)preg_match('#개정 이력.*2026-11-18.*본문 고정 제목 변경.*2026-09-01.*최초 작성#s', $b), '개정 이력 최신순');
});
