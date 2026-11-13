<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends ControleurKoffr
{
    private const ONGLETS = ['utilisateurs', 'roles', 'structures'];

    /**
     * Page « Utilisateurs, rôles et structures » : annuaire en lecture seule.
     * Réservée à l'administrateur ; l'API refait le contrôle de rôle.
     */
    public function index(Request $request)
    {
        if (! $this->roleAutorise(['admin'])) {
            return redirect()->route('tableau')->with('erreur', 'Cette page est réservée à l’administrateur.');
        }

        $reponse = $this->api()->get('/api/auth/annuaire');

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 403) {
            return redirect()->route('tableau')->with('erreur', 'Cette page est réservée à l’administrateur.');
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
            ['fonctionnalite' => 'Validation d’un transfert', 'maquette' => true, 'droits' => [
                'admin' => false, 'comptable_matieres' => false, 'agent_patrimoine' => false,
                'responsable_service' => true, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Scan de contrôle terrain et constat', 'maquette' => false, 'droits' => [
                'admin' => false, 'comptable_matieres' => false, 'agent_patrimoine' => true,
                'responsable_service' => false, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Traitement d’un écart', 'maquette' => true, 'droits' => [
                'admin' => false, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => false, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Génération de procès-verbaux officiels', 'maquette' => true, 'droits' => [
                'admin' => false, 'comptable_matieres' => true, 'agent_patrimoine' => false,
                'responsable_service' => true, 'controleur' => false,
            ]],
            ['fonctionnalite' => 'Journal d’audit complet', 'maquette' => true, 'droits' => [
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
