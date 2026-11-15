<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\SequenceCode;
use Illuminate\Database\Seeder;

/**
 * Biens de démonstration. Les structure_id reprennent les UUID fixes du
 * seeder d'auth (Services généraux = …004, appelés ici par convention).
 */
class DatabaseSeeder extends Seeder
{
    private const SERVICES_GENERAUX = '00000000-0000-4000-8000-000000000004';
    private const RESPONSABLE = '00000000-0000-4000-8000-000000000102';

    public function run(): void
    {
        // Coordonnées de démonstration (Lomé et environs) pour la carte.
        // Coordonnées de démonstration (Lomé) : la carte et les PV en ont besoin.
        $coordonnees = [
            'PAT-SAN-2026-0412' => [6.13194, 1.22278],
            'PAT-SAN-2026-0411' => [6.13210, 1.22305],
            'PAT-SAN-2025-0231' => [6.12902, 1.21504],
            'PAT-SAN-2025-0198' => [6.12851, 1.21462],
            'PAT-SAN-2024-0087' => [6.17502, 1.23011],
        ];

        $biens = [
            ['PAT-SAN-2026-0412', 'Ordinateur portable HP 450', 'Matériel informatique', 450000, '2026-02-10', 'bon', 'Bureau 2, Services généraux', 6.1310, 1.2226],
            ['PAT-SAN-2026-0411', 'Imprimante laser', 'Matériel informatique', 185000, '2026-02-10', 'usage', 'Bureau 2, Services généraux', 6.1310, 1.2226],
            ['PAT-SAN-2025-0231', 'Climatiseur 18 000 BTU', 'Matériel informatique', 320000, '2025-06-03', 'bon', 'Salle de réunion', 6.1325, 1.2218],
            ['PAT-SAN-2025-0198', 'Groupe électrogène 20 kVA', 'Autres', 4500000, '2025-05-21', 'hors_service', 'Local technique', 6.1341, 1.2245],
            ['PAT-SAN-2024-0087', 'Véhicule utilitaire Toyota Hilux', 'Véhicules', 18000000, '2024-11-08', 'usage', 'Parking, Services généraux', 6.1305, 1.2237],
        ];

        foreach ($biens as [$code, $designation, $categorie, $valeur, $date, $etat, $localisation, $latitude, $longitude]) {
            $bien = Bien::firstOrNew(['code_unique' => $code]);
            if (! $bien->exists) {
                $bien->code_unique = $code;
            }
            $bien->fill([
                'designation' => $designation,
                'categorie' => $categorie,
                'valeur_acquisition' => $valeur,
                'date_acquisition' => $date,
                'etat' => $etat,
                'statut' => 'en_service',
                'localisation' => $localisation,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'latitude' => $coordonnees[$code][0] ?? null,
                'longitude' => $coordonnees[$code][1] ?? null,
                'structure_id' => self::SERVICES_GENERAUX,
                'responsable_id' => self::RESPONSABLE,
            ])->save();
        }

        // Compteurs alignés sur les codes déjà utilisés.
        SequenceCode::updateOrCreate(
            ['ministere_code' => 'SAN', 'annee' => 2026],
            ['dernier_numero' => 412],
        );
        SequenceCode::updateOrCreate(
            ['ministere_code' => 'SAN', 'annee' => 2025],
            ['dernier_numero' => 231],
        );
        SequenceCode::updateOrCreate(
            ['ministere_code' => 'SAN', 'annee' => 2024],
            ['dernier_numero' => 87],
        );
    }
}
