<?php
/**
 * 점검 기준 대응표: 기준별 미터(덮음·사이트 전용·빈칸, 각 기준 전체 대비) → 기준별 표(기본 접힘).
 * @var Site $site @var string $title @var string $lead @var list<array<string,mixed>> $coverage
 */
$status = function (array $it): array {
    if (!$it['issues']) return ['gap', '빈칸'];
    return $it['site_only'] ? ['site', '사이트 전용'] : ['covered', '덮음'];
};
$sum = [];
$all = ['items' => 0, 'covered' => 0, 'site' => 0];
foreach ($coverage as $fw) {
    $c = ['covered' => 0, 'site' => 0, 'gap' => 0];
    foreach ($fw['items'] as $it) $c[$status($it)[0]]++;
    $sum[$fw['id']] = $c;
    $all['items'] += count($fw['items']);
    $all['covered'] += $c['covered'];
    $all['site'] += $c['site'];
}
$pct = fn(int $n, int $m): int => $m ? (int)round($n * 100 / $m) : 0;
/** 막대 조각 폭 클래스(1% 단위, CSP 때문에 style 속성 대신). 1건이라도 있으면 최소 1% */
$w = fn(int $n, int $m): string => 'ws-w-' . ($m && $n ? max(1, (int)round($n * 100 / $m)) : 0);
?>
<div class="ws-page ws-page--wide">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<?php if (!$coverage): ?>
<p class="ws-empty">표준 항목 파일을 읽지 못했습니다.</p>
<?php else: ?>
<p class="ws-cov-line"><span>기준 <b><?= count($coverage) ?></b>종</span><i></i><span>항목 <b><?= $all['items'] ?></b>개 중 대응 <b><?= $all['covered'] + $all['site'] ?></b>개</span><i></i><span>대응률 <b><?= $pct($all['covered'] + $all['site'], $all['items']) ?></b>%</span></p>

<section class="ws-sec ws-cov-chart" aria-labelledby="cov-chart-h">
<div class="ws-cov-chart-head">
<h2 id="cov-chart-h">기준별 대응</h2>
<ul class="ws-legend" aria-label="범례">
<li><span class="ws-legend-key ws-meter-fill--covered" aria-hidden="true"></span><span class="ws-tx">덮음</span></li>
<li><span class="ws-legend-key ws-meter-fill--site" aria-hidden="true"></span><span class="ws-tx">사이트 전용</span></li>
<li><span class="ws-legend-key ws-legend-key--gap" aria-hidden="true"></span><span class="ws-tx">빈칸</span></li>
</ul>
</div>
<p class="ws-cov-note">막대 길이는 각 기준의 전체 항목 수 대비 · 기준마다 항목 수가 다름</p>
<ol class="ws-meters" aria-label="기준별 대응 막대">
<?php foreach ($coverage as $fw): $c = $sum[$fw['id']]; $m = count($fw['items']); $name = (string)$fw['name']; ?>
<li class="ws-meter">
<span class="ws-meter-name"><a href="#fw-<?= Site::e($fw['id']) ?>"><?= Site::e($name) ?></a><?php if (!$fw['verified']): ?><span class="ws-cov-pending">일부 항목 출처 확인 중</span><?php endif ?></span>
<span class="ws-meter-bar" role="img" aria-label="<?= Site::e($name) ?>: 항목 <?= $m ?>개 중 덮음 <?= $c['covered'] ?>, 사이트 전용 <?= $c['site'] ?>, 빈칸 <?= $c['gap'] ?>"><?php if ($c['covered']): ?><span class="ws-meter-fill ws-meter-fill--covered <?= $w($c['covered'], $m) ?>"></span><?php endif ?><?php if ($c['site']): ?><span class="ws-meter-fill ws-meter-fill--site <?= $w($c['site'], $m) ?>"></span><?php endif ?></span>
<span class="ws-meter-num"><span class="ws-cov-num"><b><?= $c['covered'] + $c['site'] ?></b> / <?= $m ?></span><span class="ws-cov-num ws-meter-pct"><?= $pct($c['covered'] + $c['site'], $m) ?>%</span></span>
</li>
<?php endforeach ?>
</ol>
</section>

<section class="ws-sec" aria-labelledby="cov-tables-h">
<h2 id="cov-tables-h">기준별 항목표</h2>
<p class="ws-cov-note">항목마다 대응 이슈와 상태 · 빈칸은 아직 다루지 않은 항목</p>
<?php foreach ($coverage as $fw): $c = $sum[$fw['id']]; ?>
<section class="ws-sec ws-fold" id="fw-<?= Site::e($fw['id']) ?>">
<details>
<summary><h2><?= Site::e($fw['name']) ?></h2><span class="ws-fold-count"><?= $c['covered'] + $c['site'] ?> / <?= count($fw['items']) ?></span><span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>
<div class="ws-fold-body">
<p class="ws-cov-meta"><?= Site::e((string)$fw['year']) ?>년판 · 항목 <?= count($fw['items']) ?>개 중 덮음 <?= $c['covered'] ?> · 사이트 전용 <?= $c['site'] ?> · 빈칸 <?= $c['gap'] ?> · <a href="<?= Site::e($fw['url']) ?>" rel="noopener noreferrer">공식 출처</a><?php if (!$fw['verified']): ?> · <span class="ws-cov-pending">일부 항목 출처 확인 중</span><?php endif ?></p>
<div class="ws-table-wrap">
<table class="ws-table ws-cov-table">
<thead>
<tr><th scope="col">항목</th><th scope="col">대응 이슈</th><th scope="col">상태</th></tr>
</thead>
<tbody>
<?php foreach ($fw['items'] as $it): [$k, $label] = $status($it); ?>
<tr>
<td><span class="ws-id"><?= Site::e($it['id']) ?></span> <?= Site::e($it['title_ko']) ?><?php if ($it['title'] !== $it['title_ko']): ?><span class="ws-cov-en"><?= Site::e($it['title']) ?></span><?php endif ?></td>
<td><?php if ($it['issues']): foreach ($it['issues'] as $n => $iid): ?><?= $n ? ' · ' : '' ?><a class="ws-id" href="<?= Site::e($site->url('issues/' . $iid)) ?>"><?= Site::e($iid) ?></a><?php endforeach; else: ?><span class="ws-cov-none">없음</span><?php endif ?></td>
<td><span class="ws-badge ws-badge--<?= $k ?>"><?= Site::e($label) ?></span></td>
</tr>
<?php endforeach ?>
</tbody>
</table>
</div>
</div>
</details>
<a class="ws-anchor" href="#fw-<?= Site::e($fw['id']) ?>" aria-label="<?= Site::e($fw['name']) ?> 표 주소 복사"><span aria-hidden="true">#</span></a>
</section>
<?php endforeach ?>
</section>
<?php endif ?>
</div>
