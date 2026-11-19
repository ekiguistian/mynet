<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageTemplate extends Model { protected $fillable=['code','name','body','active','created_by','updated_by']; protected function casts(): array{return ['active'=>'boolean'];} public function creator(){return $this->belongsTo(User::class,'created_by');} public function updater(){return $this->belongsTo(User::class,'updated_by');} public function queueItems(){return $this->hasMany(WhatsappQueue::class,'template_id');} }
