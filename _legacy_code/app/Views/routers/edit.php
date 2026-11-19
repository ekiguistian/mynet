<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-heading compact-heading"><div><div class="eyebrow"><a href="<?= site_url('/area/routers') ?>">Router MikroTik</a> <span>·</span> KONFIGURASI</div><h1>Edit router</h1><p>Ubah endpoint API dan kredensial router. Kata sandi kosong mempertahankan kata sandi yang tersimpan.</p></div><a class="button button-soft" href="<?= site_url('/area/routers') ?>">← Kembali</a></div>
<section class="panel form-panel"><form method="post" action="<?= site_url('/routers/' . $router['id'] . '/update') ?>" class="record-form"><?= csrf_field() ?>
    <div class="form-field"><label>Nama router</label><input name="name" value="<?= esc(old('name') ?? $router['name']) ?>" required maxlength="120"></div>
    <div class="form-field"><label>IP Public / VPN</label><input name="host" value="<?= esc(old('host') ?? $settings['host']) ?>" required></div>
    <div class="form-field"><label>Port API</label><input type="number" min="1" max="65535" name="api_port" value="<?= esc(old('api_port') ?? $settings['port']) ?>" required></div>
    <div class="form-field"><label>Username</label><input name="username" value="<?= esc(old('username') ?? $settings['username']) ?>" required autocomplete="off"></div>
    <div class="form-field"><label>Password baru (opsional)</label><input type="password" name="password" autocomplete="new-password"><small class="field-help">Host/IP, port, username, dan password disimpan terenkripsi. Kosongkan password untuk mempertahankan password saat ini.</small></div>
    <div class="form-field"><label>Gunakan SSL</label><select name="api_ssl" required><option value="0" <?= empty($settings['ssl']) ? 'selected' : '' ?>>Tidak</option><option value="1" <?= ! empty($settings['ssl']) ? 'selected' : '' ?>>Ya</option></select><small class="field-help">Pilihan ini mengikuti layanan API yang diaktifkan di RouterOS.</small></div>
    <div class="form-field"><label>Verifikasi sertifikat SSL</label><select name="verify_ssl" required><option value="1" <?= ! empty($settings['verify_ssl']) ? 'selected' : '' ?>>Ya</option><option value="0" <?= empty($settings['verify_ssl']) ? 'selected' : '' ?>>Tidak</option></select></div>
    <?php if (! mynet_user('branch_id')): ?><div class="form-field"><label>Cabang</label><select name="branch_id" required><?php foreach ($branches as $branch): ?><option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === (int) $router['branch_id'] ? 'selected' : '' ?>><?= esc($branch['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
    <div class="form-actions"><a class="button button-soft" href="<?= site_url('/area/routers') ?>">Batal</a><button class="button button-primary" type="submit">Simpan perubahan →</button></div>
</form></section>
<?= $this->endSection() ?>
