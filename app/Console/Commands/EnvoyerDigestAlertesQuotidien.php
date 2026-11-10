<?php

namespace App\Console\Commands;

use App\Models\Dossier;
use App\Models\Setting;
use App\Notifications\DigestAlertesQuotidien;
use App\Support\SafeNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Recalcule les alertes puis envoie le récapitulatif quotidien des dossiers en
 * alerte à UN seul destinataire (paramètre « email_alertes »). L'équipe, elle,
 * voit le signe ⚠️ directement sur les dossiers.
 */
class EnvoyerDigestAlertesQuotidien extends Command
{
    protected $signature = 'alertes:digest-quotidien';

    protected $description = "Recalcule les alertes et envoie le récapitulatif quotidien au destinataire configuré.";

    public function handle(): int
    {
        // Toujours recalculer, même sans destinataire : c'est ce qui fait
        // apparaître le signe ⚠️ sur les dossiers dont un délai vient d'expirer.
        $this->call('alertes:recalculer');

        $email = Setting::get('email_alertes');
        if (! $email) {
            $this->info('Aucun destinataire configuré pour le mail des alertes (Paramètres).');
            return self::SUCCESS;
        }

        $dossiers = Dossier::withAlertes()->with(['client'])->orderBy('reference_affaire')->get();

        if ($dossiers->isEmpty()) {
            $this->info('Aucun dossier en alerte, rien à envoyer.');
            return self::SUCCESS;
        }

        SafeNotifier::send(Notification::route('mail', $email), new DigestAlertesQuotidien(
            $dossiers->map(fn (Dossier $d) => [
                'reference' => $d->reference_affaire,
                'clientNom' => $d->client?->nom ?? '—',
                'url'       => $d->url_alerte,
                'alertes'   => $d->alert_labels,
            ])->all()
        ));

        $this->info("Digest envoyé à {$email} pour {$dossiers->count()} dossier(s) en alerte.");
        return self::SUCCESS;
    }
}
