<x-filament-panels::page>
    @php($today = $this->todayAttendance())
    <x-filament::section heading="Status hari ini">
        @if (! $today)
            <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada absensi hari ini. Aktifkan GPS dan tekan tombol absen untuk mengirim lokasi serta foto.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <div><p class="text-sm text-gray-500">Cabang</p><p class="font-medium">{{ $today->branch?->name }}</p></div>
                <div><p class="text-sm text-gray-500">Jam masuk</p><p class="font-medium">{{ $today->check_in?->format('H:i') ?? 'Belum absen' }}</p></div>
                <div><p class="text-sm text-gray-500">Jam pulang</p><p class="font-medium">{{ $today->check_out?->format('H:i') ?? 'Belum absen' }}</p></div>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Riwayat absensi" class="mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="py-2 pr-4">Tanggal</th><th class="py-2 pr-4">Cabang</th><th class="py-2 pr-4">Masuk</th><th class="py-2 pr-4">Pulang</th><th class="py-2 pr-4">Status</th><th class="py-2">Lokasi</th></tr></thead>
                <tbody>
                    @forelse ($this->recentAttendance() as $attendance)
                        <tr class="border-b"><td class="py-2 pr-4">{{ $attendance->work_date->format('d/m/Y') }}</td><td class="py-2 pr-4">{{ $attendance->branch?->name }}</td><td class="py-2 pr-4">{{ $attendance->check_in?->format('H:i') ?? '—' }}</td><td class="py-2 pr-4">{{ $attendance->check_out?->format('H:i') ?? '—' }}</td><td class="py-2 pr-4">{{ ucfirst($attendance->status) }}</td><td class="py-2">@if ($attendance->check_in_latitude && $attendance->check_in_longitude)<a class="text-primary-600 underline" target="_blank" rel="noopener" href="https://maps.google.com/?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}">Lihat peta</a>@else — @endif</td></tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-500">Belum ada riwayat absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
