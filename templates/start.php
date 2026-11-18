<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?= $site->render('_pending', ['items' => ['입문 이슈 10건과 읽는 순서', '이슈별 읽음 표시와 진도(이 브라우저에만 저장)', '용어 사전 연결']]) ?>
<p class="ws-page-next">지금 읽을 문서: <a href="<?= Site::e($site->url('guide')) ?>">문서 읽는 법</a><i></i><a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a></p>
</div>
