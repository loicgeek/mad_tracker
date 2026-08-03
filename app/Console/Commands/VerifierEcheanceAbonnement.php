<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\EcheanceAbonnement;
use App\Support\Abonnement;
use App\Support\SafeNotifier;
use Illuminate\Console\Command;

/**
 * Vérifie l'échéance de l'abonnement de LA PLATEFORME (cette instance),
 * pas des comptes individuels. Désactive toute la plateforme si dépassée,
 * ou envoie un rappel aux super_admin si l'échéance approche.
 */
class VerifierEcheanceAbonnement extends Command
{
    protected $signature = 'abonnement:verifier-echeance';

    protected $description = "Désactive la plateforme si l'abonnement est expiré et envoie un rappel avant expiration.";

    public function handle(): int
    {
        $dateEcheance = Abonnement::dateEcheance();

        if (! $dateEcheance) {
            $this->info('Aucune échéance définie — rien à vérifier.');
            return self::SUCCESS;
        }

        $superAdmins = User::where('role', 'super_admin')->where('actif', true)->get();
        $rappelJours = (int) Setting::get('echeance_rappel_jours', 7);

        if (Abonnement::actif() && $dateEcheance->isPast()) {
            Abonnement::definirActif(false);

            $notification = new EcheanceAbonnement('expire', $dateEcheance->format('d/m/Y'));
            foreach ($superAdmins as $admin) {
                SafeNotifier::send($admin, $notification);
            }

            $this->info("Plateforme désactivée : échéance {$dateEcheance->format('d/m/Y')} dépassée.");
            return self::SUCCESS;
        }

        if (Abonnement::actif() && $dateEcheance->isSameDay(now()->addDays($rappelJours))) {
            $notification = new EcheanceAbonnement('rappel', $dateEcheance->format('d/m/Y'));
            foreach ($superAdmins as $admin) {
                SafeNotifier::send($admin, $notification);
            }

            $this->info("Rappel envoyé : échéance {$dateEcheance->format('d/m/Y')} dans {$rappelJours} jour(s).");
            return self::SUCCESS;
        }

        $this->info('Abonnement en règle, rien à faire.');
        return self::SUCCESS;
    }
}
