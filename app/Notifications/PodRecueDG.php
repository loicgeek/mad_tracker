<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PodRecueDG extends Notification
{
    public function __construct(
        public readonly int    $dossierId,
        public readonly string $reference,
        public readonly string $clientNom,
        public readonly string $datePod,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Dossier {$this->reference} — POD reçue, affaire clôturée")
            ->greeting("Bonjour,")
            ->line("La preuve de livraison (POD) du dossier **{$this->reference}** ({$this->clientNom}) a été reçue le **{$this->datePod}**.")
            ->line("L'affaire est désormais clôturée.")
            ->action('Voir le dossier', route('dossiers.show', $this->dossierId));
    }
}
