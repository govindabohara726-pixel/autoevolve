<?php

namespace App\Services;

use App\Models\AiAction;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\ContentVersion;
use App\Models\Site;
use Illuminate\Support\Str;

class ContentEngine
{
    public function __construct(private AiClient $ai) {}

    public function generate(string $topic, string $type = 'guide', ?Site $site = null): ContentItem
    {
        $siteContext = $site ? "Site: {$site->name}. Language: {$site->language}. Settings: ".json_encode($site->settings ?? []) : 'Site context not provided.';
        $data = $this->ai->json([
            ['role'=>'system','content'=>'You are AutoEvolve, an expert editorial and SEO system. Return JSON only. Never invent statistics, prices, claims, or sources. Write genuinely useful decision content.'],
            ['role'=>'user','content'=>"{$siteContext}\nCreate a {$type} about: {$topic}. Return keys: title, excerpt, primary_keyword, search_intent, meta_title, meta_description, body. body must contain summary, intro, sections[{heading,body}], takeaways[], faq[{question,answer}], sources[]. Keep factual claims conservative and mark anything that needs external verification."],
        ]);

        $title = trim((string)($data['title'] ?? $topic));
        $slug = $this->uniqueSlug($title, $site);
        [$health, $seo] = $this->scores($data);

        $item = ContentItem::create([
            'workspace_id'=>$site?->workspace_id,
            'site_id'=>$site?->id,
            'title'=>$title,
            'slug'=>$slug,
            'excerpt'=>$data['excerpt'] ?? null,
            'body'=>$this->normalizeBody($data['body'] ?? []),
            'primary_keyword'=>$data['primary_keyword'] ?? $topic,
            'search_intent'=>$data['search_intent'] ?? 'informational',
            'meta_title'=>$data['meta_title'] ?? $title,
            'meta_description'=>$data['meta_description'] ?? ($data['excerpt'] ?? null),
            'health_score'=>$health,
            'seo_score'=>$seo,
            'risk_score'=>'low',
            'status'=>'draft',
        ]);

        AiAction::create([
            'workspace_id'=>$site?->workspace_id,
            'site_id'=>$site?->id,
            'content_item_id'=>$item->id,
            'action_type'=>'generate',
            'risk_level'=>'low',
            'status'=>'completed',
            'summary'=>'Generated a new draft for “'.$title.'”.',
            'payload'=>['topic'=>$topic,'type'=>$type],
            'executed_at'=>now(),
        ]);

        return $item;
    }

