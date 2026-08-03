<?php

namespace App\Http\Middleware;

use App\Support\Abonnement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbonnementActif
{
    /**
     * Bloque toute l'application si l'abonnement de la plateforme (cette
     * instance) est expiré — sauf pour le super_admin, seul habilité à
     * enregistrer un paiement pour la réactiver.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin() && ! Abonnement::actif()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Abonnement de la plateforme expiré — contactez votre fournisseur.']);
        }

        return $next($request);
    }
}
