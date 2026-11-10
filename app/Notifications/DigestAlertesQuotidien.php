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
     * @param array<int, array{reference: string, clientNom: string, url?: string, alertes: array<int, string>}> $dossiers
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
            ->subject("Alertes du jour — {$count} dossier(s) à traiter")
            ->greeting('Bonjour,')
            ->line("{$count} dossier(s) ont un délai dépassé. Cliquez sur une référence pour ouvrir le dossier à l'étape concernée :");

        foreach ($this->dossiers as $d) {
            $ref = isset($d['url']) ? "[{$d['reference']}]({$d['url']})" : "**{$d['reference']}**";
            $mail->line("{$ref} ({$d['clientNom']}) : " . implode(', ', $d['alertes']));
        }

        return $mail->action('Voir tous les dossiers en alerte', route('dossiers.index', ['filterAlertes' => 'oui']));
    }
}
