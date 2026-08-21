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
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->renameColumn('coc_coo', 'coc_recu');
        });
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->boolean('co_recu')->default(false)->after('coc_recu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->dropColumn('co_recu');
        });
        Schema::table('etape_facturations', function (Blueprint $table) {
            $table->renameColumn('coc_recu', 'coc_coo');
        });
    }
};
