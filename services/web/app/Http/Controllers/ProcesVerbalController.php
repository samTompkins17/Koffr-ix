<?php

namespace App\Http\Controllers;

use App\Support\ExportFichier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class ProcesVerbalController extends ControleurKoffr
{
    /** Les PV sont générés à la demande, sans écriture dans les services. */
    public function index()
    {
        $this->refuser();
        $campagnes = $this->lireJson('/api/inventaire/campagnes', liste: true);
        $sortis = $this->tousLesBiens(['statut' => 'sorti']);

        return view('proces-verbaux.index', [
            'campagnes' => $campagnes,
            'sortis' => $sortis,
            'structures' => $this->nomsComplets('/api/auth/structures', array_merge(
                array_column($campagnes, 'structure_id'),
                array_column($sortis, 'structure_id'),
            )),
        ]);
    }

    /** PV d'inventaire d'une campagne : constats, écarts, signatures. */
    public function inventaire(Request $request, string $campagneId)
    {
        $this->refuser();
        $donnees = $this->lireJson('/api/inventaire/campagnes/'.$campagneId, introuvable: true);
        abort_unless(isset($donnees['campagne']['id'], $donnees['compteurs']['controles']), 503, 'Campagne indisponible.');
        $controles = $this->lireJson('/api/inventaire/campagnes/'.$campagneId.'/controles', liste: true);
        abort_if(count($controles) !== (int) $donnees['compteurs']['controles'], 503, 'Les constats de la campagne sont incomplets.');

        $vue = [
            'campagne' => $donnees['campagne'],
            'compteurs' => $donnees['compteurs'],
            'controles' => $controles,
            'structures' => $this->nomsComplets('/api/auth/structures', [$donnees['campagne']['structure_id']]),
            'biens' => $this->biensParIds(array_column($controles, 'bien_id')),
            'personnes' => $this->nomsComplets('/api/auth/users', array_column($controles, 'agent_id')),
        ];

        return $request->query('format') === 'pdf'
            ? ExportFichier::pdf('proces-verbaux.inventaire-pdf', $vue, 'pv-inventaire-'.$campagneId.'.pdf')
            : view('proces-verbaux.inventaire', $vue);
    }

    /** PV de sortie d'un bien : dernier mouvement de type sortie. */
    public function sortie(Request $request, string $bienId)
    {
        $this->refuser();
        $bien = $this->lireJson('/api/patrimoine/biens/'.$bienId, introuvable: true);
        abort_unless(isset($bien['id'], $bien['code_unique']), 503, 'Bien indisponible.');
        $mouvements = $this->lireJson('/api/patrimoine/biens/'.$bienId.'/mouvements', liste: true);
        $sortie = collect($mouvements)->firstWhere('type', 'sortie');

        if (! $sortie) {
            return redirect()->route('proces-verbaux.index')
                ->with('erreur', 'Ce bien n’a pas de sortie enregistrée : aucun PV de sortie à générer.');
        }

        $vue = [
            'bien' => $bien,
            'sortie' => $sortie,
            'structures' => $this->nomsComplets('/api/auth/structures', [
                $bien['structure_id'] ?? null,
                $sortie['structure_origine_id'] ?? null,
                $sortie['structure_destination_id'] ?? null,
            ]),
            'personnes' => $this->nomsComplets('/api/auth/users', [$sortie['auteur_id'] ?? null]),
        ];

        return $request->query('format') === 'pdf'
            ? ExportFichier::pdf('proces-verbaux.sortie-pdf', $vue, 'pv-sortie-'.$bienId.'.pdf')
            : view('proces-verbaux.sortie', $vue);
    }

    private function refuser(): void
    {
        abort_unless($this->roleAutorise(['admin', 'comptable_matieres', 'responsable_service']), 403);
    }

    private function biensParIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $biens = [];

        // Un lot ne dépasse jamais le plafond de pagination du service patrimoine.
        foreach (array_chunk($ids, 100) as $lot) {
            foreach ($this->tousLesBiens(['ids' => implode(',', $lot)]) as $bien) {
                $biens[$bien['id']] = $bien;
            }
        }
        abort_if(array_diff($ids, array_keys($biens)) !== [], 503, 'Les fiches des biens sont incomplètes.');

        return $biens;
    }

    private function tousLesBiens(array $filtres): array
    {
        $biens = [];
        $page = 1;
        $dernierePage = null;
        $total = null;

        do {
            $donnees = $this->lireJson('/api/patrimoine/biens', array_merge($filtres, ['page' => $page, 'per_page' => 100]));
            abort_unless(isset($donnees['data'], $donnees['last_page'])
                && is_array($donnees['data']) && array_is_list($donnees['data'])
                && is_int($donnees['last_page']) && $donnees['last_page'] >= $page,
                503, 'La liste des biens est incomplète.');
            $dernierePage ??= $donnees['last_page'];
            $total ??= $donnees['total'] ?? null;
            abort_if($dernierePage !== $donnees['last_page']
                || (isset($donnees['current_page']) && $donnees['current_page'] !== $page)
                || ($donnees['data'] === [] && ($page > 1 || $dernierePage > 1)),
                503, 'La liste des biens est incomplète.');
            foreach ($donnees['data'] as $bien) {
                abort_unless(is_array($bien) && isset($bien['id']), 503, 'Réponse patrimoine invalide.');
                abort_if(isset($biens[$bien['id']]), 503, 'La pagination des biens est incohérente.');
                $biens[$bien['id']] = $bien;
            }
            $page++;
        } while ($page <= $dernierePage);

        abort_if($total !== null && count($biens) !== (int) $total, 503, 'La liste des biens est incomplète.');

        return array_values($biens);
    }

    private function nomsComplets(string $chemin, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $noms = [];
        foreach ($this->lireJson($chemin, ['ids' => implode(',', $ids)], true) as $element) {
            abort_unless(isset($element['id']) && (isset($element['nom']) || isset($element['name'])), 503, 'Annuaire indisponible.');
            $noms[$element['id']] = $element['nom'] ?? $element['name'];
        }
        abort_if(array_diff($ids, array_keys($noms)) !== [], 503, 'Les noms demandés sont incomplets.');

        return $noms;
    }

    private function lireJson(string $chemin, array $parametres = [], bool $liste = false, bool $introuvable = false): array
    {
        try {
            $reponse = $this->api()->get($chemin, $parametres);
        } catch (ConnectionException $exception) {
            abort(503, 'Un service est momentanément indisponible. Aucun PV n’a été généré.');
        }

        if ($reponse->status() === 401) {
            throw new HttpResponseException($this->sessionExpiree());
        }
        abort_if($reponse->status() === 403, 403);
        abort_if($introuvable && $reponse->status() === 404, 404);
        abort_unless($reponse->successful(), 503, 'Un service est momentanément indisponible. Aucun PV n’a été généré.');
        $donnees = $reponse->json();
        abort_unless(is_array($donnees), 503, 'Réponse de service invalide.');
        if ($liste) {
            abort_unless(str_starts_with(ltrim($reponse->body()), '[') && array_is_list($donnees), 503, 'Réponse de service invalide.');
            foreach ($donnees as $element) {
                abort_unless(is_array($element), 503, 'Réponse de service invalide.');
            }
        }

        return $donnees;
    }
}
