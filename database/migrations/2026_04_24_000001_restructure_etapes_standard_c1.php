<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MAD Fournisseur : renommer date_docs_recus → date_validation_document
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->renameColumn('date_docs_recus', 'date_validation_document');
        });

        // Facturation : nouveaux champs, supprimer numero_facture_interne
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->string('numero_facture')->nullable()->after('date_facturation');
            $table->date('date_echeance_facture')->nullable()->after('numero_facture');
            $table->boolean('coc_coo')->default(false)->after('devise');
            $table->boolean('validation_facture_client')->default(false)->after('coc_coo');
            $table->date('date_validation_facture')->nullable()->after('validation_facture_client');
        });

        // Migrer les données de numero_facture_interne vers numero_facture si vide
        DB::statement('UPDATE etape_facturations SET numero_facture = numero_facture_interne WHERE numero_facture IS NULL AND numero_facture_interne IS NOT NULL');

        // Migrer numero_facture du dossier vers etape_facturations si vide
        DB::statement('
            UPDATE etape_facturations
            SET numero_facture = (SELECT numero_facture FROM dossiers WHERE dossiers.id = etape_facturations.dossier_id)
            WHERE numero_facture IS NULL
              AND (SELECT numero_facture FROM dossiers WHERE dossiers.id = etape_facturations.dossier_id) IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->renameColumn('date_validation_document', 'date_docs_recus');
        });

        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->dropColumn(['numero_facture', 'date_echeance_facture', 'coc_coo', 'validation_facture_client', 'date_validation_facture']);
        });
    }
};
