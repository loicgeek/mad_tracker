<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeLivraison extends Model
{
    protected $table = 'etape_livraisons';
    protected $fillable = [
        'dossier_id', 'date_livraison_prevue', 'date_livraison_reelle',
        'mode_transport', 'awb_bl_numero', 'motif_retard', 'applicable', 'observations', 'complete',
    ];
    protected $casts = [
        'date_livraison_prevue'  => 'date',
        'date_livraison_reelle'  => 'date',
        'applicable'             => 'boolean',
        'complete'               => 'boolean',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }

    public function getEcartJoursAttribute(): ?int
    {
        if ($this->date_livraison_reelle && $this->date_livraison_prevue) {
            return $this->date_livraison_prevue->diffInDays($this->date_livraison_reelle, false);
        }
        return null;
    }
}
