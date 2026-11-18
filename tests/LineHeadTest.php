<?php
require_once __DIR__ . '/support.php';

/**
 * 줄머리 금칙 회귀 검사(실제 저장소 전체 화면): 줄 맨 앞에 올 수 있는 자리가 남았는지 HTML 구조로 본다.
 * - 인라인 코드 바로 뒤 조사·닫는 괄호·문장부호·가운뎃점이 nowrap 묶음(ws-nw) 밖에 있으면 실패
 * - 글자 조각(태그·코드 밖)에 묶이지 않은 가운뎃점, 닫는 괄호+조사가 남으면 실패
 * 화면 폭에 따른 실제 줄바꿈은 브라우저 검사(Playwright)로 따로 본다. 이 검사는 렌더러가 묶음을 빠뜨리지 않는지만 본다.
 */
function linehead_problems(string $html): array
{
    if (!preg_match('#<main\b[^>]*>(.*)</main>#s', $html, $m)) return ['main 없음'];
    // 경로 표시(항목마다 한 줄 고정)와 pre 는 빼고 본다
    $h = (string)preg_replace(['#<pre\b.*?</pre>#s', '#<nav class="ws-crumbs".*?</nav>#s'], '', $m[1]);
    // 묶인 덩어리는 한 글자로 본다(안쪽은 줄바꿈이 없음)
    do { $h = (string)preg_replace('#<span class="ws-nw">((?:(?!<span class="ws-nw">).)*?)</span>#su', '■', $h, -1, $n); } while ($n > 0);
    $out = [];
    if (preg_match_all('#</code>([가-힣)\]」,.:;·]{1,3})#u', $h, $mm)) foreach ($mm[0] as $x) $out[] = '코드 뒤 묶이지 않음: ' . $x;
    $seg = preg_split('#(<[^>]+>)#', $h, -1, PREG_SPLIT_DELIM_CAPTURE);
    $code = 0;
    foreach ($seg as $j => $s) {
        if ($j % 2) { if (preg_match('#^<code\b#', $s)) $code++; elseif ($s === '</code>') $code--; continue; }
        if ($code > 0) continue;
        if (str_contains($s, '·')) $out[] = '묶이지 않은 가운뎃점: ' . trim(mb_substr($s, 0, 40));
        if (preg_match('#[)\]」][가-힣]#u', $s, $p)) $out[] = '괄호 뒤 조사: ' . trim(mb_substr($s, 0, 40));
    }
    return $out;
}

test('줄머리 금칙(실제 저장소 전 화면): 코드 뒤 조사·괄호·가운뎃점이 모두 묶여 있음', function () {
    $root = dirname(__DIR__);
    $s = new Site(Repository::load($root), '/white-security/', 'static', new DateTimeImmutable('2026-11-18'));
    $paths = ['', ...array_keys($s->pages())];
    foreach ($s->repo->all() as $i) $paths[] = 'issues/' . $i->id;
    $bad = [];
    foreach ($paths as $p) {
        $r = $s->route($p);
        if ($r['type'] !== 'html') continue;
        foreach (linehead_problems($r['body']) as $x) $bad[] = ($p ?: 'home') . ': ' . $x;
    }
    assert_true(count($paths) > 20, '검사한 화면 수');
    assert_same([], array_slice($bad, 0, 15), '줄머리 금칙 위반 ' . count($bad) . '건');
});

test('줄머리 검사기 자체: 묶이지 않은 경우를 잡는다', function () {
    $wrap = fn(string $x) => '<main>' . $x . '</main>';
    assert_true((bool)linehead_problems($wrap('<p><code>a</code>를</p>')), '코드+조사');
    assert_true((bool)linehead_problems($wrap('<p><a>A</a> · <a>B</a></p>')), '태그 사이 가운뎃점');
    assert_same([], linehead_problems($wrap(Site::keep('<p><code>a</code>를 쓰고 위험·주의·참고, 긴 비밀번호·토큰·주민등록번호·카드번호 (괄호)는</p>'))));
});
