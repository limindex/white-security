<?php /** @var Site $site @var string $title @var string $lead */ ?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<section class="ws-sec">
<h2>목적</h2>
<p>White Security(화이트 보안)는 바이브 코딩 보안 가이드와 AI 코딩 도구용 점검 스킬의 이름. 빠르게 코드를 만드는 AI 코딩 도구가 입력 검증·비밀키 관리 같은 기본을 자주 빠뜨리는 문제에 대한 대응. 같은 기준을 사람은 문서로 읽고, AI 코딩 도구는 스킬로 점검하는 구조.</p>
</section>
<section class="ws-sec">
<h2>사용 방법</h2>
<ul>
<li>필요할 때, 특히 배포 전에 대화창에 <code>보안 점검해 줘</code> 한마디로 프로젝트 전체 읽기 전용 검토.</li>
<li>확실하게 실행하려면 <code>/white-security:review</code>.</li>
<li>요청할 때만 실행. “배포할게”처럼 배포 이야기가 나오면 점검 여부를 먼저 질문.</li>
</ul>
<p>설치 방법은 <a href="<?= Site::e($site->url('install')) ?>">스킬 설치</a>.</p>
</section>
<section class="ws-sec">
<h2>범위</h2>
<p>흔한 실수를 크게 줄이는 데까지. 보안 감사·침투 테스트의 대체 수단 아님.</p>
</section>
<section class="ws-sec">
<h2>운영 원칙</h2>
<ul>
<li>이슈마다 공개 출처와 최종 확인일 표기. 기준 개정·링크 깨짐·제보 같은 사유가 생기면 재확인 후 확인일 갱신.</li>
<li>문서 번호는 바뀌지 않음. 내용이 바뀌면 개정 이력에 날짜와 변경 요지 기록.</li>
<li>심각도는 <a href="<?= Site::e($site->url('severity')) ?>">심각도 기준</a>의 영향 × 악용 쉬움으로 판정.</li>
</ul>
</section>
<section class="ws-sec">
<h2>작성 기준</h2>
<p>OWASP·CWE·국내 진단가이드 등 <?= count($site->repo->frameworks()) ?>개 기준에 맞춰 작성 · <a href="<?= Site::e($site->url('coverage')) ?>">점검 기준 대응표</a></p>
</section>
<?= $site->render('_stacks', []) ?>
<section class="ws-sec">
<h2>라이선스</h2>
<p>문서와 스킬 본문은 CC BY-SA 4.0, 예시 코드와 스크립트는 MIT. <?php if ($site->pageOn('sources')): ?>자세한 내용은 <a href="<?= Site::e($site->url('sources')) ?>">출처·라이선스</a>.<?php endif ?></p>
</section>
</div>
