<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // dossiers : étendre type_commande + ajouter dimensions et date_promise_client
        DB::statement("ALTER TABLE dossiers MODIFY COLUMN type_commande ENUM('standard','pj_c1','pj_c2','pj_c3','c1','c2','c3') NULL");

        Schema::table('dossiers', function (Blueprint $table) {
            $table->string('dimensions')->nullable()->after('poids');
            $table->date('date_promise_client')->nullable()->after('incoterm_lieu');
        });

        // etape_mad_fournisseurs : docs techniques, photos emballage, nom valideur
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->boolean('docs_techniques_recus')->default(false)->after('docs_recus');
            $table->boolean('photos_emballage_recues')->default(false)->after('photos_recues');
            $table->string('nom_valideur')->nullable()->after('delai_validation_jours');
        });

        // etape_livraisons : type de document de transport
        Schema::table('etape_livraisons', function (Blueprint $table) {
            $table->string('type_doc_transport')->nullable()->after('mode_transport');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE dossiers MODIFY COLUMN type_commande ENUM('standard','projet') NULL");

        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn(['dimensions', 'date_promise_client']);
        });

        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['docs_techniques_recus', 'photos_emballage_recues', 'nom_valideur']);
        });

        Schema::table('etape_livraisons', function (Blueprint $table) {
            $table->dropColumn('type_doc_transport');
        });
    }
};
