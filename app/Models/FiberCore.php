<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class FiberCore extends Model
{
    use BelongsToBranch;

    public $timestamps = false;

    protected static function branchScopePath(): string
    {
        return 'odc.branch_id';
    }

    protected $fillable = ['odc_id', 'core_number', 'color', 'route_label', 'status'];

    protected function casts(): array
    {
        return ['core_number' => 'integer'];
    }

    public function odc()
    {
        return $this->belongsTo(FiberOdc::class, 'odc_id');
    }

    public function odps()
    {
        return $this->hasMany(FiberOdp::class, 'core_id');
    }
}
