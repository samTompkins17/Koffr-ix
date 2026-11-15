<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends ControleurKoffr
{
    private const ONGLETS = ['utilisateurs', 'roles', 'structures'];

    /**
     * Page « Utilisateurs, rôles et structures » : annuaire et création.
     * Réservée à l'administrateur ; l'API refait le contrôle de rôle.
     */
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['admin'])) {
            abort(403, 'Cette page est réservée à l’administrateur.');
        }

        $reponse = $this->api()->get('/api/auth/annuaire');

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 403) {
            abort(403, 'Cette page est réservée à l’administrateur.');
        }

        if (! $reponse->successful()) {
            return redirect()->route('tableau')->with('erreur', $this->messageErreur($reponse));
        }

        $comptes = $reponse->json('comptes') ?? [];
        $structures = $reponse->json('structures') ?? [];

        // Nombre de biens par structure : un seul appel groupé au service patrimoine.
        $stats = $this->api()->get('/api/patrimoine/stats');
        $parStructure = $stats->successful() ? ($stats->json('par_structure') ?? []) : [];

        // Responsable affiché : le compte « responsable de service » rattaché.
        $responsables = collect($comptes)
            ->where('role', 'responsable_service')
            ->groupBy('structure_id')
            ->map(fn ($groupe) => $groupe->first()['name'])
            ->all();

        // Un onglet inconnu retombe sur « Utilisateurs ».
        $onglet = in_array($request->query('onglet'), self::ONGLETS, true)
            ? $request->query('onglet')
            : 'utilisateurs';

        return view('admin.index', [
            'onglet' => $onglet,
            'comptes' => $comptes,
            'structures' => $structures,
            'roles' => config('koffr.roles'),
            'parStructure' => $parStructure,
            'responsables' => $responsables,
            'matrice' => $this->matrice(),
        ]);
    }

    /**
     * Création d'un compte. Le mot de passe n'est jamais renvoyé ni journalisé :
     * l'API le hache avant l'enregistrement.
     */
    public function creerCompte(Request $request)
    {
        if (! $this->roleAutorise(['admin'])) {
            abort(403, 'Cette page est réservée à l’administrateur.');
        }

        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
            'structure_id' => ['required', 'uuid'],
        ], [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $reponse = $this->api()->post('/api/auth/comptes', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 422) {
            return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
                ->withInput()->withErrors($reponse->json('errors') ?? []);
        }

        if (! $reponse->successful()) {
            return back()->withInput()->with('erreur', $this->messageErreur($reponse));
        }

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
            ->with('succes', 'Compte créé pour '.$reponse->json('name').'.');
    }

    /**
     * Création d'une structure (ministère, service ou établissement).
     */
    public function creerStructure(Request $request)
    {
        if (! $this->roleAutorise(['admin'])) {
            abort(403, 'Cette page est réservée à l’administrateur.');
        }

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:ministere,service,etablissement'],
            'region' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'uuid'],
        ]);

        $reponse = $this->api()->post('/api/auth/structures', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 422) {
            return redirect()->route('admin.index', ['onglet' => 'structures'])
                ->withInput()->withErrors($reponse->json('errors') ?? []);
        }

        if (! $reponse->successful()) {
            return back()->withInput()->with('erreur', $this->messageErreur($reponse));
        }

        return redirect()->route('admin.index', ['onglet' => 'structures'])
            ->with('succes', 'Structure « '.$reponse->json('nom').' » créée.');
    }

    /**
     * Matrice des droits (AGENTS.md §6). Les lignes `maquette` décrivent des
     * droits prévus par les écrans de démonstration mais non codés.
     */
    private function matrice(): array
    {
        $tous = ['admin' => true, 'comptable_matieres' => true, 'agent_patrimoine' => true, 'responsable_service' => true, 'controleur' => true];

        return [
            ['fonctionnalite' => 'Consultation générale du patrimoine', 'maquette' => false, 'droits' => $tous],
            ['fonctionnalite' => 'Création et immatriculation d’un bien', 'maquette' => false, 'droits' => [
                'admin' => true, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => false, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Déclaration de transfert et d’affectation', 'maquette' => false, 'droits' => [
                'admin' => false, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => true, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Validation d’un transfert', 'maquette' => false, 'droits' => [
                'admin' => false, 'comptable_matieres' => false, 'agent_patrimoine' => false,
                'responsable_service' => true, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Scan de contrôle terrain et constat', 'maquette' => false, 'droits' => [
                'admin' => false, 'comptable_matieres' => false, 'agent_patrimoine' => true,
                'responsable_service' => false, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Traitement d’un écart', 'maquette' => false, 'droits' => [
                'admin' => false, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => false, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Génération de procès-verbaux officiels', 'maquette' => false, 'droits' => [
                'admin' => true, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => true, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Journal d’audit complet', 'maquette' => false, 'droits' => [
                'admin' => true, 'comptable_matieres' => false, 'agent_patrimoine' => false,
                'responsable_service' => false, 'controleur' => 'lecture',
            ]],
            ['fonctionnalite' => 'Gestion des comptes et structures', 'maquette' => false, 'droits' => [
                'admin' => true, 'comptable_matieres' => false, 'agent_patrimoine' => false,
                'responsable_service' => false, 'controleur' => false,
            ]],
        ];
    }
}
