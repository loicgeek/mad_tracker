<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Rappel/alerte sur l'échéance de l'ABONNEMENT DE LA PLATEFORME (cette
 * instance), pas d'un compte utilisateur individuel — envoyée uniquement
 * aux super_admin (les seuls à gérer la facturation).
 */
class EcheanceAbonnement extends Notification
{
    public function __construct(
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
            'reference'            => 'Abonnement plateforme',
            'type'                 => 'echeance_abonnement',
            'etat'                 => $this->etat,
            'date_echeance'        => $this->dateEcheance,
            'nouveau_statut_label' => $this->etat === 'expire' ? 'Abonnement expiré — plateforme désactivée' : 'Échéance proche',
            'action_suggeree'      => $this->etat === 'expire'
                ? 'Enregistrer un paiement pour réactiver la plateforme'
                : 'Penser à renouveler avant expiration',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->etat === 'expire') {
            return (new MailMessage)
                ->subject('⚠ Abonnement plateforme expiré — application désactivée')
                ->greeting("Bonjour {$notifiable->prenom},")
                ->line("L'abonnement de la plateforme a été désactivé automatiquement car son échéance ({$this->dateEcheance}) est dépassée.")
                ->line('Tous les utilisateurs (sauf super administrateur) sont bloqués jusqu\'à réactivation.')
                ->action('Gérer les paiements', route('paiements.index'));
        }

        return (new MailMessage)
            ->subject('Rappel — échéance de l\'abonnement plateforme proche')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("L'abonnement de la plateforme arrive à échéance le **{$this->dateEcheance}**.")
            ->line('Enregistrez un paiement avant cette date pour éviter la désactivation automatique de toute l\'application.')
            ->action('Gérer les paiements', route('paiements.index'));
    }
}
