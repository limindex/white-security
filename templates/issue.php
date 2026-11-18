<?php /** @var Site $site @var Issue $issue */ ?>
<article>
<h1><?= Site::e($issue->title) ?></h1>
<p><?= Site::e($issue->id) ?> · <?= Site::e($issue->severity) ?> · 확인 <?= Site::e($issue->checked->format('Y-m-d')) ?></p>
<?php foreach ($issue->sections as $heading => $md): if ($heading === '코드') continue; ?>
<section>
<h2><?= Site::e($heading) ?></h2>
<?= Markdown::render($md) ?>
</section>
<?php endforeach ?>
<?php if ($issue->code): ?>
<section>
<h2>코드</h2>
<?php foreach ($issue->code as $lang => $pair): ?>
<h3><?= Site::e($lang) ?></h3>
<p>✗ 뚫리는 코드</p>
<pre><code><?= Site::e($pair['bad']) ?></code></pre>
<p>✓ 막는 코드</p>
<pre><code><?= Site::e($pair['good']) ?></code></pre>
<?php endforeach ?>
</section>
<?php endif ?>
<?php if ($issue->sources): ?>
<section>
<h2>출처</h2>
<ul>
<?php foreach ($issue->sources as $s): ?>
<li><?= Site::e((string)($s['title'] ?? '')) ?> (<?= Site::e((string)($s['year'] ?? '')) ?>)</li>
<?php endforeach ?>
</ul>
</section>
<?php endif ?>
</article>
