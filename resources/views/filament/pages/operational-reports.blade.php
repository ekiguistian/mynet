<x-filament-panels::page>
    @php($report = $this->getReportData())
    <div class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-56">
            <label for="report-period" class="mb-1 block text-sm font-medium">Periode laporan</label>
            <select id="report-period" wire:model.live="period" class="fi-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($this->getPeriodOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <p class="text-sm text-gray-500">{{ $report['start']?->format('d/m/Y') ?? 'Awal data' }} – {{ $report['end']?->format('d/m/Y') ?? 'Sekarang' }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Pendapatan', $report['income']], ['Pengeluaran', $report['expenses']], ['Arus kas bersih', $report['net']], ['Pembayaran diterima', $report['payments']]] as [$label, $value])
            <x-filament::section><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">Rp {{ number_format((float) $value, 0, ',', '.') }}</p></x-filament::section>
        @endforeach
        @foreach ([['Tagihan belum lunas', $report['unpaid_invoices'], true], ['Tiket terbuka', $report['tickets_open'], false], ['Pekerjaan selesai', $report['work_orders_completed'], false], ['Absensi tercatat', $report['attendance_count'], false], ['Barang masuk', $report['stock_in'], false], ['Barang keluar', $report['stock_out'], false]] as [$label, $value, $currency])
            <x-filament::section><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $currency ? 'Rp '.number_format((float) $value, 0, ',', '.') : number_format((float) $value, 0, ',', '.') }}</p></x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Laporan per teknisi" class="mt-6">
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="py-2">Teknisi</th><th class="py-2">Cabang</th><th class="py-2">Tiket</th><th class="py-2">Pekerjaan</th><th class="py-2">Absensi</th></tr></thead><tbody>
            @forelse ($report['technicians'] as $technician)
                <tr class="border-b"><td class="py-2">{{ $technician->name }}</td><td class="py-2">{{ $technician->branch?->name ?? '—' }}</td><td class="py-2">{{ $technician->tickets_count }}</td><td class="py-2">{{ $technician->work_orders_count }}</td><td class="py-2">{{ $technician->attendance_count }}</td></tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-500">Belum ada data teknisi pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </x-filament::section>

    <x-filament::section heading="Rincian arus kas" class="mt-6">
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="py-2">Tanggal</th><th class="py-2">Cabang</th><th class="py-2">Jenis</th><th class="py-2">Kategori</th><th class="py-2">Keterangan</th><th class="py-2 text-right">Jumlah</th></tr></thead><tbody>
            @forelse ($report['finance_entries'] as $entry)
                <tr class="border-b"><td class="py-2">{{ $entry->transaction_date->format('d/m/Y') }}</td><td class="py-2">{{ $entry->branch?->name }}</td><td class="py-2">{{ $entry->entry_type === 'income' ? 'Pendapatan' : 'Pengeluaran' }}</td><td class="py-2">{{ $entry->categoryRecord?->name ?? $entry->category }}</td><td class="py-2">{{ $entry->description }}</td><td class="py-2 text-right">Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="6" class="py-4 text-center text-gray-500">Belum ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $report['finance_entries']->links() }}</div>
    </x-filament::section>
</x-filament-panels::page>
