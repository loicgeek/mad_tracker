<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etape_livraisons', function (Blueprint $table) {
            $table->string('motif_retard')->nullable()->after('awb_bl_numero');
        });
    }

    public function down(): void
    {
        Schema::table('etape_livraisons', function (Blueprint $table) {
            $table->dropColumn('motif_retard');
        });
    }
};
