<?php /** @var Site $site @var string $message */ ?>
<h1>찾을 수 없음</h1>
<p><?= Site::e($message) ?></p>
<p><a href="<?= Site::e($site->url('')) ?>">홈으로</a></p>
