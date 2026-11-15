<?php

namespace App\Http\Controllers;

class CarteController extends ControleurKoffr
{
    /**
     * Carte de localisation : uniquement les biens qui ont des coordonnées.
     * La carte est dessinée côté navigateur (Leaflet + fond OpenStreetMap).
     */
    public function index()
    {
        $reponse = $this->api()->get('/api/patrimoine/biens', ['per_page' => 100]);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        $biens = $reponse->successful() ? ($reponse->json('data') ?? []) : [];

        $points = collect($biens)
            ->filter(fn (array $bien) => $bien['latitude'] !== null && $bien['longitude'] !== null)
            ->map(fn (array $bien) => [
                'id' => $bien['id'],
                'code' => $bien['code_unique'],
                'designation' => $bien['designation'],
                'etat' => $bien['etat'],
                'localisation' => $bien['localisation'],
                'latitude' => (float) $bien['latitude'],
                'longitude' => (float) $bien['longitude'],
                'url' => route('biens.fiche', $bien['id']),
            ])
            ->values()
            ->all();

        return view('carte.index', [
            'points' => $points,
            'totalBiens' => count($biens),
        ]);
    }
}
