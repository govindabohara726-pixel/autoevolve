<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ContentRevision extends Model
{
    use HasUuids;

    protected $fillable = [
        'content_item_id','ai_action_id','proposed_snapshot','reason','risk_level','status','created_by','resolved_by','resolved_at'
    ];

    protected function casts(): array
    {
        return ['proposed_snapshot' => 'array', 'resolved_at' => 'datetime'];
    }

    public function content() { return $this->belongsTo(ContentItem::class, 'content_item_id'); }
    public function aiAction() { return $this->belongsTo(AiAction::class, 'ai_action_id'); }
}
