<?php /** @var Site $site @var string $message */ ?>
<div class="ws-page ws-page--error">
<header class="ws-page-head">
<p class="ws-code-num">404</p>
<h1>찾을 수 없음</h1>
<p class="ws-page-lead"><?= Site::e($message) ?> · 문서 번호(WS-…)는 바뀌지 않으므로 검색으로 다시 찾기 가능</p>
</header>
<ul class="ws-links">
<li><a href="<?= Site::e($site->url('')) ?>">홈</a></li>
<li><a href="<?= Site::e($site->url('start')) ?>">처음이라면</a></li>
<li><a href="<?= Site::e($site->url('coverage')) ?>">점검 기준 대응표</a></li>
</ul>
</div>
