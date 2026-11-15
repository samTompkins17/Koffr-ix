<?php

namespace Database\Seeders;

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : structures et comptes (mot de passe « password »).
 * Les données sont fictives, le projet est pédagogique.
 *
 * Les UUID des structures et des comptes sont volontairement fixes : les autres
 * services (patrimoine, inventaire) ne stockent que des UUID et peuvent ainsi
 * référencer ces lignes sans clé étrangère entre bases.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Structures (ministères puis services rattachés) ---
        $sante = $this->structure('00000000-0000-4000-8000-000000000001', [
            'nom' => 'Ministère de la Santé', 'code' => 'SAN', 'type' => 'ministere', 'region' => 'Maritime',
        ]);

        $this->structure('00000000-0000-4000-8000-000000000002', [
            'nom' => 'Ministère des Finances et du Budget', 'code' => 'FIN', 'type' => 'ministere', 'region' => 'Maritime',
        ]);

        $this->structure('00000000-0000-4000-8000-000000000003', [
            'nom' => 'Ministère de l’Éducation', 'code' => 'EDU', 'type' => 'ministere', 'region' => 'Maritime',
        ]);

        $servicesGeneraux = $this->structure('00000000-0000-4000-8000-000000000004', [
            'nom' => 'Services généraux', 'code' => 'SAN-SG', 'type' => 'service', 'region' => 'Maritime', 'parent_id' => $sante->id,
        ]);

        $ressourcesHumaines = $this->structure('00000000-0000-4000-8000-000000000005', [
            'nom' => 'Ressources humaines', 'code' => 'SAN-RH', 'type' => 'service', 'region' => 'Maritime', 'parent_id' => $sante->id,
        ]);

        $this->structure('00000000-0000-4000-8000-000000000006', [
            'nom' => 'Hôpital régional de Lomé', 'code' => 'SAN-HRL', 'type' => 'etablissement', 'region' => 'Maritime', 'parent_id' => $sante->id,
        ]);

        $this->structure('00000000-0000-4000-8000-000000000007', [
            'nom' => 'Centre de santé de zone', 'code' => 'SAN-CSZ', 'type' => 'etablissement', 'region' => 'Maritime', 'parent_id' => $sante->id,
        ]);

        $inspection = $this->structure('00000000-0000-4000-8000-000000000008', [
            'nom' => 'Inspection générale', 'code' => 'SAN-IG', 'type' => 'service', 'region' => 'Maritime', 'parent_id' => $sante->id,
        ]);

        // --- Comptes de démonstration, un par rôle ---
        $comptes = [
            ['00000000-0000-4000-8000-000000000101', 'Ama K.', 'comptable@koffr.local', 'comptable_matieres', $sante->id],
            ['00000000-0000-4000-8000-000000000102', 'Kodjo A.', 'responsable@koffr.local', 'responsable_service', $servicesGeneraux->id],
            ['00000000-0000-4000-8000-000000000103', 'Yao T.', 'agent@koffr.local', 'agent_patrimoine', $servicesGeneraux->id],
            ['00000000-0000-4000-8000-000000000104', 'Abla M.', 'abla.m@koffr.local', 'responsable_service', $ressourcesHumaines->id],
            ['00000000-0000-4000-8000-000000000105', 'Mawuli S.', 'controleur@koffr.local', 'controleur', $inspection->id],
            ['00000000-0000-4000-8000-000000000106', 'Edem R.', 'admin@koffr.local', 'admin', $sante->id],
        ];

        foreach ($comptes as [$id, $nom, $email, $role, $structureId]) {
            $utilisateur = User::firstOrNew(['email' => $email]);
            if (! $utilisateur->exists) {
                $utilisateur->id = $id;
            }
            $utilisateur->name = $nom;
            $utilisateur->role = $role;
            $utilisateur->structure_id = $structureId;
            $utilisateur->actif = true;
            $utilisateur->password = 'password'; // haché automatiquement par le cast du modèle
            $utilisateur->save();
        }
    }

    private function structure(string $id, array $attributs): Structure
    {
        $structure = Structure::firstOrNew(['code' => $attributs['code']]);
        if (! $structure->exists) {
            $structure->id = $id;
        }
        $structure->fill($attributs)->save();

        return $structure;
    }
}
