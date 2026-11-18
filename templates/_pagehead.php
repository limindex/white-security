<?php /** 페이지 머리: 제목 + 한 줄 설명(명사형) @var string $title @var string $lead */ ?>
<header class="ws-page-head">
<h1><?= Site::e($title) ?></h1>
<p class="ws-page-lead"><?= Site::e($lead) ?></p>
</header>
