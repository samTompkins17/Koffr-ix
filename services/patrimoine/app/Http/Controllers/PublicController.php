<?php

namespace App\Http\Controllers;

use App\Models\Bien;

class PublicController extends Controller
{
    /**
     * Vérification publique d'un bien (aucune connexion).
     * On ne divulgue ni la valeur, ni le responsable, ni la localisation précise.
     */
    public function bien(string $code)
    {
        $bien = Bien::where('code_unique', $code)->first();

        if (! $bien) {
            return response()->json(['message' => 'Ce code ne correspond à aucun bien enregistré.'], 404);
        }

        return response()->json([
            'bien_de_l_etat' => true,
            'immatriculation' => $bien->code_unique,
            'categorie' => $bien->categorie,
            'ministere_code' => explode('-', $bien->code_unique)[1] ?? null,
        ]);
    }
}
