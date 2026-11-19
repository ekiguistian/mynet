<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attendance extends Model { protected $fillable=['branch_id','user_id','work_date','check_in','check_out','status','notes']; protected function casts(): array{return ['work_date'=>'date','check_in'=>'datetime','check_out'=>'datetime'];} public function branch(){return $this->belongsTo(Branch::class);} public function user(){return $this->belongsTo(User::class);} }
