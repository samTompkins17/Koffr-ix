<?php

namespace App\Http\Controllers;

class VueNationaleController extends ControleurKoffr
{
    /**
     * Vue nationale : agrégation en lecture seule des compteurs par ministère.
     * Aucun service ne fait ce croisement (chaque base ignore les autres) :
     * le web combine la hiérarchie d'auth, les compteurs de patrimoine et
     * les campagnes d'inventaire.
     */
    public function index()
    {
        $hierarchie = $this->api()->get('/api/auth/hierarchie');

        if ($hierarchie->status() === 401) {
            return $this->sessionExpiree();
        }

        $structures = $hierarchie->successful() ? $hierarchie->json() : [];
        $stats = $this->api()->get('/api/patrimoine/stats')->json() ?? [];
        $campagnes = $this->api()->get('/api/inventaire/campagnes')->json() ?? [];

        $parId = collect($structures)->keyBy('id');
        $biensParStructure = $stats['par_structure'] ?? [];
        $valeurParStructure = $stats['valeur_par_structure'] ?? [];

        // Rattache une structure à son ministère en remontant les parents (3 niveaux max).
        $ministereDe = function (?string $structureId) use ($parId): ?string {
            $courant = $structureId ? $parId->get($structureId) : null;
            $profondeur = 0;

            while ($courant && $profondeur < 3) {
                if (($courant['type'] ?? null) === 'ministere') {
                    return $courant['id'];
                }
                $courant = $parId->get($courant['parent_id'] ?? null);
                $profondeur++;
            }

            return null;
        };

        $lignes = [];
        foreach ($parId as $id => $structure) {
            if (($structure['type'] ?? null) !== 'ministere') {
                continue;
            }
            $lignes[$id] = [
                'id' => $id,
                'nom' => $structure['nom'],
                'code' => $structure['code'],
                'biens' => 0,
                'valeur' => 0,
                'controles' => 0,
                'ecarts' => 0,
            ];
        }

        foreach ($parId as $id => $structure) {
            $ministere = $ministereDe($id);
            if (! $ministere || ! isset($lignes[$ministere])) {
                continue;
            }
            $lignes[$ministere]['biens'] += (int) ($biensParStructure[$id] ?? 0);
            $lignes[$ministere]['valeur'] += (int) ($valeurParStructure[$id] ?? 0);
        }

        foreach ($campagnes as $campagne) {
            $ministere = $ministereDe($campagne['structure_id'] ?? null);
            if (! $ministere || ! isset($lignes[$ministere])) {
                continue;
            }
            $lignes[$ministere]['controles'] += (int) ($campagne['controles_count'] ?? 0);
            $lignes[$ministere]['ecarts'] += (int) ($campagne['ecarts_count'] ?? 0);
        }

        foreach ($lignes as $id => $ligne) {
            $lignes[$id]['taux'] = $ligne['biens'] > 0
                ? (int) round($ligne['controles'] / $ligne['biens'] * 100)
                : 0;
        }

        $lignes = collect($lignes)->sortByDesc('biens')->values()->all();

        return view('vue-nationale.index', [
            'lignes' => $lignes,
            'totalBiens' => (int) ($stats['total'] ?? 0),
            'valeurTotale' => (int) ($stats['valeur_totale'] ?? 0),
            'totalControles' => collect($campagnes)->sum('controles_count'),
            'totalEcarts' => collect($campagnes)->sum('ecarts_count'),
        ]);
    }
}
