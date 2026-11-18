<?php
require_once __DIR__ . '/support.php';

/** 이슈 화면: 머리 · 절 앵커 · 프롬프트 카드 · 체크 상자 · 접힘 · 목차 · 인쇄 머리 */
function ip_site(): Site
{
    $raw = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $raw = str_replace('WS-INJ-001', 'WS-INJ-002', $raw);
    $raw = str_replace("값은 항상 바인딩으로 넘긴다.\n", "값은 항상 바인딩으로 넘긴다.\n\n```text\n쿼리에 <값>을 이어 붙인 곳을 찾아 줘.\n```\n\n- 결과 확인 문장.\n", $raw);
    $raw = str_replace("- 쿼리에 문자열 연결이 없는가\n", "- 쿼리에 문자열 연결이 없어야 한다\n- `ORDER BY` 값은 허용 목록이어야 한다\n- 오류 문장을 화면에 그대로 보이지 않아야 한다\n", $raw);
    return new Site(Repository::load(make_root(['WS-INJ-002-x.md' => $raw])), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
}

function ip_body(): string { return ip_site()->route('issues/WS-INJ-002')['body']; }

test('이슈 머리: 문서 번호·유형 라벨(묶음) → 제목 → 심각도+근거 → 문서 정보 → 기준 번호 순서', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<header class="ws-doc-head">(.*?)</header>#s', $b, $m), '머리');
    $h = $m[1];
    $order = ['class="ws-docid"', '<h1', 'class="ws-sevline"', 'class="ws-docinfo"', 'class="ws-maps"'];
    $last = -1;
    foreach ($order as $k) { $p = strpos($h, $k); assert_true($p !== false && $p > $last, "순서 $k"); $last = $p; }
    assert_true((bool)preg_match('#class="ws-docid">.*?WS-INJ-002.*?웹 애플리케이션.*?상세 문서#s', $h), '문서 번호·묶음·깊이');
    assert_true((bool)preg_match('#class="ws-sevline">.*?ws-badge--high.*?입력 한 줄로 악용 가능#s', $h), '심각도 근거 한 줄');
    foreach (['게시', '최종 확인', '다음 검토 예정', '적용 대상'] as $k) assert_contains('<dt>' . $k . '</dt>', $h);
});

test('이슈 절: 영구 링크 앵커(고정 id), 요약은 강조 상자', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<section class="ws-summary" id="summary"#', $b), '요약 상자');
    foreach (['situation' => '발생 상황', 'background' => '위험 배경', 'response' => '대응 방법', 'checklist' => '점검 항목'] as $id => $h) {
        assert_true((bool)preg_match('#<h2 id="' . $id . '">' . $h . '</h2>\s*<a class="ws-anchor" href="\#' . $id . '" aria-label="' . $h . ' 절 주소 복사"#u', $b), "앵커 $id");
    }
    foreach (['detection', 'sources', 'revisions'] as $id) assert_contains('<a class="ws-anchor" href="#' . $id . '"', $b);
});

test('탐지 규칙·출처·개정 이력은 기본 접힘 details (JS 없이 동작)', function () {
    $b = ip_body();
    foreach (['detection' => '탐지 규칙', 'sources' => '출처', 'revisions' => '개정 이력'] as $id => $h) {
        assert_true((bool)preg_match('#<section class="ws-sec ws-fold" id="' . $id . '"[^>]*>\s*<details>\s*<summary>\s*<h2>' . $h . '</h2>#u', $b), "접힘 $id");
    }
    assert_true(!preg_match('#<details open>#', $b), '기본 접힘');
    assert_true((bool)preg_match('#id="detection".*?쿼리 조립 지점#s', $b), '탐지 규칙 본문 유지');
});

test('대응 방법 코드 블록은 복사 버튼 있는 프롬프트 카드', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#id="response".*?<figure class="ws-block ws-block--prompt"><figcaption><span class="ws-block-label">프롬프트</span><button type="button" class="ws-copy" data-copy aria-label="프롬프트 복사">#s', $b), '프롬프트 카드');
    assert_contains('쿼리에 &lt;값&gt;을 이어 붙인 곳을 찾아 줘.', $b);
});

