<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?= $site->render('_pending', ['items' => ['사용 언어·프레임워크·기능 선택', '선택에 맞춘 점검표와 진행 저장(이 브라우저에만 저장)', 'AI 코딩 도구에 붙여 넣을 점검 지시문', '인쇄용 점검표와 Markdown 내보내기']]) ?>
<p class="ws-page-next">지금 쓸 수 있는 방법: <a href="<?= Site::e($site->url('install')) ?>">스킬 설치</a> 후 코딩 도구에 보안 검토 요청</p>
</div>
