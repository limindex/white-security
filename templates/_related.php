<?php /** 관련 문서 링크 묶음 @var Site $site @var list<string> $links (Site::PAGES 키) */ ?>
<nav class="ws-related" aria-labelledby="ws-related-h">
<h2 id="ws-related-h">관련 문서</h2>
<ul class="ws-links">
<?php foreach ($links as $slug): ?>
<li><a href="<?= Site::e($site->url($slug)) ?>"><span class="ws-tx"><?= Site::e(Site::PAGES[$slug]['title']) ?></span></a></li>
<?php endforeach ?>
</ul>
</nav>
