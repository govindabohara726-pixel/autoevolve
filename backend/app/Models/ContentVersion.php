<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentVersion extends Model
{
    use HasUuids;
    protected $fillable = ['content_item_id','snapshot','reason','created_by'];
    protected function casts(): array { return ['snapshot' => 'array']; }
    public function content(): BelongsTo { return $this->belongsTo(ContentItem::class, 'content_item_id'); }
}
