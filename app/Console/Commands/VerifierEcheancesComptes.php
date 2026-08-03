<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\EcheanceCompte;
use App\Support\SafeNotifier;
use Illuminate\Console\Command;

class VerifierEcheancesComptes extends Command
{
    protected $signature = 'comptes:verifier-echeances';

    protected $description = "Désactive les comptes dont l'échéance est dépassée et envoie les rappels avant expiration.";

    public function handle(): int
    {
        $rappelJours = (int) Setting::get('echeance_rappel_jours', 7);
        $superAdmins = User::where('role', 'super_admin')->where('actif', true)->get();

        $expires = User::whereNotNull('date_echeance')
            ->where('role', '!=', 'super_admin')
            ->where('actif', true)
            ->whereDate('date_echeance', '<', now())
            ->get();

        foreach ($expires as $user) {
            $user->update(['actif' => false]);

            $notification = new EcheanceCompte($user->id, $user->nom_complet, 'expire', $user->date_echeance->format('d/m/Y'));
            SafeNotifier::send($user, $notification);
            foreach ($superAdmins as $admin) {
                SafeNotifier::send($admin, $notification);
            }

            $this->info("Compte désactivé : {$user->nom_complet} (échéance {$user->date_echeance->format('d/m/Y')})");
        }

        $rappels = User::whereNotNull('date_echeance')
            ->where('role', '!=', 'super_admin')
            ->where('actif', true)
            ->whereDate('date_echeance', now()->addDays($rappelJours)->toDateString())
            ->get();

        foreach ($rappels as $user) {
            $notification = new EcheanceCompte($user->id, $user->nom_complet, 'rappel', $user->date_echeance->format('d/m/Y'));
            foreach ($superAdmins as $admin) {
                SafeNotifier::send($admin, $notification);
            }

            $this->info("Rappel envoyé pour : {$user->nom_complet} (échéance {$user->date_echeance->format('d/m/Y')})");
        }

        $this->info("Terminé : {$expires->count()} compte(s) désactivé(s), {$rappels->count()} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
