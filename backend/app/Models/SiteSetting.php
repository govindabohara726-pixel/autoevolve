<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['autonomy_level','stale_after_days','max_actions_per_run','auto_publish_low_risk','site_name','site_url'];
    protected function casts(): array { return ['autonomy_level'=>'integer','stale_after_days'=>'integer','max_actions_per_run'=>'integer','auto_publish_low_risk'=>'boolean']; }

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], ['autonomy_level'=>2,'stale_after_days'=>30,'max_actions_per_run'=>5,'auto_publish_low_risk'=>true,'site_name'=>'AutoEvolve']);
    }
}
