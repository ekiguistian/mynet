<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Versi aplikasi">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Nama aplikasi</dt><dd class="mt-1 font-medium">mynet</dd></div>
                <div><dt class="text-sm text-gray-500">Versi terpasang</dt><dd class="mt-1 font-medium">{{ $this->version() }}</dd></div>
                <div><dt class="text-sm text-gray-500">Laravel</dt><dd class="mt-1 font-medium">{{ app()->version() }}</dd></div>
                <div><dt class="text-sm text-gray-500">Lingkungan</dt><dd class="mt-1 font-medium">{{ app()->environment() }}</dd></div>
            </dl>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Pembaruan kode aplikasi dilakukan melalui commit dan pull request repository. Setelah kode baru dipasang, jalankan migrasi database dari halaman ini.</p>
            <a class="mt-3 inline-flex text-primary-600 underline" href="https://github.com/ekiguistian/mynet" target="_blank" rel="noopener">Buka repository GitHub</a>
            <form wire:submit="saveVersion" class="mt-6 space-y-3">
                {{ $this->form }}
                <x-filament::button type="submit" size="sm">Simpan versi</x-filament::button>
            </form>
        </x-filament::section>

        <x-filament::section heading="Pembaruan database">
            <p class="text-sm text-gray-600 dark:text-gray-300">Tombol di bagian atas menjalankan migrasi database yang tersedia. Pastikan backup database terbaru tersedia sebelum menerapkan migrasi pada server produksi.</p>
            <p class="mt-3 text-sm">Status koneksi database: <span class="font-medium">{{ config('database.default') }}</span></p>
        </x-filament::section>
    </div>

    <x-filament::section heading="Lisensi MIT" class="mt-6">
        <p class="mb-4 text-sm">mynet didistribusikan di bawah lisensi MIT.</p>
        <details class="text-sm">
            <summary class="cursor-pointer font-medium">Baca teks lisensi</summary>
            <pre class="mt-3 whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-xs dark:bg-gray-900">{{ file_get_contents(base_path('LICENSE')) }}</pre>
        </details>
    </x-filament::section>
</x-filament-panels::page>
