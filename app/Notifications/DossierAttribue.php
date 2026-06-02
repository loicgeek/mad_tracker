<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DossierAttribue extends Notification
{
    public function __construct(
        public readonly int    $dossierId,
        public readonly string $reference,
        public readonly string $clientNom,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'dossier_id' => $this->dossierId,
            'reference'  => $this->reference,
            'client'     => $this->clientNom,
            'type'       => 'attribution',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Dossier {$this->reference} — Affaire attribuée")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Le dossier **{$this->reference}** ({$this->clientNom}) vous a été attribué.")
            ->line("Veuillez compléter les informations de mise à disposition fournisseur.")
            ->action('Voir le dossier', route('dossiers.show', $this->dossierId));
    }
}
