<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    use BelongsToBranch;

    public $timestamps = false;

    protected static function branchScopePath(): string
    {
        return 'customer.branch_id';
    }

    protected $fillable = ['customer_id', 'provider', 'recipient', 'message', 'status', 'provider_reference', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
