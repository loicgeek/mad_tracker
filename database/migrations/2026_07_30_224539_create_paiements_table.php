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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('devise', 3)->default('XAF');
            $table->string('methode')->nullable();
            $table->date('date_paiement');
            $table->unsignedTinyInteger('mois_couverts')->nullable();
            $table->date('date_echeance_resultante');
            $table->text('notes')->nullable();
            $table->foreignId('saisi_par_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
