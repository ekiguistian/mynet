<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id', 'invoice_id', 'customer_id', 'provider', 'payment_method',
        'external_reference', 'amount', 'status', 'checkout_url', 'paid_at', 'raw_payload',
    ];

    protected $hidden = ['raw_payload'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'raw_payload' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            if ($payment->exists && $payment->getOriginal('status') === 'paid' && $payment->isDirty('status')) {
                throw ValidationException::withMessages(['status' => 'Pembayaran yang sudah berhasil tidak dapat dibatalkan atau diubah.']);
            }

            if (filled($payment->invoice_id)) {
                $invoice = Invoice::query()->findOrFail($payment->invoice_id);
                $payment->branch_id = $invoice->branch_id;
                $payment->customer_id = $invoice->customer_id;
            }

            if ($payment->status === 'paid' && blank($payment->paid_at)) {
                $payment->paid_at = now();
            }
        });

        static::created(function (Payment $payment): void {
            $payment->synchronizePaidPayment();
        });

        static::updated(function (Payment $payment): void {
            if ($payment->wasChanged('status') && $payment->status === 'paid') {
                $payment->synchronizePaidPayment();
            }
        });
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    private function synchronizePaidPayment(): void
    {
        if ($this->status !== 'paid') {
            return;
        }

        $invoice = Invoice::query()->whereKey($this->invoice_id)->lockForUpdate()->first();

        if (! $invoice) {
            return;
        }

        $paidTotal = (float) $invoice->payments()->where('status', 'paid')->sum('amount');

        if ($paidTotal >= (float) $invoice->total && $invoice->status !== 'void') {
            $invoice->forceFill(['status' => 'paid', 'paid_at' => $this->paid_at ?? now()])->saveQuietly();
        }

        $reference = 'PAYMENT:'.$this->getKey();

        if (! FinanceEntry::query()->where('reference', $reference)->exists()) {
            FinanceEntry::query()->create([
                'branch_id' => $this->branch_id,
                'entry_type' => 'income',
                'category' => 'Pembayaran pelanggan',
                'amount' => $this->amount,
                'description' => 'Pembayaran untuk tagihan '.$invoice->invoice_no,
                'reference' => $reference,
                'transaction_date' => ($this->paid_at ?? now())->toDateString(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
