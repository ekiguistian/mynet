<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class FinanceEntry extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'finance_category_id',
        'entry_type',
        'category',
        'amount',
        'description',
        'reference',
        'transaction_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FinanceEntry $entry): void {
            $entry->user_id ??= auth()->id();

            if (blank($entry->finance_category_id)) {
                return;
            }

            $category = FinanceCategory::query()->findOrFail($entry->finance_category_id);

            if (! in_array($category->type, ['both', $entry->entry_type], true)) {
                throw ValidationException::withMessages([
                    'finance_category_id' => 'Kategori tidak sesuai dengan jenis transaksi.',
                ]);
            }

            if ($category->branch_id !== null && (int) $category->branch_id !== (int) $entry->branch_id) {
                throw ValidationException::withMessages([
                    'finance_category_id' => 'Kategori ini terdaftar di cabang lain.',
                ]);
            }

            $entry->category = $category->name;
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