    public function improve(ContentItem $item, string $reason, bool $publish = false): ContentItem
    {
        $current = json_encode($item->only(['title','excerpt','body','primary_keyword','search_intent','meta_title','meta_description']), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $data = $this->ai->json([
            ['role'=>'system','content'=>'You improve existing website content without changing its URL intent. Return JSON only. Do not invent facts or citations.'],
            ['role'=>'user','content'=>"Reason for improvement: {$reason}\nCurrent content: {$current}\nReturn the complete improved object with title, excerpt, primary_keyword, search_intent, meta_title, meta_description, body. body must contain summary, intro, sections, takeaways, faq, sources."],
        ]);
        [$health, $seo] = $this->scores($data);
        $proposed = [
            'title'=>$data['title'] ?? $item->title,
            'excerpt'=>$data['excerpt'] ?? $item->excerpt,
            'body'=>$this->normalizeBody($data['body'] ?? $item->body),
            'primary_keyword'=>$data['primary_keyword'] ?? $item->primary_keyword,
            'search_intent'=>$data['search_intent'] ?? $item->search_intent,
            'meta_title'=>$data['meta_title'] ?? $item->meta_title,
            'meta_description'=>$data['meta_description'] ?? $item->meta_description,
            'health_score'=>$health,
            'seo_score'=>$seo,
            'risk_score'=>'medium',
        ];

        if ($publish) {
            $this->saveVersion($item, $reason, 'ai:auto');
            $item->fill($proposed);
            $item->status = 'published';
            $item->published_at ??= now();
            $item->save();
            AiAction::create([
                'workspace_id'=>$item->workspace_id,'site_id'=>$item->site_id,'content_item_id'=>$item->id,
                'action_type'=>'improve','risk_level'=>'medium','status'=>'completed','summary'=>$reason,
                'payload'=>['auto_published'=>true],'executed_at'=>now(),
            ]);
            return $item->fresh();
        }

        $revision = ContentRevision::create([
            'content_item_id'=>$item->id,
            'proposed_snapshot'=>$proposed,
            'reason'=>$reason,
            'risk_level'=>'medium',
            'status'=>'pending',
            'created_by'=>'ai',
        ]);
        $action = AiAction::create([
            'workspace_id'=>$item->workspace_id,'site_id'=>$item->site_id,'content_item_id'=>$item->id,
            'action_type'=>'improve','risk_level'=>'medium','status'=>'pending_approval','summary'=>$reason,
            'payload'=>['auto_published'=>false,'revision_id'=>$revision->id],
        ]);
        $revision->update(['ai_action_id'=>$action->id]);

        return $item->fresh();
    }

    public function applyRevision(ContentRevision $revision, string $resolvedBy): ContentItem
    {
        abort_unless($revision->status === 'pending', 409);
        $item = $revision->content;
        $this->saveVersion($item, $revision->reason ?: 'Approved AI revision', $resolvedBy);
        $item->fill($revision->proposed_snapshot);
        $item->save();
        $revision->update(['status'=>'applied','resolved_by'=>$resolvedBy,'resolved_at'=>now()]);
        $revision->aiAction?->update(['status'=>'completed','executed_at'=>now()]);
        return $item->fresh();
    }

    public function rejectRevision(ContentRevision $revision, string $resolvedBy): void
    {
        abort_unless($revision->status === 'pending', 409);
        $revision->update(['status'=>'rejected','resolved_by'=>$resolvedBy,'resolved_at'=>now()]);
        $revision->aiAction?->update(['status'=>'rejected']);
    }

    private function saveVersion(ContentItem $item, string $reason, string $createdBy): void
    {
        ContentVersion::create([
            'content_item_id'=>$item->id,
            'snapshot'=>$item->only(['title','slug','excerpt','body','primary_keyword','search_intent','meta_title','meta_description','status','health_score','seo_score','risk_score','published_at']),
            'reason'=>$reason,
            'created_by'=>$createdBy,
        ]);
    }

    private function uniqueSlug(string $title, ?Site $site = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base; $i = 2;
        $query = fn(string $candidate) => ContentItem::where('slug',$candidate)->when($site, fn($q)=>$q->where('site_id',$site->id));
        while ($query($slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }

    private function normalizeBody(array $body): array
    {
        return [
            'summary'=>(string)($body['summary'] ?? ''),
            'intro'=>(string)($body['intro'] ?? ''),
            'sections'=>array_values(array_filter($body['sections'] ?? [], 'is_array')),
            'takeaways'=>array_values(array_filter($body['takeaways'] ?? [], 'is_string')),
            'faq'=>array_values(array_filter($body['faq'] ?? [], 'is_array')),
            'sources'=>array_values(array_filter($body['sources'] ?? [], 'is_array')),
        ];
    }

    private function scores(array $data): array
    {
        $body = $this->normalizeBody($data['body'] ?? []);
        $seo = 45;
        if (filled($data['primary_keyword'] ?? null)) $seo += 15;
        if (mb_strlen((string)($data['meta_title'] ?? '')) >= 25) $seo += 10;
        if (mb_strlen((string)($data['meta_description'] ?? '')) >= 80) $seo += 10;
        if (count($body['sections']) >= 3) $seo += 10;
        if (count($body['faq']) >= 2) $seo += 10;
        $health = min(100, 55 + min(25, count($body['sections']) * 5) + (count($body['takeaways']) ? 10 : 0) + (count($body['faq']) ? 10 : 0));
        return [min(100,$health), min(100,$seo)];
    }
}
