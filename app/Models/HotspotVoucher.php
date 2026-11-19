<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HotspotVoucher extends Model { protected $fillable=['branch_id','router_id','profile','username','password','status','generated_by','unit_price']; protected function casts(): array{return ['unit_price'=>'decimal:2'];} public function branch(){return $this->belongsTo(Branch::class);} public function router(){return $this->belongsTo(Router::class);} public function generator(){return $this->belongsTo(User::class,'generated_by');} }
