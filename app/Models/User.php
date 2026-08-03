<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const ROLES = [
        'super_admin'  => 'Super administrateur',
        'administratif' => 'Administratif',
        'admin'        => 'Administrateur',
        'gestionnaire' => 'Gestionnaire',
        'lecteur'      => 'Lecteur',
    ];

    protected $fillable = [
        'nom', 'prenom', 'initiales', 'email', 'password', 'role', 'actif',
        'created_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comptesCrees(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAdministratif(): bool
    {
        return $this->role === 'administratif';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
