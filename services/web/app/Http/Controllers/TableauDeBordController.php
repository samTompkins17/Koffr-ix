<?php

namespace App\Http\Controllers;

class TableauDeBordController extends ControleurKoffr
{
    public function index()
    {
        $stats = $this->api()->get('/api/patrimoine/stats');

        if ($stats->status() === 401) {
            return $this->sessionExpiree();
        }

        $campagnes = $this->api()->get('/api/inventaire/campagnes');
        $mouvements = $this->api()->get('/api/patrimoine/mouvements/recents', ['limite' => 5]);

        $campagnesListe = $campagnes->successful() ? $campagnes->json() : [];
        $stats = $stats->successful() ? $stats->json() : [];
        $mouvementsListe = $mouvements->successful() ? $mouvements->json() : [];

        $totalBiens = (int) ($stats['total'] ?? 0);
        $controles = collect($campagnesListe)->sum('controles_count');
        $ecarts = collect($campagnesListe)->sum('ecarts_count');

        // Taux d'inventaire : part des biens déjà contrôlés dans les campagnes.
        $taux = $totalBiens > 0 ? (int) round($controles / $totalBiens * 100) : 0;

        $parCategorie = collect($stats['par_categorie'] ?? []);
        $maximumCategorie = max(1, (int) $parCategorie->max());

        return view('tableau', [
            'totalBiens' => $totalBiens,
            'valeurTotale' => (int) ($stats['valeur_totale'] ?? 0),
            'tauxInventaire' => min($taux, 100),
            'ecartsOuverts' => (int) $ecarts,
            'campagnes' => $campagnesListe,
            'categories' => $parCategorie,
            'maximumCategorie' => $maximumCategorie,
            'mouvements' => $mouvementsListe,
            'personnes' => $this->noms('/api/auth/users', array_column($mouvementsListe, 'auteur_id')),
        ]);
    }
}
