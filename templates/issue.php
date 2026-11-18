<?php
/** @var Site $site @var Issue $issue */
$checked = $issue->checked->format('Y-m-d');
$maps = array_filter($issue->maps);
$n = 0;
?>
<div class="ws-issue<?= $issue->code ? '' : ' ws-issue--nocode' ?>">
<article class="ws-doc">
<header class="ws-doc-head">
<p class="ws-crumb"><?= Site::e($site->groupName($issue->group)) ?></p>
<h1><?= Site::e($issue->title) ?></h1>
<p class="ws-meta">
<span class="ws-id"><?= Site::e($issue->id) ?></span>
<?= Site::severityBadge($issue->severity) ?>
<?= $site->staleBadge($issue) ?>
<i></i>
<span class="ws-meta-date">마지막 확인 <time datetime="<?= Site::e($checked) ?>"><?= Site::e($checked) ?></time></span>
</p>
<?php if ($maps): ?>
<ul class="ws-maps" aria-label="기준 번호">
<?php foreach ($maps as $key => $codes): foreach ($codes as $c): ?>
<li class="ws-chip"><?php if (!str_starts_with($c, strtoupper($key) . '-')): ?><span class="ws-chip-k"><?= Site::e(Site::mapLabel($key)) ?></span><?php endif ?><?php if ($u = Site::cweUrl($key, $c)): ?><a class="ws-chip-v" href="<?= Site::e($u) ?>" rel="noopener noreferrer"><?= Site::e($c) ?></a><?php else: ?><span class="ws-chip-v"><?= Site::e($c) ?></span><?php endif ?></li>
<?php endforeach; endforeach ?>
</ul>
<?php endif ?>
</header>

<?php if (isset($issue->sections['한 줄 요약'])): ?>
<div class="ws-summary"><?= Markdown::render($issue->sections['한 줄 요약']) ?></div>
<?php endif ?>

<?php foreach ($issue->sections as $heading => $md): if ($heading === '코드' || $heading === '한 줄 요약') continue; $n++; ?>
<section class="ws-sec<?= $heading === '체크 항목' ? ' ws-sec--check' : '' ?>" aria-labelledby="sec-<?= $n ?>">
<h2 id="sec-<?= $n ?>"><?= Site::e($heading) ?></h2>
<?= Markdown::render($md) ?>
</section>
<?php endforeach ?>

<section class="ws-sec ws-sources" aria-labelledby="sec-src">
<h2 id="sec-src">출처</h2>
<ol>
<?php foreach ($issue->sources as $s): ?>
<li><a href="<?= Site::e($s['url']) ?>" rel="noopener noreferrer"><?= Site::e($s['title']) ?></a><span class="ws-year"><?= Site::e((string)$s['year']) ?></span></li>
<?php endforeach ?>
</ol>
</section>

<p class="ws-checked">마지막 확인 <time datetime="<?= Site::e($checked) ?>"><?= Site::e($checked) ?></time><?php if ($site->isStale($issue)): ?><i></i><span>확인일 뒤 <?= Site::STALE_DAYS ?>일 넘게 지남 · 출처와 최신 동향 다시 확인 필요</span><?php endif ?></p>
</article>

<?php if ($issue->code): $langs = array_keys($issue->code); ?>
<aside class="ws-code" aria-label="코드 예시">
<div class="ws-code-head">
<h2>코드</h2>
<div class="ws-tabs" role="tablist" aria-label="언어">
<?php foreach ($langs as $k => $lang): ?>
<button type="button" class="ws-tab" role="tab" id="tab-<?= Site::e($lang) ?>" aria-controls="code-<?= Site::e($lang) ?>" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>" tabindex="<?= $k === 0 ? '0' : '-1' ?>" data-lang="<?= Site::e($lang) ?>"><span class="ws-tx"><?= Site::e(Site::langLabel($lang)) ?></span></button>
<?php endforeach ?>
</div>
</div>
<?php foreach ($issue->code as $lang => $pair): ?>
<div class="ws-code-panel<?= $lang === $langs[0] ? ' is-active' : '' ?>" id="code-<?= Site::e($lang) ?>" role="tabpanel" aria-labelledby="tab-<?= Site::e($lang) ?>" data-lang-panel="<?= Site::e($lang) ?>">
<h3 class="ws-code-lang"><?= Site::e(Site::langLabel($lang)) ?></h3>
<?php foreach (['bad' => '✗ 뚫리는 코드', 'good' => '✓ 막는 코드'] as $kind => $label): ?>
<figure class="ws-snippet ws-snippet--<?= $kind ?>">
<figcaption><span class="ws-snippet-label"><?= $label ?></span><button type="button" class="ws-copy" data-copy aria-label="<?= Site::e(Site::langLabel($lang)) ?> <?= $kind === 'bad' ? '뚫리는' : '막는' ?> 코드 복사"><span class="ws-tx">복사</span></button></figcaption>
<pre tabindex="0"><code class="lang-<?= Site::e($lang) ?>"><?= Site::e($pair[$kind]) ?></code></pre>
</figure>
<?php endforeach ?>
</div>
<?php endforeach ?>
</aside>
<?php endif ?>
</div>
