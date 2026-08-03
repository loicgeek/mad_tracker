<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SafeNotifier
{
    /**
     * Envoie une notification sans jamais laisser un échec (mail mal configuré,
     * SMTP injoignable...) faire échouer l'opération en cours (création de
     * dossier, import, etc.). L'échec est loggé au lieu d'être propagé.
     */
    public static function send(mixed $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $e) {
            Log::error('Échec d\'envoi de notification : ' . $e->getMessage(), [
                'notification' => get_class($notification),
                'notifiable'   => is_object($notifiable) && method_exists($notifiable, 'getKey')
                    ? get_class($notifiable) . '#' . $notifiable->getKey()
                    : (is_object($notifiable) ? get_class($notifiable) : (string) $notifiable),
            ]);
        }
    }
}
