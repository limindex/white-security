<?php /** @var Site $site @var string $message */ ?>
<div class="ws-page ws-page--error">
<p class="ws-eyebrow">404</p>
<h1>찾을 수 없음</h1>
<p class="ws-page-lead"><?= Site::e($message) ?></p>
<p class="ws-actions"><a class="ws-btn ws-btn--primary" href="<?= Site::e($site->url('')) ?>">홈으로</a></p>
</div>
