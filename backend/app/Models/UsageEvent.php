<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UsageEvent extends Model {
    protected $fillable=['workspace_id','site_id','user_id','meter','quantity','meta','occurred_at'];
    protected function casts(): array { return ['meta'=>'array','occurred_at'=>'datetime']; }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
    public function site(){ return $this->belongsTo(Site::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
