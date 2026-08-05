<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('telephones')->nullable()->after('contact_phone');
        });
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->json('telephones')->nullable()->after('contact_email');
        });
        Schema::table('transporteurs', function (Blueprint $table) {
            $table->json('telephones')->nullable()->after('contact_phone');
        });

        // Backfill : l'ancien numéro unique devient le premier élément de la liste.
        foreach (['clients', 'transporteurs'] as $tableName) {
            $rows = DB::table($tableName)->whereNotNull('contact_phone')->where('contact_phone', '!=', '')->get(['id', 'contact_phone']);
            foreach ($rows as $row) {
                DB::table($tableName)->where('id', $row->id)
                    ->update(['telephones' => json_encode([$row->contact_phone])]);
            }
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('contact_phone');
        });
        Schema::table('transporteurs', function (Blueprint $table) {
            $table->dropColumn('contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('contact_email');
        });
        Schema::table('transporteurs', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('contact_email');
        });

        foreach (['clients', 'transporteurs'] as $tableName) {
            $rows = DB::table($tableName)->whereNotNull('telephones')->get(['id', 'telephones']);
            foreach ($rows as $row) {
                $numeros = json_decode($row->telephones, true) ?: [];
                DB::table($tableName)->where('id', $row->id)->update(['contact_phone' => $numeros[0] ?? null]);
            }
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('telephones');
        });
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn('telephones');
        });
        Schema::table('transporteurs', function (Blueprint $table) {
            $table->dropColumn('telephones');
        });
    }
};
