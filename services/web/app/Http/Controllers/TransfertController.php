<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransfertController extends ControleurKoffr
{
    /**
     * Page « Affectations et transferts » : on choisit le bien concerné,
     * puis le type de mouvement, la destination et le motif.
     */
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'responsable_service'])) {
            return redirect()->route('biens.index')->with('erreur', 'Votre rôle ne permet pas de déclarer un mouvement.');
        }

        $biens = $this->api()->get('/api/patrimoine/biens', ['per_page' => 100])->json('data', []);
        $bienId = $request->query('bien');

        return view('transferts.index', [
            'biens' => $biens,
            'bienSelectionne' => collect($biens)->firstWhere('id', $bienId),
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
            'structures' => $this->noms('/api/auth/structures', array_column($biens, 'structure_id')),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->roleAutorise(['comptable_matieres', 'responsable_service'])) {
            return redirect()->route('biens.index')->with('erreur', 'Votre rôle ne permet pas de déclarer un mouvement.');
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
            ->with('succes', 'Mouvement enregistré dans l’historique du bien.');
    }
}
