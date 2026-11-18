<?php
/** @var Site $site @var Issue $issue */
$checked = $issue->checked->format('Y-m-d');
$published = $issue->published->format('Y-m-d');
$maps = array_filter($issue->maps);
$addr = $site->setting('origin') . $site->url('issues/' . $issue->id);
$fold = function (string $id, string $heading, string $body, string $count = ''): string {
    return '<section class="ws-sec ws-fold" id="' . Site::e($id) . '">'
        . '<details><summary><h2>' . Site::e($heading) . '</h2>'
        . ($count !== '' ? '<span class="ws-fold-count">' . Site::e($count) . '</span>' : '')
        . '<span class="ws-fold-hint" aria-hidden="true"><span class="ws-fold-closed">펼치기</span><span class="ws-fold-open">접기</span></span></summary>'
        . '<div class="ws-fold-body">' . $body . '</div></details>'
        . '<a class="ws-anchor" href="#' . Site::e($id) . '" aria-label="' . Site::e($heading) . ' 절 주소 복사"><span aria-hidden="true">#</span></a></section>';
};
$toc = [];
if (isset($issue->sections['요약'])) $toc[] = ['summary', '요약'];
$n = 0;
$body = '';
foreach ($issue->sections as $heading => $md) {
    if ($heading === '코드 예시' || $heading === '요약') continue;
    $n++;
    $id = IssueView::sectionId($heading, $n);
    $toc[] = [$id, $heading];
    $html = Markdown::render($heading === '탐지 규칙' && $issue->detection !== null ? IssueView::detectionMarkdown($issue->detection) : $md);
    if ($heading === '탐지 규칙') $html = IssueView::reportLines($html);
    $html = IssueView::blocks($html, $heading === '대응 방법' ? '프롬프트' : '예시');
    if (in_array($heading, IssueView::FOLDED, true)) { $body .= $fold($id, $heading, $html); continue; }
    $extra = '';
    if ($heading === '점검 항목') {
        [$html, $count] = IssueView::checklist($html, $issue->id);
        if ($count) $extra = '<p class="ws-check-status" data-check-status="' . Site::e($issue->id) . '"><span class="ws-check-n"><span data-check-count>0</span> / ' . $count . ' 완료</span>'
            . '<button type="button" class="ws-linkbtn" data-check-reset><span class="ws-tx">초기화</span></button></p>';
    }
    $body .= '<section class="ws-sec" aria-labelledby="' . $id . '"><div class="ws-sec-head"><h2 id="' . $id . '">' . Site::e($heading) . '</h2>'
        . '<a class="ws-anchor" href="#' . $id . '" aria-label="' . Site::e($heading) . ' 절 주소 복사"><span aria-hidden="true">#</span></a></div>'
        . $extra . $html . '</section>';
}
if ($issue->code) $toc[] = ['code', '코드 예시'];
$toc[] = ['sources', '출처'];
if ($issue->revisions) $toc[] = ['revisions', '개정 이력'];
?>
<div class="ws-issue<?= $issue->code ? '' : ' ws-issue--nocode' ?>">
<article class="ws-doc">
<p class="ws-print-meta"><span><?= Site::e($issue->id) ?></span><span>최종 확인 <?= Site::e($checked) ?></span><span><?= Site::e($addr) ?></span></p>
<header class="ws-doc-head">
<p class="ws-docid"><span class="ws-id"><?= Site::e($issue->id) ?></span><i></i><span><?= Site::e($site->groupName($issue->group)) ?></span><i></i><span><?= $issue->depth === 'full' ? '상세 문서' : '간략 문서' ?></span></p>
<h1><?= Site::t($issue->title) ?></h1>
<div class="ws-sevline"><span class="ws-sevline-badges"><?= Site::severityBadge($issue->severity) ?></span><?php if ($issue->severityReason !== null): ?><span class="ws-sr">심각도 근거</span><?php if (preg_match('/^영향:\s*(.+?)\s+·\s+악용:\s*(.+)$/u', $issue->severityReason, $why)): ?><dl class="ws-sevwhy"><div><dt>영향</dt><dd><?= Site::t($why[1]) ?></dd></div><div><dt>악용</dt><dd><?= Site::t($why[2]) ?></dd></div></dl><?php else: ?><p class="ws-sevwhy-text"><?= Site::t($issue->severityReason) ?></p><?php endif ?><?php endif ?></div>
<dl class="ws-docinfo">
<div><dt>게시</dt><dd><time datetime="<?= Site::e($published) ?>"><?= Site::e($published) ?></time></dd></div>
<div><dt>최종 확인</dt><dd><time datetime="<?= Site::e($checked) ?>"><?= Site::e($checked) ?></time></dd></div>
<?php if ($issue->appliesTo): ?>
<div class="ws-docinfo-wide"><dt>적용 대상</dt><dd><?= Site::t(implode(' · ', $issue->appliesTo)) ?></dd></div>
<?php endif ?>
</dl>
<?php if ($maps): ?>
<ul class="ws-maps" aria-label="기준 번호">
<?php foreach ($maps as $key => $codes): foreach ($codes as $c): ?><?= Site::mapChip($key, $c) ?>
<?php endforeach; endforeach ?>
</ul>
<?php endif ?>
<?php if ($issue->code): ?>
<p class="ws-jump"><a href="#code">코드 예시로 이동</a></p>
<?php endif ?>
</header>

