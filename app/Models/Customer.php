<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Customer extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'plan_id',
        'sales_user_id',
        'created_by',
        'customer_code',
        'full_name',
        'email',
        'phone',
        'address',
        'service_type',
        'connection_mode',
        'static_ip',
        'mikrotik_router_id',
        'mikrotik_username',
        'mikrotik_password',
        'due_day',
        'auto_billing',
        'device_serial',
        'odp_port_id',
        'status',
        'installed_at',
        'notes',
    ];

    protected $hidden = ['mikrotik_password'];

    protected function casts(): array
    {
        return [
            'mikrotik_password' => 'encrypted',
            'due_day' => 'integer',
            'auto_billing' => 'boolean',
            'installed_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if ($customer->plan_id) {
                $plan = Plan::query()->findOrFail($customer->plan_id);

                if ($plan->branch_id !== null && (int) $plan->branch_id !== (int) $customer->branch_id) {
                    throw ValidationException::withMessages(['plan_id' => 'Paket ini terdaftar di cabang lain.']);
                }
            }

            if ($customer->mikrotik_router_id) {
                $router = Router::query()->findOrFail($customer->mikrotik_router_id);

                if ((int) $router->branch_id !== (int) $customer->branch_id) {
                    throw ValidationException::withMessages(['mikrotik_router_id' => 'Router ini terdaftar di cabang lain.']);
                }
            }

            if ($customer->service_type === 'static_ip' && ! filter_var($customer->static_ip, FILTER_VALIDATE_IP)) {
                throw ValidationException::withMessages(['static_ip' => 'Alamat IP static harus berupa alamat IPv4 atau IPv6 yang valid.']);
            }

            if ((int) $customer->due_day < 1 || (int) $customer->due_day > 31) {
                throw ValidationException::withMessages(['due_day' => 'Tanggal jatuh tempo harus antara 1 dan 31.']);
            }
        });

        static::creating(function (Customer $customer): void {
            if (blank($customer->customer_code)) {
                $customer->customer_code = 'MYN-'.now()->format('ym').'-'.Str::upper(Str::random(6));
            }

            if (blank($customer->created_by)) {
                $customer->created_by = auth()->id();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function userAccount(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'mikrotik_router_id');
    }

    public function odpPort(): BelongsTo
    {
        return $this->belongsTo(FiberOdpPort::class, 'odp_port_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }
}
