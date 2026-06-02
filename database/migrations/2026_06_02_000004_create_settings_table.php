<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();       // libellé affiché dans l'UI
            $table->string('group')->default('general'); // pour regrouper les paramètres
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('settings')->insert([
            ['key' => 'dg_email',              'value' => null,  'label' => 'Email Direction Générale (notifications POD)', 'group' => 'notifications', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'validation_delai_jours','value' => '5',   'label' => 'Délai de validation documents par défaut (jours)', 'group' => 'mad', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'app_nom',               'value' => 'MAD Tracker', 'label' => 'Nom de l\'application', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
