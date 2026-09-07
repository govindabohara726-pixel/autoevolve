<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAction extends Model
{
    use HasUuids;
    protected $fillable = ['content_item_id','action_type','risk_level','status','summary','payload','executed_at'];
    protected function casts(): array { return ['payload' => 'array', 'executed_at' => 'datetime']; }
    public function content(): BelongsTo { return $this->belongsTo(ContentItem::class, 'content_item_id'); }
}
