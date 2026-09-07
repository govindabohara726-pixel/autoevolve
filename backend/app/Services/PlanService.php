<?php
namespace App\Services;
use App\Models\UsageEvent;
use App\Models\Workspace;
use RuntimeException;
class PlanService {
    public function plan(Workspace $workspace): array { return config('plans.plans.'.$workspace->plan, config('plans.plans.'.config('plans.default'))); }
    public function limit(Workspace $workspace, string $meter): int { return (int) data_get($this->plan($workspace),'limits.'.$meter,0); }
    public function used(Workspace $workspace, string $meter): int {
        return (int) UsageEvent::where('workspace_id',$workspace->id)->where('meter',$meter)->where('occurred_at','>=',now()->startOfMonth())->sum('quantity');
    }
    public function remaining(Workspace $workspace, string $meter): int { return max(0,$this->limit($workspace,$meter)-$this->used($workspace,$meter)); }
    public function assertCanUse(Workspace $workspace, string $meter, int $quantity=1): void {
        if ($workspace->status !== 'active') throw new RuntimeException('This workspace is not active.');
        if (!$workspace->isTrialing() && !$workspace->subscriptionActive()) throw new RuntimeException('Your trial has ended. Choose a plan to continue using AI automation.');
        $limit=$this->limit($workspace,$meter);
        if($limit>0 && $this->used($workspace,$meter)+$quantity>$limit) throw new RuntimeException('Your '.$workspace->plan.' plan has reached its '.$meter.' monthly limit.');
    }
    public function record(Workspace $workspace, string $meter, int $quantity=1, mixed $site=null, mixed $user=null, array $meta=[]): void {
        UsageEvent::create(['workspace_id'=>$workspace->id,'site_id'=>$site?->id,'user_id'=>$user?->id,'meter'=>$meter,'quantity'=>$quantity,'meta'=>$meta ?: null,'occurred_at'=>now()]);
    }
}
