<?php
/**
 * 홈: 한 줄 소개 → 세 갈래 시작점 → 보안 이슈 묶음 요약(이름·이슈 수·한 줄 설명, 누르면 그 묶음 이슈 목록).
 * 면책 문구는 바닥글·소개에만. 경로 표시의 묶음 링크(#grp-…)는 site.js openTarget 이 그 묶음을 펼침.
 * @var Site $site @var array $groups @var array $byGroup
 */
$first = array_slice($site->repo->start(), 0, 3);
?>
<div class="ws-home">
<section class="ws-home-intro" aria-labelledby="ws-home-h">
<h1 id="ws-home-h">AI 코딩 보안 점검 스킬</h1>
<p class="ws-home-lead"><?= implode('', array_map(fn($x) => '<span>' . Site::e($x) . '</span>', explode(' · ', Site::HOME_DESC))) ?></p>
</section>

<section class="ws-paths" aria-labelledby="ws-paths-h">
<h2 class="ws-sr" id="ws-paths-h">시작점</h2>
<section class="ws-path ws-path--lead" aria-labelledby="ws-lead-h">
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
<h3>내 사이트 점검</h3>
<p>사용 기술과 기능에 맞춘 점검표 · AI 점검 지시문</p>
</a>
<?php else: ?>
<a class="ws-path" href="<?= Site::e($site->url('guide')) ?>">
<h3>문서 읽는 법</h3>
<p>문서 번호 · 절 구성 · 기준 번호의 의미</p>
</a>
<?php endif ?>
<a class="ws-path" href="<?= Site::e($site->url('install')) ?>">
<h3>스킬 설치</h3>
<p>필요할 때, 특히 배포 전에 “보안 점검해 줘” 한마디. 변경분만이 아닌 프로젝트 전체 읽기 전용 검토.</p>
<span class="ws-path-go">설치 안내 4단계</span>
</a>
</section>

<section class="ws-home-sec" aria-labelledby="ws-grp-h">
<div class="ws-home-sec-head">
<h2 id="ws-grp-h">보안 이슈 묶음</h2>
<span class="ws-fold-count"><?= count(array_filter($groups, fn($g) => ($byGroup[$g['id']] ?? []) !== [])) ?>묶음 · <?= count($site->repo->all()) ?>건</span>
</div>
<ul class="ws-grp-index">
<?php foreach ($groups as $g): $list = $byGroup[$g['id']] ?? []; if (!$list) continue; ?>
<li id="grp-<?= Site::e($g['id']) ?>">
<details>
<summary>
<span class="ws-grp-main"><span class="ws-grp-name"><?= Site::e($g['name']) ?></span><?php if ($g['when'] !== null): ?><span class="ws-grp-when"><?= Site::t($g['when']) ?></span><?php endif ?></span>
<span class="ws-grp-n"><?= count($list) ?>건</span>
<span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span>
</summary>
<ul class="ws-grp-issues">
<?php foreach ($list as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><span class="ws-grp-title"><?= Site::t($i->title) ?></span><span class="ws-id"><?= Site::e($i->id) ?></span><?= Site::severityBadge($i->severity) ?></a></li>
<?php endforeach ?>
</ul>
</details>
</li>
<?php endforeach ?>
</ul>
</section>
</div>
