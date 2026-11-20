<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class BranchWhatsappIntegration extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'encrypted_value'];

    protected function casts(): array
    {
        return ['encrypted_value' => 'encrypted:array'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
