<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FiberOdpPort extends Model { public $timestamps=false; protected $fillable=['odp_id','port_number','status','customer_id']; protected function casts(): array{return ['port_number'=>'integer'];} public function odp(){return $this->belongsTo(FiberOdp::class,'odp_id');} public function customer(){return $this->belongsTo(Customer::class);} }
