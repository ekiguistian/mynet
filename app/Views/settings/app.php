<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-heading compact-heading"><div><div class="eyebrow">KONFIGURASI <span>·</span> APP</div><h1>Pengaturan aplikasi</h1><p>Identitas, logo, favicon, SEO, dan tautan sosial.</p></div></div>
<form method="post" action="<?= site_url('/settings/save') ?>" enctype="multipart/form-data" class="panel form-panel settings-panel">
    <?= csrf_field() ?><div class="record-form">
        <div class="form-field"><label>Nama aplikasi</label><input name="site_name" required maxlength="120" value="<?= esc(old('site_name', $settings['site_name'] ?? 'Mynet Billing')) ?>"></div>
        <div class="form-field"><label>Slogan</label><input name="site_tagline" value="<?= esc(old('site_tagline', $settings['site_tagline'] ?? '')) ?>"></div>
        <div class="form-field"><label>Logo (PNG/JPG · maks. 2 MB)</label><input type="file" name="logo_file" accept="image/png,image/jpeg"><?php if (! empty($settings['logo_url'])): ?><img class="brand-preview" src="<?= base_url($settings['logo_url']) ?>" alt="Logo saat ini"><?php endif; ?></div>
        <div class="form-field"><label>Favicon (PNG / ICO · maks. 2 MB)</label><input type="file" name="favicon_file" accept="image/png,image/x-icon,.ico"><?php if (! empty($settings['favicon_url'])): ?><img class="favicon-preview" src="<?= base_url($settings['favicon_url']) ?>" alt="Favicon saat ini"><?php endif; ?></div>
        <div class="form-field"><label>Telepon publik</label><input name="public_phone" value="<?= esc(old('public_phone', $settings['public_phone'] ?? '')) ?>"></div>
        <div class="form-field"><label>Alamat publik</label><input name="public_address" value="<?= esc(old('public_address', $settings['public_address'] ?? '')) ?>"></div>
        <div class="form-field field-wide"><label>URL canonical</label><input type="url" name="canonical_url" placeholder="https://domain-anda.id" value="<?= esc(old('canonical_url', $settings['canonical_url'] ?? '')) ?>"></div>
        <div class="form-field field-wide"><label>Deskripsi SEO</label><textarea name="seo_description" rows="3" maxlength="320"><?= esc(old('seo_description', $settings['seo_description'] ?? '')) ?></textarea></div>
        <div class="form-field field-wide"><label>Kata kunci SEO</label><input name="seo_keywords" value="<?= esc(old('seo_keywords', $settings['seo_keywords'] ?? '')) ?>"></div>
        <div class="form-field field-wide"><label>URL gambar preview sosial / SEO</label><input type="url" name="social_image_url" placeholder="https://domain-anda.id/gambar-share.jpg" value="<?= esc(old('social_image_url', $settings['social_image_url'] ?? '')) ?>"><small class="field-help">Dipakai sebagai Open Graph dan Twitter Card saat halaman dibagikan.</small></div>
        <div class="form-field"><label>Index mesin pencari</label><select name="seo_robots"><option value="index,follow" <?= ($settings['seo_robots'] ?? 'noindex,nofollow') === 'index,follow' ? 'selected' : '' ?>>Index dan ikuti tautan</option><option value="noindex,nofollow" <?= ($settings['seo_robots'] ?? 'noindex,nofollow') === 'noindex,nofollow' ? 'selected' : '' ?>>Jangan index</option></select></div>
        <div class="form-field"><label>Google Search Console verification</label><input name="google_site_verification" value="<?= esc($settings['google_site_verification'] ?? '') ?>"></div>
        <div class="form-field"><label>Google Tag ID</label><input name="google_tag_id" placeholder="G-XXXXXXXXXX atau GTM-XXXXXXX" value="<?= esc(old('google_tag_id', $settings['google_tag_id'] ?? '')) ?>"><small class="field-help">Masukkan ID terverifikasi; snippet Google Tag dimuat otomatis.</small></div>
        <div class="form-field"><label>Facebook</label><input type="url" name="social_facebook" value="<?= esc(old('social_facebook', $settings['social_facebook'] ?? '')) ?>"></div>
        <div class="form-field"><label>Instagram</label><input type="url" name="social_instagram" value="<?= esc(old('social_instagram', $settings['social_instagram'] ?? '')) ?>"></div>
        <div class="form-field"><label>TikTok</label><input type="url" name="social_tiktok" value="<?= esc(old('social_tiktok', $settings['social_tiktok'] ?? '')) ?>"></div>
        <div class="form-field"><label>YouTube</label><input type="url" name="social_youtube" value="<?= esc(old('social_youtube', $settings['social_youtube'] ?? '')) ?>"></div>
        <div class="form-field"><label>X</label><input type="url" name="social_x" value="<?= esc(old('social_x', $settings['social_x'] ?? '')) ?>"></div>
        <div class="form-actions"><button class="button button-primary" type="submit">Simpan pengaturan</button></div>
    </div>
</form>
<?= $this->endSection() ?>
