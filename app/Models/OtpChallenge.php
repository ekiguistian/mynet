<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_uuid',
        'user_id',
        'purpose',
        'channel',
        'recipient',
        'requested_value',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $hidden = ['recipient', 'requested_value', 'code_hash'];

    protected function casts(): array
    {
        return [
            'recipient' => 'encrypted',
            'requested_value' => 'encrypted',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
