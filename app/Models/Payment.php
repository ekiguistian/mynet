<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model { protected $fillable=['branch_id','invoice_id','customer_id','provider','payment_method','external_reference','amount','status','checkout_url','paid_at','raw_payload']; protected function casts(): array{return ['amount'=>'decimal:2','paid_at'=>'datetime','raw_payload'=>'array'];} public function branch(){return $this->belongsTo(Branch::class);} public function invoice(){return $this->belongsTo(Invoice::class);} public function customer(){return $this->belongsTo(Customer::class);} }
