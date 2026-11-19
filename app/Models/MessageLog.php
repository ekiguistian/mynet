<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageLog extends Model { public $timestamps=false; protected $fillable=['customer_id','provider','recipient','message','status','provider_reference','created_at']; protected function casts(): array{return ['created_at'=>'datetime'];} public function customer(){return $this->belongsTo(Customer::class);} }
