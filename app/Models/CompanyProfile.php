<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_key',
        'company_name',
        'owner_name',
        'owner_phone',
        'responsible_name',
        'responsible_phone',
        'noc_phone',
        'cs_phone',
        'email',
        'address',
        'social_media',
    ];

    protected function casts(): array
    {
        return ['social_media' => 'array'];
    }
}
