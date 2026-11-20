<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\BranchWhatsappIntegration;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\FinanceCategory;
use App\Models\FinanceEntry;
use App\Models\HotspotVoucher;
use App\Models\IntegrationSetting;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Router;
use App\Models\User;
use App\Models\WorkOrder;
use App\Observers\ActivityLogObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            ActivityLog::class,
            AppSetting::class,
            Attendance::class,
            Branch::class,
            BranchWhatsappIntegration::class,
            CompanyProfile::class,
            Customer::class,
            FinanceCategory::class,
            FinanceEntry::class,
            HotspotVoucher::class,
            IntegrationSetting::class,
            InventoryMovement::class,
            Invoice::class,
            MaintenanceTicket::class,
            Payment::class,
            Plan::class,
            Product::class,
            Router::class,
            User::class,
            WorkOrder::class,
        ] as $modelClass) {
            if ($modelClass !== ActivityLog::class) {
                $modelClass::observe(ActivityLogObserver::class);
            }
        }

        Event::listen(Login::class, function (Login $event): void {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            ActivityLog::query()->create([
                'user_id' => $event->user->getKey(),
                'branch_id' => $event->user->branch_id,
                'event' => 'login',
                'description' => 'Pengguna masuk ke aplikasi',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
