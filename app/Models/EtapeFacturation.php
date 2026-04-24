<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeFacturation extends Model
{
    protected $table = 'etape_facturations';
    protected $fillable = [
        'dossier_id', 'facture_emise', 'date_facturation',
        'numero_facture', 'date_echeance_facture',
        'montant', 'devise', 'coc_coo',
        'validation_facture_client', 'date_validation_facture',
        'paiement_recu', 'date_paiement',
        'observations', 'complete',
    ];
    protected $casts = [
        'date_facturation'         => 'date',
        'date_echeance_facture'    => 'date',
        'date_validation_facture'  => 'date',
        'date_paiement'            => 'date',
        'facture_emise'            => 'boolean',
        'coc_coo'                  => 'boolean',
        'validation_facture_client'=> 'boolean',
        'paiement_recu'            => 'boolean',
        'complete'                 => 'boolean',
        'montant'                  => 'decimal:2',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }

    public function getEcartPaiementAttribute(): ?int
    {
        if ($this->date_echeance_facture && $this->date_paiement) {
            return $this->date_echeance_facture->diffInDays($this->date_paiement, false);
        }
        return null;
    }
}
