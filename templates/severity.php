<?php
/** 심각도 기준: 영향 × 악용 쉬움. @var Site $site @var string $title @var string $lead */
$impact = [
    'big' => ['큼', '데이터 유출(다수 사용자·DB 전체) · 계정 탈취 · 서버 명령 실행 · 사용량 과금 폭증 같은 큰 비용'],
    'mid' => ['중간', '일부 정보 노출 · 서비스 중단과 속도 저하 · 한도 안의 비용'],
    'low' => ['작음', '단독으로는 피해 없음 · 다른 결함과 결합해야 의미가 생기는 노출'],
];
$ease = [
    'easy' => ['쉬움', '인증 불필요 · 입력 하나나 주소 하나로 가능 · 자동화 도구나 공개된 공격 방법 존재'],
    'norm' => ['보통', '일반 사용자 로그인 필요 · 특정 기능이나 설정 조건 필요'],
    'hard' => ['어려움', '관리자 권한이나 내부 접근 필요 · 여러 단계나 사용자 속임 필요'],
];
/** 판정표: 영향 행 × 악용 쉬움 열 */
$matrix = [
    'big' => ['easy' => 'high', 'norm' => 'high', 'hard' => 'medium'],
    'mid' => ['easy' => 'medium', 'norm' => 'medium', 'hard' => 'low'],
    'low' => ['easy' => 'low', 'norm' => 'low', 'hard' => 'low'],
];
$grades = [
    'high' => ['큰 피해가 쉽게 일어나는 상태', '배포 전 수정'],
    'medium' => ['피해가 제한되거나 악용에 조건이 필요한 상태', '다음 수정 계획에 포함'],
    'low' => ['단독으로는 피해가 작은 상태', '여유 있을 때 개선'],
];
$examples = [
    ['high', 'WS-INJ-001', 'SQL 인젝션 (SQLi)', 'DB 전체 조회·변조·삭제', '입력 칸 하나에 넣는 글자만으로 가능, 자동화 도구 흔함'],
    ['medium', 'WS-BOT-001', 'Scraping · AI Crawler (AI 크롤러 대량 수집)', '서버 비용 증가·속도 저하, 데이터 유출·변조는 아님', '공격 의도 없는 대량 수집으로도 발생'],
    ['low', null, '응답 헤더의 서버 버전 노출 (가상 예)', '단독 피해 없음, 알려진 취약점 탐색의 단서', '누구나 응답 헤더로 확인'],
];
?>
<div class="ws-page ws-page--doc">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<section class="ws-sec" aria-labelledby="sev-how">
<h2 id="sev-how">판정 방식</h2>
<p>심각도 = 영향 × 악용 쉬움. 두 축을 각각 세 단계로 판정한 뒤 판정표의 조합으로 등급 결정. 구조는 <a href="https://owasp.org/www-community/OWASP_Risk_Rating_Methodology" rel="noopener noreferrer">OWASP Risk Rating Methodology</a>의 가능성 × 영향을 세 단계로 줄인 방식.</p>
<dl class="ws-grades">
<?php foreach ($grades as $sev => [$def, $when]): ?>
<div>
<dt><?= Site::severityBadge($sev) ?></dt>
<dd><span><?= Site::e($def) ?></span><span class="ws-grades-when"><?= Site::e($when) ?></span></dd>
</div>
<?php endforeach ?>
</dl>
</section>

<section class="ws-sec" aria-labelledby="sev-impact">
<h2 id="sev-impact">축 1 · 영향</h2>
<p>문제가 실제로 악용됐을 때의 피해 크기.</p>
<div class="ws-table-wrap">
<table class="ws-table ws-table--def">
<thead><tr><th scope="col">단계</th><th scope="col">기준</th></tr></thead>
<tbody>
<?php foreach ($impact as [$name, $desc]): ?>
<tr><th scope="row"><?= Site::e($name) ?></th><td><?= Site::t($desc) ?></td></tr>
<?php endforeach ?>
</tbody>
</table>
</div>
</section>

<section class="ws-sec" aria-labelledby="sev-ease">
<h2 id="sev-ease">축 2 · 악용 쉬움</h2>
<p>공격자가 넘어야 하는 문턱. 인증 필요 여부와 자동화 가능성이 핵심.</p>
<div class="ws-table-wrap">
<table class="ws-table ws-table--def">
<thead><tr><th scope="col">단계</th><th scope="col">기준</th></tr></thead>
<tbody>
<?php foreach ($ease as [$name, $desc]): ?>
<tr><th scope="row"><?= Site::e($name) ?></th><td><?= Site::t($desc) ?></td></tr>
<?php endforeach ?>
</tbody>
</table>
</div>
</section>

<section class="ws-sec" aria-labelledby="sev-matrix">
<h2 id="sev-matrix">판정표</h2>
<div class="ws-table-wrap">
<table class="ws-matrix">
<caption class="ws-sr">영향(행)과 악용 쉬움(열)에 따른 심각도</caption>
<thead><tr><td></td><?php foreach ($ease as [$name]): ?><th scope="col">악용 <?= Site::e($name) ?></th><?php endforeach ?></tr></thead>
<tbody>
<?php foreach ($matrix as $row => $cols): ?>
<tr><th scope="row">영향 <?= Site::e($impact[$row][0]) ?></th><?php foreach ($cols as $sev): ?><td><?= Site::severityBadge($sev) ?></td><?php endforeach ?></tr>
<?php endforeach ?>
</tbody>
</table>
</div>
</section>

<section class="ws-sec" aria-labelledby="sev-ex">
<h2 id="sev-ex">판정 예</h2>
<ol class="ws-examples">
<?php foreach ($examples as [$sev, $id, $name, $imp, $how]): $found = $id !== null ? $site->repo->find($id) : null; ?>
<li>
<p class="ws-examples-head"><?= Site::severityBadge($sev) ?><?php if ($found): ?><a href="<?= Site::e($site->url('issues/' . $id)) ?>"><?= Site::e($found->title) ?></a><span class="ws-id"><?= Site::e($id) ?></span><?php else: ?><span class="ws-examples-name"><?= Site::e($name) ?></span><?php if ($id !== null): ?><span class="ws-id"><?= Site::e($id) ?></span><?php endif ?><?php endif ?></p>
<dl class="ws-examples-why">
<div><dt>영향</dt><dd><?= Site::e($imp) ?></dd></div>
<div><dt>악용</dt><dd><?= Site::e($how) ?></dd></div>
</dl>
</li>
<?php endforeach ?>
</ol>
</section>

<section class="ws-sec" aria-labelledby="sev-skill">
<h2 id="sev-skill">문서·스킬 보고 표기</h2>
<dl class="ws-deflist">
<div><dt>이슈 문서</dt><dd>제목 아래 심각도 배지와 심각도 근거(영향·악용 두 줄)</dd></div>
<div><dt>검토 요청 보고</dt><dd>같은 세 등급으로 묶은 표. 행마다 문서 번호·국내 기준·파일:줄·영향·이유·고칠 방법. 등급은 실제 코드를 보고 이 기준(영향 × 악용 쉬움)으로 판정.</dd></div>
<div><dt>환경에 따른 차이</dt><dd>실제 환경에 따라 보고 등급과 문서 등급이 다를 수 있음. 관리자 로그인 뒤에만 있는 기능은 악용 쉬움 한 단계 하향.</dd></div>
</dl>
<?= $site->render('_report', []) ?>
</section>
</div>
