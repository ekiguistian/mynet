<?php

namespace App\Filament\Pages;

use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\OtpDeliveryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileSetup extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Akun Saya';

    protected static ?string $navigationLabel = 'Profil & Verifikasi';

    protected static ?string $title = 'Profil dan keamanan akun';

    protected static string $view = 'filament.pages.profile-setup';

    public ?array $data = [];

    public array $codes = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->form->fill([
            'name' => $user->name,
            'full_name' => $user->full_name,
            'avatar_path' => $user->avatar_path,
            'password' => null,
            'password_confirmation' => null,
            'new_email' => null,
            'new_phone' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil')->schema([
                Forms\Components\TextInput::make('name')->label('Nama tampilan')->required()->maxLength(160),
                Forms\Components\TextInput::make('full_name')->label('Nama lengkap')->maxLength(160),
                Forms\Components\FileUpload::make('avatar_path')->label('Foto profil')->image()->disk('public')->directory('avatars')->maxSize(2048),
            ])->columns(2),
            Forms\Components\Section::make('Kata sandi')->description('Gunakan kata sandi unik minimal 12 karakter untuk melindungi akun.')->schema([
                Forms\Components\TextInput::make('password')->label('Kata sandi baru')->password()->revealable()->required(fn (): bool => auth()->user()?->force_password_change ?? false)->minLength(12)->confirmed()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('password_confirmation')->label('Ulangi kata sandi')->password()->revealable()->dehydrated(false),
            ])->columns(2),
            Forms\Components\Section::make('Email')->description(fn (): string => auth()->user()?->email_verified_at ? 'Email terverifikasi: '.auth()->user()->email : 'Email harus diverifikasi sebelum akun dapat digunakan.')->schema([
                Forms\Components\Placeholder::make('current_email')->label('Email saat ini')->content(fn (): string => auth()->user()?->email ?: 'Belum ditambahkan'),
                Forms\Components\TextInput::make('new_email')->label('Email baru')->email()->maxLength(180),
            ])->columns(2),
            Forms\Components\Section::make('Nomor WhatsApp')->description(fn (): string => auth()->user()?->phone_verified_at ? 'WhatsApp terverifikasi: '.auth()->user()->phone : 'Nomor yang diperbarui diverifikasi melalui WhatsApp lama dan baru.')->schema([
                Forms\Components\Placeholder::make('current_phone')->label('Nomor saat ini')->content(fn (): string => auth()->user()?->phone ?: 'Belum ditambahkan'),
                Forms\Components\TextInput::make('new_phone')->label('Nomor WhatsApp baru')->tel()->maxLength(30),
            ])->columns(2),
        ])->statePath('data');
    }

    public function saveProfile(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $data = $this->form->getState();
        $oldAvatar = $user->avatar_path;
        $user->fill([
            'name' => $data['name'],
            'full_name' => $data['full_name'] ?? null,
            'avatar_path' => $data['avatar_path'] ?? $oldAvatar,
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->force_password_change = false;
        }

        $user->save();

        if ($oldAvatar && $oldAvatar !== $user->avatar_path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $this->data['password'] = null;
        $this->data['password_confirmation'] = null;
        Notification::make()->title('Profil tersimpan')->success()->send();
    }

    public function sendInitialEmailVerification(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (blank($user->email)) {
            Notification::make()->title('Tambahkan email terlebih dahulu')->danger()->send();

            return;
        }

        app(OtpDeliveryService::class)->issue($user, 'initial_email', [
            ['channel' => 'email', 'recipient' => $user->email],
        ]);

        Notification::make()->title('Kode dikirim ke email saat ini')->success()->send();
    }

    public function requestEmailChange(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $value = trim((string) ($this->data['new_email'] ?? ''));

        Validator::make(['email' => $value], ['email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->getKey())]])->validate();

        if (strcasecmp($value, (string) $user->email) === 0) {
            Notification::make()->title('Email baru harus berbeda')->warning()->send();

            return;
        }

        $recipients = [];

        if (filled($user->email)) {
            $recipients[] = ['channel' => 'email', 'recipient' => $user->email];
        }

        $recipients[] = ['channel' => 'email', 'recipient' => $value];
        app(OtpDeliveryService::class)->issue($user, 'email_change', $recipients, $value);
        $this->data['new_email'] = null;

        Notification::make()->title('Kode verifikasi dikirim ke email lama dan baru')->success()->send();
    }

    public function requestPhoneChange(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $value = trim((string) ($this->data['new_phone'] ?? ''));

        Validator::make(['phone' => $value], ['phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9() .-]{8,30}$/', Rule::unique('users', 'phone')->ignore($user->getKey())]])->validate();

        if ($value === (string) $user->phone) {
            Notification::make()->title('Nomor WhatsApp baru harus berbeda')->warning()->send();

            return;
        }

        $recipients = [];

        if (filled($user->phone)) {
            $recipients[] = ['channel' => 'whatsapp', 'recipient' => $user->phone];
        }

        $recipients[] = ['channel' => 'whatsapp', 'recipient' => $value];
        app(OtpDeliveryService::class)->issue($user, 'phone_change', $recipients, $value);
        $this->data['new_phone'] = null;

        Notification::make()->title('Kode verifikasi dikirim ke WhatsApp lama dan baru')->success()->send();
    }

    public function verifyChallenge(int $challengeId): void
    {
        $code = (string) ($this->codes[$challengeId] ?? '');
        Validator::make(['code' => $code], ['code' => ['required', 'digits:6']])->validate();

        $challenge = OtpChallenge::query()->whereKey($challengeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $completed = app(OtpDeliveryService::class)->verify(auth()->user(), $challenge, $code);
        $this->codes[$challengeId] = '';

        if ($completed) {
            Notification::make()->title('Kontak berhasil diverifikasi')->success()->send();
        } else {
            Notification::make()->title('Kode diterima. Masukkan kode dari kontak lain dalam permintaan ini.')->success()->send();
        }

        auth()->user()->refresh();
    }

    public function pendingChallenges()
    {
        return OtpChallenge::query()->where('user_id', auth()->id())
            ->whereNull('verified_at')->where('expires_at', '>', now())
            ->latest()->get();
    }
}
