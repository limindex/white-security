<?php
/**
 * 스킬 설치: 탭(Claude Code · Codex·Cursor 등 · 직접 복사) → 번호 단계(command 한 상자 하나 + 한 줄 설명 + 예시 출력) → Troubleshooting.
 * command 값은 data/site.json(marketplace · plugin_id · github)에서만. 비면 command 를 그리지 않는다.
 * 개발 용어·고유명사(terminal·Marketplace·Plugin·install·command·repository·Claude Code…)는 원문 그대로.
 * @var Site $site @var string $title @var string $lead
 */
$market = $site->setting('marketplace');
$plugin = $site->setting('plugin_id');
$repo = $site->repoSlug();
$mname = $plugin !== '' ? substr($plugin, strpos($plugin, '@') + 1) : '';
$ready = $market !== '' && $plugin !== '' && $repo !== '';
$pluginName = $plugin !== '' ? substr($plugin, 0, (int)strpos($plugin, '@')) : '';

$cmd = fn(string $c, string $label = 'command'): string =>
    '<figure class="ws-block ws-block--cmd"><figcaption><span class="ws-block-label">' . Site::e($label) . '</span>'
    . '<button type="button" class="ws-copy" data-copy aria-label="' . Site::e($c) . ' 복사"><span class="ws-tx">복사</span></button></figcaption>'
    . '<pre tabindex="0"><code>' . Site::e($c) . '</code></pre></figure>';
$prompt = fn(string $c): string =>
    '<figure class="ws-block ws-block--prompt"><figcaption><span class="ws-block-label">프롬프트</span>'
    . '<button type="button" class="ws-copy" data-copy aria-label="프롬프트 복사"><span class="ws-tx">복사</span></button></figcaption>'
    . '<pre tabindex="0"><code>' . Site::e($c) . '</code></pre></figure>';
$out = fn(string $text): string =>
    '<figure class="ws-out"><figcaption>예시 출력</figcaption><pre><code>' . Site::e($text) . '</code></pre></figure>';
/** @param string $body 이미 안전한 HTML */
$step = fn(int $n, string $head, string $tag, string $desc, string $body): string =>
    '<li class="ws-step"><div class="ws-step-head"><span class="ws-step-n" aria-hidden="true">' . $n . '</span><h3>' . Site::e($head) . '</h3>'
    . ($tag !== '' ? '<span class="ws-step-tag">' . Site::e($tag) . '</span>' : '') . '</div>'
    . '<p class="ws-step-desc">' . $desc . '</p>' . $body . '</li>';
$report = "[위험] WS-INJ-001 app/search.py:42\n       검색어가 SQL 문장에 그대로 연결\n[주의] WS-SEC-001 .env.example:3\n       실제 키로 보이는 값";
$tabs = ['claude' => 'Claude Code', 'cli' => 'Codex·Cursor 등 (terminal)', 'copy' => '직접 복사'];
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<?php if (!$ready): ?>
<?= $site->render('_pending', ['items' => ['Claude Code Plugin install command', 'Codex·Cursor 등 skills 형식 도구용 command', 'repository 직접 복사 방법']]) ?>
<?php else: ?>
<?php if (!$site->released()): ?>
<p class="ws-note"><span class="ws-tx">공개 준비 중 · command는 repository 공개 후 동작</span></p>
<?php endif ?>

<div class="ws-itabs" data-tabs>
<div class="ws-itabs-list" role="tablist" aria-label="설치 방법">
<?php foreach ($tabs as $id => $label): ?>
<button type="button" role="tab" id="tab-inst-<?= $id ?>" aria-controls="inst-<?= $id ?>" aria-selected="<?= $id === 'claude' ? 'true' : 'false' ?>" class="ws-itab"><span class="ws-tx"><?= Site::e($label) ?></span></button>
<?php endforeach ?>
</div>

<section class="ws-ipanel" id="inst-claude" role="tabpanel" aria-labelledby="tab-inst-claude">
<h2 class="ws-ipanel-h">Claude Code</h2>
<ol class="ws-steps">
<?= $step(1, '준비 확인', '', 'terminal에서 실행 · Claude Code 대화창이 열리면 준비 완료 · 없으면 <a href="https://code.claude.com/docs/en/setup" rel="noopener noreferrer">Claude Code 공식 설치 문서</a>',
    $cmd('claude') . $out("✻ Welcome to Claude Code!\n\n>")) ?>
<?= $step(2, 'Marketplace 추가', '최초 1회', Site::e($market) . ' repository를 Marketplace로 등록 · Claude Code 대화창에 입력',
    $cmd('/plugin marketplace add ' . $market) . $out('✔ Successfully added marketplace: ' . $mname)) ?>
