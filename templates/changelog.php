<?php /** @var Site $site @var string $title @var string $lead */
$revs = $site->allRevisions();
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<section class="ws-sec" aria-labelledby="ws-ver-h">
<h2 id="ws-ver-h">사이트·스킬 버전</h2>
<p class="ws-emptybox"><?= Site::t('기록 없음 · 첫 버전 공개 후 표시') ?></p>
</section>
<section class="ws-sec" aria-labelledby="ws-rev-h">
<div class="ws-sec-head"><h2 id="ws-rev-h">문서 개정</h2><span class="ws-fold-count"><?= count($revs) ?>건</span></div>
<p class="ws-sec-note"><?= Site::t('이슈 문서별 개정 기록 · 날짜 최신순 · 각 문서 끝 개정 이력과 같은 내용') ?></p>
<?php if ($revs): $days = []; foreach ($revs as $r) $days[$r['date']][] = $r; ?>
<?php foreach ($days as $date => $rows): ?>
<section class="ws-revday" aria-labelledby="rev-<?= Site::e($date) ?>">
<h3 class="ws-revday-h" id="rev-<?= Site::e($date) ?>"><time datetime="<?= Site::e($date) ?>"><?= Site::e($date) ?></time><span class="ws-fold-count"><?= count($rows) ?>건</span></h3>
<ol class="ws-recent ws-recent--nodate">
<?php foreach ($rows as $r): $i = $r['issue']; ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>">
<span class="ws-recent-main"><span class="ws-recent-title"><?= Site::t($i->title) ?></span><span class="ws-recent-note"><?= Site::t($r['note']) ?></span></span>
<span class="ws-id"><?= Site::e($i->id) ?></span>
<span class="ws-recent-badges"><?= Site::severityBadge($i->severity) ?></span>
</a></li>
<?php endforeach ?>
</ol>
</section>
<?php endforeach ?>
<?php else: ?>
<p class="ws-emptybox">등록된 이슈 없음</p>
<?php endif ?>
</section>
</div>
