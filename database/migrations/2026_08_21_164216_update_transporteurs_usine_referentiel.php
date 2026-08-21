<?php

use App\Models\Transporteur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renomme l'entrée référentiel "USINE" (venant de l'import legacy) en
     * "Transitaire usine", et ajoute une nouvelle entrée "FCA usine" —
     * demandé par le client pour la liste Transporteurs.
     */
    public function up(): void
    {
        DB::table('transporteurs')->where('nom', 'USINE')->update(['nom' => 'Transitaire usine']);

        Transporteur::firstOrCreate(['nom' => 'FCA usine']);
    }

    public function down(): void
    {
        DB::table('transporteurs')->where('nom', 'Transitaire usine')->update(['nom' => 'USINE']);

        $fcaUsine = Transporteur::where('nom', 'FCA usine')->first();
        if ($fcaUsine && ! DB::table('dossiers')->where('transporteur_id', $fcaUsine->id)->exists()) {
            $fcaUsine->delete();
        }
    }
};
