<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceEntry extends Model { protected $fillable=['branch_id','entry_type','category','amount','description','reference','transaction_date','user_id']; protected function casts(): array{return ['amount'=>'decimal:2','transaction_date'=>'date'];} public function branch(){return $this->belongsTo(Branch::class);} public function user(){return $this->belongsTo(User::class);} }