test('점검 항목: 실제 체크 상자, 이슈별 저장 키, 진행 표시', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<ul class="ws-checklist" data-check="WS-INJ-002">(.*?)</ul>#s', $b, $m), '점검 목록');
    assert_same(3, preg_match_all('#<input type="checkbox" id="chk-WS-INJ-002-(\d)"#', $m[1], $ids), '체크 상자 3개');
    assert_same(['1', '2', '3'], $ids[1]);
    assert_same(3, preg_match_all('#<label for="chk-WS-INJ-002-\d">#', $m[1]), 'label 연결');
    assert_contains('<code>ORDER BY</code>', $m[1]);
    assert_true((bool)preg_match('#<p class="ws-check-status"[^>]*><span class="ws-check-n"><span data-check-count>0</span> / 3 완료</span>#', $b), '진행 표시');
});

test('목차: 본문 절과 코드 예시로 가는 링크, 코드 칸 id', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<nav class="ws-toc" aria-labelledby="ws-toc-h">(.*?)</nav>#s', $b, $m), '목차');
    foreach (['summary', 'situation', 'background', 'response', 'checklist', 'detection', 'code', 'sources', 'revisions'] as $id) assert_contains('href="#' . $id . '"', $m[1]);
    assert_true((bool)preg_match('#<aside class="ws-code" id="code"#', $b), '코드 칸 id');
});

test('인쇄용 머리·꼬리: 문서 번호·최종 확인일·주소', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<p class="ws-print-meta">.*?WS-INJ-002.*?최종 확인 2026-11-18.*?/white-security/issues/WS-INJ-002#s', $b), '인쇄 머리');
});

