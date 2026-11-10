<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Nouvelles règles d'alerte (5 alertes, nouveaux délais, aucune alerte sur un
 * dossier finalisé) : on recalcule tous les dossiers une fois pour que les
 * signes ⚠️ obsolètes disparaissent sans attendre un ré-enregistrement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('alertes:recalculer');
    }

    public function down(): void
    {
        // Recalcul non réversible de façon significative — pas de down().
    }
};
