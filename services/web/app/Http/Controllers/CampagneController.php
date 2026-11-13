<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CampagneController extends ControleurKoffr
{
    public function index()
    {
        $reponse = $this->api()->get('/api/inventaire/campagnes');

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        $campagnes = $reponse->successful() ? $reponse->json() : [];

        return view('campagnes.index', [
            'campagnes' => $campagnes,
            'structures' => $this->noms('/api/auth/structures', array_column($campagnes, 'structure_id')),
        ]);
    }

    public function creer()
    {
        if (! $this->roleAutorise(['comptable_matieres'])) {
            return redirect()->route('campagnes.index')->with('erreur', 'Seul le comptable des matières peut ouvrir une campagne.');
        }

        return view('campagnes.creer', [
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
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

        return redirect()->route('campagnes.fiche', $reponse->json('id'))
            ->with('succes', 'Campagne ouverte.');
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
        $biens = $this->biensParIds(array_column($controles, 'bien_id'));

        return view('campagnes.fiche', [
            'campagne' => $donnees['campagne'],
            'compteurs' => $donnees['compteurs'],
            'controles' => $controles,
            'structures' => $this->noms('/api/auth/structures', [$donnees['campagne']['structure_id']]),
            'biens' => $biens,
            'personnes' => $this->noms('/api/auth/users', array_column($controles, 'agent_id')),
        ]);
    }

    public function ecarts(string $id)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'controleur'])) {
            return redirect()->route('campagnes.index')->with('erreur', 'La liste des écarts est réservée au comptable et au contrôleur.');
        }

        $reponse = $this->api()->get('/api/inventaire/campagnes/'.$id.'/ecarts');

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 403) {
            return redirect()->route('campagnes.index')
                ->with('erreur', 'La liste des écarts est réservée au comptable et au contrôleur.');
        }

        if (! $reponse->successful()) {
            return redirect()->route('campagnes.index')->with('erreur', $this->messageErreur($reponse));
        }

        $ecarts = $reponse->json();
        $biens = $this->biensParIds(array_column($ecarts, 'bien_id'));

        $campagne = $this->api()->get('/api/inventaire/campagnes/'.$id)->json('campagne');

        return view('campagnes.ecarts', [
            'campagne' => $campagne,
            'ecarts' => $ecarts,
            'biens' => $biens,
            'personnes' => $this->noms('/api/auth/users', array_column($ecarts, 'agent_id')),
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
