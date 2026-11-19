<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FiberCore extends Model { public $timestamps=false; protected $fillable=['odc_id','core_number','color','route_label','status']; protected function casts(): array{return ['core_number'=>'integer'];} public function odc(){return $this->belongsTo(FiberOdc::class,'odc_id');} public function odps(){return $this->hasMany(FiberOdp::class,'core_id');} }
