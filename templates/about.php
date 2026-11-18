<?php /** @var Site $site */ ?>
<div class="ws-page">
<h1>소개</h1>
<p class="ws-page-lead">AI와 함께 코딩하는 사람을 위한 한국어 보안 문서와 점검 스킬</p>
<section class="ws-sec">
<h2>만든 이유</h2>
<p>AI 코딩 도구는 빠르게 코드를 만들지만, 입력 검증이나 비밀키 관리 같은 기본을 자주 빠뜨립니다. 같은 기준을 사람은 문서로 읽고, AI 도구는 스킬로 따르게 하려고 만들었습니다.</p>
</section>
<section class="ws-sec">
<h2>약속하는 범위</h2>
<p>흔한 실수를 크게 줄이는 데까지입니다. 보안 감사나 침투 테스트를 대신하지 않습니다.</p>
</section>
<section class="ws-sec">
<h2>출처 원칙</h2>
<p>이슈마다 공개된 출처와 마지막 확인일을 적습니다. 확인일이 <?= Site::STALE_DAYS ?>일을 넘으면 "확인 필요" 표시가 붙습니다.</p>
</section>
<section class="ws-sec">
<h2>라이선스</h2>
<p>문서와 스킬 본문은 CC BY-SA 4.0, 예시 코드와 스크립트는 MIT입니다.</p>
</section>
</div>
