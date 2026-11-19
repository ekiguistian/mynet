<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BranchWhatsappIntegration extends Model { protected $fillable=['branch_id','encrypted_value']; protected function casts(): array{return ['encrypted_value'=>'encrypted:array'];} public function branch(){return $this->belongsTo(Branch::class);} }
