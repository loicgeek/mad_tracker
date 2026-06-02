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
        'montant_client', 'devise_client',
        'montant_fournisseur', 'devise_fournisseur',
        'taux_change',
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
        'montant_client'           => 'decimal:2',
        'montant_fournisseur'      => 'decimal:2',
        'taux_change'              => 'decimal:6',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }

    public function getEcartPaiementAttribute(): ?int
    {
        if ($this->date_echeance_facture && $this->date_paiement) {
            return $this->date_echeance_facture->diffInDays($this->date_paiement, false);
        }
        return null;
    }

    public function getMontantClientEurAttribute(): ?float
    {
        if (! $this->montant_client) return null;
        if ($this->devise_client === 'EUR') return (float) $this->montant_client;
        if ($this->taux_change) return round((float) $this->montant_client / (float) $this->taux_change, 2);
        return null;
    }

    public function getMontantFournisseurEurAttribute(): ?float
    {
        if (! $this->montant_fournisseur) return null;
        if ($this->devise_fournisseur === 'EUR') return (float) $this->montant_fournisseur;
        if ($this->taux_change) return round((float) $this->montant_fournisseur / (float) $this->taux_change, 2);
        return null;
    }

    public function getMargeEurAttribute(): ?float
    {
        $cli = $this->montant_client_eur;
        $fou = $this->montant_fournisseur_eur;
        if ($cli !== null && $fou !== null) return round($cli - $fou, 2);
        return null;
    }
}
