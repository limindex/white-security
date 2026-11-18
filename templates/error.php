<?php /** @var Site $site @var string $message */ ?>
<div class="ws-page ws-page--error">
<header class="ws-page-head">
<p class="ws-code-num">404</p>
<h1>찾을 수 없음</h1>
<p class="ws-page-lead"><?= Site::e($message) ?> · 문서 번호(WS-…)는 바뀌지 않으므로 검색으로 다시 찾기 가능</p>
</header>
<ul class="ws-links">
<li><a href="<?= Site::e($site->url('')) ?>"><span class="ws-tx">홈</span></a></li>
<li><a href="<?= Site::e($site->url('start')) ?>"><span class="ws-tx">처음이라면</span></a></li>
<li><a href="<?= Site::e($site->url('install')) ?>"><span class="ws-tx">스킬 설치</span></a></li>
</ul>
</div>
