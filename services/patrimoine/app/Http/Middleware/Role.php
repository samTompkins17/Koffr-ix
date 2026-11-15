<?php

namespace App\Http\Middleware;

use App\Support\Journal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle des rôles : `role:admin,comptable_matieres`.
 * 401 si le visiteur n'est pas authentifié, 403 si le rôle ne convient pas.
 */
class Role
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $utilisateur = $request->attributes->get('utilisateur');

        if (! $utilisateur) {
            return response()->json(['message' => 'Authentification requise.'], 401);
        }

        if (! in_array($utilisateur['role'], $roles, true)) {
            Journal::ecrire(
                $utilisateur['id'],
                'acces_refuse',
                $request->path(),
                'Rôle insuffisant : '.$utilisateur['role'],
            );

            return response()->json([
                'message' => 'Vous n’avez pas les droits pour cette action.',
            ], 403);
        }

        return $next($request);
    }
}
