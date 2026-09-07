<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model {
    use HasUuids;
    protected $fillable=['workspace_id','provider','provider_subscription_id','provider_price_id','plan','status','current_period_end','cancel_at_period_end','payload'];
    protected function casts(): array { return ['current_period_end'=>'datetime','cancel_at_period_end'=>'boolean','payload'=>'array']; }
    public function workspace(){ return $this->belongsTo(Workspace::class); }
}
