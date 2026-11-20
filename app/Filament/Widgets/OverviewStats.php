<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\FinanceEntryResource;
use App\Filament\Resources\InventoryMovementResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MaintenanceTicketResource;
use App\Filament\Resources\WorkOrderResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->canAccessFeature('dashboard');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [
            Stat::make('Pelanggan aktif', Customer::query()->where('status', 'active')->count())
                ->description('Layanan internet aktif')->icon('heroicon-o-user-group')->color('primary')
                ->url($user->canAccessFeature('customers') ? CustomerResource::getUrl() : null),
        ];

        if ($user->canAccessFeature('invoices')) {
            $stats[] = Stat::make('Tagihan belum lunas', Invoice::query()->where('status', 'unpaid')->count())
                ->description('Total Rp '.number_format((float) Invoice::query()->where('status', 'unpaid')->sum('total'), 0, ',', '.'))
                ->icon('heroicon-o-document-currency-dollar')->color('warning')->url(InvoiceResource::getUrl());
        }

        if ($user->canAccessFeature('finance')) {
            $stats[] = Stat::make('Pendapatan bulan ini', 'Rp '.number_format((float) Payment::query()->where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'), 0, ',', '.'))
                ->description('Pembayaran terkonfirmasi')->icon('heroicon-o-banknotes')->color('success')->url(FinanceEntryResource::getUrl());
        }

        if ($user->canAccessFeature('tickets')) {
            $stats[] = Stat::make('Tiket terbuka', MaintenanceTicket::query()->whereNotIn('status', ['resolved', 'closed'])->count())
                ->description('Gangguan menunggu tindak lanjut')->icon('heroicon-o-ticket')->color('danger')->url(MaintenanceTicketResource::getUrl());
        }

        if ($user->canAccessFeature('inventory')) {
            $lowStock = Product::query()->whereColumn('stock_qty', '<=', 'minimum_stock')->count();
            $stats[] = Stat::make('Barang perlu restok', $lowStock)
                ->description('Stok sama dengan atau di bawah batas minimum')->icon('heroicon-o-cube')->color($lowStock ? 'warning' : 'success')->url(InventoryMovementResource::getUrl());
        }

        if ($user->canAccessFeature('work_orders')) {
            $stats[] = Stat::make('Pekerjaan selesai bulan ini', WorkOrder::query()->where('status', 'completed')->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count())
                ->description('Order teknisi selesai')->icon('heroicon-o-wrench-screwdriver')->color('success')->url(WorkOrderResource::getUrl());
        }

        return $stats;
    }
}
