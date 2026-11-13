<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Mouvement;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MouvementController extends Controller
{
    public function index(string $bienId)
    {
        $bien = Bien::findOrFail($bienId);

        return response()->json($bien->mouvements()->get());
    }

    /**
     * Derniers mouvements toutes structures confondues (tableau de bord).
     * Le web affiche le code et la désignation du bien concerné.
     */
    public function recents(Request $request)
    {
        $limite = min(max((int) $request->query('limite', 5), 1), 20);

        $mouvements = Mouvement::with('bien')
            ->orderByDesc('date')
            ->limit($limite)
            ->get()
            ->map(fn (Mouvement $mouvement) => [
                'id' => $mouvement->id,
                'type' => $mouvement->type,
                'date' => $mouvement->date,
                'motif' => $mouvement->motif,
                'statut_validation' => $mouvement->statut_validation,
                'auteur_id' => $mouvement->auteur_id,
                'bien_id' => $mouvement->bien_id,
                'bien_code' => $mouvement->bien?->code_unique,
                'bien_designation' => $mouvement->bien?->designation,
            ]);

        return response()->json($mouvements);
    }

    /**
     * Déclare un mouvement.
     * Transfert : en une seule transaction, on écrit le mouvement puis on met
     * à jour la structure et le responsable du bien (pas de validation préalable).
     */
    public function store(Request $request, string $bienId)
    {
        $donnees = $request->validate([
            'type' => ['required', Rule::in(Mouvement::$types)],
            'structure_destination_id' => [
                Rule::requiredIf(fn () => in_array($request->input('type'), ['affectation', 'transfert'], true)),
                'nullable',
                'uuid',
            ],
            'motif' => [
                Rule::requiredIf(fn () => in_array($request->input('type'), ['transfert', 'affectation', 'sortie'], true)),
                'nullable',
                'string',
                'max:500',
            ],
            'responsable_id' => ['nullable', 'uuid'],
            'date' => ['nullable', 'date'],
        ]);

        $acteur = $request->attributes->get('utilisateur');

        $mouvement = DB::transaction(function () use ($bienId, $donnees, $acteur) {
            $bien = Bien::lockForUpdate()->findOrFail($bienId);

            $mouvement = Mouvement::create([
                'bien_id' => $bien->id,
                'type' => $donnees['type'],
                'date' => $donnees['date'] ?? now(),
                'structure_origine_id' => $bien->structure_id,
                'structure_destination_id' => $donnees['structure_destination_id'] ?? null,
                'motif' => $donnees['motif'] ?? '—',
                'statut_validation' => 'valide',
                'auteur_id' => $acteur['id'],
            ]);

            if (in_array($donnees['type'], ['affectation', 'transfert'], true)) {
                $bien->structure_id = $donnees['structure_destination_id'];
                if (! empty($donnees['responsable_id'])) {
                    $bien->responsable_id = $donnees['responsable_id'];
                }
                $bien->save();
            }

            if ($donnees['type'] === 'sortie') {
                $bien->statut = 'sorti';
                $bien->save();
            }

            return $mouvement;
        });

        Journal::ecrire($acteur['id'], 'mouvement_'.$donnees['type'], $mouvement->bien_id, $mouvement->motif);

        return response()->json($mouvement, 201);
    }
}
