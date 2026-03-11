<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeFacturation extends Model
{
    protected $table = 'etape_facturations';
    protected $fillable = [
        'dossier_id', 'facture_emise', 'date_facturation',
        'numero_facture_interne', 'paiement_recu', 'date_paiement',
        'montant', 'devise', 'observations', 'complete',
    ];
    protected $casts = [
        'date_facturation' => 'date',
        'date_paiement'    => 'date',
        'facture_emise'    => 'boolean',
        'paiement_recu'    => 'boolean',
        'complete'         => 'boolean',
        'montant'          => 'decimal:2',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
}
