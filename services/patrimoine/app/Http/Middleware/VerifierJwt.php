<?php

namespace App\Http\Middleware;

use App\Support\Journal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Vérifie le JWT localement avec le secret partagé JWT_SECRET.
 * Aucun appel au service auth : chaque service est autonome.
 * Le contenu du jeton est placé dans les attributs de la requête.
 */
class VerifierJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $jeton = $request->bearerToken();

        if (! $jeton) {
            return response()->json(['message' => 'Authentification requise.'], 401);
        }

        try {
            $charge = JWTAuth::setToken($jeton)->getPayload();
        } catch (Throwable) {
            // Jeton expiré, signature invalide ou jeton malformé.
            return response()->json(['message' => 'Session expirée ou jeton invalide.'], 401);
        }

        $request->attributes->set('utilisateur', [
            'id' => $charge->get('sub'),
            'nom' => $charge->get('name'),
            'role' => $charge->get('role'),
            'structure_id' => $charge->get('structure_id'),
            'ministere_code' => $charge->get('ministere_code'),
        ]);

        return $next($request);
    }
}
