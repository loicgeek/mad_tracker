<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeTransitaire extends Model
{
    protected $table = 'etape_transitaires';
    protected $fillable = [
        'dossier_id', 'transitaire_communique',
        'date_reception_infos_transitaire', 'date_instructions_envoyees',
        'date_enlevement', 'temps_traitement_jours', 'observations', 'complete',
    ];
    protected $casts = [
        'date_reception_infos_transitaire' => 'date',
        'date_instructions_envoyees'       => 'date',
        'date_enlevement'                  => 'date',
        'transitaire_communique'           => 'boolean',
        'complete'                         => 'boolean',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
}
