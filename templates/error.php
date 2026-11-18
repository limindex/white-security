<?php /** @var Site $site @var string $message */
$links = array_values(array_filter(['start', 'install', 'guide'], fn(string $p) => $site->pageOn($p)));
?>
<div class="ws-page ws-page--error">
<header class="ws-page-head">
<h1>찾을 수 없음</h1>
<p class="ws-page-lead"><?= Site::e($message) ?></p>
</header>
<p class="ws-page-note"><?= Site::t('문서 번호(WS-…)는 바뀌지 않음 · 상단 검색에서 번호나 제목으로 다시 찾기') ?></p>
<nav class="ws-related" aria-labelledby="ws-related-h">
<h2 id="ws-related-h">주요 문서</h2>
<ul class="ws-links">
<li><a href="<?= Site::e($site->url('')) ?>"><span class="ws-tx">홈</span></a></li>
<?php foreach ($links as $slug): ?>
<li><a href="<?= Site::e($site->url($slug)) ?>"><span class="ws-tx"><?= Site::e(Site::PAGES[$slug]['title']) ?></span></a></li>
<?php endforeach ?>
</ul>
</nav>
</div>
