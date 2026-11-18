<?php
/** 지원 스택 블록: 코드 예시가 있는 스택만(Site::supportedStacks). 하나도 없으면 그리지 않는다. @var Site $site */
$stacks = $site->supportedStacks();
if (!$stacks) return;
?>
<section class="ws-sec ws-stacks" id="stacks">
<h2>지원 스택</h2>
<p>코드 예시는 쓰는 스택 하나만 읽도록 스택마다 따로 구성.</p>
<ul class="ws-stack-list">
<?php foreach ($stacks as $g): ?>
<li><strong><?= Site::e($g['name']) ?></strong><?php if ($g['items']): ?><span class="ws-stack-items"><?= Site::e(implode(' · ', $g['items'])) ?></span><?php endif ?></li>
<?php endforeach ?>
</ul>
</section>
