<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Attendance extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'work_date',
        'check_in',
        'check_out',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_photo_path',
        'check_out_photo_path',
        'check_in_ip',
        'check_out_ip',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance): void {
            $employee = User::query()->findOrFail($attendance->user_id);

            if (blank($attendance->branch_id) || (int) $employee->branch_id !== (int) $attendance->branch_id) {
                throw ValidationException::withMessages(['user_id' => 'Karyawan harus ditugaskan pada cabang absensi yang sama.']);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
