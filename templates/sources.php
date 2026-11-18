<?php /** @var Site $site @var string $title @var string $lead */
$ext = '<svg class="ws-ext" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true" focusable="false"><path d="M4.5 2.5h5v5M9.5 2.5l-7 7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$licenses = [
    ['문서·스킬 본문', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/deed.ko', '출처 표시 후 복제·배포·수정 가능 · 수정본도 같은 라이선스로 공개'],
    ['예시 코드·스크립트', 'MIT', 'https://opensource.org/license/mit', '저작권·라이선스 고지 유지 시 상업적 이용 포함 자유 사용'],
    ['글꼴', 'SIL Open Font License 1.1', 'https://openfontlicense.org/', 'Pretendard · Manrope · JetBrains Mono'],
];
$rules = [
    ['문서별 출처', '각 이슈 문서 끝 출처 절 · 자료 이름·주소·발행 연도'],
    ['기준 번호', 'OWASP·CWE·국내 점검 기준의 항목 번호를 원문 그대로 표기'],
    ['해외 자료 각색', '요약해 점검 항목 형식으로 다시 쓴 부분은 저장소 SOURCES.md에 원문·바꾼 점과 함께 기록'],
    ['최종 확인일', '출처와 내용을 마지막으로 대조한 날짜 · 각 이슈 문서 머리에 표기'],
];
$refs = $site->sourceIndex();
?>
<div class="ws-page">
<?= $site->render('_pagehead', ['title' => $title, 'lead' => $lead]) ?>
<section class="ws-sec" aria-labelledby="ws-lic-h">
<h2 id="ws-lic-h">라이선스</h2>
<dl class="ws-deflist">
<?php foreach ($licenses as [$what, $name, $url, $note]): ?>
<div><dt><?= Site::e($what) ?></dt><dd><a class="ws-deflink" href="<?= Site::e($url) ?>" rel="noopener noreferrer"><span><?= Site::e($name) ?></span><?= $ext ?></a><span class="ws-dd-note"><?= Site::t($note) ?></span></dd></div>
<?php endforeach ?>
</dl>
</section>
<section class="ws-sec" aria-labelledby="ws-cite-h">
<h2 id="ws-cite-h">인용 원칙</h2>
<dl class="ws-deflist">
<?php foreach ($rules as [$k, $v]): ?>
<div><dt><?= Site::e($k) ?></dt><dd><?= Site::t($v) ?></dd></div>
<?php endforeach ?>
</dl>
</section>
<?php if ($refs): ?>
<section class="ws-sec ws-fold" id="refs">
<details><summary><h2>인용 자료</h2><span class="ws-fold-count"><?= count($refs) ?>건</span><span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>
<div class="ws-fold-body">
<p class="ws-sec-note"><?= Site::t('모든 이슈 문서의 출처 · 같은 주소는 한 번만 · 기관 주소순') ?></p>
<ol class="ws-reflist">
<?php foreach ($refs as $r): ?>
<li><a class="ws-ref-title" href="<?= Site::e($r['url']) ?>" rel="noopener noreferrer"><?= Site::t($r['title']) ?></a>
<span class="ws-ref-meta"><span class="ws-ref-host"><?= Site::e($r['host']) ?></span><span class="ws-ref-year"><?= Site::e((string)$r['year']) ?></span><?php foreach ($r['ids'] as $id): ?><a class="ws-ref-id" href="<?= Site::e($site->url('issues/' . $id)) ?>"><?= Site::e($id) ?></a><?php endforeach ?></span></li>
<?php endforeach ?>
</ol>
</div>
</details>
</section>
<?php endif ?>
</div>
