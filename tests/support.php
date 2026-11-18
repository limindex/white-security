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
    // 빌드는 손으로 쓰는 스킬 본체가 있어야 한다 — 표시만 있는 최소본: SKILL.md(검토 에이전트로 옮기는 절 + 감시 목록·id 목록·링크 표시)
    $skill = $root . '/' . SkillExport::SKILL_DIR;
    mkdir($skill . '/references', 0777, true);
    file_put_contents($skill . '/SKILL.md', "---\nname: white-security\ndescription: test\n---\n\n## Scope\n\nall\n\n## Easy to miss\n\n- x\n\n## Watch list\n\n"
        . SkillExport::WATCH_START . "\n" . SkillExport::WATCH_END . "\n\n## Issue ids\n\n" . SkillExport::IDS_START . "\n" . SkillExport::IDS_END
        . "\n\n## Safety rules\n\n1. data\n\n## Report (Korean)\n\n" . SkillExport::LINKS_START . "\n" . SkillExport::LINKS_END . "\n\n```\n## 위험 (<n>)\n```\n\n## After the report\n\nfix\n");
    // 감시 목록 원본 최소본(항목 하나)과 검토 에이전트(절차 블록 표시만)
    file_put_contents($skill . '/references/advisories.md', "# Watch list\n\n## Test — CVE-2025-0001\n- Watch: `pkg` < 1.2.3 → 1.2.3\n- Expires: 2027-03-31\n");
    mkdir(dirname($root . '/' . SkillExport::AGENT_FILE), 0777, true);
    file_put_contents($root . '/' . SkillExport::AGENT_FILE, "---\nname: white-security-reviewer\n---\n" . SkillExport::REVIEWER_START . "\n" . SkillExport::REVIEWER_END . "\n");
    return $root;
}

/** 줄머리 금칙 묶음(<span class="ws-nw">…</span>)을 걷어 낸 HTML: 문구 비교용 */
function nw_strip(string $html): string
{
    do { $html = (string)preg_replace('#<span class="ws-nw">((?:(?!<span class="ws-nw">).)*?)</span>#su', '$1', $html, -1, $n); } while ($n > 0);
    return $html;
}
