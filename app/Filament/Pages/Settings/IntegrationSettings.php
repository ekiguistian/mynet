<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Concerns\CanManageApplicationSettings;
use App\Models\IntegrationSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class IntegrationSettings extends Page implements Forms\Contracts\HasForms
{
    use CanManageApplicationSettings;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Integrasi';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.settings.integration-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::canManage('integrations');
    }

    public function mount(): void
    {
        $values = [];

        foreach (['mikrotik', 'payment_gateway', 'whatsapp', 'smtp'] as $section) {
            $settings = IntegrationSetting::query()->where('section', $section)->first()?->encrypted_value ?? [];
            $values[$section] = is_array($settings) ? $settings : [];
        }

        foreach (['mikrotik.password', 'payment_gateway.server_key', 'payment_gateway.secret_key', 'payment_gateway.client_key', 'payment_gateway.api_key', 'whatsapp.token', 'smtp.password'] as $key) {
            data_set($values, $key, '');
        }

        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('MikroTik')->description('Kredensial API router disimpan terenkripsi.')->schema([
                Forms\Components\TextInput::make('mikrotik.host')->label('Host')->maxLength(255),
                Forms\Components\TextInput::make('mikrotik.port')->label('Port API')->numeric()->default(8728),
                Forms\Components\TextInput::make('mikrotik.username')->label('Username'),
                Forms\Components\TextInput::make('mikrotik.password')->label('Password')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state))->helperText('Kosongkan jika tidak ingin mengubah password tersimpan.'),
                Forms\Components\Toggle::make('mikrotik.use_tls')->label('Gunakan TLS'),
            ])->columns(2),
            Forms\Components\Section::make('Payment gateway')->schema([
                Forms\Components\Select::make('payment_gateway.provider')->label('Penyedia')->options(['manual' => 'Manual', 'midtrans' => 'Midtrans', 'xendit' => 'Xendit', 'tripay' => 'Tripay'])->default('manual'),
                Forms\Components\TextInput::make('payment_gateway.server_key')->label('Server key')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('payment_gateway.secret_key')->label('Secret key')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('payment_gateway.client_key')->label('Client key')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('payment_gateway.api_key')->label('API key Tripay')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('payment_gateway.merchant_code')->label('Kode merchant Tripay')->maxLength(40),
                Forms\Components\Select::make('payment_gateway.default_method')->label('Metode Tripay')->options(['BRIVA' => 'BRI Virtual Account', 'BCAVA' => 'BCA Virtual Account', 'QRIS' => 'QRIS', 'QRISC' => 'QRIS Custom', 'MANDIRIVA' => 'Mandiri Virtual Account', 'BNIVA' => 'BNI Virtual Account'])->default('BRIVA'),
                Forms\Components\Toggle::make('payment_gateway.sandbox')->label('Gunakan mode sandbox')->default(true),
                Forms\Components\TextInput::make('payment_gateway.webhook_url')->label('URL webhook')->disabled()->dehydrated(false)->helperText(fn (): string => url('/api/webhooks/payments/{provider}')),
            ])->columns(2),
            Forms\Components\Section::make('WhatsApp gateway')->schema([
                Forms\Components\TextInput::make('whatsapp.endpoint')->label('URL API')->url()->maxLength(255),
                Forms\Components\TextInput::make('whatsapp.sender')->label('Nomor pengirim')->tel()->maxLength(30),
                Forms\Components\TextInput::make('whatsapp.token')->label('Token API')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
            ])->columns(2),
            Forms\Components\Section::make('SMTP Email')->schema([
                Forms\Components\TextInput::make('smtp.host')->label('Host SMTP'),
                Forms\Components\TextInput::make('smtp.port')->label('Port')->numeric()->default(587),
                Forms\Components\Select::make('smtp.encryption')->label('Enkripsi')->options(['tls' => 'STARTTLS', 'ssl' => 'SSL', '' => 'Tanpa enkripsi']),
                Forms\Components\TextInput::make('smtp.username')->label('Username'),
                Forms\Components\TextInput::make('smtp.password')->label('Password')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\TextInput::make('smtp.from_address')->label('Alamat pengirim')->email(),
                Forms\Components\TextInput::make('smtp.from_name')->label('Nama pengirim')->default('mynet'),
            ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $actorId = auth()->id();

        foreach (['mikrotik', 'payment_gateway', 'whatsapp', 'smtp'] as $section) {
            $record = IntegrationSetting::query()->firstOrNew(['section' => $section]);
            $previous = is_array($record->encrypted_value) ? $record->encrypted_value : [];
            $updated = $data[$section] ?? [];

            foreach (['password', 'server_key', 'secret_key', 'client_key', 'api_key', 'token'] as $secret) {
                if (blank($updated[$secret] ?? null) && filled($previous[$secret] ?? null)) {
                    $updated[$secret] = $previous[$secret];
                }
            }

            $record->encrypted_value = $updated;
            $record->updated_by = $actorId;
            $record->save();
        }

        Notification::make()->title('Konfigurasi integrasi tersimpan')->success()->send();
    }
}
