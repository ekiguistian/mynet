<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffEarning extends Model { protected $fillable=['branch_id','user_id','work_order_id','earning_type','period','amount','status','paid_at']; protected function casts(): array{return ['amount'=>'decimal:2','paid_at'=>'datetime'];} public function branch(){return $this->belongsTo(Branch::class);} public function user(){return $this->belongsTo(User::class);} public function workOrder(){return $this->belongsTo(WorkOrder::class);} }
