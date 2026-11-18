<?php
require_once __DIR__ . '/support.php';

/** 설치 페이지: 탭(Claude Code · 직접 복사, 확인한 도구만) · 번호 단계 · command 한 상자 하나 · 예시 출력 · 보고 예시 표 · Troubleshooting */
const INST_JSON = '{"publisher": "LimIndex", "github": "https://github.com/limindex/white-security", "marketplace": "limindex/skills", "plugin_id": "white-security@limindex", "released": false}';

function inst_body(?string $json = INST_JSON): string
{
    $root = make_root();
    if ($json !== null) file_put_contents($root . '/data/site.json', $json);
    return (new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18')))->route('install')['body'];
}

test('설치: 탭 2개(확인한 도구만), Claude Code 가 첫 탭, Codex·Cursor 탭 없음', function () {
    $b = nw_strip(inst_body());
    assert_true((bool)preg_match_all('#<button type="button" role="tab"[^>]*>(?:<span class="ws-tx">)?([^<]+)#u', $b, $m), '탭');
    assert_same(['Claude Code', '직접 복사'], $m[1]);
    assert_true(!str_contains($b, 'inst-cli') && !str_contains($b, 'npx skills'), '확인 전 도구 탭 없음');
    assert_true((bool)preg_match('#<div class="ws-itabs" data-tabs>#', $b), '탭 묶음');
});

test('설치(Claude Code): 번호 단계 4개, command 는 한 상자에 하나, 순서·조건 표시', function () {
    $b = inst_body();
    assert_true((bool)preg_match('#<section[^>]*id="inst-claude"[^>]*>(.*?)</section>#s', $b, $m), 'Claude Code 판');
    $p = $m[1];
    assert_same(4, preg_match_all('#<li class="ws-step">#', $p), '단계 4개');
    foreach (['준비 확인', 'Marketplace 추가', 'Plugin 설치', '동작 확인'] as $h) assert_contains($h, $p);
    preg_match_all('#<pre tabindex="0"><code>([^<]*)</code></pre>#', $p, $cmds);
    foreach ($cmds[1] as $c) assert_true(!str_contains(html_entity_decode($c), "\n"), "한 상자에 command 하나: $c");
    foreach (['claude', '/plugin marketplace add limindex/skills', '/plugin install white-security@limindex', '/white-security:review'] as $c) assert_true(in_array($c, $cmds[1], true), "command $c");
    assert_contains('최초 1회', $p);
    assert_contains('② 완료 후 실행', $p);
    assert_contains('href="https://code.claude.com/docs/en/setup"', $p);
    assert_true(substr_count($p, 'class="ws-out"') >= 3, '예시 출력 상자');
    assert_true(substr_count($p, '예시 출력') >= 3, '예시 표시');
    assert_contains('class="ws-table ws-table--report"', $p);
    assert_contains('<th scope="col">고칠 방법</th>', $p);
    assert_contains('스킬 실행 허용을 한 번 확인', nw_strip($p));
    assert_contains('훅 설치나 설정 변경 없음', nw_strip($p));
    assert_true(!str_contains($p, '/permissions'), '설정 변경 단계 없음');
});

test('설치: Troubleshooting 접힘(업데이트·삭제 포함), 공개 전 안내', function () {
    $b = inst_body();
    assert_true((bool)preg_match('#<details class="ws-trouble">\s*<summary>#', $b), '접힌 Troubleshooting');
    foreach (['command not found', 'marketplace add', '반응 없음', '/plugin marketplace update limindex', '/plugin marketplace remove limindex'] as $w) assert_contains($w, $b);
    assert_contains('공개 전 · 설치 명령은 저장소 공개 후 동작', nw_strip($b));
    assert_true(!str_contains(nw_strip(inst_body(str_replace('"released": false', '"released": true', INST_JSON))), '공개 전 · 설치 명령은'), '공개 후 안내 없음');
});

test('설치: 설정이 비면 command 를 그리지 않음, 옛 repository 이름 없음', function () {
    $b = inst_body(null);
    assert_true(!str_contains($b, '/plugin install'), 'command 숨김');
    assert_true(!str_contains($b, 'juslim'), '옛 이름');
    assert_true(!str_contains($b, '준비 중') && !str_contains($b, 'ws-pending'), '준비 중 구역 없음');
});

test('용어: 화면 라벨·고유명사는 원문, 설명 문장은 한국어(터미널·저장소·명령)', function () {
    $root = make_root();
    file_put_contents($root . '/data/site.json', INST_JSON);
    $s = new Site(Repository::load($root), '/white-security/', 'live');
    foreach (['', 'install', 'about', 'guide', 'severity', 'start', 'check', 'issues/WS-INJ-001'] as $p) {
        $b = strip_tags($s->route($p)['body']);
        foreach (['플러그인', '마켓플레이스', '장터'] as $w) assert_true(!str_contains($b, $w), "$p: $w");
    }
    $i = strip_tags(inst_body());
    foreach (['Marketplace', 'Plugin', 'command', '터미널', '저장소'] as $w) assert_contains($w, $i);
    foreach (['terminal에서', 'repository를', 'repository 공개', 'command는', 'command 입력'] as $w) assert_true(!str_contains($i, $w), "설명 문장 속 영문: $w");
});

test('설치(직접 복사): 스킬 폴더 경로는 plugins/white-security/skills/white-security (SkillExport::SKILL_DIR)', function () {
    $b = inst_body();
    assert_true((bool)preg_match('#<section[^>]*id="inst-copy"[^>]*>(.*?)</section>#s', $b, $m), '직접 복사 판');
    assert_contains('cp -r white-security/' . SkillExport::SKILL_DIR . ' ~/.claude/skills/', html_entity_decode($m[1]));
});

test('설치: 사용법 안내 순서 — 큰 줄 "보안 점검해 줘", 작은 줄 확실하게 실행(/white-security:review · 스킬 이름 포함 프롬프트)', function () {
    $b = nw_strip(inst_body());
    preg_match_all('#<section[^>]*id="inst-(claude|copy)"[^>]*>(.*?)</section>#s', $b, $m, PREG_SET_ORDER);
    assert_same(2, count($m));
    foreach ($m as [, $id, $p]) {
        $main = strpos($p, '<code>보안 점검해 줘</code>');
        $sub = strpos($p, 'ws-step-sub');
        assert_true($main !== false && $sub !== false && $main < $sub, "$id: 한마디가 먼저");
        assert_contains('확실하게 실행하려면', $p);
        assert_contains('등급별 표와 고칠 방법 보고', $p);
        assert_true(!str_contains($p, '이 프로젝트 보안 점검해 줘'), "$id: 옛 문구 없음");
    }
    assert_true((bool)preg_match('#ws-step-sub.*<code>/white-security:review</code>#s', $m[0][2]), 'Claude Code: command');
    assert_contains('<code>white-security 스킬로 보안 점검해 줘</code>', $m[1][2]);
    assert_true(!str_contains($m[0][2], 'white-security 스킬로 보안 점검해 줘'), 'Claude Code 탭엔 스킬 이름 문구 없음');
    assert_contains('코드 작성·수정 중 위험한 코드는 별도 요청 없이 스킬이 자동 점검', $b);
});

test('보고 예시: 설치·심각도 기준이 같은 표(등급·문서 번호·파일:줄·이유·고칠 방법), 한 줄 형식은 스킬 보고 줄과 같음', function () {
    $root = make_root();
    file_put_contents($root . '/data/site.json', INST_JSON);
    $s = new Site(Repository::load($root), '/white-security/', 'live');
    $tbl = fn(string $b) => preg_match('#<table class="ws-table ws-table--report">.*?</table>#s', $b, $m) ? $m[0] : '';
    $a = $tbl($s->route('install')['body']); $b = $tbl($s->route('severity')['body']);
    assert_true($a !== '' && $a === $b, '같은 표');
    foreach (['등급', '문서 번호', '파일:줄', '이유', '고칠 방법'] as $h) assert_contains('<th scope="col">' . $h . '</th>', $a);
    $skill = file_get_contents(__DIR__ . '/../' . SkillExport::SKILL_DIR . '/SKILL.md');
    assert_contains('`[위험|주의|참고] <id> <file:line> — <reason> → <fix>`', $skill);
    assert_contains('<code class="ws-code-line">[위험|주의|참고] &lt;문서 번호&gt; &lt;파일:줄&gt; — &lt;이유&gt; → &lt;고칠 방법&gt;', nw_strip($s->route('severity')['body']));
    $review = file_get_contents(__DIR__ . '/../' . SkillExport::SKILL_DIR . '/references/review.md');
    assert_contains('| # | id | 파일:줄 | 이유 | 고칠 방법 |', $review);
});
