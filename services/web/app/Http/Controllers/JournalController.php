<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Journal d'audit : consultation en lecture seule.
 * Les journaux restent par service (jamais fusionnés) : la page les
 * présente en trois sections, source par source.
 */
class JournalController extends ControleurKoffr
{
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['admin', 'controleur'])) {
            abort(403, 'Le journal d’audit est réservé à l’administrateur et au contrôleur.');
        }

        $filtre = (string) $request->query('action', '');

        $sources = [
            'auth' => '/api/auth/journal',
            'patrimoine' => '/api/patrimoine/journal',
            'inventaire' => '/api/inventaire/journal',
        ];

        $entrees = [];
        $auteurs = [];

        foreach ($sources as $service => $chemin) {
            $reponse = $this->api()->get($chemin, array_filter(['action' => $filtre]));

            if ($reponse->status() === 401) {
                return $this->sessionExpiree();
            }

            if (! $reponse->successful()) {
                $entrees[$service] = [];
                continue;
            }

            $entrees[$service] = collect($reponse->json('data') ?? [])
                ->map(function (array $entree) use ($service) {
                    $entree['service'] = $service;

                    return $entree;
                })
                ->all();

            $auteurs = array_merge($auteurs, array_column($entrees[$service], 'utilisateur_id'));
        }

        return view('journal.index', [
            'entrees' => $entrees,
            'auteurs' => $this->noms('/api/auth/users', $auteurs),
            'filtre' => $filtre,
        ]);
    }
}
