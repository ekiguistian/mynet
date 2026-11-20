<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class TicketUpdate extends Model
{
    use BelongsToBranch;

    protected $fillable = ['ticket_id', 'user_id', 'status', 'comment'];

    protected static function branchScopePath(): string
    {
        return 'ticket.branch_id';
    }

    public function ticket()
    {
        return $this->belongsTo(MaintenanceTicket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
