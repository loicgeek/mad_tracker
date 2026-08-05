<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporteur extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom', 'pays', 'contact_nom', 'contact_email', 'telephones', 'notes',
    ];

    protected $casts = ['telephones' => 'array'];

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }
}
