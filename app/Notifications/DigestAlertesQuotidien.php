<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DigestAlertesQuotidien extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<int, array{reference: string, clientNom: string, alertes: array<int, string>}> $dossiers
     */
    public function __construct(
        public readonly array $dossiers,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->dossiers);

        $mail = (new MailMessage)
            ->subject("Récapitulatif quotidien — {$count} dossier(s) en alerte")
            ->greeting("Bonjour,")
            ->line("Voici les dossiers avec au moins un champ non renseigné ou en retard :");

        foreach ($this->dossiers as $d) {
            $mail->line("**{$d['reference']}** ({$d['clientNom']}) — " . implode(', ', $d['alertes']));
        }

        return $mail->action('Voir les dossiers en alerte', route('dossiers.index', ['filterAlertes' => 'oui']));
    }
}
