<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Router extends Model { protected $fillable=['branch_id','name','host','api_port','username','password','api_ssl','verify_ssl','active']; protected function casts(): array{return ['host'=>'encrypted','api_port'=>'encrypted','username'=>'encrypted','password'=>'encrypted','api_ssl'=>'boolean','verify_ssl'=>'boolean','active'=>'boolean'];} public function branch(){return $this->belongsTo(Branch::class);} public function customers(){return $this->hasMany(Customer::class,'mikrotik_router_id');} public function vouchers(){return $this->hasMany(HotspotVoucher::class);} }
