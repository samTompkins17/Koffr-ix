<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EcartController extends ControleurKoffr
{
    /**
     * Page « Écarts et rapports » : tous les écarts, en lecture seule.
     */
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'controleur'])) {
            return redirect()->route('tableau')->with('erreur', 'La liste des écarts est réservée au comptable et au contrôleur.');
        }

        $campagneFiltree = $request->query('campagne');

        $reponse = $this->api()->get('/api/inventaire/ecarts', array_filter([
            'campagne_id' => $campagneFiltree,
        ]));

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 403) {
            return redirect()->route('tableau')->with('erreur', 'La liste des écarts est réservée au comptable et au contrôleur.');
        }

        $ecarts = $reponse->successful() ? $reponse->json() : [];

        $campagnes = $this->api()->get('/api/inventaire/campagnes')->json() ?? [];
        $stats = $this->api()->get('/api/patrimoine/stats')->json() ?? [];

        $totalBiens = (int) ($stats['total'] ?? 0);
        $controles = collect($campagnes)->sum('controles_count');

        return view('ecarts.index', [
            'ecarts' => $ecarts,
            'campagnes' => $campagnes,
            'campagneFiltree' => $campagneFiltree,
            'biens' => $this->biensParIds(array_column($ecarts, 'bien_id')),
            'personnes' => $this->noms('/api/auth/users', array_column($ecarts, 'agent_id')),
            'totalBiens' => $totalBiens,
            'controles' => (int) $controles,
            'taux' => $totalBiens > 0 ? (int) round($controles / $totalBiens * 100) : 0,
        ]);
    }

    /**
     * Export CSV de la liste des écarts.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $reponse = $this->api()->get('/api/inventaire/ecarts', array_filter([
            'campagne_id' => $request->query('campagne'),
        ]));

        $ecarts = $reponse->successful() ? $reponse->json() : [];
        $biens = $this->biensParIds(array_column($ecarts, 'bien_id'));
        $personnes = $this->noms('/api/auth/users', array_column($ecarts, 'agent_id'));

        return response()->streamDownload(function () use ($ecarts, $biens, $personnes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, ['Code patrimoine', 'Désignation', 'État fiche', 'État constaté', 'Agent', 'Date'], ';');

            foreach ($ecarts as $ecart) {
                $bien = $biens[$ecart['bien_id']] ?? null;
                fputcsv($sortie, [
                    $bien['code_unique'] ?? $ecart['bien_id'],
                    $bien['designation'] ?? '',
                    config('koffr.etats')[$ecart['etat_fiche']]['libelle'] ?? $ecart['etat_fiche'],
                    config('koffr.etats')[$ecart['etat_constate']]['libelle'] ?? $ecart['etat_constate'],
                    $personnes[$ecart['agent_id']] ?? '',
                    substr((string) $ecart['date'], 0, 10),
                ], ';');
            }

            fclose($sortie);
        }, 'ecarts-koffr-ix-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

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

        return $reponse->successful()
            ? collect($reponse->json('data', []))->keyBy('id')->all()
            : [];
    }
}
