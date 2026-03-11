<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeCloture extends Model
{
    protected $table = 'etape_clotures';
    protected $fillable = [
        'dossier_id', 'pod_recue', 'date_pod',
        'pod_reference', 'pod_source', 'observations', 'complete',
    ];
    protected $casts = [
        'date_pod'  => 'date',
        'pod_recue' => 'boolean',
        'complete'  => 'boolean',
    ];
    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
}
