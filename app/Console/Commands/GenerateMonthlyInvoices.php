<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('billing:generate-invoices {--period= : Periode tagihan dalam format YYYY-MM}')]
#[Description('Buat tagihan bulanan untuk pelanggan dengan tagihan otomatis.')]
class GenerateMonthlyInvoices extends Command
{
    public function handle(): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $this->error('Periode harus menggunakan format YYYY-MM.');

            return self::FAILURE;
        }

        $periodStart = CarbonImmutable::createFromFormat('!Y-m-d', $period.'-01');
        $created = 0;

        Customer::query()->with('plan')
            ->where('auto_billing', true)
            ->where('status', 'active')
            ->whereHas('plan', fn ($query) => $query->where('active', true))
            ->chunkById(100, function ($customers) use ($period, $periodStart, &$created): void {
                foreach ($customers as $customer) {
                    $dueDate = $periodStart->day(min((int) $customer->due_day, $periodStart->daysInMonth));
                    $invoice = Invoice::query()->firstOrCreate(
                        ['customer_id' => $customer->getKey(), 'period' => $period],
                        [
                            'branch_id' => $customer->branch_id,
                            'amount' => $customer->plan?->monthly_price ?? 0,
                            'discount' => 0,
                            'status' => 'unpaid',
                            'due_date' => $dueDate->toDateString(),
                            'created_by' => null,
                        ],
                    );

                    $created += $invoice->wasRecentlyCreated ? 1 : 0;
                }
            });

        $this->info("{$created} tagihan dibuat untuk periode {$period}.");

        return self::SUCCESS;
    }
}
