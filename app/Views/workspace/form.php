<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-heading compact-heading"><div><div class="eyebrow"><a href="<?= site_url('/area/' . $module) ?>"><?= esc($definition['title']) ?></a> <span>·</span> FORM DATA</div><h1><?= esc($title) ?></h1><p>Lengkapi informasi di bawah ini untuk memperbarui data operasional.</p></div><a class="button button-soft" href="<?= site_url('/area/' . $module) ?>">← Kembali</a></div>
<section class="panel form-panel"><form method="post" action="<?= site_url('/area/' . $module . '/simpan') ?>" class="record-form"><?= csrf_field() ?>
    <?php foreach ($fields as $field): ?>
        <div class="form-field <?= $field['type'] === 'textarea' ? 'field-wide' : '' ?>" <?= $module === 'customers' && in_array($field['name'], ['mikrotik_username', 'mikrotik_password'], true) ? 'data-connection-mode="pppoe"' : ($module === 'customers' && $field['name'] === 'static_ip' ? 'data-connection-mode="static_ip"' : '') ?>>
            <label for="field-<?= esc($field['name']) ?>"><?= esc($field['label']) ?><?= $field['required'] ? '<span class="required-mark">*</span>' : '' ?></label>
            <?php if ($field['name'] === 'created_by_display'): ?>
                <input id="field-created-by" value="<?= esc(mynet_user('name')) ?>" readonly>
                <small class="field-help">Pengguna ini akan dicatat otomatis sebagai pembuat data.</small>
            <?php elseif ($field['name'] === 'customer_code'): ?>
                <div class="input-with-button"><input id="field-customer-code" name="customer_code" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" value="<?= esc(old('customer_code') ?? $generatedCustomerCode ?? '') ?>" required><button type="button" class="button button-soft" data-generate-customer-id data-url="<?= esc(site_url('/area/customers/generate-id')) ?>">Generate</button></div>
                <small class="field-help">ID pelanggan harus unik. Jika ID sama dengan data lain, gunakan tombol Generate untuk membuat ID 10 angka baru.</small>
            <?php elseif ($field['name'] === 'phone'): ?>
                <input id="field-phone" name="phone" type="tel" inputmode="tel" pattern="62[0-9]{8,13}" maxlength="15" value="<?= esc(old('phone') ?? '') ?>" required placeholder="6281234567890">
                <small class="field-help">Gunakan nomor WhatsApp aktif dengan awalan 62.</small>
            <?php elseif (str_starts_with($field['type'], 'select:')): $choices = explode(',', substr($field['type'], 7)); ?>
                <select id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" <?= $field['required'] ? 'required' : '' ?>><option value="">Pilih <?= esc(strtolower($field['label'])) ?></option><?php foreach ($choices as $choice): ?><option value="<?= esc($choice) ?>" <?= (string) old($field['name']) === $choice ? 'selected' : '' ?>><?= esc(match ($choice) { 'pppoe' => 'PPPoE', 'static_ip' => 'IP static', 'hotspot' => 'Voucher hotspot', 'create' => 'Buat baru di MikroTik', 'sync' => 'Sinkronisasi ke MikroTik', 'ont_onu' => 'ONT/ONU', 'router' => 'Router', 'accessory' => 'Aksesori', 'other' => 'Lainnya', '1' => 'Ya', '0' => 'Tidak', default => ucfirst($choice) }) ?></option><?php endforeach; ?></select>
                <?php if ($field['name'] === 'connection_mode'): ?><small class="field-help" data-static-queue-help hidden>IP static selalu disinkronkan sebagai Simple Queue di MikroTik. Profil paket menentukan batas upload dan download.</small><?php endif; ?>
            <?php elseif (str_starts_with($field['type'], 'options:')): ?>
                <select id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" <?= in_array($field['name'], ['plan_id', 'mikrotik_router_id', 'device_serial', 'odp_port_id', 'product_id'], true) ? 'data-branch-filter' : '' ?> <?= $field['name'] === 'plan_id' ? 'data-service-filter' : '' ?> <?= $field['required'] ? 'required' : '' ?>><option value="">Pilih <?= esc(strtolower($field['label'])) ?></option><?php foreach ($field['options'] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= isset($option['branch_id']) ? 'data-branch="' . (int) $option['branch_id'] . '"' : '' ?> <?= isset($option['service_type']) ? 'data-service="' . esc($option['service_type']) . '"' : '' ?> <?= (string) old($field['name']) === (string) $option['id'] ? 'selected' : '' ?>><?= esc($option['name']) ?></option><?php endforeach; ?></select>
            <?php elseif ($field['type'] === 'textarea'): ?>
                <textarea id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" rows="4" <?= $field['required'] ? 'required' : '' ?> placeholder="<?= esc($field['label']) ?>"><?= esc(old($field['name']) ?? '') ?></textarea>
            <?php else: ?>
                <input id="field-<?= esc($field['name']) ?>" name="<?= esc($field['name']) ?>" type="<?= esc($field['type']) ?>" value="<?= esc(old($field['name']) ?? '') ?>" <?= $field['required'] ? 'required' : '' ?> <?= $field['type'] === 'number' ? 'min="0" step="0.01"' : '' ?> <?= $field['name'] === 'password' ? 'minlength="12" autocomplete="new-password"' : ($field['type'] === 'password' ? 'autocomplete="new-password"' : '') ?> placeholder="<?= esc($field['label']) ?>">
            <?php endif; ?>
            <?php if ($field['name'] === 'password'): ?><small class="field-help">Kata sandi awal tidak akan tampil lagi dan pengguna wajib menggantinya saat masuk pertama kali.</small><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="form-actions"><a class="button button-soft" href="<?= site_url('/area/' . $module) ?>">Batal</a><button type="submit" class="button button-primary">Simpan data <span>→</span></button></div>
</form></section>
<?= $this->endSection() ?>
