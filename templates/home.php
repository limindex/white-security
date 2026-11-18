<?php /** @var Site $site @var array $groups @var array $byGroup */ ?>
<h1>화이트 보안</h1>
<?php foreach ($groups as $g): $list = $byGroup[$g['id']] ?? []; if (!$list) continue; ?>
<section>
<h2><?= Site::e($g['name']) ?></h2>
<ul>
<?php foreach ($list as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"><?= Site::e($i->title) ?></a> <small><?= Site::e($i->id) ?> · <?= Site::e($i->severity) ?></small></li>
<?php endforeach ?>
</ul>
</section>
<?php endforeach ?>
