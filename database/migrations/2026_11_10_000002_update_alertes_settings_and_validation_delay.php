<?php

use App\Models\Dossier;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demande client du 10/11/2026 :
 *  - alertes envoyées par mail à UNE seule personne (« pour moi seulement ») :
 *    nouveau paramètre email_alertes, initialisé avec l'email DG ;
 *    le digest n'est plus envoyé à email_secondaire_alertes (valeur conservée) ;
 *  - validation des documents : alerte au-delà de 3 jours (au lieu de 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Setting::get('email_alertes') === null) {
            Setting::set('email_alertes', Setting::get('dg_email'));
        }
        Setting::where('key', 'email_alertes')->update([
            'label' => 'Destinataire du mail quotidien des alertes',
            'group' => 'notifications',
        ]);

        if (in_array(Setting::get('validation_delai_jours'), [null, '5'], true)) {
            Setting::set('validation_delai_jours', (string) Dossier::DELAI_VALIDATION_DEFAUT);
        }

        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->integer('delai_validation_jours')->default(Dossier::DELAI_VALIDATION_DEFAUT)->change();
        });

        // Seuls les dossiers soumis aux alertes (créés depuis le cutoff) sont
        // concernés ; 5 était la valeur par défaut, jamais un choix explicite.
        DB::table('etape_mad_fournisseurs')
            ->where('delai_validation_jours', 5)
            ->whereIn('dossier_id', Dossier::withTrashed()
                ->where('created_at', '>=', Dossier::ALERTES_CUTOFF)
                ->select('id'))
            ->update(['delai_validation_jours' => Dossier::DELAI_VALIDATION_DEFAUT]);
    }

    public function down(): void
    {
        Schema::table('etape_mad_fournisseurs', function (Blueprint $table) {
            $table->integer('delai_validation_jours')->default(5)->change();
        });

        if (Setting::get('validation_delai_jours') === (string) Dossier::DELAI_VALIDATION_DEFAUT) {
            Setting::set('validation_delai_jours', '5');
        }

        Setting::where('key', 'email_alertes')->delete();
        \Illuminate\Support\Facades\Cache::forget('setting:email_alertes');
    }
};
