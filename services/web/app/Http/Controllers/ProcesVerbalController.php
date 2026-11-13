<?php

namespace App\Http\Controllers;

class ProcesVerbalController extends ControleurKoffr
{
    /**
     * Les PV sont générés à la demande à partir des données existantes :
     * rien n'est stocké, donc rien ne peut être modifié après coup.
     */
    public function index()
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres', 'responsable_service'])) {
            return redirect()->route('tableau')->with('erreur', 'La génération des procès-verbaux est réservée au comptable et au responsable.');
        }

        $campagnes = $this->api()->get('/api/inventaire/campagnes')->json() ?? [];
        $sortis = $this->api()->get('/api/patrimoine/biens', ['statut' => 'sorti', 'per_page' => 100])->json('data', []);

        return view('proces-verbaux.index', [
            'campagnes' => $campagnes,
            'sortis' => $sortis,
            'structures' => $this->noms('/api/auth/structures', array_merge(
                array_column($campagnes, 'structure_id'),
                array_column($sortis, 'structure_id'),
            )),
        ]);
    }

    /**
     * PV d'inventaire d'une campagne : constats, écarts, signatures.
     */
    public function inventaire(string $campagneId)
    {
        if ($refus = $this->refuser()) {
            return $refus;
        }

        $reponse = $this->api()->get('/api/inventaire/campagnes/'.$campagneId);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if (! $reponse->successful()) {
            abort(404, 'Campagne introuvable.');
        }

        $donnees = $reponse->json();
        $controles = $this->api()->get('/api/inventaire/campagnes/'.$campagneId.'/controles')->json() ?? [];

        return view('proces-verbaux.inventaire', [
            'campagne' => $donnees['campagne'],
            'compteurs' => $donnees['compteurs'],
            'controles' => $controles,
            'structures' => $this->noms('/api/auth/structures', [$donnees['campagne']['structure_id']]),
            'biens' => $this->biensParIds(array_column($controles, 'bien_id')),
            'personnes' => $this->noms('/api/auth/users', array_column($controles, 'agent_id')),
        ]);
    }

    /**
     * PV de sortie d'un bien : dernier mouvement de type sortie.
     */
    public function sortie(string $bienId)
    {
        if ($refus = $this->refuser()) {
            return $refus;
        }

        $reponse = $this->api()->get('/api/patrimoine/biens/'.$bienId);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if (! $reponse->successful()) {
            abort(404, 'Bien introuvable.');
        }

        $bien = $reponse->json();
        $mouvements = $this->api()->get('/api/patrimoine/biens/'.$bienId.'/mouvements')->json() ?? [];
        $sortie = collect($mouvements)->firstWhere('type', 'sortie');

        if (! $sortie) {
            return redirect()->route('proces-verbaux.index')
                ->with('erreur', 'Ce bien n’a pas de sortie enregistrée : aucun PV de sortie à générer.');
        }

        return view('proces-verbaux.sortie', [
            'bien' => $bien,
            'sortie' => $sortie,
            'structures' => $this->noms('/api/auth/structures', [
                $bien['structure_id'] ?? null,
                $sortie['structure_origine_id'] ?? null,
                $sortie['structure_destination_id'] ?? null,
            ]),
            'personnes' => $this->noms('/api/auth/users', [$sortie['auteur_id'] ?? null]),
        ]);
    }

    private function refuser()
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres', 'responsable_service'])) {
            return redirect()->route('tableau')->with('erreur', 'La génération des procès-verbaux est réservée au comptable et au responsable.');
        }

        return null;
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
