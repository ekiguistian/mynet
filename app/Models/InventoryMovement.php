<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryMovement extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = ['branch_id', 'product_id', 'direction', 'quantity', 'unit_cost', 'reason', 'reference', 'user_id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryMovement $movement): void {
            if ($movement->exists && $movement->isDirty()) {
                throw ValidationException::withMessages(['movement' => 'Pergerakan stok yang sudah dicatat tidak dapat diubah. Catat pergerakan koreksi baru.']);
            }

            if (! in_array($movement->direction, ['in', 'out'], true) || (float) $movement->quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Arah dan jumlah pergerakan stok tidak valid.']);
            }

            $product = Product::query()->findOrFail($movement->product_id);

            if (blank($movement->branch_id)) {
                $movement->branch_id = $product->branch_id ?? auth()->user()?->branch_id;
            }

            if ($product->branch_id !== null && (int) $movement->branch_id !== (int) $product->branch_id) {
                throw ValidationException::withMessages(['product_id' => 'Barang ini terdaftar di cabang lain.']);
            }

            $movement->user_id ??= auth()->id();
        });

        static::created(function (InventoryMovement $movement): void {
            $product = Product::query()->whereKey($movement->product_id)->lockForUpdate()->firstOrFail();
            $current = (float) $product->stock_qty;
            $quantity = (float) $movement->quantity;

            if ($movement->direction === 'out' && $quantity > $current) {
                throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi. Stok tersedia: '.$current.' '.$product->unit.'.']);
            }

            $product->stock_qty = $movement->direction === 'in' ? $current + $quantity : $current - $quantity;
            $product->saveQuietly();
        });
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
