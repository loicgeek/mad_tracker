<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Auto-generated: DOS-2024-001
            $table->foreignId('user_id')->constrained()->comment('Responsable dossier');
            $table->foreignId('client_id')->constrained();
            $table->foreignId('fournisseur_id')->constrained();
            $table->string('numero_facture')->nullable();
            $table->string('reference_affaire')->nullable();
            $table->string('pays_destination')->nullable();

            // Incoterm
            $table->enum('incoterm', [
                'FCA_USINE',
                'FCA_TRANSITAIRE',
                'CPT',
                'CFR',
                'EXW',
                'AUTRES',
            ])->default('FCA_USINE');
            $table->string('incoterm_lieu')->nullable(); // lieu précis

            $table->enum('categorie', [
                'FCA_USINE',
                'FCA_TRANSITAIRE',
                'CPT',
                'AUTRES',
            ])->nullable();

            // Transitaire client
            $table->string('transitaire_nom')->nullable();
            $table->string('transitaire_contact')->nullable();
            $table->decimal('poids', 10, 2)->nullable();
            $table->decimal('cout_transitaire', 10, 2)->nullable();

            // Statut global calculé
            $table->enum('statut', [
                'en_attente',       // créé, en attente MAD
                'mad_fournisseur',  // MAD fournisseur faite
                'facture',          // facture émise
                'transitaire_ok',   // transitaire communiqué
                'enleve',           // enlevé par transitaire
                'livre',            // livré client
                'finalise',         // POD reçue, clôturé
            ])->default('en_attente');

            // Alertes
            $table->boolean('alerte_retard_mad')->default(false);
            $table->boolean('alerte_facture_manquante')->default(false);
            $table->boolean('alerte_transitaire_manquant')->default(false);
            $table->boolean('alerte_pod_manquante')->default(false);
            $table->boolean('alerte_livraison_depassee')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
