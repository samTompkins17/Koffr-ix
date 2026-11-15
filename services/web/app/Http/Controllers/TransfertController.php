<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransfertController extends ControleurKoffr
{
    /**
     * Page « Affectations et transferts » : déclaration, et validation des
     * mouvements en attente quand la session est celle d'un responsable.
     */
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'responsable_service'])) {
            abort(403, 'Votre rôle ne permet pas de déclarer un mouvement.');
        }

        $biens = $this->api()->get('/api/patrimoine/biens', ['per_page' => 100])->json('data', []);
        $bienId = $request->query('bien');

        // Mouvements déjà déclarés, en attente de décision du responsable.
        $enAttente = $this->api()->get('/api/patrimoine/mouvements/en-attente')->json() ?? [];

        return view('transferts.index', [
            'enAttente' => $enAttente,
            'biens' => $biens,
            'bienSelectionne' => collect($biens)->firstWhere('id', $bienId),
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
            'structures' => $this->noms('/api/auth/structures', array_merge(
                array_column($biens, 'structure_id'),
                array_column($enAttente, 'structure_origine_id'),
                array_column($enAttente, 'structure_destination_id'),
            )),
            'personnes' => $this->noms('/api/auth/users', array_column($enAttente, 'auteur_id')),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'responsable_service'])) {
            abort(403, 'Votre rôle ne permet pas de déclarer un mouvement.');
        }

        $donnees = $request->validate([
            'bien_id' => ['required', 'uuid'],
            'type' => ['required', 'in:affectation,transfert,maintenance,sortie'],
            'structure_destination_id' => ['required_if:type,affectation,transfert', 'nullable', 'uuid'],
            'motif' => ['required_if:type,affectation,transfert,sortie', 'nullable', 'string', 'max:500'],
        ], [
            'structure_destination_id.required_if' => 'La structure de destination est obligatoire pour une affectation ou un transfert.',
            'motif.required_if' => 'Le motif est obligatoire pour ce type de mouvement.',
        ]);

        $bienId = $donnees['bien_id'];
        unset($donnees['bien_id']);

        $reponse = $this->api()->post('/api/patrimoine/biens/'.$bienId.'/mouvements', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 422) {
            return back()->withInput()->withErrors($reponse->json('errors') ?? [])
                ->with('erreur', $this->messageErreur($reponse));
        }

        if (! $reponse->successful()) {
            return back()->withInput()->with('erreur', $this->messageErreur($reponse));
        }

        return redirect()->route('biens.fiche', $bienId)
            ->with('succes', in_array($donnees['type'], ['affectation', 'transfert'], true)
                ? 'Mouvement déclaré : il attend la validation du responsable de service.'
                : 'Mouvement enregistré dans l’historique du bien.');
    }

    public function valider(Request $request, string $mouvementId)
    {
        return $this->decider($request, $mouvementId, 'valider');
    }

    public function refuser(Request $request, string $mouvementId)
    {
        return $this->decider($request, $mouvementId, 'refuser');
    }

    /**
     * Décision du responsable : le mouvement reste inchangé, une décision est
     * ajoutée et la structure du bien n'évolue qu'en cas de validation.
     */
    private function decider(Request $request, string $mouvementId, string $action)
    {
        if (! $this->roleAutorise(['responsable_service'])) {
            abort(403, 'Seul le responsable de service valide un transfert.');
        }

        $donnees = $request->validate(['motif' => ['nullable', 'string', 'max:500']]);

        $reponse = $this->api()->post('/api/patrimoine/mouvements/'.$mouvementId.'/'.$action, $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 409) {
            return back()->with('erreur', 'Ce mouvement a déjà été traité.');
        }

        if (! $reponse->successful()) {
            return back()->with('erreur', $this->messageErreur($reponse));
        }

        return back()->with('succes', $action === 'valider'
            ? 'Transfert validé : la structure du bien est mise à jour.'
            : 'Transfert refusé : le bien reste dans sa structure.');
    }
}
