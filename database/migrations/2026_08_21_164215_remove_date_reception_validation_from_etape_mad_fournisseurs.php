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
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->dropColumn('date_reception_validation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->date('date_reception_validation')->nullable()->after('date_demande_validation');
        });
    }
};
