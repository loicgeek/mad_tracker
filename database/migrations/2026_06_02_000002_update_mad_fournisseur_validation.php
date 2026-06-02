<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            // Date à laquelle le fournisseur confirme que le matériel est ok
            $table->date('date_mad_fournisseur')->nullable()->after('date_mad_prevue');

            // Validation des documents
            $table->date('date_demande_validation')->nullable()->after('date_validation_document');
            $table->date('date_reception_validation')->nullable()->after('date_demande_validation');
            $table->integer('delai_validation_jours')->default(5)->after('date_reception_validation');
        });
    }

    public function down(): void
    {
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['date_mad_fournisseur', 'date_demande_validation', 'date_reception_validation', 'delai_validation_jours']);
        });
    }
};
