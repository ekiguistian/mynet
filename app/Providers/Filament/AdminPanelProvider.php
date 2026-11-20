<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\OverviewStats;
use App\Http\Middleware\EnsureAccountSetup;
use App\Models\AppSetting;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('mynet')
            ->darkMode()
            ->defaultThemeMode(ThemeMode::System)
            ->spa()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                'Manajemen Pelanggan',
                'Keuangan & Tagihan',
                'Inventaris',
                'Jaringan & MikroTik',
                'Operasional',
                'Laporan',
                'Organisasi & Pengguna',
                'Akun Saya',
                'Pengaturan Sistem',
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.partials.seo', [
                'settings' => AppSetting::query()->whereIn('setting_key', [
                    'seo_title', 'seo_description', 'seo_keywords', 'google_search_console_url', 'social_media',
                ])->pluck('setting_value', 'setting_key')->all(),
            ]))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                OverviewStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAccountSetup::class,
            ]);
    }
}