test('탐지 규칙 없는 brief 이슈(site_only)도 깨지지 않고 목차는 있는 절만', function () {
    // site_only 가 아니면 탐지 규칙이 필수(IssueTest)라, 절이 빠진 화면은 site_only 이슈로 확인한다
    $raw = "---\nid: WS-SSRF-009\ntitle: SSRF 짧은 문서\nslug: ssrf-short\ngroup: web\nseverity: medium\nsite_only: true\ndepth: brief\nlanguages: [python]\nmaps:\n  owasp2025: [A01]\nchecked: 2026-11-18\nsources:\n  - title: t\n    url: https://example.org/x\n    year: 2025\n---\n\n## 요약\n\n요약.\n\n## 점검 항목\n\n- 항목 하나\n\n## 코드 예시\n\n### python\n\n✗ 취약 코드\n\n```python\nbad()\n```\n\n✓ 안전 코드\n\n```python\ngood()\n```\n";
    $s = new Site(Repository::load(make_root(['WS-SSRF-009-x.md' => $raw])), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    $r = $s->route('issues/WS-SSRF-009');
    assert_same(200, $r['status']);
    preg_match('#<nav class="ws-toc"[^>]*>(.*?)</nav>#s', $r['body'], $m);
    assert_true(!str_contains($m[1], '#detection') && !str_contains($m[1], '#revisions'), '없는 절 제외');
    assert_contains('href="#checklist"', $m[1]);
});

test('심각도 근거: "영향: … · 악용: …" 은 영향/악용 두 줄 정의, 형식이 다르면 문장 그대로', function () {
    $raw = file_get_contents(__DIR__ . '/fixtures/issues/web/WS-INJ-001-sql-injection.md');
    $raw = str_replace(['WS-INJ-001', 'severity_reason: 데이터베이스 전체 유출·변조로 이어지는 영향, 입력 한 줄로 악용 가능'], ['WS-INJ-003', 'severity_reason: "영향: DB 전체 조회·변조 · 악용: 입력 칸 하나로 가능"'], $raw);
    $s = new Site(Repository::load(make_root(['WS-INJ-003-x.md' => $raw])), '/white-security/', 'live', new DateTimeImmutable('2026-11-18'));
    $b = $s->route('issues/WS-INJ-003')['body'];
    assert_true((bool)preg_match('#<dl class="ws-sevwhy"><div><dt>영향</dt><dd>DB 전체 <span class="ws-nw">조회·</span>변조</dd></div><div><dt>악용</dt><dd>입력 칸 하나로 가능</dd></div></dl>#su', $b), '두 줄 정의');
    assert_true(!str_contains($b, '영향: DB'), '콜론 라벨 문장 없음');
    $plain = ip_body();
    assert_true((bool)preg_match('#<p class="ws-sevwhy-text">데이터베이스 전체 #u', $plain), '형식 밖은 문장');
});

test('줄머리 금칙: 괄호·코드 뒤 조사와 가운뎃점을 nowrap 으로 묶고 pre 안은 건드리지 않음', function () {
    $h = Site::keep('<p>인젝션)는 AI · 보내기·파일 <code>x</code>를 쓴다</p><pre><code>a)는 b·c</code></pre>');
    assert_contains('<span class="ws-nw">인젝션)는</span>', $h);
    assert_contains('<span class="ws-nw">보내기·</span>', $h);
    assert_contains('<span class="ws-nw">AI ·</span>', $h);
    assert_contains('<span class="ws-nw"><code>x</code>를</span>', $h);
    assert_contains('<span class="ws-nw"><code>a</code>·</span><code>b</code>', Site::keep('<p><code>a</code>·<code>b</code></p>'));
    assert_contains('<pre><code>a)는 b·c</code></pre>', $h);
    assert_same('<span class="ws-nw"><span class="ws-nw">글(웹·</span>메일)을</span>', Site::keep('글(웹·메일)을'), '두 규칙이 겹쳐도 태그가 깨지지 않음(겹친 span)');
    assert_true(!preg_match('/[\x{200B}-\x{200F}\x{2060}\x{FEFF}]/u', $h), '보이지 않는 문자 없음');
});

test('프롬프트 카드: 줄마다 블록 span, 줄바꿈 문자 유지(복사 때 원래 줄)', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#ws-block--prompt.*?<pre tabindex="0"><code class="lang-text"><span class="ws-pl">쿼리에 &lt;값&gt;을 이어 붙인 곳을 찾아 줘.</span></code></pre>#s', $b), '줄 span');
    $h = IssueView::promptLines("a줄\nb줄)은 끝\n");
    assert_same("<span class=\"ws-pl\">a줄</span>\n<span class=\"ws-pl\"><span class=\"ws-nw\">b줄)은</span> 끝</span>", $h);
});

test('탐지 규칙 화면: 한 줄 원본을 검사·등급별 하위 목록으로 펼쳐 보인다(원본·스킬 파일은 한 줄 그대로)', function () {
    $b = ip_body();
    assert_true((bool)preg_match('#<section class="ws-sec ws-fold" id="detection">(.*?)</section>#s', $b, $m), '탐지 규칙 절');
    $d = $m[1];
    assert_true((bool)preg_match('#<li><strong>검사 1 — 값 이어 붙이기</strong>\s*<ul>\s*<li>지적할 때: 요청 값을 쿼리 문자열에 이어 붙이는 경우.</li>\s*<li>지적하지 않을 때: 매개변수 바인딩을 쓰는 경우.</li>#su', $d), '검사 하위 목록');
    assert_true((bool)preg_match('#<li><strong>등급</strong>\s*<ul>\s*<li>위험: .*?</li>\s*<li>주의: .*?</li>\s*<li>참고: DB 오류 원문 노출.</li>#su', $d), '등급 하위 목록');
    assert_contains('<strong>찾는 곳</strong>: 쿼리 조립 지점', $d);
    assert_contains('<strong>보고 형식</strong>:', $d);
    assert_true(!str_contains($d, ' / 지적하지 않을 때'), '구분자 숨김');
});
