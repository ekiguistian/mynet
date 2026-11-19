<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · <?= esc(mynet_setting('site_name', 'Mynet Billing')) ?></title>
    <?php if (mynet_setting('favicon_url')): ?><link rel="icon" href="<?= base_url(mynet_setting('favicon_url')) ?>"><?php else: ?><link rel="icon" href="<?= base_url('favicon.ico') ?>"><?php endif; ?>
    <?php if (mynet_setting('seo_description')): ?><meta name="description" content="<?= esc(mynet_setting('seo_description')) ?>"><?php endif; ?>
    <meta name="robots" content="<?= esc(mynet_setting('seo_robots', 'noindex,nofollow')) ?>">
    <?php if (mynet_setting('social_image_url')): ?><meta property="og:image" content="<?= esc(mynet_setting('social_image_url')) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
    <?php if (mynet_setting('canonical_url')): ?><link rel="canonical" href="<?= esc(rtrim(mynet_setting('canonical_url'), '/') . '/') ?>"><?php endif; ?>
    <?php $googleTagId = mynet_setting('google_tag_id', ''); if (preg_match('/^GTM-[A-Z0-9]+$/', (string) $googleTagId)): ?><script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= esc($googleTagId) ?>');</script><?php elseif (preg_match('/^(G-[A-Z0-9]+|AW-[0-9]+)$/', (string) $googleTagId)): ?><script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($googleTagId) ?>"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= esc($googleTagId) ?>');</script><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/mynet.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mynet-overrides.css') ?>">
</head>
<body class="login-page">
<?php if (preg_match('/^GTM-[A-Z0-9]+$/', (string) $googleTagId)): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc($googleTagId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>
<div class="login-ambient ambient-one"></div><div class="login-ambient ambient-two"></div>
<div class="login-wrap">
    <div class="login-brand"><span class="brand-mark"><?php if (mynet_setting('logo_url')): ?><img src="<?= base_url(mynet_setting('logo_url')) ?>" alt=""><?php else: ?><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 22V10h5v2.2a5.8 5.8 0 0 1 4.8-2.6c3.6 0 5.8 2.5 5.8 6.1V22h-5.1v-5.4c0-1.8-.9-2.5-2.4-2.5s-2.5 1.1-2.5 2.9V22H6Zm18.5 0V7h5v15h-5Z" fill="currentColor"/></svg><?php endif; ?></span><span><strong><?= esc(mynet_setting('site_name', 'mynet')) ?></strong><small><?= esc(mynet_setting('site_tagline', 'NETWORK MANAGER')) ?></small></span></div>
    <div class="login-card">
        <div class="login-eyebrow"><span class="status-dot"></span> PORTAL OPERASIONAL</div>
        <h1>Selamat datang<br>kembali.</h1>
        <p class="login-intro">Masuk ke dashboard pengelolaan jaringan Anda.</p>
        <?php if (session()->getFlashdata('error')): ?><div class="flash flash-error"><span>!</span><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?><div class="flash flash-success"><span>✓</span><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
        <form method="post" action="<?= site_url('/login') ?>" class="login-form">
            <?= csrf_field() ?>
            <label for="username">Username</label>
            <div class="input-wrap"><span class="input-icon">♙</span><input id="username" name="username" type="text" autocomplete="username" placeholder="nama pengguna" value="<?= esc(old('username') ?? '') ?>" required autofocus></div>
            <div class="label-row"><label for="password">Kata sandi</label><a href="#" class="muted-link" aria-label="Hubungi administrator untuk reset kata sandi">Lupa kata sandi?</a></div>
            <div class="input-wrap"><span class="input-icon">⌑</span><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required><button type="button" class="password-toggle" data-password-toggle="password" aria-label="Tampilkan kata sandi">◉</button></div>
            <button type="submit" class="button button-primary button-full">Masuk ke dashboard <span>→</span></button>
        </form>
        <div class="login-security"><span>▣</span> Koneksi Anda dilindungi enkripsi.</div>
    </div>
    <div class="login-footer"><span>© <?= date('Y') ?> Mynet Network Manager</span><span>Privasi &nbsp;·&nbsp; Bantuan</span></div>
    <div class="login-social-links"><?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X'] as $key => $label): ?><?php if (mynet_setting('social_' . $key)): ?><a href="<?= esc(mynet_setting('social_' . $key)) ?>" target="_blank" rel="noopener noreferrer"><?= esc($label) ?></a><?php endif; ?><?php endforeach; ?></div>
</div>
<div class="login-side-note"><span class="pulse-line"></span><span>MENGELOLA KONEKTIVITAS<br><strong>DENGAN LEBIH BAIK.</strong></span></div>
<script src="<?= base_url('assets/js/mynet.js') ?>" defer></script>
</body>
</html>
