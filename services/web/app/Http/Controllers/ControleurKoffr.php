<?php

namespace App\Http\Controllers;

use App\Support\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\Response;

/**
 * Helpers communs : client API avec le jeton de session et gestion
 * de l'expiration (écran « session expirée »).
 */
abstract class ControleurKoffr extends Controller
{
    protected function api(): ApiClient
    {
        return ApiClient::session();
    }

    /**
     * Contrôle de rôle côté interface. L'API refait le contrôle : c'est elle
     * qui fait foi, le web ne fait qu'éviter d'afficher une action interdite.
     */
    protected function roleAutorise(array $roles): bool
    {
        return in_array(session('utilisateur.role'), $roles, true);
    }

    protected function sessionExpiree(): RedirectResponse
    {
        session()->forget(['jwt', 'utilisateur', 'expire_le']);

        return redirect()->route('session.expiree');
    }

    /**
     * Résout des noms en un seul appel groupé (jamais un appel par ligne).
     * Retourne un tableau id => nom.
     */
    protected function noms(string $chemin, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        $reponse = $this->api()->get($chemin, ['ids' => implode(',', $ids)]);

        if (! $reponse->successful()) {
            return [];
        }

        return collect($reponse->json())
            ->mapWithKeys(fn (array $element) => [$element['id'] => $element['nom'] ?? $element['name'] ?? '—'])
            ->all();
    }

    /**
     * Message d'erreur lisible renvoyé par l'API.
     */
    protected function messageErreur(Response $reponse): string
    {
        return $reponse->json('message') ?? 'Une erreur est survenue. Veuillez réessayer.';
    }
}
