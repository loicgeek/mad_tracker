<?php

namespace App\Console\Commands;

use App\Models\Dossier;
use App\Models\Setting;
use App\Notifications\DigestAlertesQuotidien;
use App\Support\SafeNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Envoie un récapitulatif quotidien des dossiers en alerte (champs non
 * renseignés, retards) aux destinataires configurés dans les Paramètres.
 */
class EnvoyerDigestAlertesQuotidien extends Command
{
    protected $signature = 'alertes:digest-quotidien';

    protected $description = "Envoie un récapitulatif quotidien des dossiers en alerte aux destinataires configurés.";

    public function handle(): int
    {
        $dossiers = Dossier::withAlertes()->with(['client'])->orderBy('reference_affaire')->get();

        if ($dossiers->isEmpty()) {
            $this->info('Aucun dossier en alerte, rien à envoyer.');
            return self::SUCCESS;
        }

        $recipients = array_filter([
            Setting::get('dg_email'),
            Setting::get('email_secondaire_alertes'),
        ]);

        if (empty($recipients)) {
            $this->info('Aucun destinataire configuré pour le digest des alertes.');
            return self::SUCCESS;
        }

        $notification = new DigestAlertesQuotidien(
            $dossiers->map(fn (Dossier $d) => [
                'reference' => $d->reference_affaire,
                'clientNom' => $d->client?->nom ?? '—',
                'alertes'   => $d->alert_labels,
            ])->all()
        );

        foreach ($recipients as $email) {
            SafeNotifier::send(Notification::route('mail', $email), $notification);
        }

        $this->info("Digest envoyé à " . count($recipients) . " destinataire(s) pour {$dossiers->count()} dossier(s) en alerte.");
        return self::SUCCESS;
    }
}
