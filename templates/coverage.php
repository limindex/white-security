<?php
/** @var Site $site @var string $title @var string $lead @var list<array<string,mixed>> $coverage */
$status = function (array $it): array {
    if (!$it['issues']) return ['gap', '빈칸'];
    return $it['site_only'] ? ['site', '사이트 전용'] : ['covered', '덮음'];
};
$sum = [];
foreach ($coverage as $fw) {
    $c = ['covered' => 0, 'site' => 0, 'gap' => 0];
    foreach ($fw['items'] as $it) $c[$status($it)[0]]++;
    $sum[$fw['id']] = $c;
}
?>
<div class="ws-page ws-page--wide">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<?php if (!$coverage): ?>
<p class="ws-empty">표준 항목 파일을 읽지 못했습니다.</p>
<?php else: ?>
<h2 class="ws-cov-h">기준별 요약</h2>
<div class="ws-table-wrap">
<table class="ws-table ws-cov-summary">
<thead>
<tr><th scope="col">기준</th><th scope="col">항목 수</th><th scope="col">덮음</th><th scope="col">사이트 전용</th><th scope="col">빈칸</th></tr>
</thead>
<tbody>
<?php foreach ($coverage as $fw): $c = $sum[$fw['id']]; ?>
<tr>
<th scope="row"><a href="#fw-<?= Site::e($fw['id']) ?>"><?= Site::e($fw['name']) ?></a></th>
<td><?= count($fw['items']) ?></td>
<td><?= $c['covered'] ?></td>
<td><?= $c['site'] ?></td>
<td><?= $c['gap'] ?></td>
</tr>
<?php endforeach ?>
</tbody>
</table>
</div>

<?php foreach ($coverage as $fw): $c = $sum[$fw['id']]; ?>
<section class="ws-cov" id="fw-<?= Site::e($fw['id']) ?>">
<h2 class="ws-cov-h"><?= Site::e($fw['name']) ?></h2>
<p class="ws-cov-meta"><?= Site::e((string)$fw['year']) ?>년판 · 항목 <?= count($fw['items']) ?>개 중 덮음 <?= $c['covered'] ?> · 사이트 전용 <?= $c['site'] ?> · 빈칸 <?= $c['gap'] ?> · <a href="<?= Site::e($fw['url']) ?>" rel="noopener noreferrer">공식 출처</a><?php if (!$fw['verified']): ?> · <span class="ws-cov-pending">일부 항목 출처 확인 중</span><?php endif ?></p>
<div class="ws-table-wrap">
<table class="ws-table ws-cov-table">
<thead>
<tr><th scope="col">항목</th><th scope="col">우리 이슈</th><th scope="col">상태</th></tr>
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
</section>
<?php endforeach ?>
<?php endif ?>
</div>
