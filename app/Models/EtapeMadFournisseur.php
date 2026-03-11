<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeMadFournisseur extends Model
{
    protected $table = 'etape_mad_fournisseurs';
    protected $fillable = [
        'dossier_id', 'date_mad_prevue', 'date_mad_reelle',
        'docs_recus', 'photos_recues', 'coc_recu', 'date_docs_recus',
        'observations', 'complete',
    ];
    protected $casts = [
        'date_mad_prevue'  => 'date',
        'date_mad_reelle'  => 'date',
        'date_docs_recus'  => 'date',
        'docs_recus'       => 'boolean',
        'photos_recues'    => 'boolean',
        'coc_recu'         => 'boolean',
        'complete'         => 'boolean',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }

    public function getEcartJoursAttribute(): ?int
    {
        if ($this->date_mad_reelle && $this->date_mad_prevue) {
            return $this->date_mad_prevue->diffInDays($this->date_mad_reelle, false);
        }
        return null;
    }
}
