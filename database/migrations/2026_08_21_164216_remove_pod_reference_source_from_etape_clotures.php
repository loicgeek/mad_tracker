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
        Schema::table('etape_clotures', function (Blueprint $table) {
            $table->dropColumn(['pod_reference', 'pod_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etape_clotures', function (Blueprint $table) {
            $table->string('pod_reference')->nullable()->after('date_pod');
            $table->string('pod_source')->nullable()->after('pod_reference');
        });
    }
};
