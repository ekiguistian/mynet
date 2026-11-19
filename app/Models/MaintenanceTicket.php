<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceTicket extends Model { protected $fillable=['branch_id','customer_id','ticket_no','title','category','description','priority','status','assigned_to','scheduled_at','resolution','opened_by']; protected function casts(): array{return ['scheduled_at'=>'datetime'];} public function branch(){return $this->belongsTo(Branch::class);} public function customer(){return $this->belongsTo(Customer::class);} public function assignee(){return $this->belongsTo(User::class,'assigned_to');} public function opener(){return $this->belongsTo(User::class,'opened_by');} public function updates(){return $this->hasMany(TicketUpdate::class,'ticket_id');} }
