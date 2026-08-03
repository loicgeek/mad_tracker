<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Abonnement de la PLATEFORME (cette instance de l'application), pas d'un
 * utilisateur individuel. Géré exclusivement par le super_admin (l'éditeur) :
 * quand il expire, toute l'application se bloque pour tout le monde sauf le
 * super_admin, qui reste seul à pouvoir enregistrer un paiement pour la
 * réactiver.
 */
class Abonnement
{
    public static function actif(): bool
    {
        return (bool) Setting::get('abonnement_actif', true);
    }

    public static function dateEcheance(): ?Carbon
    {
        $v = Setting::get('abonnement_date_echeance');
        return $v ? Carbon::parse($v) : null;
    }

    public static function activerJusquau(Carbon $dateEcheance): void
    {
        Setting::set('abonnement_actif', '1');
        Setting::set('abonnement_date_echeance', $dateEcheance->format('Y-m-d'));
    }

    public static function definirActif(bool $actif): void
    {
        Setting::set('abonnement_actif', $actif ? '1' : '0');
    }
}
