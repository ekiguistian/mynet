<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class HotspotVoucher extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'router_id', 'profile', 'username', 'password', 'status', 'generated_by', 'unit_price'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'unit_price' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (HotspotVoucher $voucher): void {
            $voucher->generated_by ??= auth()->id();
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
