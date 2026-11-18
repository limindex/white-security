<?php
/**
 * @var Site $site @var string $title @var string $content @var list<string> $errors @var ?string $current @var ?string $page
 * @var bool $wide @var ?string $group @var list<array{0:string,1:?string}> $crumbs @var string $desc @var string $ogType @var ?string $canonical
 */
$full = $title !== '' ? $title . ' — ' . Site::BRAND . ' ' . Site::BRAND_DESC : Site::BRAND . ' — ' . Site::BRAND_DESC;
$publisher = $site->setting('publisher');
$github = $site->setting('github');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Site::e($full) ?></title>
<meta name="description" content="<?= Site::e($desc) ?>">
<?php if ($canonical !== null): ?>
<link rel="canonical" href="<?= Site::e($canonical) ?>">
<meta property="og:url" content="<?= Site::e($canonical) ?>">
<?php endif ?>
<meta property="og:title" content="<?= Site::e($full) ?>">
<meta property="og:description" content="<?= Site::e($desc) ?>">
<meta property="og:type" content="<?= Site::e($ogType) ?>">
<meta property="og:site_name" content="<?= Site::e(Site::BRAND) ?>">
<meta property="og:locale" content="ko_KR">
<meta name="theme-color" content="#fbfaf8">
<link rel="icon" href="<?= Site::e($site->url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= Site::e($site->url('assets/css/site.css')) ?>">
<script src="<?= Site::e($site->url('assets/js/site.js')) ?>"></script>
</head>
<body>
<a class="ws-skip" href="#ws-main">본문 바로가기</a>
<p class="ws-sr" id="ws-live" aria-live="polite"></p>
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
<a class="ws-brand" href="<?= Site::e($site->url('')) ?>"<?= $title === '' && !$page && $current === null ? ' aria-current="page"' : '' ?>>
<svg class="ws-mark" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="1" y="1" width="22" height="22" rx="6" fill="currentColor"/><path class="ws-mark-w" d="M6 8l2.5 8.5L12 10l3.5 6.5L18 8"/></svg>
<span class="ws-brand-name"><?= Site::e(Site::BRAND) ?></span>
<i class="ws-brand-sep" aria-hidden="true"></i>
<span class="ws-brand-desc"><?= Site::e(Site::BRAND_DESC) ?></span>
</a>
<?php if ($publisher !== ''): ?>
<span class="ws-publisher">by <?= Site::e($publisher) ?></span>
<?php endif ?>
<div class="ws-search" role="search">
<svg class="ws-search-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="7" cy="7" r="4.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M10.5 10.5L13.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
<input type="search" id="ws-q" class="ws-search-input" placeholder="문서 검색" aria-label="문서 검색" autocomplete="off" spellcheck="false"
 role="combobox" aria-expanded="false" aria-controls="ws-q-list" aria-autocomplete="list" aria-keyshortcuts="/" data-search="<?= Site::e($site->url('api/search.json')) ?>">
<kbd class="ws-kbd" aria-hidden="true">/</kbd>
<div class="ws-results" id="ws-q-pop" hidden>
<ul class="ws-results-list" id="ws-q-list" role="listbox" aria-label="검색 결과"></ul>
<p class="ws-results-none" hidden>검색 결과 없음 · 다른 낱말이나 문서 번호(WS-…)로 검색</p>
<p class="ws-results-more" hidden></p>
</div>
<p class="ws-sr" id="ws-q-status" aria-live="polite"></p>
</div>
<?php if ($github !== ''): ?>
<a class="ws-gh" href="<?= Site::e($github) ?>" rel="noopener noreferrer"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M8 .2a8 8 0 0 0-2.5 15.6c.4 0 .5-.2.5-.4v-1.5c-2.2.5-2.7-1-2.7-1-.4-.9-.9-1.2-.9-1.2-.7-.5.1-.5.1-.5.8.1 1.2.8 1.2.8.7 1.3 1.9.9 2.4.7 0-.5.3-.9.5-1.1-1.8-.2-3.6-.9-3.6-4 0-.9.3-1.6.8-2.1-.1-.2-.4-1 .1-2.1 0 0 .7-.2 2.2.8a7.5 7.5 0 0 1 4 0c1.5-1 2.2-.8 2.2-.8.4 1.1.2 1.9.1 2.1.5.6.8 1.3.8 2.1 0 3.1-1.9 3.8-3.6 4 .3.3.5.8.5 1.5v2.2c0 .2.1.5.6.4A8 8 0 0 0 8 .2z"/></svg><span class="ws-tx">GitHub</span></a>
<?php endif ?>
<button class="ws-menu-btn" type="button" aria-controls="ws-menu" aria-expanded="false" aria-label="문서 메뉴"><svg class="ws-menu-ico" viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><path class="ws-menu-ico-open" d="M3 6h14M3 10h14M3 14h14"/><path class="ws-menu-ico-close" d="M5 5l10 10M15 5L5 15"/></svg></button>
</header>
<div class="ws-shell<?= $wide ? ' ws-shell--wide' : '' ?>">
<?= $site->render('_nav', ['current' => $current, 'page' => $page, 'group' => $group]) ?>
<main id="ws-main" class="ws-main" tabindex="-1">
<?php if ($crumbs): $last = count($crumbs) - 1; ?>
<nav class="ws-crumbs" aria-label="경로"><ol><?php foreach ($crumbs as $n => [$label, $href]): ?><?php if ($n === $last): ?><li aria-current="page"><span><?= Site::e($label) ?></span></li><?php elseif ($href !== null): ?><li><a href="<?= Site::e($href) ?>"><?= Site::e($label) ?></a></li><?php else: ?><li><span><?= Site::e($label) ?></span></li><?php endif ?><?php endforeach ?></ol></nav>
<?php endif ?>
<?= $content ?>
<footer class="ws-foot">
<p class="ws-foot-scope"><span>흔한 실수를 크게 줄이는 용도</span><i></i><span><?= Site::t('보안 감사·침투 테스트를 대신하지 않음') ?></span></p>
<p class="ws-foot-links"><?php if ($publisher !== ''): ?><span><?= Site::e($publisher) ?> 발행</span><i></i><?php endif ?><?php if ($site->pageOn('sources')): ?><a href="<?= Site::e($site->url('sources')) ?>"><?= Site::t('출처·라이선스') ?></a><?php endif ?></p>
<p class="ws-foot-license"><span>문서 CC BY-SA 4.0</span><i></i><span>예시 코드 MIT</span></p>
</footer>
</main>
</div>
</body>
</html>