<?php if (isset($issue->sections['요약'])): ?>
<section class="ws-summary" id="summary" aria-labelledby="summary-h">
<h2 id="summary-h">요약</h2>
<?= Markdown::render($issue->sections['요약']) ?>
</section>
<?php endif ?>

<?= $body ?>

<?php
$src = '<ol class="ws-sources">';
foreach ($issue->sources as $s) $src .= '<li><a href="' . Site::e($s['url']) . '" rel="noopener noreferrer">' . Site::t($s['title']) . '</a><span class="ws-year">' . Site::e((string)$s['year']) . '</span></li>';
echo $fold('sources', '출처', $src . '</ol>', count($issue->sources) . '건');
if ($issue->revisions) {
    $rev = '<ol class="ws-revlist">';
    foreach ($issue->revisions as $r) $rev .= '<li><time datetime="' . Site::e($r['date']) . '">' . Site::e($r['date']) . '</time><span>' . Site::t($r['note']) . '</span></li>';
    echo $fold('revisions', '개정 이력', $rev . '</ol>', count($issue->revisions) . '건');
}
?>

<p class="ws-checked"><span>최종 확인 <time datetime="<?= Site::e($checked) ?>"><?= Site::e($checked) ?></time></span></p>
</article>

<div class="ws-rail">
<nav class="ws-toc" aria-labelledby="ws-toc-h">
<h2 id="ws-toc-h">이 문서의 목차</h2>
<ol>
<?php foreach ($toc as [$id, $label]): ?>
<li><a href="#<?= Site::e($id) ?>"><span class="ws-tx"><?= Site::e($label) ?></span></a></li>
<?php endforeach ?>
</ol>
</nav>
<?php if ($issue->code): $langs = array_keys($issue->code); ?>
<aside class="ws-code" id="code" aria-labelledby="code-h">
<div class="ws-code-head">
<h2 id="code-h">코드 예시</h2>
<div class="ws-tabs" role="tablist" aria-label="언어">
<?php foreach ($langs as $k => $lang): ?>
<button type="button" class="ws-tab" role="tab" id="tab-<?= Site::e($lang) ?>" aria-controls="code-<?= Site::e($lang) ?>" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>" tabindex="<?= $k === 0 ? '0' : '-1' ?>" data-lang="<?= Site::e($lang) ?>"><span class="ws-tx"><?= Site::e(Site::langLabel($lang)) ?></span></button>
<?php endforeach ?>
</div>
</div>
<?php foreach ($issue->code as $lang => $pair): ?>
<div class="ws-code-panel<?= $lang === $langs[0] ? ' is-active' : '' ?>" id="code-<?= Site::e($lang) ?>" role="tabpanel" aria-labelledby="tab-<?= Site::e($lang) ?>" data-lang-panel="<?= Site::e($lang) ?>">
<h3 class="ws-code-lang"><?= Site::e(Site::langLabel($lang)) ?></h3>
<?php foreach (['bad' => '✗ 취약 코드', 'good' => '✓ 안전 코드'] as $kind => $label): ?>
<figure class="ws-snippet ws-snippet--<?= $kind ?>">
<figcaption><span class="ws-snippet-label"><?= $label ?></span><button type="button" class="ws-copy" data-copy aria-label="<?= Site::e(Site::langLabel($lang)) ?> <?= $kind === 'bad' ? '취약' : '안전' ?> 코드 복사"><span class="ws-tx">복사</span></button></figcaption>
<pre tabindex="0"><code class="lang-<?= Site::e(Stacks::lang($lang)) ?>"><?= Site::e($pair[$kind]) ?></code></pre>
</figure>
<?php endforeach ?>
</div>
<?php endforeach ?>
</aside>
<?php endif ?>
</div>
</div>
