<?php
/**
 * 왼쪽 메뉴: 시작하기 · 보안 이슈(묶음별 접힘) · 참고.
 * 좁은 화면에서는 바깥 details 가 "메뉴" 접기 막대가 된다(JS 없으면 펼친 채).
 * @var Site $site @var ?string $current @var ?string $page @var ?string $group
 */
$byGroup = $site->repo->byGroup();
$total = count($site->repo->all());
$section = function (string $name) use ($site, $page): string {
    $out = '<h2 class="ws-nav-h">' . Site::e($name) . '</h2><ul class="ws-nav-pages">';
    foreach ($site->pages() as $slug => $m) {
        if ($m['section'] !== $name || ($m['nav'] ?? true) === false) continue;
        $out .= '<li><a href="' . Site::e($site->url($slug)) . '"' . ($page === $slug ? ' aria-current="page"' : '') . '>' . Site::e($m['title']) . '</a></li>';
    }
    return $out . '</ul>';
};
$where = $page !== null ? Site::PAGES[$page]['title'] ?? '' : ($group !== null ? $site->groupName($group) : '');
?>
<details class="ws-side" id="ws-menu" open>
<summary class="ws-menu-bar"><span class="ws-tx">메뉴</span><?php if ($where !== ''): ?><span class="ws-menu-where"><?= Site::e($where) ?></span><?php endif ?></summary>
<nav class="ws-nav" aria-label="문서 메뉴">
<?= $section(Site::NAV_SECTIONS[0]) ?>
<h2 class="ws-nav-h ws-nav-h--count"><span>보안 이슈</span> <span class="ws-nav-count"><?= $total ?></span></h2>
<?php foreach ($site->repo->groups() as $g): $list = $byGroup[$g['id']] ?? []; if (!$list) continue; ?>
<details class="ws-nav-grp" data-group="<?= Site::e($g['id']) ?>"<?= $group === $g['id'] ? ' open' : '' ?>>
<summary>
<span class="ws-nav-grp-name"><?= Site::e($g['name']) ?></span>
<span class="ws-nav-count"><?= count($list) ?></span>
</summary>
<ul>
<?php foreach ($list as $i): ?>
<li><a href="<?= Site::e($site->url('issues/' . $i->id)) ?>"<?= $current === $i->id ? ' aria-current="page"' : '' ?>><span class="ws-nav-name"><?= Site::e($i->title) ?></span></a></li>
<?php endforeach ?>
</ul>
</details>
<?php endforeach ?>
<?= $section(Site::NAV_SECTIONS[1]) ?>
</nav>
</details>
