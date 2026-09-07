<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Site extends Model {
    use HasUuids;
    protected $fillable=['workspace_id','name','slug','domain','status','autonomy_level','language','timezone','settings','last_evolved_at'];
    protected function casts(): array { return ['settings'=>'array','last_evolved_at'=>'datetime','autonomy_level'=>'integer']; }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
    public function content(){ return $this->hasMany(ContentItem::class); }
    public function actions(){ return $this->hasMany(AiAction::class); }
    public function opportunities(){ return $this->hasMany(Opportunity::class); }
}
