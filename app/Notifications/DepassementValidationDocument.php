<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DepassementValidationDocument extends Notification
{
    public function __construct(
        public readonly int    $dossierId,
        public readonly string $reference,
        public readonly string $clientNom,
        public readonly string $dateDemande,
        public readonly int    $delaiJours,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'dossier_id'   => $this->dossierId,
            'reference'    => $this->reference,
            'client'       => $this->clientNom,
            'type'         => 'depassement_validation',
            'date_demande' => $this->dateDemande,
            'delai_jours'  => $this->delaiJours,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠ Dossier {$this->reference} — Délai de validation dépassé")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Le délai de validation des documents du dossier **{$this->reference}** ({$this->clientNom}) est dépassé.")
            ->line("Demande envoyée le **{$this->dateDemande}** — délai prévu : **{$this->delaiJours} jours**.")
            ->line("Veuillez relancer le fournisseur.")
            ->action('Voir le dossier', route('dossiers.show', $this->dossierId));
    }
}
