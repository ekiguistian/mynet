<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-lock-closed">Simpan konfigurasi terenkripsi</x-filament::button>
    </form>
</x-filament-panels::page>
