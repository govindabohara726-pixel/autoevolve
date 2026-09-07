<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasUuids;
    protected $fillable = ['type','topic','keyword','reason','priority','status'];
    protected function casts(): array { return ['priority' => 'integer']; }
}
