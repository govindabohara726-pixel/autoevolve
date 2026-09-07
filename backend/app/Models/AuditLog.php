<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
    protected $fillable=['workspace_id','user_id','action','target_type','target_id','meta','ip_address','user_agent'];
    protected function casts(): array { return ['meta'=>'array']; }
    public function user(){ return $this->belongsTo(User::class); }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
}
