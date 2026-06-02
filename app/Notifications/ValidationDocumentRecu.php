<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ValidationDocumentRecu extends Notification
{
    public function __construct(
        public readonly int    $dossierId,
        public readonly string $reference,
        public readonly string $clientNom,
        public readonly string $dateReception,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'dossier_id'     => $this->dossierId,
            'reference'      => $this->reference,
            'client'         => $this->clientNom,
            'type'           => 'validation_document_recu',
            'date_reception' => $this->dateReception,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Dossier {$this->reference} — Validation documents reçue")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("La validation des documents du dossier **{$this->reference}** ({$this->clientNom}) a été reçue le **{$this->dateReception}**.")
            ->action('Voir le dossier', route('dossiers.show', $this->dossierId));
    }
}
