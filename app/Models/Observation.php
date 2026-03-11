<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    protected $fillable = ['dossier_id', 'user_id', 'etape', 'contenu', 'type'];

    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'alerte'     => '⚠️',
            'blocage'    => '🔴',
            'resolution' => '✅',
            default      => 'ℹ️',
        };
    }
}
