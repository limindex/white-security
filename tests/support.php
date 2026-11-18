<?php
// 테스트용 임시 저장소 루트를 만든다. 실제 issues/ 는 건드리지 않는다.
require_once __DIR__ . '/../src/bootstrap.php';
function make_root(array $extra = []): string
{
    $root = sys_get_temp_dir() . '/ws-test-' . bin2hex(random_bytes(4));
    mkdir($root . '/data', 0777, true);
    mkdir($root . '/issues/web', 0777, true);
    copy(__DIR__ . '/../data/groups.json', $root . '/data/groups.json');
    mkdir($root . '/data/frameworks', 0777, true);
    foreach (glob(__DIR__ . '/../data/frameworks/*.json') as $f) copy($f, $root . '/data/frameworks/' . basename($f));
    foreach (glob(__DIR__ . '/fixtures/issues/web/*.md') as $f) copy($f, $root . '/issues/web/' . basename($f));
    foreach ($extra as $name => $content) file_put_contents($root . '/issues/web/' . $name, $content);
    // 빌드는 손으로 쓰는 스킬 본체가 있어야 한다 — 표시만 있는 최소본: 상시 SKILL.md(규칙 요약), 검토 SKILL.md(신뢰·등급 절 + 라우팅 표)
    $skill = $root . '/' . SkillExport::SKILL_DIR;
    mkdir($skill, 0777, true);
    file_put_contents($skill . '/SKILL.md', "---\nname: white-security\ndescription: test\n---\n\n## Rules\n\n" . SkillExport::RULES_START . "\n" . SkillExport::RULES_END . "\n");
    $review = $root . '/' . SkillExport::REVIEW_DIR;
    mkdir($review, 0777, true);
    file_put_contents($review . '/SKILL.md', "---\nname: review\ndescription: test\n---\n\n## Trust and safety rules\n\n1. data\n\n## Grades and report line\n\ngrade\n\n## Routing\n\n" . SkillExport::ROUTING_START . "\n" . SkillExport::ROUTING_END . "\n");
    // 검토 절차 최소본(링크 규칙 표시 포함)과 검토 에이전트(절차 블록 표시만)
    mkdir($root . '/' . SkillExport::REF_DIR, 0777, true);
    file_put_contents($root . '/' . SkillExport::REF_DIR . '/review.md', "# review\n\nintro\n\n## 0. Scope\n\nall\n\n" . SkillExport::LINKS_START . "\n" . SkillExport::LINKS_END . "\n");
    mkdir(dirname($root . '/' . SkillExport::AGENT_FILE), 0777, true);
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, "---\nname: white-security-reviewer\n---\n" . SkillExport::REVIEWER_START . "\n" . SkillExport::REVIEWER_END . "\n");
    return $root;
}
