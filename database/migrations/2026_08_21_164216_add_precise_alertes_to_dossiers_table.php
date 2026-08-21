<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->boolean('alerte_mad_reelle_manquante')->default(false)->after('alerte_cout_reel_manquant');
            $table->boolean('alerte_validation_documents_manquante')->default(false)->after('alerte_mad_reelle_manquante');
            $table->boolean('alerte_co_coc_manquant')->default(false)->after('alerte_validation_documents_manquante');
            $table->boolean('alerte_transitaire_infos_manquantes')->default(false)->after('alerte_co_coc_manquant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn([
                'alerte_mad_reelle_manquante',
                'alerte_validation_documents_manquante',
                'alerte_co_coc_manquant',
                'alerte_transitaire_infos_manquantes',
            ]);
        });
    }
};
