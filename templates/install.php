<?php /** @var Site $site */
$cmds = [
    ['Claude Code', 'Claude Code 플러그인으로 설치', "/plugin marketplace add juslim-afk/white-security\n/plugin install white-security@white-security"],
    ['여러 도구 공용', 'skills 형식을 읽는 코딩 도구에 설치', 'npx skills add juslim-afk/white-security'],
];
?>
<div class="ws-page">
<h1>설치</h1>
<p class="ws-page-lead">코딩 도구에 스킬 설치 · 이후 코드를 쓰거나 검토할 때 스킬이 스스로 점검</p>
<?php foreach ($cmds as [$name, $desc, $cmd]): ?>
<section class="ws-sec">
<h2><?= Site::e($name) ?></h2>
<p><?= Site::e($desc) ?></p>
<figure class="ws-snippet ws-snippet--cmd">
<figcaption><span class="ws-snippet-label">명령</span><button type="button" class="ws-copy" data-copy aria-label="<?= Site::e($name) ?> 설치 명령 복사"><span class="ws-tx">복사</span></button></figcaption>
<pre tabindex="0"><code><?= Site::e($cmd) ?></code></pre>
</figure>
</section>
<?php endforeach ?>
<section class="ws-sec">
<h2>쓰기 전에</h2>
<p>스킬은 흔한 실수를 크게 줄이는 용도이며, 보안 감사나 침투 테스트를 대신하지 않습니다.</p>
</section>
</div>
