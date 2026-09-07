<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'is_admin'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_admin' => 'boolean'];
    }

    public function ownedWorkspaces(){ return $this->hasMany(Workspace::class,'owner_id'); }
    public function memberships(){ return $this->hasMany(Membership::class); }
    public function workspaces(){ return $this->belongsToMany(Workspace::class,'memberships')->withPivot('role','accepted_at')->withTimestamps(); }
}
