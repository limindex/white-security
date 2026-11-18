<?php
/**
 * 스킬 설치: 탭(Claude Code · 직접 복사) → 번호 단계(명령 한 상자 하나 + 짧은 설명 + 예시 출력) → Troubleshooting.
 * 명령 값은 data/site.json(marketplace · plugin_id · github)에서만. 비면 명령을 그리지 않는다.
 * 탭은 설치와 발동을 손으로 확인한 도구만(설계 11절). Codex·Cursor·직접 복사는 실제 확인 뒤에 다시 연다
 * (직접 복사는 스킬 분리 11-18 뒤 검토 에이전트·규칙 폴더 허용이 따라가지 않아 숨김. 패널은 $tabs 에 'copy' 를 더하면 다시 나옴).
 * 설명 문장은 한국어(터미널·저장소·명령), 화면 라벨·고유명사(Marketplace·Plugin·command·Claude Code…)는 원문 그대로.
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
    . '<p class="ws-step-desc">' . Site::keep($desc) . '</p>' . $body . '</li>';
/** 사용법 안내: 큰 줄 = 한마디 프롬프트, 작은 줄 = 확실하게 실행하려면(command 또는 스킬 이름 프롬프트) */
$use = fn(string $sub): string => $prompt('보안 점검해 줘')
    . '<div class="ws-step-sub"><p class="ws-step-sub-h">확실하게 실행하려면</p>' . $sub . '</div>';
$tabs = ['claude' => 'Claude Code'];
$report = $site->render('_report', []);
/** 첫 실행 허용 안내(Plugin 설치만): 스킬은 allowed-tools 로 자기 설치 폴더만 읽음(훅·설정 변경 없음) */
$allow = '<p class="ws-step-note">' . Site::t('보안 점검을 처음 요청할 때 Claude Code가 검토 스킬 실행 허용을 한 번 확인. 허용 범위는 이 Plugin의 규칙 폴더 읽기뿐이며 훅 설치나 설정 변경 없음. 코드 작성 중 자동 점검은 허용 확인 없이 동작.') . '</p>';
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<?php if ($ready): ?>
<?php if (!$site->released()): ?>
<p class="ws-note"><span class="ws-tx">공개 전 · 설치 명령은 저장소 공개 후 동작</span></p>
<?php endif ?>

<?php $multi = count($tabs) > 1; ?>
<div class="ws-itabs"<?= $multi ? ' data-tabs' : '' ?>>
<?php if (count($tabs) > 1): ?>
<div class="ws-itabs-list" role="tablist" aria-label="설치 방법">
<?php foreach ($tabs as $id => $label): ?>
<button type="button" role="tab" id="tab-inst-<?= $id ?>" aria-controls="inst-<?= $id ?>" aria-selected="<?= $id === 'claude' ? 'true' : 'false' ?>" class="ws-itab"><span class="ws-tx"><?= Site::e($label) ?></span></button>
<?php endforeach ?>
</div>
<?php endif ?>

<section class="ws-ipanel<?= $multi ? '' : ' ws-ipanel--solo' ?>" id="inst-claude"<?= $multi ? ' role="tabpanel" aria-labelledby="tab-inst-claude"' : '' ?>>
<h2 class="ws-ipanel-h">Claude Code</h2>
<ol class="ws-steps">
<?= $step(1, '준비 확인', '', '터미널에서 실행. Claude Code 대화창이 열리면 준비 완료, 없으면 <a href="https://code.claude.com/docs/en/setup" rel="noopener noreferrer">Claude Code 공식 설치 문서</a> 참고.',
    $cmd('claude') . $out("✻ Welcome to Claude Code!\n\n>")) ?>
<?= $step(2, 'Marketplace 추가', '최초 1회', Site::e($market) . ' 저장소를 Marketplace로 등록하는 명령. Claude Code 대화창에 입력.',
    $cmd('/plugin marketplace add ' . $market) . $out('✔ Successfully added marketplace: ' . $mname)) ?>
