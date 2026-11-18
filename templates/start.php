<?php
/** 처음이라면: 먼저 알아 둘 것 2개 → 읽는 순서(data/start.json, 흔하고 피해가 큰 순) → 다음 단계. @var Site $site @var string $title @var string $lead @var list<array{issue:Issue,reason:string}> $start */
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<section class="ws-sec" aria-labelledby="st-pre">
<h2 id="st-pre">먼저 알아 둘 것</h2>
<ul class="ws-links">
<li><a href="<?= Site::e($site->url('guide')) ?>"><span class="ws-tx">문서 읽는 법</span></a></li>
<li><a href="<?= Site::e($site->url('severity')) ?>"><span class="ws-tx">심각도 기준</span></a></li>
</ul>
</section>

<?php if ($start): ?>
<section class="ws-sec" aria-labelledby="st-order">
<h2 id="st-order">읽는 순서</h2>
<p class="ws-st-note">흔하고 피해가 큰 순</p>
<ol class="ws-readlist">
<?php foreach ($start as $row): $i = $row['issue']; ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>">
<span class="ws-readlist-main"><span class="ws-readlist-title"><?= Site::t($i->title) ?></span><span class="ws-readlist-why"><?= Site::t($row['reason']) ?></span></span>
<span class="ws-id"><?= Site::e($i->id) ?></span>
<?= Site::severityBadge($i->severity) ?>
</a></li>
<?php endforeach ?>
</ol>
</section>
<?php endif ?>

<section class="ws-sec" aria-labelledby="st-next">
<h2 id="st-next">다음 단계</h2>
<ul class="ws-links">
<li><a href="<?= Site::e($site->url('install')) ?>"><span class="ws-tx">스킬 설치</span></a></li>
</ul>
<p class="ws-page-note">설치 뒤에는 코드 작성 중 위 이슈를 스킬이 자동 점검.</p>
</section>
</div>
