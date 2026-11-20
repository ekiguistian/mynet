<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\FinanceEntry;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

class OperationalReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Operasional';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.operational-reports';

    public string $period = 'monthly';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->canAccessFeature('reports');
    }

    public function getPeriodOptions(): array
    {
        return ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'quarterly' => 'Per 3 bulan', 'yearly' => 'Tahunan', 'all' => 'Semua waktu'];
    }

    public function getReportData(): array
    {
        [$start, $end] = $this->periodRange();
        $finance = FinanceEntry::query()->when($start, fn ($query) => $query->whereDate('transaction_date', '>=', $start))->when($end, fn ($query) => $query->whereDate('transaction_date', '<=', $end));
        $financeEntries = (clone $finance)->with(['branch', 'categoryRecord'])->latest('transaction_date')->paginate(50);
        $income = (clone $finance)->where('entry_type', 'income')->sum('amount');
        $expenses = (clone $finance)->where('entry_type', 'expense')->sum('amount');
        $payments = Payment::query()->where('status', 'paid')->when($start, fn ($query) => $query->whereDate('paid_at', '>=', $start))->when($end, fn ($query) => $query->whereDate('paid_at', '<=', $end));
        $movements = InventoryMovement::query()->when($start, fn ($query) => $query->whereDate('created_at', '>=', $start))->when($end, fn ($query) => $query->whereDate('created_at', '<=', $end));
        $tickets = MaintenanceTicket::query()->when($start, fn ($query) => $query->whereDate('created_at', '>=', $start))->when($end, fn ($query) => $query->whereDate('created_at', '<=', $end));
        $workOrders = WorkOrder::query()->when($start, fn ($query) => $query->whereDate('created_at', '>=', $start))->when($end, fn ($query) => $query->whereDate('created_at', '<=', $end));
        $attendance = Attendance::query()->when($start, fn ($query) => $query->whereDate('work_date', '>=', $start))->when($end, fn ($query) => $query->whereDate('work_date', '<=', $end));
        $techniciansQuery = User::query()->whereIn('role', ['technician', 'helper'])
            ->when(auth()->user()?->isBranchScoped(), fn ($query) => $query->where('branch_id', auth()->user()->branch_id));
        $technicians = $techniciansQuery->withCount([
            'workOrders as work_orders_count' => fn ($query) => $query->when($start, fn ($q) => $q->whereDate('created_at', '>=', $start))->when($end, fn ($q) => $q->whereDate('created_at', '<=', $end)),
            'attendances as attendance_count' => fn ($query) => $query->when($start, fn ($q) => $q->whereDate('work_date', '>=', $start))->when($end, fn ($q) => $q->whereDate('work_date', '<=', $end)),
            'maintenanceTickets as tickets_count' => fn ($query) => $query->when($start, fn ($q) => $q->whereDate('created_at', '>=', $start))->when($end, fn ($q) => $q->whereDate('created_at', '<=', $end)),
        ])->orderBy('name')->get();

        return [
            'period' => $this->period,
            'start' => $start,
            'end' => $end,
            'income' => $income,
            'expenses' => $expenses,
            'net' => $income - $expenses,
            'payments' => (clone $payments)->sum('amount'),
            'unpaid_invoices' => Invoice::query()->where('status', 'unpaid')->sum('total'),
            'stock_in' => (clone $movements)->where('direction', 'in')->sum('quantity'),
            'stock_out' => (clone $movements)->where('direction', 'out')->sum('quantity'),
            'tickets_open' => (clone $tickets)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'work_orders_completed' => (clone $workOrders)->where('status', 'completed')->count(),
            'attendance_count' => (clone $attendance)->whereNotNull('check_in')->count(),
            'finance_entries' => $financeEntries,
            'technicians' => $technicians,
        ];
    }

    private function periodRange(): array
    {
        $today = CarbonImmutable::today();

        return match ($this->period) {
            'daily' => [$today, $today],
            'weekly' => [$today->startOfWeek(), $today->endOfWeek()],
            'quarterly' => [$today->startOfQuarter(), $today->endOfQuarter()],
            'yearly' => [$today->startOfYear(), $today->endOfYear()],
            'all' => [null, null],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };
    }
}
