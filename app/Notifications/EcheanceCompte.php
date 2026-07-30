<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EcheanceCompte extends Notification
{
    public function __construct(
        public readonly int    $userId,
        public readonly string $userNom,
        public readonly string $etat, // 'rappel' | 'expire'
        public readonly string $dateEcheance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id'          => $this->userId,
            'reference'        => "Compte {$this->userNom}",
            'type'             => 'echeance_compte',
            'etat'             => $this->etat,
            'date_echeance'    => $this->dateEcheance,
            'nouveau_statut_label' => $this->etat === 'expire' ? 'Compte désactivé' : 'Échéance proche',
            'action_suggeree'  => $this->etat === 'expire'
                ? 'Enregistrer un paiement pour réactiver le compte'
                : 'Penser à renouveler avant expiration',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->etat === 'expire') {
            return (new MailMessage)
                ->subject("⚠ Compte {$this->userNom} désactivé — échéance dépassée")
                ->greeting("Bonjour {$notifiable->prenom},")
                ->line("Le compte de **{$this->userNom}** a été désactivé automatiquement car son échéance ({$this->dateEcheance}) est dépassée.")
                ->line("Enregistrez un paiement pour réactiver ce compte.")
                ->action('Gérer les paiements', route('paiements.index'));
        }

        return (new MailMessage)
            ->subject("Rappel — échéance du compte {$this->userNom} proche")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Le compte de **{$this->userNom}** arrive à échéance le **{$this->dateEcheance}**.")
            ->line("Pensez à enregistrer un paiement avant cette date pour éviter la désactivation automatique.")
            ->action('Gérer les paiements', route('paiements.index'));
    }
}
