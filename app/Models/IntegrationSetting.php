<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IntegrationSetting extends Model { protected $fillable=['section','encrypted_value','updated_by']; protected function casts(): array{return ['encrypted_value'=>'encrypted:array'];} public function updater(){return $this->belongsTo(User::class,'updated_by');} }
