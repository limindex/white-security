<?php /** @var Site $site @var string $title @var string $lead */
$cmds = [
    ['Claude Code', 'Claude Code 플러그인 설치', "/plugin marketplace add juslim-afk/white-security\n/plugin install white-security@white-security"],
    ['여러 도구 공용', 'skills 형식을 읽는 코딩 도구에 설치', 'npx skills add juslim-afk/white-security'],
];
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?php foreach ($cmds as $n => [$name, $desc, $cmd]): ?>
<section class="ws-sec" aria-labelledby="inst-<?= $n ?>">
<h2 id="inst-<?= $n ?>"><?= Site::e($name) ?></h2>
<p><?= Site::e($desc) ?></p>
<figure class="ws-block">
<figcaption><span class="ws-block-label">명령</span><button type="button" class="ws-copy" data-copy aria-label="<?= Site::e($name) ?> 설치 명령 복사"><span class="ws-tx">복사</span></button></figcaption>
<pre tabindex="0"><code><?= Site::e($cmd) ?></code></pre>
</figure>
</section>
<?php endforeach ?>
<section class="ws-sec">
<h2>설치 뒤</h2>
<ul>
<li>코드 작성·수정 때 해당 묶음의 점검 항목을 스킬이 스스로 대조.</li>
<li>보안 검토 요청 시 위험·주의·참고 등급별 보고. 등급 뜻은 <a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a>.</li>
<li>흔한 실수를 줄이는 용도이며 보안 감사·침투 테스트의 대체 수단 아님.</li>
</ul>
</section>
</div>
