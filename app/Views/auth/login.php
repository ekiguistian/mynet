<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Mynet Billing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/mynet.css') ?>">
</head>
<body class="login-page">
<div class="login-ambient ambient-one"></div><div class="login-ambient ambient-two"></div>
<div class="login-wrap">
    <div class="login-brand"><span class="brand-mark"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 22V10h5v2.2a5.8 5.8 0 0 1 4.8-2.6c3.6 0 5.8 2.5 5.8 6.1V22h-5.1v-5.4c0-1.8-.9-2.9-2.4-2.9s-2.5 1.1-2.5 2.9V22H6Zm18.5 0V7h5v15h-5Z" fill="currentColor"/></svg></span><span><strong>mynet</strong><small>NETWORK MANAGER</small></span></div>
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
</div>
<div class="login-side-note"><span class="pulse-line"></span><span>MENGELOLA KONEKTIVITAS<br><strong>DENGAN LEBIH BAIK.</strong></span></div>
<script src="<?= base_url('assets/js/mynet.js') ?>" defer></script>
</body>
</html>
