<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeMadFournisseur extends Model
{
    protected $table = 'etape_mad_fournisseurs';
    protected $fillable = [
        'dossier_id', 'date_mad_prevue', 'date_mad_fournisseur', 'date_mad_reelle',
        'docs_recus', 'docs_techniques_recus', 'photos_recues', 'photos_emballage_recues',
        'date_validation_document', 'date_demande_validation', 'date_reception_validation',
        'delai_validation_jours', 'nom_valideur', 'observations', 'complete',
    ];
    protected $casts = [
        'date_mad_prevue'           => 'date',
        'date_mad_fournisseur'      => 'date',
        'date_mad_reelle'           => 'date',
        'date_validation_document'  => 'date',
        'date_demande_validation'   => 'date',
        'date_reception_validation' => 'date',
        'delai_validation_jours'    => 'integer',
        'docs_recus'                => 'boolean',
        'docs_techniques_recus'     => 'boolean',
        'photos_recues'             => 'boolean',
        'photos_emballage_recues'   => 'boolean',
        'complete'                  => 'boolean',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }

    public function getEcartJoursAttribute(): ?int
    {
        if ($this->date_mad_reelle && $this->date_mad_prevue) {
            return $this->date_mad_prevue->diffInDays($this->date_mad_reelle, false);
        }
        return null;
    }

    public function getEcartValidationAttribute(): ?int
    {
        if ($this->date_mad_reelle && $this->date_validation_document) {
            return $this->date_mad_reelle->diffInDays($this->date_validation_document, false);
        }
        return null;
    }

    public function getValidationEnRetardAttribute(): bool
    {
        if (! $this->date_demande_validation || $this->date_reception_validation) return false;
        $delai = $this->delai_validation_jours ?? 5;
        return $this->date_demande_validation->addDays($delai)->isPast();
    }
}
