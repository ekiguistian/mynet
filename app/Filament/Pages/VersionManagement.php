<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Throwable;

class VersionManagement extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Versi & Pembaruan';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.version-management';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['version' => $this->version()]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('version')->label('Versi aplikasi')->required()->maxLength(40)->regex('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/')->helperText('Gunakan format semver, misalnya 1.0.0.'),
        ])->statePath('data');
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isGlobalAdministrator();
    }

    public function getHeaderActions(): array
    {
        return [Action::make('migrate')->label('Jalankan migrasi database')->icon('heroicon-o-circle-stack')->color('warning')
            ->requiresConfirmation()->modalHeading('Jalankan migrasi database?')
            ->modalDescription('Perintah migrate --force akan menerapkan migrasi yang tersedia pada database saat ini.')
            ->action(fn () => $this->runMigrations())];
    }

    public function version(): string
    {
        return (string) AppSetting::query()->where('setting_key', 'app_version')->value('setting_value')
            ?: (string) config('app.version', '1.0.0');
    }

    public function saveVersion(): void
    {
        $data = Validator::make($this->form->getState(), ['version' => ['required', 'string', 'max:40', 'regex:/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/']])->validate();
        AppSetting::query()->updateOrCreate(
            ['setting_key' => 'app_version'],
            ['setting_value' => $data['version'], 'updated_by' => auth()->id()],
        );

        Notification::make()->title('Versi aplikasi diperbarui')->success()->send();
    }

    public function runMigrations(): void
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if ($exitCode !== self::SUCCESS) {
                Notification::make()->title('Migrasi database gagal')->body(trim(Artisan::output()) ?: 'Periksa log aplikasi untuk rincian.')->danger()->send();

                return;
            }

            Notification::make()->title('Migrasi database selesai')->body(trim(Artisan::output()))->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Migrasi database gagal')->body('Periksa log aplikasi untuk rincian.')->danger()->send();
        }
    }
}
