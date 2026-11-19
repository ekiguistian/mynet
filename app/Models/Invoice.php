<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model { protected $fillable=['branch_id','customer_id','invoice_no','period','amount','discount','total','status','due_date','paid_at','notes','created_by']; protected function casts(): array{return ['amount'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2','due_date'=>'date','paid_at'=>'datetime'];} public function branch(){return $this->belongsTo(Branch::class);} public function customer(){return $this->belongsTo(Customer::class);} public function payments(){return $this->hasMany(Payment::class);} public function creator(){return $this->belongsTo(User::class,'created_by');} }
