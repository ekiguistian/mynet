<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaintenanceTicket extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'ticket_no',
        'title',
        'category',
        'description',
        'priority',
        'status',
        'assigned_to',
        'scheduled_at',
        'resolution',
        'opened_by',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (MaintenanceTicket $ticket): void {
            if ($ticket->customer_id) {
                $customer = Customer::query()->findOrFail($ticket->customer_id);

                if ($ticket->branch_id !== null && (int) $customer->branch_id !== (int) $ticket->branch_id) {
                    throw ValidationException::withMessages(['customer_id' => 'Pelanggan ini terdaftar di cabang lain.']);
                }

                $ticket->branch_id = $customer->branch_id;
            }

            if ($ticket->assigned_to) {
                $technician = User::query()->findOrFail($ticket->assigned_to);

                if ((int) $technician->branch_id !== (int) $ticket->branch_id || ! $technician->hasRole('technician', 'helper')) {
                    throw ValidationException::withMessages(['assigned_to' => 'Teknisi harus berasal dari cabang tiket.']);
                }
            }
        });

        static::creating(function (MaintenanceTicket $ticket): void {
            if (blank($ticket->ticket_no)) {
                $ticket->ticket_no = 'TKT-'.now()->format('Ym').'-'.Str::upper(Str::random(8));
            }

            if (blank($ticket->branch_id) && filled($ticket->customer_id)) {
                $ticket->branch_id = Customer::query()->whereKey($ticket->customer_id)->value('branch_id');
            }

            if (blank($ticket->opened_by)) {
                $ticket->opened_by = auth()->id();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->assignee();
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TicketUpdate::class, 'ticket_id');
    }
}
