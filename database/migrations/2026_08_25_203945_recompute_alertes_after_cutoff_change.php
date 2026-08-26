<?php

use App\Models\Dossier;
use Illuminate\Database\Migrations\Migration;

/**
 * La date de coupure des alertes (Dossier::ALERTES_CUTOFF) vient de passer du
 * 1er septembre au 17 août 2026. Les dossiers créés entre ces deux dates ont
 * déjà leurs flags alerte_* figés à false (calculés sous l'ancien cutoff) —
 * on les recalcule une fois pour que le nouveau cutoff s'applique sans
 * attendre que chaque dossier soit ré-enregistré individuellement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Dossier::query()->with([
            'etapeMadFournisseur', 'etapeFacturation',
            'etapeTransitaire', 'etapeLivraison', 'etapeCloture',
        ])->chunkById(100, function ($dossiers) {
            $dossiers->each->refreshAlertes();
        });
    }

    public function down(): void
    {
        // Recalcul non réversible de façon significative — pas de down().
    }
};
