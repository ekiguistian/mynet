<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FiberOdp extends Model { protected $fillable=['branch_id','odc_id','core_id','code','name','location','active']; protected function casts(): array{return ['active'=>'boolean'];} public function branch(){return $this->belongsTo(Branch::class);} public function odc(){return $this->belongsTo(FiberOdc::class,'odc_id');} public function core(){return $this->belongsTo(FiberCore::class,'core_id');} public function ports(){return $this->hasMany(FiberOdpPort::class,'odp_id');} }
