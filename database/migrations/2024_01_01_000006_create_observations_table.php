<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('etape', [
                'general',
                'mad_fournisseur',
                'facturation',
                'transitaire',
                'livraison',
                'cloture',
            ])->default('general');
            $table->text('contenu');
            $table->enum('type', ['info', 'alerte', 'blocage', 'resolution'])->default('info');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
