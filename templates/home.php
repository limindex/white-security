<?php
/** @var Site $site @var array $groups @var array $byGroup */
$all = $site->repo->all();
$sev = array_fill_keys(Issue::SEVERITIES, 0);
foreach ($all as $i) $sev[$i->severity]++;
?>
<div class="ws-home">
<section class="ws-hero">
<p class="ws-eyebrow">White Security</p>
<h1>화이트 보안</h1>
<p class="ws-hero-lead">AI 코딩 도구가 자주 만드는 보안 실수를 이슈별로 정리한 한국어 문서와 점검 스킬</p>
<p class="ws-promise"><span>흔한 실수를 크게 줄이는 용도</span><i></i><span>보안 감사를 대신하지 않음</span></p>
<p class="ws-actions">
<a class="ws-btn ws-btn--primary" href="<?= Site::e($site->url('install')) ?>">스킬 설치</a>
<a class="ws-btn" href="<?= Site::e($site->url('coverage')) ?>">적용 범위</a>
</p>
</section>

<dl class="ws-stats">
<div><dt>등록 이슈</dt><dd><?= count($all) ?></dd></div>
<div><dt>위험</dt><dd><?= $sev['high'] ?></dd></div>
<div><dt>주의</dt><dd><?= $sev['medium'] ?></dd></div>
<div><dt>참고</dt><dd><?= $sev['low'] ?></dd></div>
</dl>

<section class="ws-home-sec">
<h2>묶음</h2>
<p class="ws-sec-desc">코딩하는 상황별 묶음 · 묶음마다 이슈 수와 심각도</p>
<div class="ws-groups">
<?php foreach ($groups as $g):
    $list = $byGroup[$g['id']] ?? [];
    $count = array_fill_keys(Issue::SEVERITIES, 0);
    foreach ($list as $i) $count[$i->severity]++;
?>
<article class="ws-group-card<?= $list ? '' : ' is-empty' ?>">
<header class="ws-group-head"><h3><?= Site::e($g['name']) ?></h3><span class="ws-group-count"><?= count($list) ?></span></header>
<?php if ($list): ?>
<p class="ws-tally">
<?php foreach ($count as $s => $n): if (!$n) continue; ?>
<?= Site::severityBadge($s, ' ' . $n) ?>
<?php endforeach ?>
</p>
<ul class="ws-group-list">
<?php foreach ($list as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><span class="ws-group-item"><?= Site::e($i->title) ?></span><span class="ws-id"><?= Site::e($i->id) ?></span></a></li>
<?php endforeach ?>
</ul>
<?php else: ?>
<p class="ws-group-empty">등록된 이슈 없음</p>
<?php endif ?>
</article>
<?php endforeach ?>
</div>
</section>

<section class="ws-home-sec">
<h2>최근 업데이트</h2>
<p class="ws-sec-desc">마지막 확인일 기준 5건</p>
<?php $recent = $site->recent(5); if ($recent): ?>
<ol class="ws-recent">
<?php foreach ($recent as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>">
<time class="ws-recent-date" datetime="<?= Site::e($i->checked->format('Y-m-d')) ?>"><?= Site::e($i->checked->format('Y-m-d')) ?></time>
<span class="ws-recent-title"><?= Site::e($i->title) ?></span>
<span class="ws-id"><?= Site::e($i->id) ?></span>
<span class="ws-recent-badges"><?= Site::severityBadge($i->severity) ?><?= $site->staleBadge($i) ?></span>
</a></li>
<?php endforeach ?>
</ol>
<?php else: ?>
<p class="ws-empty">등록된 이슈 없음</p>
<?php endif ?>
</section>
</div>
