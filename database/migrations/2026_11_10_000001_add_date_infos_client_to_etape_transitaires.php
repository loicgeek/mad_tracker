<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étape 3 — l'alerte « Date réception infos transitaire vide 3 jours après
 * les infos transitaire reçues du client » a besoin d'une date de départ :
 * jusqu'ici « Infos transitaire reçues du client » n'était qu'une case à cocher.
 *
 * Pas de rétro-remplissage : on n'invente pas de date pour les dossiers déjà
 * cochés, l'alerte démarrera dès que la date sera saisie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etape_transitaires', function (Blueprint $table) {
            $table->date('date_infos_client')->nullable()->after('transitaire_communique');
        });
    }

    public function down(): void
    {
        Schema::table('etape_transitaires', function (Blueprint $table) {
            $table->dropColumn('date_infos_client');
        });
    }
};
