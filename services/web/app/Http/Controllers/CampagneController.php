<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CampagneController extends ControleurKoffr
{
    /**
     * Liste des campagnes avec progression et écarts, plus le formulaire
     * d'ouverture intégré à la page (comme sur la maquette).
     */
    public function index()
    {
        $reponse = $this->api()->get('/api/inventaire/campagnes');

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        $campagnes = $reponse->successful() ? $reponse->json() : [];
        $stats = $this->api()->get('/api/patrimoine/stats')->json() ?? [];
        $totalBiens = max(1, (int) ($stats['total'] ?? 0));

        return view('campagnes.index', [
            'campagnes' => $campagnes,
            'structures' => $this->noms('/api/auth/structures', array_column($campagnes, 'structure_id')),
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
            'totalBiens' => $totalBiens,
        ]);
    }

    public function enregistrer(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres'])) {
            return redirect()->route('campagnes.index')->with('erreur', 'Seul le comptable des matières peut ouvrir une campagne.');
        }

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'structure_id' => ['required', 'uuid'],
        ]);

        $reponse = $this->api()->post('/api/inventaire/campagnes', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 403) {
            return back()->withInput()->with('erreur', 'Seul le comptable des matières peut ouvrir une campagne.');
        }

        if ($reponse->status() === 422) {
            return back()->withInput()->withErrors($reponse->json('errors') ?? []);
        }

        if (! $reponse->successful()) {
            return back()->withInput()->with('erreur', $this->messageErreur($reponse));
        }

        return redirect()->route('campagnes.index')->with('succes', 'Campagne ouverte.');
    }

    public function fiche(string $id)
    {
        $reponse = $this->api()->get('/api/inventaire/campagnes/'.$id);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 404) {
            abort(404, 'Campagne introuvable.');
        }

        if (! $reponse->successful()) {
            return redirect()->route('campagnes.index')->with('erreur', $this->messageErreur($reponse));
        }

        $donnees = $reponse->json();
        $controles = $this->api()->get('/api/inventaire/campagnes/'.$id.'/controles')->json() ?? [];

        return view('campagnes.fiche', [
            'campagne' => $donnees['campagne'],
            'compteurs' => $donnees['compteurs'],
            'controles' => $controles,
            'structures' => $this->noms('/api/auth/structures', [$donnees['campagne']['structure_id']]),
            'biens' => $this->biensParIds(array_column($controles, 'bien_id')),
            'personnes' => $this->noms('/api/auth/users', array_column($controles, 'agent_id')),
        ]);
    }

    /**
     * Récupère les fiches de plusieurs biens en un seul appel groupé.
     */
    private function biensParIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        $reponse = $this->api()->get('/api/patrimoine/biens', [
            'ids' => implode(',', $ids),
            'per_page' => 100,
        ]);

        if (! $reponse->successful()) {
            return [];
        }

        return collect($reponse->json('data', []))->keyBy('id')->all();
    }
}
