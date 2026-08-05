<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fusionne le rôle "administratif" dans "admin" — les deux rôles étaient
     * source de confusion dans l'UI ; l'admin unifié récupère les pouvoirs
     * étendus qu'avait administratif (paramètres généraux + gestion des comptes).
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'administratif')->update(['role' => 'admin']);
    }

    /**
     * Pas de retour arrière possible : on ne sait plus distinguer, après coup,
     * quels comptes "admin" étaient auparavant "administratif".
     */
    public function down(): void
    {
        //
    }
};
