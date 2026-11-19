<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketUpdate extends Model { protected $fillable=['ticket_id','user_id','status','comment']; public function ticket(){return $this->belongsTo(MaintenanceTicket::class);} public function user(){return $this->belongsTo(User::class);} }
