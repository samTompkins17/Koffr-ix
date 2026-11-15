<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BienController extends ControleurKoffr
{
    /**
     * Liste des biens : filtres et recherche, puis un seul appel groupé
     * pour résoudre les noms de structures.
     */
    public function index(Request $request)
    {
        $filtres = $request->only(['q', 'categorie', 'etat', 'structure_id', 'page']);

        // Deux présentations de la même liste, comme dans les maquettes.
        $vue = $request->query('vue') === 'galerie' ? 'galerie' : 'tableau';

        $reponse = $this->api()->get('/api/patrimoine/biens', $filtres);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        $biens = $reponse->successful()
            ? $reponse->json()
            : ['data' => [], 'total' => 0, 'last_page' => 1, 'current_page' => 1];

        return view('biens.index', [
            'biens' => $biens,
            'filtres' => $filtres,
            'vue' => $vue,
            'structures' => $this->noms('/api/auth/structures', array_column($biens['data'], 'structure_id')),
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
            'erreurApi' => $reponse->successful() ? null : 'La liste des biens est momentanément indisponible.',
        ]);
    }

    public function fiche(string $id)
    {
        $reponse = $this->api()->get('/api/patrimoine/biens/'.$id);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 404) {
            abort(404, 'Bien introuvable.');
        }

        abort_unless($reponse->successful(), 503, 'La fiche est momentanément indisponible.');
        $bien = $reponse->json();
        $mouvements = $this->api()->get('/api/patrimoine/biens/'.$id.'/mouvements')->json() ?? [];
        $reponseDocuments = $this->api()->get('/api/patrimoine/biens/'.$id.'/documents');
        if ($reponseDocuments->status() === 401) {
            return $this->sessionExpiree();
        }
        $documents = $reponseDocuments->successful() ? $reponseDocuments->json() : [];

        $structureIds = array_merge(
            [$bien['structure_id'] ?? null],
            array_column($mouvements, 'structure_origine_id'),
            array_column($mouvements, 'structure_destination_id'),
        );
        $personneIds = array_merge(
            [$bien['responsable_id'] ?? null],
            array_column($mouvements, 'auteur_id'),
            array_column($documents, 'auteur_id'),
        );

        return view('biens.fiche', [
            'bien' => $bien,
            'mouvements' => $mouvements,
            'documents' => $documents,
            'documentsIndisponibles' => ! $reponseDocuments->successful(),
            'structures' => $this->noms('/api/auth/structures', $structureIds),
            'personnes' => $this->noms('/api/auth/users', $personneIds),
        ]);
    }

    public function creer()
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres'])) {
            abort(403, 'Votre rôle ne permet pas de créer un bien.');
        }

        return view('biens.formulaire', [
            'bien' => null,
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
        ]);
    }

    public function enregistrer(Request $request)
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres'])) {
            abort(403, 'Votre rôle ne permet pas de créer un bien.');
        }

        $donnees = $this->valider($request);

        $reponse = $this->api()->post('/api/patrimoine/biens', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 422) {
            return back()->withInput()->withErrors($reponse->json('errors') ?? [])
                ->with('erreur', $this->messageErreur($reponse));
        }

        if ($reponse->status() === 403) {
            abort(403, 'Votre rôle ne permet pas de créer un bien.');
        }

        if (! $reponse->successful()) {
            return back()->withInput()->with('erreur', $this->messageErreur($reponse));
        }

        return redirect()->route('biens.fiche', $reponse->json('id'))
            ->with('succes', 'Bien enregistré. Code patrimoine : '.$reponse->json('code_unique').'.');
    }

    public function modifier(string $id)
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres'])) {
            abort(403, 'Votre rôle ne permet pas de modifier un bien.');
        }

        $reponse = $this->api()->get('/api/patrimoine/biens/'.$id);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if (! $reponse->successful()) {
            abort(404, 'Bien introuvable.');
        }

        return view('biens.formulaire', [
            'bien' => $reponse->json(),
            'toutesStructures' => $this->api()->get('/api/auth/structures')->json() ?? [],
        ]);
    }

    public function mettreAJour(Request $request, string $id)
    {
        if (! $this->roleAutorise(['admin', 'comptable_matieres'])) {
            abort(403, 'Votre rôle ne permet pas de modifier un bien.');
        }

        $donnees = $this->valider($request);
        $reponse = $this->api()->put('/api/patrimoine/biens/'.$id, $donnees);

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

        return redirect()->route('biens.fiche', $id)->with('succes', 'Fiche mise à jour. Le code patrimoine reste inchangé.');
    }

    public function etiquette(string $id)
    {
        $reponse = $this->api()->get('/api/patrimoine/biens/'.$id);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if (! $reponse->successful()) {
            abort(404, 'Bien introuvable.');
        }

        return view('biens.etiquette', ['bien' => $reponse->json()]);
    }

    /**
     * Le QR code est protégé par le JWT : le web le récupère puis le sert
     * au navigateur (une balise <img> ne peut pas envoyer d'en-tête Bearer).
     */
    public function qrCode(string $id)
    {
        $reponse = $this->api()->get('/api/patrimoine/biens/'.$id.'/qrcode');

        if (! $reponse->successful()) {
            abort(404, 'QR code indisponible.');
        }

        return response($reponse->body(), 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'string'],
            'valeur_acquisition' => ['required', 'integer', 'min:0'],
            'date_acquisition' => ['required', 'date'],
            'etat' => ['required', 'in:bon,usage,hors_service'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'structure_id' => ['required', 'uuid'],
        ]);
    }
}
