<?php
/** 문서 읽는 법: 번호 체계·문서 정보·절 구성·기준 번호. @var Site $site @var string $title @var string $lead */
$prefixes = [];
foreach ($site->repo->all() as $i) {
    $p = explode('-', $i->id)[1];
    $prefixes[$p] ??= $i;
}
ksort($prefixes);
$meta = [
    ['게시일', '문서를 처음 공개한 날짜'],
    ['최종 확인일', '출처와 내용을 마지막으로 대조한 날짜'],
    ['다음 검토 예정일', '최종 확인일 + ' . Site::STALE_DAYS . '일 · 이 날이 지나면 "확인 필요" 표시'],
    ['적용 대상', '해당하는 언어·프레임워크·서비스 환경'],
    ['심각도 근거', '영향 · 악용 한 줄 요약 · 판정 기준은 심각도 기준'],
    ['개정 이력', '개정 날짜와 변경 요지 · 최신이 위'],
];
$sections = [
    ['요약', '이슈의 정의 한두 문장', ''],
    ['발생 상황', '문제가 실제로 생기는 장면', ''],
    ['위험 배경', '최근 더 위험해진 이유와 공개 사례', ''],
    ['대응 방법', 'AI 코딩 도구에 붙여 넣을 프롬프트와 확인 방법', '프롬프트 복사 버튼'],
    ['점검 항목', '코드가 지켜야 할 조건', '체크 상자 · 이 브라우저에만 저장'],
    ['탐지 규칙', '스킬이 코드에서 찾는 곳과 지적 기준', '기본 접힘'],
    ['코드 예시', '언어별 취약 코드와 안전 코드', '넓은 화면에서 오른쪽'],
    ['출처', '근거로 쓴 공개 자료와 연도', '기본 접힘'],
];
$chips = [['owasp2025', 'A05'], ['asvs5', 'V1.2'], ['cwe', 'CWE-89']];
?>
<div class="ws-page ws-page--doc">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>

<section class="ws-sec" aria-labelledby="g-id">
<h2 id="g-id">문서 번호</h2>
<p class="ws-idform"><code>WS-&lt;분류&gt;-&lt;번호&gt;</code></p>
<ul>
<li>분류: 이슈 종류를 나타내는 영문 약어 2~5자. 예: <code>INJ</code> 인젝션, <code>SSRF</code> 서버사이드 요청 위조.</li>
<li>번호: 분류 안의 세 자리 일련번호.</li>
<li>번호 불변: 제목·내용·묶음이 바뀌어도 같은 번호 유지. 인용과 링크는 번호 기준. 폐지한 번호의 재사용 없음.</li>
</ul>
<?php if ($prefixes): ?>
<div class="ws-table-wrap">
<table class="ws-table ws-table--def">
<caption class="ws-sr">현재 쓰는 분류 약어</caption>
<thead><tr><th scope="col">분류</th><th scope="col">예</th></tr></thead>
<tbody>
<?php foreach ($prefixes as $p => $i): ?>
<tr><th scope="row"><code><?= Site::e($p) ?></code></th><td><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><?= Site::e($i->title) ?></a> <span class="ws-id"><?= Site::e($i->id) ?></span></td></tr>
<?php endforeach ?>
</tbody>
</table>
</div>
<?php endif ?>
</section>

<section class="ws-sec" aria-labelledby="g-meta">
<h2 id="g-meta">문서 정보</h2>
<p>제목 아래 문서 정보 줄과 문서 끝의 개정 이력.</p>
<dl class="ws-deflist">
<?php foreach ($meta as [$k, $v]): ?>
<div><dt><?= Site::e($k) ?></dt><dd><?= Site::e($v) ?></dd></div>
<?php endforeach ?>
</dl>
</section>

<section class="ws-sec" aria-labelledby="g-sec">
<h2 id="g-sec">절 구성</h2>
<p>깊은 문서의 고정 순서. 간단한 문서는 요약 · 점검 항목 · 코드 예시 중심.</p>
<ol class="ws-seclist">
<?php foreach ($sections as [$name, $desc, $note]): ?>
<li><span class="ws-seclist-name"><?= Site::e($name) ?></span><span class="ws-seclist-desc"><?= Site::e($desc) ?><?php if ($note !== ''): ?><span class="ws-seclist-note"><?= Site::e($note) ?></span><?php endif ?></span></li>
<?php endforeach ?>
</ol>
<p>절 제목 옆 <span class="ws-hash" aria-hidden="true">#</span> 표시: 그 절의 고정 주소 복사.</p>
</section>

<section class="ws-sec" aria-labelledby="g-chip">
<h2 id="g-chip">기준 번호</h2>
<p>제목 아래 칩: 이 이슈가 대응하는 공인 보안 기준의 항목 번호. 앞은 기준 이름, 뒤는 항목 번호. CWE 번호는 MITRE 정의 페이지로 연결.</p>
<ul class="ws-maps" aria-label="기준 번호 예">
<?php foreach ($chips as [$k, $c]): ?><?= Site::mapChip($k, $c) ?><?php endforeach ?>
</ul>
<p>기준 8종 전체와 항목별 대응 이슈는 <a href="<?= Site::e($site->url('coverage')) ?>">점검 기준 대응표</a>.</p>
</section>
</div>
