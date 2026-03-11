<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = ['nom', 'prenom', 'initiales', 'email', 'password', 'role', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['actif' => 'boolean'];

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
