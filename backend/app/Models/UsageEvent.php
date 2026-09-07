<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UsageEvent extends Model {
    protected $fillable=['workspace_id','site_id','user_id','meter','quantity','meta','occurred_at'];
    protected function casts(): array { return ['meta'=>'array','occurred_at'=>'datetime']; }
}
