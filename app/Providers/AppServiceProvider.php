<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
         if (! config('mail.mailers.smtp.stream.ssl.verify_peer', true)) {
            Mail::resolved(function ($mailer) {
                $transport = $mailer->getSymfonyTransport();
                if ($transport instanceof EsmtpTransport) {
                    $transport->getStream()->setStreamOptions([
                        'ssl' => [
                            'verify_peer'      => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]);
                }
            });
        }
    }
}
