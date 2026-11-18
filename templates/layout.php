<?php
/** @var Site $site @var string $title @var string $content @var list<string> $errors @var ?string $current @var ?string $page @var bool $wide */
$links = ['coverage' => '적용 범위', 'install' => '설치', 'changelog' => '변경 내역', 'about' => '소개'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $title !== '' ? Site::e($title) . ' · 화이트 보안' : '화이트 보안 · AI 코딩 보안 점검 문서' ?></title>
<meta name="description" content="AI 코딩 도구가 자주 만드는 보안 실수를 이슈별로 정리한 한국어 문서와 점검 스킬">
<link rel="icon" href="<?= Site::e($site->url('assets/img/mark.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= Site::e($site->url('assets/css/site.css')) ?>">
<script src="<?= Site::e($site->url('assets/js/site.js')) ?>"></script>
</head>
<body>
<a class="ws-skip" href="#ws-main">본문 바로가기</a>
<?php if ($errors): ?>
<section class="ws-alert" role="alert">
<p class="ws-alert-title">원본 오류 <?= count($errors) ?>건 · 해당 항목은 화면에서 빠짐</p>
<ul>
<?php foreach ($errors as $err): ?>
<li><?= Site::e($err) ?></li>
<?php endforeach ?>
</ul>
</section>
<?php endif ?>
<header class="ws-top">
<a class="ws-brand" href="<?= Site::e($site->url('')) ?>"<?= $title === '' && !$page ? ' aria-current="page"' : '' ?>>
<svg class="ws-mark" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="2.75" y="2.75" width="18.5" height="18.5" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 12.25l3 3 6-6.5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
<span class="ws-brand-name">화이트 보안</span>
</a>
<nav class="ws-toplinks" aria-label="사이트">
<?php foreach ($links as $slug => $label): ?>
<a href="<?= Site::e($site->url($slug)) ?>"<?= $page === $slug ? ' aria-current="page"' : '' ?>><?= Site::e($label) ?></a>
<?php endforeach ?>
</nav>
</header>
<div class="ws-shell">
<div class="ws-side"><?= $site->render('_nav', ['current' => $current]) ?></div>
<main id="ws-main" class="ws-main<?= $wide ? ' ws-main--wide' : '' ?>">
<?= $content ?>
<footer class="ws-foot">
<p><span>흔한 실수를 크게 줄이는 용도</span><i></i><span>보안 감사·침투 테스트를 대신하지 않음</span></p>
<p><span>문서 CC BY-SA 4.0</span><i></i><span>예시 코드 MIT</span></p>
</footer>
</main>
</div>
</body>
</html>