<?= $step(3, 'Plugin 설치', '② 완료 후 실행', 'command 입력 시 설치 진행 · 다음 대화부터 점검 스킬 동작',
    $cmd('/plugin install ' . $plugin) . $out('✔ Successfully installed plugin: ' . $plugin . ' (scope: user)')) ?>
<?= $step(4, '동작 확인', '', '프로젝트 폴더의 Claude Code 대화창에 프롬프트 또는 command 중 하나 입력 · 위험·주의·참고 등급별 보고',
    $prompt('이 프로젝트 보안 점검해 줘') . $cmd('/' . $pluginName . ':review') . $out($report)) ?>
</ol>
</section>

<section class="ws-ipanel" id="inst-cli" role="tabpanel" aria-labelledby="tab-inst-cli">
<h2 class="ws-ipanel-h">Codex·Cursor 등 (terminal)</h2>
<ol class="ws-steps">
<?= $step(1, '준비 확인', '', 'terminal에서 실행 · v18 이상이면 준비 완료 · 없으면 <a href="https://nodejs.org/" rel="noopener noreferrer">Node.js 공식 사이트</a>',
    $cmd('node -v') . $out('v22.11.0')) ?>
<?= $step(2, '스킬 install', '', 'skills 형식을 읽는 코딩 도구용 install · 프로젝트 폴더의 terminal에서 실행',
    $cmd('npx skills add ' . $repo) . $out('✔ Installed ' . $pluginName)) ?>
<?= $step(3, '동작 확인', '', '코딩 도구의 대화창에 요청 · 위험·주의·참고 등급별 보고',
    $prompt('이 프로젝트 보안 점검해 줘') . $out($report)) ?>
</ol>
</section>

<section class="ws-ipanel" id="inst-copy" role="tabpanel" aria-labelledby="tab-inst-copy">
<h2 class="ws-ipanel-h">직접 복사</h2>
<ol class="ws-steps">
<?= $step(1, 'repository 내려받기', '', 'terminal에서 실행 · 현재 폴더 아래에 ' . Site::e($pluginName) . ' 폴더 생성',
    $cmd('git clone https://github.com/' . $repo . '.git') . $out("Cloning into '" . basename($repo) . "'...")) ?>
<?= $step(2, '스킬 폴더 복사', '① 완료 후 실행', 'Claude Code 개인 skills 폴더로 복사 · 다른 도구는 그 도구의 skills 폴더',
    $cmd('cp -r ' . basename($repo) . '/plugins/' . $pluginName . '/skills/' . $pluginName . ' ~/.claude/skills/')) ?>
<?= $step(3, '동작 확인', '', '코딩 도구를 다시 연 뒤 대화창에 요청 · 위험·주의·참고 등급별 보고',
    $prompt('이 프로젝트 보안 점검해 줘') . $out($report)) ?>
</ol>
</section>
</div>

<details class="ws-trouble">
<summary><span class="ws-tx">Troubleshooting</span><span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>
<dl class="ws-deflist">
<div><dt>command not found</dt><dd><code>claude</code>·<code>npx</code> 미설치 또는 PATH 문제 · 공식 설치 문서 재확인</dd></div>
<div><dt>marketplace add 실패</dt><dd>repository 이름 확인 · GitHub 접근 확인 · 공개 전에는 실패(비공개 repository)</dd></div>
<div><dt>설치 후 반응 없음</dt><dd>Claude Code 재시작 또는 <code>/reload-plugins</code> · <code>/plugin</code> 목록에서 <?= Site::e($pluginName) ?> 활성 여부 확인</dd></div>
<div><dt>업데이트 방법</dt><dd><code>/plugin marketplace update <?= Site::e($mname) ?></code></dd></div>
<div><dt>삭제 방법</dt><dd><code>/plugin</code> 화면에서 <?= Site::e($pluginName) ?> 선택 후 Uninstall · Marketplace째 삭제는 <code>/plugin marketplace remove <?= Site::e($mname) ?></code>(그 Marketplace의 Plugin도 함께 제거)</dd></div>
</dl>
</details>
<?php endif ?>

<section class="ws-sec">
<h2>설치 후 동작</h2>
<ul>
<li>코드 작성·수정 때 해당 묶음의 점검 항목을 스킬이 스스로 대조.</li>
<li>보안 검토 요청 시 위험·주의·참고 등급별 보고. 등급 뜻은 <a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a>.</li>
<li>흔한 실수를 줄이는 용도이며 보안 감사·침투 테스트의 대체 수단 아님.</li>
</ul>
</section>
</div>
