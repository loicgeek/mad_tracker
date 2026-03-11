<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Étape 1 : MAD Fournisseur ────────────────────────────────
        Schema::create('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained()->cascadeOnDelete();

            $table->date('date_mad_prevue')->nullable();
            $table->date('date_mad_reelle')->nullable();
            $table->integer('ecart_jours')->nullable()->storedAs(
                'CASE WHEN date_mad_reelle IS NOT NULL AND date_mad_prevue IS NOT NULL
                 THEN DATEDIFF(date_mad_reelle, date_mad_prevue)
                 ELSE NULL END'
            );

            $table->boolean('docs_recus')->default(false);
            $table->boolean('photos_recues')->default(false);
            $table->boolean('coc_recu')->default(false);
            $table->date('date_docs_recus')->nullable();

            $table->text('observations')->nullable();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });

        // ─── Étape 2 : Facturation ────────────────────────────────────
        Schema::create('etape_facturations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('facture_emise')->default(false);
            $table->date('date_facturation')->nullable();
            $table->string('numero_facture_interne')->nullable();
            $table->boolean('paiement_recu')->default(false);
            $table->date('date_paiement')->nullable();
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('devise', 3)->default('EUR');

            $table->text('observations')->nullable();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });

        // ─── Étape 3 : Coordination Transitaire ──────────────────────
        Schema::create('etape_transitaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('transitaire_communique')->default(false);
            $table->date('date_reception_infos_transitaire')->nullable();
            $table->date('date_instructions_envoyees')->nullable();
            $table->date('date_enlevement')->nullable();
            $table->integer('temps_traitement_jours')->nullable(); // MAD → enlèvement

            $table->text('observations')->nullable();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });

        // ─── Étape 4 : Livraison Client ───────────────────────────────
        Schema::create('etape_livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained()->cascadeOnDelete();

            $table->date('date_livraison_prevue')->nullable();
            $table->date('date_livraison_reelle')->nullable();
            $table->integer('ecart_livraison_jours')->nullable()->storedAs(
                'CASE WHEN date_livraison_reelle IS NOT NULL AND date_livraison_prevue IS NOT NULL
                 THEN DATEDIFF(date_livraison_reelle, date_livraison_prevue)
                 ELSE NULL END'
            );

            $table->string('mode_transport')->nullable(); // aérien, maritime, routier
            $table->string('awb_bl_numero')->nullable(); // numéro AWB ou BL
            $table->boolean('applicable')->default(true); // false pour FCA USINE/TRANSITAIRE si pas géré par nous

            $table->text('observations')->nullable();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });

        // ─── Étape 5 : Clôture (POD) ─────────────────────────────────
        Schema::create('etape_clotures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('pod_recue')->default(false);
            $table->date('date_pod')->nullable();
            $table->string('pod_reference')->nullable();
            $table->string('pod_source')->nullable(); // DHL, UPS, Bolloré, etc.

            $table->text('observations')->nullable();
            $table->boolean('complete')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etape_clotures');
        Schema::dropIfExists('etape_livraisons');
        Schema::dropIfExists('etape_transitaires');
        Schema::dropIfExists('etape_facturations');
        Schema::dropIfExists('etape_mad_fournisseurs');
    }
};
