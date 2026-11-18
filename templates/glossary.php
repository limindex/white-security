<?php /** 용어 사전: 내용이 생길 때까지 data/site.json 의 pages.glossary=false 로 꺼 둠(공개 화면에 나오지 않음). @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?= $site->render('_related', ['links' => ['guide', 'coverage']]) ?>
</div>
