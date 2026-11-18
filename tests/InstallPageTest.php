<?php
require_once __DIR__ . '/support.php';

/** 설치 페이지: 탭(Claude Code · Codex·Cursor 등 · 직접 복사) · 번호 단계 · command 한 상자 하나 · 예시 출력 · Troubleshooting */
const INST_JSON = '{"publisher": "LimIndex", "github": "https://github.com/limindex/white-security", "marketplace": "limindex/skills", "plugin_id": "white-security@limindex", "released": false}';

function inst_body(?string $json = INST_JSON): string
{
    $root = make_root();
    if ($json !== null) file_put_contents($root . '/data/site.json', $json);
    return (new Site(Repository::load($root), '/white-security/', 'live', new DateTimeImmutable('2026-11-18')))->route('install')['body'];
}

test('설치: 탭 3개, Claude Code 가 첫 탭', function () {
    $b = inst_body();
    assert_true((bool)preg_match_all('#<button type="button" role="tab"[^>]*>(?:<span class="ws-tx">)?([^<]+)#u', $b, $m), '탭');
    assert_same(['Claude Code', 'Codex·Cursor 등 (terminal)', '직접 복사'], $m[1]);
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
});

test('설치: Troubleshooting 접힘(업데이트·삭제 포함), 공개 준비 중 안내', function () {
    $b = inst_body();
    assert_true((bool)preg_match('#<details class="ws-trouble">\s*<summary>#', $b), '접힌 Troubleshooting');
    foreach (['command not found', 'marketplace add', '반응 없음', '/plugin marketplace update limindex', '/plugin marketplace remove limindex'] as $w) assert_contains($w, $b);
    assert_contains('공개 준비 중', $b);
    assert_true(!str_contains(inst_body(str_replace('"released": false', '"released": true', INST_JSON)), '공개 준비 중'), '공개 후 안내 없음');
});

test('설치: 설정이 비면 command 를 그리지 않음, 옛 repository 이름 없음', function () {
    $b = inst_body(null);
    assert_true(!str_contains($b, '/plugin install'), 'command 숨김');
    assert_true(!str_contains($b, 'juslim'), '옛 이름');
    assert_contains('class="ws-pending"', $b);
});

test('용어: 개발 용어·고유명사는 원문, 번역어 없음(전 페이지)', function () {
    $root = make_root();
    file_put_contents($root . '/data/site.json', INST_JSON);
    $s = new Site(Repository::load($root), '/white-security/', 'live');
    foreach (['', 'install', 'about', 'guide', 'severity', 'start', 'check', 'issues/WS-INJ-001'] as $p) {
        $b = strip_tags($s->route($p)['body']);
        foreach (['플러그인', '마켓플레이스', '터미널', '장터', '저장소'] as $w) assert_true(!str_contains($b, $w), "$p: $w");
    }
    $i = inst_body();
    foreach (['Marketplace', 'Plugin', 'terminal', 'repository'] as $w) assert_contains($w, $i);
});

test('설치(직접 복사): 스킬 폴더 경로는 plugins/white-security/skills/white-security (SkillExport::SKILL_DIR)', function () {
    $b = inst_body();
    assert_true((bool)preg_match('#<section[^>]*id="inst-copy"[^>]*>(.*?)</section>#s', $b, $m), '직접 복사 판');
    assert_contains('cp -r white-security/' . SkillExport::SKILL_DIR . ' ~/.claude/skills/', html_entity_decode($m[1]));
});
