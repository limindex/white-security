<?php /** @var Site $site @var string $title @var string $content @var list<string> $errors */ ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Site::e($title) ?> · 화이트 보안</title>
</head>
<body>
<?php if ($errors): ?>
<section role="alert">
<p>이슈 원본 <?= count($errors) ?>건에 오류가 있어 빠졌습니다.</p>
<ul>
<?php foreach ($errors as $err): ?>
<li><?= Site::e($err) ?></li>
<?php endforeach ?>
</ul>
</section>
<?php endif ?>
<header>
<nav>
<a href="<?= Site::e($site->url('')) ?>">홈</a>
<a href="<?= Site::e($site->url('coverage')) ?>">적용 범위</a>
<a href="<?= Site::e($site->url('install')) ?>">설치</a>
<a href="<?= Site::e($site->url('changelog')) ?>">변경 내역</a>
<a href="<?= Site::e($site->url('about')) ?>">소개</a>
</nav>
</header>
<main>
<?= $content ?>
</main>
</body>
</html>
