<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'host',
        'api_port',
        'username',
        'password',
        'api_ssl',
        'verify_ssl',
        'active',
    ];

    protected $hidden = ['host', 'api_port', 'username', 'password'];

    protected function casts(): array
    {
        return [
            'host' => 'encrypted',
            'api_port' => 'encrypted',
            'username' => 'encrypted',
            'password' => 'encrypted',
            'api_ssl' => 'boolean',
            'verify_ssl' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'mikrotik_router_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(HotspotVoucher::class);
    }
}
