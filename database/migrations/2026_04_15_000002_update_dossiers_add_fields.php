<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->enum('type_commande', ['standard', 'projet'])->nullable()->after('categorie');
            $table->decimal('cout_reel', 10, 2)->nullable()->after('cout_transitaire');
            $table->foreignId('transporteur_id')
                  ->nullable()
                  ->constrained('transporteurs')
                  ->nullOnDelete()
                  ->after('cout_reel');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropForeign(['transporteur_id']);
            $table->dropColumn(['type_commande', 'cout_reel', 'transporteur_id']);
        });
    }
};
