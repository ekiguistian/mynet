<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EarningRule extends Model { protected $fillable=['branch_id','role','calculation_type','value','active']; protected function casts(): array{return ['value'=>'decimal:2','active'=>'boolean'];} public function branch(){return $this->belongsTo(Branch::class);} }
