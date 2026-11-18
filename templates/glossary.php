<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?= $site->render('_pending', ['items' => ['업계 용어(SQL 인젝션·SSRF·IDOR 등)와 공식 용어(진단가이드·법령) 대응', '영어 표기와 쉬운 풀이', '용어별 관련 이슈 연결']]) ?>
<?= $site->render('_related', ['links' => ['guide', 'coverage']]) ?>
</div>
