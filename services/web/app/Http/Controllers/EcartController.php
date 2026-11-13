<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EcartController extends ControleurKoffr
{
    /**
     * Page « Écarts et rapports » : écarts de toutes les campagnes, avec leur
     * traitement éventuel. Le comptable peut documenter une décision.
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
            'personnes' => $this->noms('/api/auth/users', array_merge(
                array_column($ecarts, 'agent_id'),
                array_column(array_filter(array_column($ecarts, 'traitement')), 'auteur_id'),
            )),
            'totalBiens' => $totalBiens,
            'controles' => (int) $controles,
            'taux' => $totalBiens > 0 ? (int) round($controles / $totalBiens * 100) : 0,
            'actions' => ['regularise' => 'Écart régularisé', 'reforme' => 'Bien réformé', 'main_levee' => 'Main levée', 'autre' => 'Autre décision'],
        ]);
    }

    /**
     * Traitement d'un écart par le comptable : décision documentée, en ajout seul.
     */
    public function traiter(Request $request, string $controleId)
    {
        if (! $this->roleAutorise(['comptable_matieres'])) {
            return redirect()->route('ecarts.index')
                ->with('erreur', 'Seul le comptable des matières peut traiter un écart.');
        }

        $donnees = $request->validate([
            'action' => ['required', 'in:regularise,reforme,main_levee,autre'],
            'commentaire' => ['required', 'string', 'max:1000'],
        ], [
            'commentaire.required' => 'Décrivez la décision prise pour cet écart.',
        ]);

        $reponse = $this->api()->post('/api/inventaire/ecarts/'.$controleId.'/traitement', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 409) {
            return back()->with('erreur', 'Cet écart a déjà été traité.');
        }

        if ($reponse->status() === 422) {
            return back()->withInput()->withErrors($reponse->json('errors') ?? [])
                ->with('erreur', $this->messageErreur($reponse));
        }

        if (! $reponse->successful()) {
            return back()->with('erreur', $this->messageErreur($reponse));
        }

        return back()->with('succes', 'Écart traité et documenté.');
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
            fputcsv($sortie, ['Code patrimoine', 'Désignation', 'État fiche', 'État constaté', 'Agent', 'Date', 'Traitement'], ';');

            foreach ($ecarts as $ecart) {
                $bien = $biens[$ecart['bien_id']] ?? null;
                fputcsv($sortie, [
                    $bien['code_unique'] ?? $ecart['bien_id'],
                    $bien['designation'] ?? '',
                    config('koffr.etats')[$ecart['etat_fiche']]['libelle'] ?? $ecart['etat_fiche'],
                    config('koffr.etats')[$ecart['etat_constate']]['libelle'] ?? $ecart['etat_constate'],
                    $personnes[$ecart['agent_id']] ?? '',
                    substr((string) $ecart['date'], 0, 10),
                    $ecart['traitement']['action'] ?? 'Non traité',
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
