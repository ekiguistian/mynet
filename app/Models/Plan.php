<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model { protected $fillable=['branch_id','name','service_type','speed_download','speed_upload','monthly_price','hotspot_profile','active']; protected function casts(): array{return ['monthly_price'=>'decimal:2','active'=>'boolean'];} public function branch(){return $this->belongsTo(Branch::class);} public function customers(){return $this->hasMany(Customer::class);} }
