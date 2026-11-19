<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= esc($title ?? 'Mynet') ?> · Mynet Billing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/mynet.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mynet-overrides.css') ?>">
</head>
<body class="app-shell">
<div class="mobile-scrim" data-menu-close></div>
<aside class="sidebar" id="sidebar">
    <a href="<?= site_url('/dashboard') ?>" class="brand">
        <span class="brand-mark"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 22V10h5v2.2a5.8 5.8 0 0 1 4.8-2.6c3.6 0 5.8 2.5 5.8 6.1V22h-5.1v-5.4c0-1.8-.9-2.9-2.4-2.9s-2.5 1.1-2.5 2.9V22H6Zm18.5 0V7h5v15h-5Z" fill="currentColor"/></svg></span>
        <span class="brand-copy"><strong>mynet</strong><small>NETWORK MANAGER</small></span>
    </a>
    <div class="workspace-label"><span class="status-dot"></span> OPERASIONAL <span class="workspace-caret">⌄</span></div>
    <div class="nav-caption">MENU UTAMA</div>
    <nav class="side-nav" aria-label="Menu utama">
        <?php foreach (($menus ?? []) as $menu): ?>
            <?php $active = (uri_string() === ltrim($menu['url'], '/') || str_starts_with(uri_string(), ltrim($menu['url'], '/') . '/')); ?>
            <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= site_url($menu['url']) ?>">
                <span class="nav-icon"><?= esc($menu['icon']) ?></span><span><?= esc($menu['label']) ?></span>
                <?php if ($menu['label'] === 'Tagihan' && ! empty($unpaidCount)): ?><span class="nav-count"><?= (int) $unpaidCount ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="support-card">
            <span class="support-orbit">✳</span>
            <strong>Butuh bantuan?</strong>
            <p>Pusat bantuan dan panduan penggunaan Mynet.</p>
            <a href="<?= site_url('/area/routers') ?>">Buka panduan <span>↗</span></a>
        </div>
        <div class="sidebar-user">
            <div class="avatar avatar-small"><?= esc(mb_substr((string) mynet_user('name'), 0, 1)) ?></div>
            <div class="sidebar-user-copy"><strong><?= esc(mynet_user('name')) ?></strong><small><?= esc(mynet_role_label((string) mynet_user('role'))) ?></small></div>
            <form method="post" action="<?= site_url('/logout') ?>" class="logout-form"><?= csrf_field() ?><button class="logout-dot" type="submit" aria-label="Keluar" title="Keluar">↗</button></form>
        </div>
    </div>
</aside>

<main class="main-area">
    <header class="topbar">
        <button type="button" class="mobile-menu-button" aria-label="Buka menu" data-menu-open>☰</button>
        <div class="topbar-breadcrumb"><span>Workspace</span><i>/</i><strong><?= esc($title ?? 'Ringkasan') ?></strong></div>
        <div class="topbar-actions">
            <div class="branch-pill"><span class="branch-pin">⌖</span><span><?= esc(mynet_user('branch_name') ?: 'Semua cabang') ?></span><span class="chevron">⌄</span></div>
            <button class="icon-button" type="button" aria-label="Notifikasi">♧<span class="notification-dot"></span></button>
            <div class="topbar-profile"><div class="avatar"><?= esc(mb_substr((string) mynet_user('name'), 0, 1)) ?></div><span><?= esc(mynet_user('name')) ?></span><span class="chevron">⌄</span></div>
        </div>
    </header>

    <section class="page-content">
        <?php if (session()->getFlashdata('success')): ?><div class="flash flash-success"><span>✓</span><?= esc(session()->getFlashdata('success')) ?><button type="button" data-dismiss>×</button></div><?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?><div class="flash flash-error"><span>!</span><?= esc(session()->getFlashdata('error')) ?><button type="button" data-dismiss>×</button></div><?php endif; ?>
        <?php if (session()->getFlashdata('warning')): ?><div class="flash flash-warning"><span>!</span><?= esc(session()->getFlashdata('warning')) ?><button type="button" data-dismiss>×</button></div><?php endif; ?>
        <?= $this->renderSection('content') ?>
    </section>
</main>
<script src="<?= base_url('assets/js/mynet.js') ?>" defer></script>
</body>
</html>
