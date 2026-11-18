<?php /** "준비 중" 구역: 들어갈 내용 목록만 보여 준다. @var list<string> $items */ ?>
<section class="ws-pending" aria-labelledby="ws-pending-h">
<h2 id="ws-pending-h">준비 중</h2>
<p>다음 내용 정리 중 · 공개 전 반영</p>
<ul>
<?php foreach ($items as $it): ?>
<li><?= Site::e($it) ?></li>
<?php endforeach ?>
</ul>
</section>
