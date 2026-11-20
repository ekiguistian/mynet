<x-filament-panels::page>
    @if (auth()->user()->mustCompleteAccountSetup())
        <x-filament::section class="mb-6">
            <p class="text-sm">Sebelum menggunakan aplikasi, ubah kata sandi sementara dan verifikasi email Anda.</p>
        </x-filament::section>
    @endif

    <form wire:submit="saveProfile" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-check">Simpan profil dan kata sandi</x-filament::button>
    </form>

    <div class="flex flex-wrap gap-3">
        @if (! auth()->user()->email_verified_at && filled(auth()->user()->email))
            <x-filament::button color="gray" wire:click="sendInitialEmailVerification" icon="heroicon-o-envelope">Kirim kode verifikasi email saat ini</x-filament::button>
        @endif
        @if (filled($data['new_email'] ?? null))
            <x-filament::button color="primary" wire:click="requestEmailChange" icon="heroicon-o-envelope">Verifikasi email baru</x-filament::button>
        @endif
        @if (filled($data['new_phone'] ?? null))
            <x-filament::button color="primary" wire:click="requestPhoneChange" icon="heroicon-o-device-phone-mobile">Verifikasi nomor baru</x-filament::button>
        @endif
    </div>

    @php
        $challenges = $this->pendingChallenges();
    @endphp
    @if ($challenges->isNotEmpty())
        <x-filament::section heading="Kode verifikasi yang masih berlaku" class="mt-6">
            <div class="space-y-4">
                @foreach ($challenges as $challenge)
                    @php
                        $recipient = $challenge->recipient;
                        $masked = $challenge->channel === 'email'
                            ? (strlen(strstr($recipient, '@', true) ?: '') > 1 ? substr($recipient, 0, 1).'•••'.substr($recipient, strpos($recipient, '@')) : '•••'.$recipient)
                            : substr($recipient, 0, 3).'••••'.substr($recipient, -3);
                    @endphp
                    <div class="flex flex-wrap items-end gap-3 rounded-lg border p-4 dark:border-gray-700">
                        <div class="min-w-48 flex-1">
                            <p class="text-sm font-medium">{{ $challenge->channel === 'email' ? 'Email' : 'WhatsApp' }} · {{ $masked }}</p>
                            <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $challenge->purpose) }} · kedaluwarsa {{ $challenge->expires_at->diffForHumans() }}</p>
                        </div>
                        <div class="w-36">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" inputmode="numeric" maxlength="6" placeholder="6 digit" wire:model.defer="codes.{{ $challenge->id }}" />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button size="sm" wire:click="verifyChallenge({{ $challenge->id }})">Verifikasi</x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
