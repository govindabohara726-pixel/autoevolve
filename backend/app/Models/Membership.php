<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Membership extends Model {
    protected $fillable=['workspace_id','user_id','role','accepted_at'];
    protected function casts(): array { return ['accepted_at'=>'datetime']; }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
