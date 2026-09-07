<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Opportunity extends Model {
    use HasUuids;
    protected $fillable=['workspace_id','site_id','type','topic','keyword','reason','priority','status'];
    public function workspace(){ return $this->belongsTo(Workspace::class); }
    public function site(){ return $this->belongsTo(Site::class); }
}
