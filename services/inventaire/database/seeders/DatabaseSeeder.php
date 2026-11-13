<?php

namespace Database\Seeders;

use App\Models\Campagne;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Campagne de démonstration pour le ministère de la Santé.
        // L'UUID de la structure reprend celui du seeder auth.
        $campagne = Campagne::firstOrNew(['nom' => 'Inventaire 2026']);

        if (! $campagne->exists) {
            $campagne->id = '00000000-0000-4000-8000-000000000201';
        }

        $campagne->fill([
            'date_debut' => '2026-01-15',
            'date_fin' => '2026-12-15',
            'structure_id' => '00000000-0000-4000-8000-000000000001',
            'statut' => 'ouverte',
        ])->save();
    }
}
