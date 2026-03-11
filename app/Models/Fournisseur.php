<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fournisseur extends Model
{
    use SoftDeletes;

    protected $fillable = ['nom', 'pays', 'ville', 'contact_nom', 'contact_email', 'notes'];

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }
}
