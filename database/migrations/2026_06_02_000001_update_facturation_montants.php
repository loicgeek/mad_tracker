<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etape_facturations', function (Blueprint $table) {
            // Montant client (renommer montant → montant_client, devise → devise_client)
            $table->decimal('montant_client', 12, 2)->nullable()->after('devise');
            $table->string('devise_client', 3)->default('EUR')->after('montant_client');
            // Montant fournisseur
            $table->decimal('montant_fournisseur', 12, 2)->nullable()->after('devise_client');
            $table->string('devise_fournisseur', 3)->default('EUR')->after('montant_fournisseur');
            // Taux de change (vers EUR)
            $table->decimal('taux_change', 10, 6)->nullable()->after('devise_fournisseur');
        });

        // Migrer les données existantes
        DB::statement('UPDATE etape_facturations SET montant_client = montant, devise_client = devise WHERE montant IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->dropColumn(['montant_client', 'devise_client', 'montant_fournisseur', 'devise_fournisseur', 'taux_change']);
        });
    }
};
