<?php
/** @var Site $site @var array $groups @var array $byGroup */
$first = array_slice($site->repo->start(), 0, 3);
?>
<div class="ws-home">
<section class="ws-home-intro" aria-labelledby="ws-home-h">
<p class="ws-home-k"><?= Site::e(Site::BRAND) ?> · <?= Site::e(Site::BRAND_DESC) ?></p>
<h1 id="ws-home-h">AI 코딩 보안 취약점 점검 지원</h1>
<p class="ws-home-lead"><span><?= Site::e(Site::HOME_DESC) ?></span></p>
<p class="ws-scope"><span>흔한 실수를 크게 줄이는 용도</span><i></i><span>보안 감사를 대신하지 않음</span></p>
</section>

<section class="ws-paths" aria-labelledby="ws-paths-h">
<h2 class="ws-sr" id="ws-paths-h">시작점</h2>
<section class="ws-path ws-path--lead" aria-labelledby="ws-lead-h">
<span class="ws-path-k">공부하기</span>
<h3 id="ws-lead-h">처음이라면</h3>
<p>흔하고 피해가 큰 보안 이슈 10건 · 읽는 순서</p>
<?php if ($first): ?>
<ol class="ws-path-steps">
<?php foreach ($first as $row): ?>
<li><a href="<?= Site::e($site->url('issues/' . $row['issue']->id)) ?>"><?= Site::t($row['issue']->title) ?></a></li>
<?php endforeach ?>
</ol>
<?php endif ?>
<a class="ws-path-go" href="<?= Site::e($site->url('start')) ?>">10건 모두 보기</a>
</section>
<?php if ($site->pageOn('check')): ?>
<a class="ws-path" href="<?= Site::e($site->url('check')) ?>">
<span class="ws-path-k">점검하기</span>
<h3>내 사이트 점검</h3>
<p>사용 기술과 기능에 맞춘 점검표 · AI 점검 지시문</p>
</a>
<?php else: ?>
<a class="ws-path" href="<?= Site::e($site->url('guide')) ?>">
<span class="ws-path-k">이해하기</span>
<h3>문서 읽는 법</h3>
<p>문서 번호 · 절 구성 · 기준 번호의 의미</p>
</a>
<?php endif ?>
<a class="ws-path" href="<?= Site::e($site->url('install')) ?>">
<span class="ws-path-k">검토하기</span>
<h3>스킬 설치</h3>
<p>필요할 때, 특히 배포 전에 “보안 점검해 줘” 한마디. 변경분만이 아닌 프로젝트 전체 읽기 전용 검토.</p>
<span class="ws-path-go">설치 안내 4단계</span>
</a>
</section>

<section class="ws-home-sec" aria-labelledby="ws-recent-h">
<div class="ws-home-sec-head">
<h2 id="ws-recent-h">최근 개정</h2>
<a class="ws-more" href="<?= Site::e($site->url('changelog')) ?>"><span class="ws-tx">개정 이력</span></a>
</div>
<?php $recent = $site->recentRevisions(5); if ($recent): ?>
<ol class="ws-recent">
<?php foreach ($recent as $r): $i = $r['issue']; ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>">
<time class="ws-recent-date" datetime="<?= Site::e($r['date']) ?>"><?= Site::e($r['date']) ?></time>
<span class="ws-recent-main"><span class="ws-recent-title"><?= Site::t($i->title) ?></span><span class="ws-recent-note"><?= Site::t($r['note']) ?></span></span>
<span class="ws-id"><?= Site::e($i->id) ?></span>
<span class="ws-recent-badges"><?= Site::severityBadge($i->severity) ?></span>
</a></li>
<?php endforeach ?>
</ol>
<?php else: ?>
<p class="ws-empty">등록된 이슈 없음</p>
<?php endif ?>
</section>

<section class="ws-home-sec" aria-labelledby="ws-grp-h">
<div class="ws-home-sec-head">
<h2 id="ws-grp-h">보안 이슈 묶음</h2>
</div>
<ul class="ws-grp-index">
<?php foreach ($groups as $g): $list = $byGroup[$g['id']] ?? []; if (!$list) continue; ?>
<li id="grp-<?= Site::e($g['id']) ?>"<?= $list ? '' : ' class="is-empty"' ?>>
<div class="ws-grp-row">
<h3><?= Site::e($g['name']) ?></h3>
<span class="ws-grp-n"><?= count($list) ?>건</span>
</div>
<?php if ($g['when'] !== null): ?><p class="ws-grp-when"><?= Site::t($g['when']) ?></p><?php endif ?>
<?php if ($list): ?>
<ul class="ws-grp-issues">
<?php foreach ($list as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><span class="ws-tx"><?= Site::t($i->title) ?></span></a></li>
<?php endforeach ?>
</ul>
<?php endif ?>
</li>
<?php endforeach ?>
</ul>
</section>
</div>
