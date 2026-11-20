<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Concerns\CanManageApplicationSettings;
use App\Models\AppSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApplicationSettings extends Page implements Forms\Contracts\HasForms
{
    use CanManageApplicationSettings;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings.application-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::canManage('settings');
    }

    public function mount(): void
    {
        $settings = AppSetting::query()->pluck('setting_value', 'setting_key')->all();
        $social = json_decode($settings['social_media'] ?? '{}', true) ?: [];

        $this->form->fill([
            'seo_title' => $settings['seo_title'] ?? 'mynet — Billing ISP',
            'seo_description' => $settings['seo_description'] ?? '',
            'seo_keywords' => $settings['seo_keywords'] ?? '',
            'google_search_console_url' => $settings['google_search_console_url'] ?? '',
            'support_phone' => $settings['support_phone'] ?? '',
            'facebook' => $social['facebook'] ?? '',
            'instagram' => $social['instagram'] ?? '',
            'whatsapp' => $social['whatsapp'] ?? '',
            'website' => $social['website'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('brand')->label('Nama aplikasi')->content('mynet (tetap)'),
            Forms\Components\TextInput::make('seo_title')->label('Judul SEO')->required()->maxLength(180),
            Forms\Components\Textarea::make('seo_description')->label('Deskripsi meta')->rows(3)->maxLength(320),
            Forms\Components\TextInput::make('seo_keywords')->label('Kata kunci SEO')->maxLength(500),
            Forms\Components\TextInput::make('google_search_console_url')->label('Kode verifikasi Google Search Console')->maxLength(255),
            Forms\Components\TextInput::make('support_phone')->label('Nomor telepon layanan pelanggan')->tel()->maxLength(30),
            Forms\Components\TextInput::make('facebook')->label('Facebook')->url(),
            Forms\Components\TextInput::make('instagram')->label('Instagram')->url(),
            Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->url(),
            Forms\Components\TextInput::make('website')->label('Situs web')->url(),
        ])->statePath('data')->columns(2);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['social_media'] = json_encode(array_filter([
            'facebook' => $data['facebook'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'website' => $data['website'] ?? null,
        ]), JSON_THROW_ON_ERROR);
        unset($data['facebook'], $data['instagram'], $data['whatsapp'], $data['website']);

        foreach ($data as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_by' => auth()->id()],
            );
        }

        AppSetting::query()->updateOrCreate(
            ['setting_key' => 'app_name'],
            ['setting_value' => 'mynet', 'updated_by' => auth()->id()],
        );

        Notification::make()->title('Pengaturan aplikasi tersimpan')->success()->send();
    }
}
