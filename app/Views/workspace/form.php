<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-heading compact-heading"><div><div class="eyebrow"><a href="<?= site_url('/area/' . $module) ?>"><?= esc($definition['title']) ?></a> <span>·</span> FORM DATA</div><h1><?= esc($title) ?></h1><p>Lengkapi informasi di bawah ini untuk memperbarui data operasional.</p></div><a class="button button-soft" href="<?= site_url('/area/' . $module) ?>">← Kembali</a></div>
<section class="panel form-panel"><form method="post" action="<?= site_url('/area/' . $module . '/simpan') ?>" class="record-form"><?= csrf_field() ?>
    <?php foreach ($fields as $field): ?>
        <div class="form-field <?= $field['type'] === 'textarea' ? 'field-wide' : '' ?>">
            <label for="field-<?= esc($field['name']) ?>"><?= esc($field['label']) ?><?= $field['required'] ? '<span class="required-mark">*</span>' : '' ?></label>
            <?php if (str_starts_with($field['type'], 'select:')): $choices = explode(',', substr($field['type'], 7)); ?>
                <select id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" <?= $field['required'] ? 'required' : '' ?>><option value="">Pilih <?= esc(strtolower($field['label'])) ?></option><?php foreach ($choices as $choice): ?><option value="<?= esc($choice) ?>" <?= old($field['name']) === $choice ? 'selected' : '' ?>><?= esc(match ($choice) { 'pppoe' => 'PPPoE', 'hotspot' => 'Hotspot', '1' => 'Ya', '0' => 'Tidak', default => ucfirst($choice) }) ?></option><?php endforeach; ?></select>
            <?php elseif (str_starts_with($field['type'], 'options:')): ?>
                <select id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" <?= $field['required'] ? 'required' : '' ?>><option value="">Pilih <?= esc(strtolower($field['label'])) ?></option><?php foreach ($field['options'] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= (string) old($field['name']) === (string) $option['id'] ? 'selected' : '' ?>><?= esc($option['name']) ?></option><?php endforeach; ?></select>
            <?php elseif ($field['type'] === 'textarea'): ?>
                <textarea id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" rows="4" <?= $field['required'] ? 'required' : '' ?> placeholder="<?= esc($field['label']) ?>"><?= esc(old($field['name']) ?? '') ?></textarea>
            <?php else: ?>
                <input id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" type="<?= esc($field['type']) ?>" value="<?= esc(old($field['name']) ?? '') ?>" <?= $field['required'] ? 'required' : '' ?> <?= $field['type'] === 'number' ? 'min="0" step="0.01"' : '' ?> <?= $field['type'] === 'password' ? 'minlength="12" autocomplete="new-password"' : '' ?> placeholder="<?= esc($field['label']) ?>">
            <?php endif; ?>
            <?php if ($field['name'] === 'password'): ?><small class="field-help">Kata sandi awal tidak akan tampil lagi dan pengguna wajib menggantinya saat masuk pertama kali.</small><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="form-actions"><a class="button button-soft" href="<?= site_url('/area/' . $module) ?>">Batal</a><button type="submit" class="button button-primary">Simpan data <span>→</span></button></div>
</form></section>
<?= $this->endSection() ?>
