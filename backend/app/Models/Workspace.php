<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Workspace extends Model {
    use HasUuids;
    protected $fillable=['owner_id','name','slug','status','plan','trial_ends_at','stripe_customer_id','billing_email'];
    protected function casts(): array { return ['trial_ends_at'=>'datetime']; }
    public function owner(){ return $this->belongsTo(User::class,'owner_id'); }
    public function memberships(){ return $this->hasMany(Membership::class); }
    public function users(){ return $this->belongsToMany(User::class,'memberships')->withPivot('role','accepted_at')->withTimestamps(); }
    public function sites(){ return $this->hasMany(Site::class); }
    public function subscriptions(){ return $this->hasMany(Subscription::class); }
    public function isTrialing(): bool { return $this->trial_ends_at?->isFuture() ?? false; }
    public function subscriptionActive(): bool { return $this->subscriptions()->whereIn('status',['active','trialing'])->exists(); }
}
