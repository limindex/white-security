<?php /** @var Site $site @var string $title @var string $lead */
$revs = $site->allRevisions();
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<?php if ($vers = $site->repo->versions()): ?>
<section class="ws-sec" aria-labelledby="ws-ver-h">
<h2 id="ws-ver-h">스킬 버전</h2>
<p class="ws-sec-note">플러그인 버전별 변경 · 저장소의 CHANGELOG.md와 같은 내용</p>
<?php foreach ($vers as $v): ?>
<section class="ws-ver" aria-labelledby="ver-<?= Site::e($v['version']) ?>">
<h3 class="ws-ver-h" id="ver-<?= Site::e($v['version']) ?>"><?= Site::e($v['version']) ?></h3>
<?= Markdown::render($v['md']) ?>
</section>
<?php endforeach ?>
</section>
<?php endif ?>
<section class="ws-sec" aria-labelledby="ws-rev-h">
<div class="ws-sec-head"><h2 id="ws-rev-h">문서 개정</h2><span class="ws-fold-count"><?= count($revs) ?>건</span></div>
<p class="ws-sec-note"><?= Site::t('이슈 문서별 개정 기록 · 날짜 최신순 · 각 문서 끝 개정 이력과 같은 내용') ?></p>
<?php if ($revs): $days = []; foreach ($revs as $r) $days[$r['date']][] = $r; ?>
<?php foreach ($days as $date => $rows):
    /* 같은 날 '최초 작성' 여러 건은 "N개 문서 첫 게시" 한 줄(펼치면 문서 목록)로 묶음 */
    $firsts = array_values(array_filter($rows, fn($r) => $r['note'] === Site::FIRST_NOTE));
    if (count($firsts) < 2) $firsts = [];
    $rest = $firsts ? array_values(array_filter($rows, fn($r) => $r['note'] !== Site::FIRST_NOTE)) : $rows; ?>
<section class="ws-revday" aria-labelledby="rev-<?= Site::e($date) ?>">
<h3 class="ws-revday-h" id="rev-<?= Site::e($date) ?>"><time datetime="<?= Site::e($date) ?>"><?= Site::e($date) ?></time><span class="ws-fold-count"><?= count($rows) ?>건</span></h3>
<?php if ($rest): ?>
<ol class="ws-recent ws-recent--nodate">
<?php foreach ($rest as $r): $i = $r['issue']; ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>">
<span class="ws-recent-main"><span class="ws-recent-title"><?= Site::t($i->title) ?></span><span class="ws-recent-note"><?= Site::t($r['note']) ?></span></span>
<span class="ws-id"><?= Site::e($i->id) ?></span>
<span class="ws-recent-badges"><?= Site::severityBadge($i->severity) ?></span>
</a></li>
<?php endforeach ?>
</ol>
<?php endif ?>
<?php if ($firsts): ?>
<details class="ws-subfold ws-revfirst">
<summary><span class="ws-tx"><?= count($firsts) ?>개 문서 첫 게시</span><span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>
<ul class="ws-revfirst-list">
<?php foreach ($firsts as $r): $i = $r['issue']; ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><?= Site::t(Site::navTitle($i->title)) ?></a> <span class="ws-id"><?= Site::e($i->id) ?></span></li>
<?php endforeach ?>
</ul>
</details>
<?php endif ?>
</section>
<?php endforeach ?>
<?php else: ?>
<p class="ws-emptybox">등록된 이슈 없음</p>
<?php endif ?>
</section>
</div>
