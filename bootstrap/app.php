<?php

use App\Console\Commands\VerifierEcheanceAbonnement;
use App\Http\Middleware\EnsureAbonnementActif;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'             => EnsureRole::class,
            'account.active'   => EnsureAccountActive::class,
            'abonnement.actif' => EnsureAbonnementActif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(VerifierEcheanceAbonnement::class)->dailyAt('06:00');
    })
    ->create();
