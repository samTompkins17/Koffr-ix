<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Controle;
use App\Support\Journal;
use Illuminate\Http\Request;

class CampagneController extends Controller
{
    public function index(Request $request)
    {
        $requete = Campagne::query()->orderByDesc('date_debut');

        if ($request->filled('structure_id')) {
            $requete->where('structure_id', $request->query('structure_id'));
        }

        return response()->json($requete->get());
    }

    /**
     * Ouverture d'une campagne : réservée au comptable des matières.
     */
    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'structure_id' => ['required', 'uuid'],
        ]);

        $campagne = Campagne::create($donnees + ['statut' => 'ouverte']);

        Journal::ecrire(
            $request->attributes->get('utilisateur')['id'],
            'ouverture_campagne',
            $campagne->id,
            $campagne->nom,
        );

        return response()->json($campagne, 201);
    }

    public function show(string $id)
    {
        $campagne = Campagne::findOrFail($id);

        return response()->json([
            'campagne' => $campagne,
            'compteurs' => [
                'controles' => Controle::where('campagne_id', $campagne->id)->count(),
                'ecarts' => Controle::where('campagne_id', $campagne->id)->where('ecart_detecte', true)->count(),
                'introuvables' => Controle::where('campagne_id', $campagne->id)
                    ->where('etat_constate', 'introuvable')->count(),
            ],
        ]);
    }
}
