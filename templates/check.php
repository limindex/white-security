<?php /** 내 사이트 점검: 내용이 생길 때까지 data/site.json 의 pages.check=false 로 꺼 둠(공개 화면에 나오지 않음). @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?= $site->render('_related', ['links' => ['install', 'severity']]) ?>
</div>
