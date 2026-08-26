<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE dossiers MODIFY incoterm ENUM('FCA_USINE','FCA_TRANSITAIRE','CPT','CFR','EXW','FOB','CIF','AUTRES') NOT NULL DEFAULT 'FCA_USINE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE dossiers MODIFY incoterm ENUM('FCA_USINE','FCA_TRANSITAIRE','CPT','CFR','EXW','AUTRES') NOT NULL DEFAULT 'FCA_USINE'");
    }
};
