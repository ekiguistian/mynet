<x-filament-panels::page>
    @php($logs = $this->logs())
    <div class="mb-4 grid gap-3 md:grid-cols-2">
        <select wire:model.live="eventFilter" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"><option value="">Semua aktivitas</option>@foreach ($this->eventOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <x-filament::input.wrapper><x-filament::input type="search" wire:model.live.debounce.400ms="userFilter" placeholder="Cari nama pengguna" /></x-filament::input.wrapper>
    </div>
    <div class="overflow-x-auto rounded-xl border dark:border-gray-700"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Waktu</th><th class="p-3">Pengguna</th><th class="p-3">Cabang</th><th class="p-3">Aktivitas</th><th class="p-3">Data</th><th class="p-3">Alamat IP</th></tr></thead><tbody>
        @forelse ($logs as $log)
            <tr class="border-b"><td class="p-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td><td class="p-3">{{ $log->user?->name ?? 'Sistem' }}</td><td class="p-3">{{ $log->branch?->name ?? '—' }}</td><td class="p-3"><span class="font-medium">{{ $log->event }}</span><div class="text-gray-500">{{ $log->description }}</div></td><td class="max-w-sm truncate p-3">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td><td class="p-3">{{ $log->ip_address ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada log aktivitas.</td></tr>
        @endforelse
    </tbody></table></div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-filament-panels::page>
