<?php

namespace App\Http\Controllers;

use App\Support\ExportFichier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class ExportController extends ControleurKoffr
{
    public function biens(Request $request)
    {

        // La page affichée et per_page ne doivent jamais tronquer un export.
        $biens = $this->tousLesBiens($request->only(['q', 'categorie', 'etat', 'structure_id']));
        $structures = $this->nomsComplets('/api/auth/structures', array_column($biens, 'structure_id'));
        $colonnes = ['Code patrimoine', 'Désignation', 'Catégorie', 'Structure', 'État', 'Statut', 'Valeur (FCFA)', 'Date d’acquisition'];
        $lignes = [];

        foreach ($biens as $bien) {
            $lignes[] = [
                $bien['code_unique'],
                $bien['designation'],
                $bien['categorie'],
                $structures[$bien['structure_id']] ?? '',
                config('koffr.etats')[$bien['etat']]['libelle'] ?? $bien['etat'],
                $bien['statut'] === 'en_service' ? 'En service' : 'Sorti',
                (int) $bien['valeur_acquisition'],
                substr((string) $bien['date_acquisition'], 0, 10),
            ];
        }

        return $this->fichier('biens', 'Liste des biens', $colonnes, $lignes);
    }

    public function ecarts(Request $request)
    {
        abort_unless($this->roleAutorise(['comptable_matieres', 'controleur']), 403);

        $ecarts = $this->lireJson('/api/inventaire/ecarts', array_filter([
            'campagne_id' => $request->query('campagne'),
        ]), true);
        $biens = [];
        $ids = array_values(array_unique(array_column($ecarts, 'bien_id')));

        // L'API patrimoine plafonne chaque page à 100 biens, même pour le filtre ids.
        foreach (array_chunk($ids, 100) as $lot) {
            foreach ($this->tousLesBiens(['ids' => implode(',', $lot)]) as $bien) {
                $biens[$bien['id']] = $bien;
            }
        }
        abort_if(array_diff($ids, array_keys($biens)) !== [], 503, 'Les fiches des biens sont incomplètes.');

        $personnes = $this->nomsComplets('/api/auth/users', array_column($ecarts, 'agent_id'));
        $colonnes = ['Code patrimoine', 'Désignation', 'État fiche', 'État constaté', 'Agent', 'Date', 'Traitement'];
        $lignes = [];

        foreach ($ecarts as $ecart) {
            $bien = $biens[$ecart['bien_id']];
            $lignes[] = [
                $bien['code_unique'],
                $bien['designation'],
                config('koffr.etats')[$ecart['etat_fiche']]['libelle'] ?? $ecart['etat_fiche'],
                config('koffr.etats')[$ecart['etat_constate']]['libelle'] ?? $ecart['etat_constate'],
                $personnes[$ecart['agent_id']] ?? '',
                substr((string) $ecart['date'], 0, 10),
                $ecart['traitement']['action'] ?? 'Non traité',
            ];
        }

        return $this->fichier('ecarts', 'Liste des écarts', $colonnes, $lignes);
    }

    private function fichier(string $prefixe, string $titre, array $colonnes, array $lignes)
    {
        $nom = $prefixe.'-koffr-ix-'.now()->format('Y-m-d').'.pdf';

        return ExportFichier::pdf('exports.tableau', compact('titre', 'colonnes', 'lignes'), $nom, 'landscape');
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

    private function lireJson(string $chemin, array $parametres = [], bool $liste = false): array
    {
        try {
            $reponse = $this->api()->get($chemin, $parametres);
        } catch (ConnectionException $exception) {
            abort(503, 'Un service est momentanément indisponible. Aucun export n’a été généré.');
        }

        // Même politique sur tous les appels, y compris les pages suivantes et l'annuaire.
        if ($reponse->status() === 401) {
            throw new HttpResponseException($this->sessionExpiree());
        }
        abort_if($reponse->status() === 403, 403);
        abort_unless($reponse->successful(), 503, 'Un service est momentanément indisponible. Aucun export n’a été généré.');
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
