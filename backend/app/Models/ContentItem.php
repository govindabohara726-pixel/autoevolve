<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentItem extends Model
{
    use HasUuids;

    protected $fillable = ['workspace_id','site_id','title','slug','excerpt','body','primary_keyword','search_intent','status','health_score','seo_score','risk_score','meta_title','meta_description','published_at'];

    protected function casts(): array
    {
        return ['body' => 'array', 'published_at' => 'datetime', 'health_score' => 'integer', 'seo_score' => 'integer'];
    }

    public function workspace(){ return $this->belongsTo(Workspace::class); }
    public function site(){ return $this->belongsTo(Site::class); }
    public function versions(): HasMany { return $this->hasMany(ContentVersion::class); }
    public function actions(): HasMany { return $this->hasMany(AiAction::class); }
    public function revisions(): HasMany { return $this->hasMany(ContentRevision::class); }
    public function pendingRevisions(): HasMany { return $this->revisions()->where('status','pending'); }
}
