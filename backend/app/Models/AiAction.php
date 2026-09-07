<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AiAction extends Model {
    use HasUuids;
    protected $fillable=['workspace_id','site_id','content_item_id','action_type','risk_level','status','summary','payload','executed_at'];
    protected function casts(): array { return ['payload'=>'array','executed_at'=>'datetime']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function content(): BelongsTo { return $this->belongsTo(ContentItem::class,'content_item_id'); }
}
