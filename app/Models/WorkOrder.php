<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkOrder extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'technician_id',
        'sales_user_id',
        'work_order_no',
        'title',
        'description',
        'billable_amount',
        'status',
        'scheduled_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billable_amount' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WorkOrder $workOrder): void {
            if ($workOrder->customer_id) {
                $customer = Customer::query()->findOrFail($workOrder->customer_id);

                if ($workOrder->branch_id !== null && (int) $customer->branch_id !== (int) $workOrder->branch_id) {
                    throw ValidationException::withMessages(['customer_id' => 'Pelanggan ini terdaftar di cabang lain.']);
                }

                $workOrder->branch_id = $customer->branch_id;
            }

            foreach (['technician_id', 'sales_user_id'] as $field) {
                if ($workOrder->{$field}) {
                    $employee = User::query()->findOrFail($workOrder->{$field});

                    if ((int) $employee->branch_id !== (int) $workOrder->branch_id) {
                        throw ValidationException::withMessages([$field => 'Karyawan harus berasal dari cabang pekerjaan.']);
                    }
                }
            }
        });

        static::creating(function (WorkOrder $workOrder): void {
            if (blank($workOrder->work_order_no)) {
                $workOrder->work_order_no = 'WO-'.now()->format('Ym').'-'.Str::upper(Str::random(8));
            }

            if (blank($workOrder->branch_id) && filled($workOrder->customer_id)) {
                $workOrder->branch_id = Customer::query()->whereKey($workOrder->customer_id)->value('branch_id');
            }

            if (blank($workOrder->created_by)) {
                $workOrder->created_by = auth()->id();
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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(StaffEarning::class);
    }
}