<?= $step(3, 'Plugin 설치', '② 완료 후 실행', '점검 스킬을 설치하는 명령. 다음 대화부터 점검 스킬 동작.',
    $cmd('/plugin install ' . $plugin) . $out('✔ Successfully installed plugin: ' . $plugin . ' (scope: user)')) ?>
<?= $step(4, '동작 확인', '', '프로젝트 폴더의 Claude Code 대화창에 한마디 입력. 위험·주의·참고 등급별 표와 고칠 방법 보고.',
    $use($cmd('/' . $pluginName . ':review')) . $allow . $report) ?>
</ol>
</section>

<?php if (isset($tabs['copy'])): ?>
<section class="ws-ipanel" id="inst-copy" role="tabpanel" aria-labelledby="tab-inst-copy">
<h2 class="ws-ipanel-h">직접 복사</h2>
<ol class="ws-steps">
<?= $step(1, '저장소 내려받기', '', '터미널에서 실행. 현재 폴더 아래에 ' . Site::e(basename($repo)) . ' 폴더 생성.',
    $cmd('git clone https://github.com/' . $repo . '.git') . $out("Cloning into '" . basename($repo) . "'...")) ?>
<?= $step(2, '스킬 폴더 복사', '① 완료 후 실행', 'Claude Code 개인 skills 폴더로 스킬만 복사하는 명령.',
    $cmd('cp -r ' . basename($repo) . '/plugins/' . $pluginName . '/skills/' . $pluginName . ' ~/.claude/skills/')) ?>
<?= $step(3, '동작 확인', '', 'Claude Code를 다시 연 뒤 대화창에 한마디 입력. 위험·주의·참고 등급별 표와 고칠 방법 보고.',
    $use($prompt($pluginName . ' 스킬로 보안 점검해 줘')) . $report) ?>
</ol>
</section>
<?php endif ?>
</div>

<details class="ws-trouble">
<summary><span class="ws-tx">Troubleshooting</span><span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>
<dl class="ws-deflist">
<div><dt>command not found</dt><dd><code>claude</code> 미설치 또는 PATH 문제. Claude Code 공식 설치 문서 재확인.</dd></div>
<div><dt>marketplace add 실패</dt><dd>저장소 이름과 GitHub 접근 확인. 공개 전에는 비공개 저장소라 실패.</dd></div>
<div><dt>설치 후 반응 없음</dt><dd>Claude Code 재시작 또는 <code>/reload-plugins</code> 실행. <code>/plugin</code> 목록에서 <?= Site::e($pluginName) ?> 활성 여부 확인.</dd></div>
<div><dt>업데이트 방법</dt><dd><code>/plugin marketplace update <?= Site::e($mname) ?></code></dd></div>
<div><dt>삭제 방법</dt><dd><code>/plugin</code> 화면에서 <?= Site::e($pluginName) ?> 선택 후 Uninstall. Marketplace째 삭제는 <code>/plugin marketplace remove <?= Site::e($mname) ?></code>(그 Marketplace의 Plugin도 함께 제거).</dd></div>
</dl>
</details>
<?php endif ?>

<section class="ws-sec">
<h2>설치 후 동작</h2>
<ul>
<li>대화창에 “보안 점검해 줘” 한마디면 위험·주의·참고 등급별 보고. 등급 뜻은 <a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a>.</li>
<li>확실하게 실행하려면 <code>/<?= Site::e($pluginName ?: 'white-security') ?>:review</code>.</li>
<li>코드 작성·수정 중 위험한 코드는 별도 요청 없이 스킬이 자동 점검. 파일 읽기·허용 확인 없음.</li>
<li>흔한 실수를 줄이는 용도이며 보안 감사·침투 테스트의 대체 수단 아님.</li>
</ul>
</section>
<?= $site->render('_stacks', []) ?>
</div>
