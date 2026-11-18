<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<section class="ws-sec">
<h2>목적</h2>
<p>White Security(화이트 보안)는 바이브 코딩 보안 가이드와 AI 코딩 도구용 점검 스킬의 이름. 빠르게 코드를 만드는 AI 코딩 도구가 입력 검증·비밀키 관리 같은 기본을 자주 빠뜨리는 문제에 대한 대응. 같은 기준을 사람은 문서로 읽고, AI 코딩 도구는 스킬로 따르는 구조.</p>
</section>
<section class="ws-sec">
<h2>범위</h2>
<p>흔한 실수를 크게 줄이는 데까지. 보안 감사·침투 테스트의 대체 수단 아님.</p>
</section>
<section class="ws-sec">
<h2>운영 원칙</h2>
<ul>
<li>이슈마다 공개 출처와 최종 확인일 표기. 확인일 뒤 <?= Site::STALE_DAYS ?>일이 지나면 "확인 필요" 표시.</li>
<li>문서 번호는 바뀌지 않음. 내용이 바뀌면 개정 이력에 날짜와 변경 요지 기록.</li>
<li>심각도는 <a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a>의 영향 × 악용 쉬움으로 판정.</li>
</ul>
</section>
<section class="ws-sec">
<h2>라이선스</h2>
<p>문서와 스킬 본문은 CC BY-SA 4.0, 예시 코드와 스크립트는 MIT. 자세한 내용은 <a href="<?= Site::e($site->url('sources')) ?>">출처·라이선스</a>.</p>
</section>
</div>
