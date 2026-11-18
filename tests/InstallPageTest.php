<?php
require_once __DIR__ . '/support.php';

/** 설치 페이지: Claude Code 하나(확인한 도구만, 직접 복사·Codex·Cursor 는 실측 뒤) · 번호 단계 · command 한 상자 하나 · 예시 출력 · 보고 예시 표 · Troubleshooting */
const INST_JSON = '{"publisher": "LimIndex", "github": "https://github.com/limindex/white-security", "marketplace": "limindex/skills", "plugin_id": "white-security@limindex", "released": false}';

function inst_body(?string $json = INST_JSON): string
{
    $root = make_root();
    if ($json !== null) file_put_contents($root . '/data/site.json', $json);
    return (new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18')))->route('install')['body'];
}

test('설치: 확인한 도구만 — Claude Code 판 하나(탭 줄 없음), 직접 복사·Codex·Cursor 없음', function () {
    $b = nw_strip(inst_body());
    assert_true(!str_contains($b, 'role="tab"'), '판이 하나면 탭 줄 없음');
    assert_contains('id="inst-claude"', $b);
    assert_true(!str_contains($b, 'inst-copy') && !str_contains($b, 'git clone'), '직접 복사 숨김(다른 에이전트·직접 복사는 실제 시험 통과 뒤)');
    assert_true(!str_contains($b, 'inst-cli') && !str_contains($b, 'npx skills'), '확인 전 도구 탭 없음');
    assert_true((bool)preg_match('#<div class="ws-itabs">#', $b) && !str_contains($b, 'data-tabs') && !str_contains($b, 'role="tabpanel"'), '판 하나: 탭 역할 없음');
    assert_contains('ws-ipanel--solo', $b);
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
    assert_contains('검토 중에는 프로젝트 파일만 읽어 별도 허용 설정 불필요', nw_strip($p));
    assert_contains('규칙 문서를 열 때 Claude Code가 읽기 허용을 한 번 확인할 수 있음', nw_strip($p));
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

test('설치: 사용법 안내 순서 — 큰 줄 "보안 점검해 줘", 작은 줄 확실하게 실행(/white-security:review)', function () {
    $b = nw_strip(inst_body());
    preg_match_all('#<section[^>]*id="inst-(claude|copy)"[^>]*>(.*?)</section>#s', $b, $m, PREG_SET_ORDER);
    assert_same(1, count($m));
    foreach ($m as [, $id, $p]) {
        $main = strpos($p, '<code>보안 점검해 줘</code>');
        $sub = strpos($p, 'ws-step-sub');
        assert_true($main !== false && $sub !== false && $main < $sub, "$id: 한마디가 먼저");
        assert_contains('확실하게 실행하려면', $p);
        assert_contains('등급별 표와 고칠 방법 보고', $p);
        assert_true(!str_contains($p, '이 프로젝트 보안 점검해 줘'), "$id: 옛 문구 없음");
    }
    assert_true((bool)preg_match('#ws-step-sub.*<code>/white-security:review</code>#s', $m[0][2]), 'Claude Code: command');
    assert_true(!str_contains($m[0][2], 'white-security 스킬로 보안 점검해 줘'), 'Claude Code 탭엔 스킬 이름 문구 없음');
    // 요청할 때만(11-19): 배포 전 한마디, 배포 이야기에는 먼저 질문, 코드 작성 중 자동 점검 주장 없음
    assert_contains('특히 배포 전에', $b);
    assert_contains('요청할 때만 실행', $b);
    assert_contains('점검 여부를 대화당 한 번 먼저 질문', $b);
    foreach (['자동 점검', '코드 작성', '상시', '스킬 실행 허용'] as $s) assert_true(!str_contains(nw_strip($b), $s), "설치 화면에 \"$s\" 없음");
});

test('보고 예시: 설치·심각도 기준이 같은 표(등급·문서 번호·파일:줄·영향·이유·고칠 방법), 스킬 보고 표와 같은 칸', function () {
    $root = make_root();
    file_put_contents($root . '/data/site.json', INST_JSON);
    $s = new Site(Repository::load($root), '/white-security/', 'live');
    $tbl = fn(string $b) => preg_match('#<table class="ws-table ws-table--report">.*?</table>#s', $b, $m) ? $m[0] : '';
    $a = $tbl($s->route('install')['body']); $b = $tbl($s->route('severity')['body']);
    assert_true($a !== '' && $a === $b, '같은 표');
    foreach (['등급', '문서 번호', '파일:줄', '영향', '이유', '고칠 방법'] as $h) assert_contains('<th scope="col">' . $h . '</th>', $a);
    $skill = file_get_contents(__DIR__ . '/../' . SkillExport::SKILL_DIR . '/SKILL.md');
    assert_contains('| # | id | 파일:줄 | 영향 | 이유 | 고칠 방법 |', $skill);
    // 상시 스킬을 없앤 뒤(11-19) 코드 작성 중 메모 항목 없음
    $sev = nw_strip($s->route('severity')['body']);
    assert_true(!str_contains($sev, '보안 메모') && !str_contains($sev, '코드 작성 중'), '심각도 기준에 상시 메모 없음');
    // 등급 판정 기준은 심각도 기준 페이지(영향 × 악용 쉬움)와 같다
    assert_contains('이 기준(영향 × 악용 쉬움)으로 판정', $sev);
});
