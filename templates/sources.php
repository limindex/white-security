<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<section class="ws-sec">
<h2>라이선스</h2>
<dl class="ws-deflist">
<div><dt>문서·스킬 본문</dt><dd>CC BY-SA 4.0</dd></div>
<div><dt>예시 코드·스크립트</dt><dd>MIT</dd></div>
<div><dt>글꼴</dt><dd>Pretendard · Manrope · JetBrains Mono, 모두 SIL Open Font License 1.1</dd></div>
</dl>
</section>
</div>
