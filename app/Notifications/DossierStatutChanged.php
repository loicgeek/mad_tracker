<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DossierStatutChanged extends Notification
{
    public function __construct(
        public readonly int    $dossierId,
        public readonly string $reference,
        public readonly string $clientNom,
        public readonly string $ancienStatut,
        public readonly string $nouveauStatut,
        public readonly string $nouveauStatutLabel,
        public readonly string $actionSuggeree,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'dossier_id'           => $this->dossierId,
            'reference'            => $this->reference,
            'client'               => $this->clientNom,
            'ancien_statut'        => $this->ancienStatut,
            'nouveau_statut'       => $this->nouveauStatut,
            'nouveau_statut_label' => $this->nouveauStatutLabel,
            'action_suggeree'      => $this->actionSuggeree,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Dossier {$this->reference} — Statut mis à jour : {$this->nouveauStatutLabel}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Le dossier **{$this->reference}** ({$this->clientNom}) est passé au statut : **{$this->nouveauStatutLabel}**.")
            ->line("Action suggérée : {$this->actionSuggeree}")
            ->action('Voir le dossier', route('dossiers.show', $this->dossierId));
    }
}
