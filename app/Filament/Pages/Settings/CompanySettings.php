<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Concerns\CanManageApplicationSettings;
use App\Models\CompanyProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CompanySettings extends Page implements Forms\Contracts\HasForms
{
    use CanManageApplicationSettings;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Data Perusahaan';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.settings.company-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::canManage('company_settings');
    }

    public function mount(): void
    {
        $profile = CompanyProfile::query()->firstOrCreate(['profile_key' => 'primary']);
        $this->form->fill([
            ...$profile->only(['company_name', 'owner_name', 'owner_phone', 'responsible_name', 'responsible_phone', 'noc_phone', 'cs_phone', 'email', 'address']),
            'facebook' => $profile->social_media['facebook'] ?? '',
            'instagram' => $profile->social_media['instagram'] ?? '',
            'whatsapp' => $profile->social_media['whatsapp'] ?? '',
            'website' => $profile->social_media['website'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('company_name')->label('Nama perusahaan')->maxLength(160),
            Forms\Components\TextInput::make('owner_name')->label('Nama pemilik')->maxLength(160),
            Forms\Components\TextInput::make('owner_phone')->label('Telepon pemilik')->tel()->maxLength(30),
            Forms\Components\TextInput::make('responsible_name')->label('Nama penanggung jawab')->maxLength(160),
            Forms\Components\TextInput::make('responsible_phone')->label('Telepon penanggung jawab')->tel()->maxLength(30),
            Forms\Components\TextInput::make('noc_phone')->label('Telepon NOC')->tel()->maxLength(30),
            Forms\Components\TextInput::make('cs_phone')->label('Telepon layanan pelanggan')->tel()->maxLength(30),
            Forms\Components\TextInput::make('email')->label('Email perusahaan')->email()->maxLength(180),
            Forms\Components\Textarea::make('address')->label('Alamat perusahaan')->rows(3)->columnSpanFull(),
            Forms\Components\TextInput::make('facebook')->label('Facebook')->url(),
            Forms\Components\TextInput::make('instagram')->label('Instagram')->url(),
            Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->url(),
            Forms\Components\TextInput::make('website')->label('Situs web')->url(),
        ])->statePath('data')->columns(2);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $socialMedia = array_filter([
            'facebook' => $data['facebook'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'website' => $data['website'] ?? null,
        ]);
        unset($data['facebook'], $data['instagram'], $data['whatsapp'], $data['website']);

        CompanyProfile::query()->updateOrCreate(
            ['profile_key' => 'primary'],
            [...$data, 'social_media' => $socialMedia],
        );

        Notification::make()->title('Data perusahaan tersimpan')->success()->send();
    }
}
