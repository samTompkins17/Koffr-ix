<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Mouvement;
use App\Models\ValidationMouvement;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MouvementController extends Controller
{
    public function index(string $bienId)
    {
        $bien = Bien::findOrFail($bienId);

        return response()->json(
            $bien->mouvements()->with('validation')->get()->map(fn (Mouvement $m) => $this->presenter($m))
        );
    }

    /**
     * Derniers mouvements toutes structures confondues (tableau de bord).
     */
    public function recents(Request $request)
    {
        $limite = min(max((int) $request->query('limite', 5), 1), 20);

        $mouvements = Mouvement::with(['bien', 'validation'])
            ->orderByDesc('date')
            ->limit($limite)
            ->get()
            ->map(fn (Mouvement $mouvement) => $this->presenter($mouvement) + [
                'bien_code' => $mouvement->bien?->code_unique,
                'bien_designation' => $mouvement->bien?->designation,
            ]);

        return response()->json($mouvements);
    }

    /**
     * Mouvements en attente de validation (responsable de service).
     */
    public function enAttente()
    {
        $mouvements = Mouvement::with(['bien', 'validation'])
            ->where('statut_validation', 'en_attente')
            ->whereDoesntHave('validation')
            ->orderBy('date')
            ->get()
            ->map(fn (Mouvement $mouvement) => $this->presenter($mouvement) + [
                'bien_code' => $mouvement->bien?->code_unique,
                'bien_designation' => $mouvement->bien?->designation,
            ]);

        return response()->json($mouvements);
    }

    /**
     * Déclare un mouvement.
     * Une affectation ou un transfert reste « en_attente » : la structure du bien
     * ne change qu'à la validation par le responsable de service. Une sortie et
     * une maintenance sont immédiates.
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
        $aValider = in_array($donnees['type'], ['affectation', 'transfert'], true);

        $mouvement = DB::transaction(function () use ($bienId, $donnees, $acteur, $aValider) {
            $bien = Bien::lockForUpdate()->findOrFail($bienId);

            $mouvement = Mouvement::create([
                'bien_id' => $bien->id,
                'type' => $donnees['type'],
                'date' => $donnees['date'] ?? now(),
                'structure_origine_id' => $bien->structure_id,
                'structure_destination_id' => $donnees['structure_destination_id'] ?? null,
                'motif' => $donnees['motif'] ?? '—',
                'statut_validation' => $aValider ? 'en_attente' : 'valide',
                'auteur_id' => $acteur['id'],
            ]);

            if ($donnees['type'] === 'sortie') {
                $bien->statut = 'sorti';
                $bien->save();
            }

            return $mouvement;
        });

        Journal::ecrire($acteur['id'], 'mouvement_'.$donnees['type'], $mouvement->bien_id, $mouvement->motif);

        return response()->json($this->presenter($mouvement->load('validation')), 201);
    }

    /**
     * Validation par le responsable : le mouvement n'est pas modifié, une
     * décision est ajoutée et la structure du bien est mise à jour.
     */
    public function valider(Request $request, string $mouvementId)
    {
        return $this->decider($request, $mouvementId, 'validee');
    }

    public function refuser(Request $request, string $mouvementId)
    {
        return $this->decider($request, $mouvementId, 'refusee');
    }

    private function decider(Request $request, string $mouvementId, string $decision)
    {
        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $mouvement = Mouvement::with('validation')->find($mouvementId);

        if (! $mouvement) {
            return response()->json(['message' => 'Mouvement introuvable.'], 404);
        }

        if ($mouvement->statut_validation !== 'en_attente' || $mouvement->validation) {
            return response()->json(['message' => 'Ce mouvement a déjà été traité.'], 409);
        }

        $acteur = $request->attributes->get('utilisateur');

        DB::transaction(function () use ($mouvement, $acteur, $decision, $donnees) {
            ValidationMouvement::create([
                'mouvement_id' => $mouvement->id,
                'decideur_id' => $acteur['id'],
                'decision' => $decision,
                'motif' => $donnees['motif'] ?? null,
                'date' => now(),
            ]);

            if ($decision === 'validee' && $mouvement->structure_destination_id) {
                $bien = Bien::lockForUpdate()->findOrFail($mouvement->bien_id);
                $bien->structure_id = $mouvement->structure_destination_id;
                $bien->save();
            }
        });

        Journal::ecrire($acteur['id'], 'mouvement_'.$decision, $mouvement->bien_id, $donnees['motif'] ?? null);

        return response()->json($this->presenter($mouvement->fresh('validation')));
    }

    /**
     * Statut lisible : la décision de validation prime sur le statut initial.
     */
    private function presenter(Mouvement $mouvement): array
    {
        $decision = $mouvement->validation?->decision;

        return [
            'id' => $mouvement->id,
            'bien_id' => $mouvement->bien_id,
            'type' => $mouvement->type,
            'date' => $mouvement->date,
            'structure_origine_id' => $mouvement->structure_origine_id,
            'structure_destination_id' => $mouvement->structure_destination_id,
            'motif' => $mouvement->motif,
            'statut_validation' => $mouvement->statut_validation,
            'statut_effectif' => match ($decision) {
                'validee' => 'valide',
                'refusee' => 'refuse',
                default => $mouvement->statut_validation,
            },
            'validation' => $mouvement->validation ? [
                'decision' => $mouvement->validation->decision,
                'motif' => $mouvement->validation->motif,
                'date' => $mouvement->validation->date,
                'decideur_id' => $mouvement->validation->decideur_id,
            ] : null,
            'auteur_id' => $mouvement->auteur_id,
        ];
    }
}
