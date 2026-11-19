<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AppSetting extends Model { protected $fillable=['setting_key','setting_value','updated_by']; public function updater(){return $this->belongsTo(User::class,'updated_by');} }
