<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductSerial extends Model { protected $fillable=['branch_id','product_id','serial_number','status','customer_id']; public function branch(){return $this->belongsTo(Branch::class);} public function product(){return $this->belongsTo(Product::class);} public function customer(){return $this->belongsTo(Customer::class);} }
