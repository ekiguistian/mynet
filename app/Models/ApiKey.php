<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'name', 'prefix', 'secret_hash', 'scopes', 'created_by', 'last_used_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
