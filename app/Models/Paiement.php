<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'user_id', 'montant', 'devise', 'methode', 'date_paiement',
        'mois_couverts', 'date_echeance_resultante', 'notes', 'saisi_par_id',
    ];

    protected $casts = [
        'montant'                  => 'decimal:2',
        'date_paiement'            => 'date',
        'date_echeance_resultante' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }
}
