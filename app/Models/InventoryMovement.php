<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model { protected $fillable=['branch_id','product_id','direction','quantity','unit_cost','reason','reference','user_id']; protected function casts(): array{return ['quantity'=>'decimal:2','unit_cost'=>'decimal:2'];} public function branch(){return $this->belongsTo(Branch::class);} public function product(){return $this->belongsTo(Product::class);} public function user(){return $this->belongsTo(User::class);} }
