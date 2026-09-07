<?php
namespace App\Console\Commands;
use App\Models\Site;
use App\Services\EvolutionEngine;
use App\Services\PlanService;
use Illuminate\Console\Command;
use Throwable;
class RunEvolution extends Command {
    protected $signature='autoevolve:evolve {--site=}';
    protected $description='Run autonomous evolution for active SaaS sites.';
    public function handle(EvolutionEngine $engine, PlanService $plans): int {
        $query=Site::with('workspace')->where('status','active'); if($id=$this->option('site'))$query->where('id',$id); $ran=0;
        $query->chunk(50,function($sites) use($engine,$plans,&$ran){ foreach($sites as $site){ $workspace=$site->workspace; if(!$workspace || $workspace->status!=='active')continue; if(!$workspace->isTrialing() && !$workspace->subscriptionActive())continue; try{$plans->assertCanUse($workspace,'evolution_runs'); $result=$engine->run($site); $plans->record($workspace,'evolution_runs',1,$site,null,['source'=>'scheduler']+$result); $ran++; $this->line($site->name.': '.json_encode($result));}catch(Throwable $e){$this->warn($site->name.': '.$e->getMessage());} } });
        $this->info("Evolution completed for {$ran} site(s)."); return self::SUCCESS;
    }
}
