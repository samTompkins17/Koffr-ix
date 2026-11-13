<?php

namespace App\Http\Controllers;

class TableauDeBordController extends ControleurKoffr
{
    public function index()
    {
        $stats = $this->api()->get('/api/patrimoine/stats');
        $campagnes = $this->api()->get('/api/inventaire/campagnes');

        if ($stats->status() === 401) {
            return $this->sessionExpiree();
        }

        $derniersBiens = $this->api()->get('/api/patrimoine/biens', ['per_page' => 5]);

        $biens = $derniersBiens->successful() ? $derniersBiens->json('data', []) : [];

        return view('tableau', [
            'stats' => $stats->successful() ? $stats->json() : [],
            'campagnes' => $campagnes->successful() ? $campagnes->json() : [],
            'biens' => $biens,
            'structures' => $this->noms('/api/auth/structures', array_column($biens, 'structure_id')),
        ]);
    }
}
