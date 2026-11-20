<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Invoice extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'invoice_no',
        'period',
        'amount',
        'discount',
        'total',
        'status',
        'due_date',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice): void {
            if ($invoice->exists && $invoice->getOriginal('status') === 'paid' && $invoice->isDirty(['customer_id', 'period', 'amount', 'discount', 'total', 'due_date', 'status'])) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan yang sudah lunas tidak dapat diubah.']);
            }

            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $invoice->period)) {
                throw ValidationException::withMessages(['period' => 'Periode tagihan harus menggunakan format YYYY-MM.']);
            }

            if ((float) $invoice->discount > (float) $invoice->amount) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh lebih besar dari jumlah tagihan.']);
            }

            $invoice->total = max(0, (float) $invoice->amount - (float) $invoice->discount);

            if (filled($invoice->customer_id)) {
                $customer = Customer::query()->findOrFail($invoice->customer_id);

                if ($invoice->branch_id !== null && (int) $invoice->branch_id !== (int) $customer->branch_id) {
                    throw ValidationException::withMessages(['customer_id' => 'Pelanggan ini terdaftar di cabang lain.']);
                }

                $invoice->branch_id = $customer->branch_id;
            }

            if (blank($invoice->due_date) && filled($invoice->period)) {
                $periodStart = CarbonImmutable::createFromFormat('!Y-m-d', $invoice->period.'-01');
                $customer = Customer::query()->find($invoice->customer_id);
                $invoice->due_date = $periodStart->day(min((int) ($customer?->due_day ?? 20), $periodStart->daysInMonth))->toDateString();
            }

            if (! $invoice->exists && blank($invoice->created_by)) {
                $invoice->created_by = auth()->id();
            }
        });

        static::creating(function (Invoice $invoice): void {
            if (blank($invoice->invoice_no)) {
                $invoice->invoice_no = 'INV-'.now()->format('Ym').'-'.Str::upper(Str::random(8));
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
