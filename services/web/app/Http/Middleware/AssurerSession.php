<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sans jeton en session : retour à la connexion.
 * Jeton expiré : écran « session expirée » (le JWT ne dure que 15 minutes).
 */
class AssurerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('jwt')) {
            return redirect()->route('connexion')->with('erreur', 'Veuillez vous connecter pour continuer.');
        }

        if (session('expire_le') && now()->greaterThan(session('expire_le'))) {
            session()->forget(['jwt', 'utilisateur', 'expire_le']);

            return redirect()->route('session.expiree');
        }

        return $next($request);
    }
}
