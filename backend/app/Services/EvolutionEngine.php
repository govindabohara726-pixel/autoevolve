<?php

namespace App\Services;

use App\Models\AiAction;
use App\Models\ContentItem;
use App\Models\Opportunity;
use App\Models\Site;
use App\Models\SiteSetting;
use Throwable;

class EvolutionEngine
{
    public function __construct(private ContentEngine $content, private AiClient $ai) {}

    public function run(?Site $site = null): array
    {
        $legacy = $site ? null : SiteSetting::current();
        $siteSettings = $site?->settings ?? [];
        $staleAfter = $site ? (int)($siteSettings['stale_after_days'] ?? 30) : $legacy->stale_after_days;
        $maxActions = $site ? (int)($siteSettings['max_actions_per_run'] ?? 5) : $legacy->max_actions_per_run;
        $autonomy = $site ? (int)$site->autonomy_level : $legacy->autonomy_level;
        $cutoff = now()->subDays(max(1,$staleAfter));

        $items = ContentItem::where('status','published')
            ->when($site, fn($q)=>$q->where('site_id',$site->id))
            ->where(fn($q)=>$q->where('health_score','<',70)->orWhere('updated_at','<',$cutoff))
            ->orderBy('health_score')
            ->limit(max(1,min(20,$maxActions)))
            ->get();

        $improved = [];
        foreach ($items as $item) {
            try {
                $reason = $item->health_score < 70
                    ? "Content health is {$item->health_score}/100. Improve clarity, usefulness and on-page SEO without changing URL intent."
                    : "Content is older than {$staleAfter} days. Refresh evergreen clarity and flag time-sensitive facts.";
                $auto = $autonomy >= 3;
                $this->content->improve($item, $reason, $auto);
                $improved[] = ['id'=>$item->id,'title'=>$item->title,'status'=>$auto?'auto-published':'pending approval'];
            } catch (Throwable $e) {
                AiAction::create(['workspace_id'=>$item->workspace_id,'site_id'=>$item->site_id,'content_item_id'=>$item->id,'action_type'=>'improve','risk_level'=>'medium','status'=>'failed','summary'=>$e->getMessage()]);
            }
        }

        $opportunities = 0;
        if ($this->ai->configured()) {
            try { $opportunities = $this->discoverOpportunities($site); } catch (Throwable) {}
        }
        if ($site) $site->update(['last_evolved_at'=>now()]);
        return ['autonomy'=>$autonomy,'improved'=>$improved,'opportunities_found'=>$opportunities];
    }

    public function discoverOpportunities(?Site $site = null): int
    {
        $existing = ContentItem::when($site, fn($q)=>$q->where('site_id',$site->id))->pluck('primary_keyword')->filter()->take(100)->implode(', ');
        $context = $site ? "Site {$site->name}. Language {$site->language}." : 'Legacy site.';
        $data = $this->ai->json([
            ['role'=>'system','content'=>'You are a conservative editorial opportunity planner. Return JSON only. Do not claim search volume or trends you cannot verify.'],
            ['role'=>'user','content'=>"{$context} Existing site keywords: {$existing}. Suggest 5 adjacent decision-focused content gaps. Return {opportunities:[{type,topic,keyword,reason,priority}]}. Priority must be 1-100 and mean internal relevance, not search volume."],
        ]);
        $count = 0;
        foreach (($data['opportunities'] ?? []) as $row) {
            if (!is_array($row) || blank($row['topic'] ?? null)) continue;
            Opportunity::updateOrCreate(
                ['site_id'=>$site?->id,'topic'=>(string)$row['topic']],
                ['workspace_id'=>$site?->workspace_id,'type'=>$row['type'] ?? 'topic_gap','keyword'=>$row['keyword'] ?? null,'reason'=>$row['reason'] ?? 'Adjacent editorial opportunity.','priority'=>max(1,min(100,(int)($row['priority'] ?? 50))),'status'=>'new']
            );
            $count++;
        }
        AiAction::create(['workspace_id'=>$site?->workspace_id,'site_id'=>$site?->id,'action_type'=>'discover_opportunities','risk_level'=>'low','status'=>'completed','summary'=>"Discovered {$count} editorial opportunities.",'executed_at'=>now()]);
        return $count;
    }
}
