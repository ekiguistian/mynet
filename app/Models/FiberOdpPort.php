<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class FiberOdpPort extends Model
{
    use BelongsToBranch;

    public $timestamps = false;

    protected static function branchScopePath(): string
    {
        return 'odp.branch_id';
    }

    protected $fillable = ['odp_id', 'port_number', 'status', 'customer_id'];

    protected function casts(): array
    {
        return ['port_number' => 'integer'];
    }

    public function odp()
    {
        return $this->belongsTo(FiberOdp::class, 'odp_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
