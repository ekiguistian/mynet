<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class FiberOdc extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'code', 'name', 'location', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cores()
    {
        return $this->hasMany(FiberCore::class, 'odc_id');
    }

    public function odps()
    {
        return $this->hasMany(FiberOdp::class, 'odc_id');
    }
}
