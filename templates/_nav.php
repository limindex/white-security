<?php
/** 왼쪽 목록: 검색 + 묶음별 이슈. 이슈가 없는 묶음은 뺀다. @var Site $site @var ?string $current */
$byGroup = $site->repo->byGroup();
$total = count($site->repo->all());
?>
<nav class="ws-nav" aria-label="이슈 목록">
<div class="ws-search">
<svg class="ws-search-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="7" cy="7" r="4.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M10.5 10.5L13.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
<input type="search" id="ws-q" class="ws-search-input" placeholder="이슈·점검 항목 검색" aria-label="이슈 검색" autocomplete="off" spellcheck="false" data-search="<?= Site::e($site->url('api/search.json')) ?>">
</div>
<p class="ws-search-status" id="ws-q-status" aria-live="polite"></p>
<details class="ws-nav-fold" open>
<summary><span>이슈 목록</span><span class="ws-nav-total"><?= $total ?></span></summary>
<?php if (!$total): ?>
<p class="ws-nav-empty-all">등록된 이슈 없음</p>
<?php endif ?>
<?php foreach ($site->repo->groups() as $g): $list = $byGroup[$g['id']] ?? []; if (!$list) continue; ?>
<section class="ws-nav-group" data-group="<?= Site::e($g['id']) ?>">
<h2 class="ws-nav-title"><?= Site::e($g['name']) ?></h2>
<ul>
<?php foreach ($list as $i): ?>
<li data-id="<?= Site::e($i->id) ?>"><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"<?= $current === $i->id ? ' aria-current="page"' : '' ?>><span class="ws-nav-name"><?= Site::e($i->title) ?></span><span class="ws-nav-sub"><span class="ws-nav-id"><?= Site::e($i->id) ?></span><?= $site->staleBadge($i) ?></span></a></li>
<?php endforeach ?>
</ul>
</section>
<?php endforeach ?>
<p class="ws-nav-none" hidden>검색 결과 없음 · 다른 낱말로 검색</p>
</details>
</nav>
