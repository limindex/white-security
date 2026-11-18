<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<p class="ws-empty">기록 없음 · 첫 버전 공개 후 표시</p>
<p class="ws-page-note">문서별 개정 이력은 각 이슈 문서 끝에 기록.</p>
</div>
