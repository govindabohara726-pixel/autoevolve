<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class ApiKey extends Model {
    use HasUuids;
    protected $fillable=['workspace_id','name','token_hash','last_used_at','revoked_at'];
    protected function casts(): array { return ['last_used_at'=>'datetime','revoked_at'=>'datetime']; }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
}
