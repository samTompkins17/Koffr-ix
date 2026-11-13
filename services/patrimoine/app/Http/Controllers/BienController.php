<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Support\GenerateurCode;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BienController extends Controller
{
    /**
     * Liste paginée avec filtres : catégorie, structure, état, recherche libre.
     */
    public function index(Request $request)
    {
        $requete = Bien::query();

        if ($recherche = trim((string) $request->query('q'))) {
            $requete->where(function ($sous) use ($recherche) {
                $sous->where('designation', 'ilike', "%{$recherche}%")
                    ->orWhere('code_unique', 'ilike', "%{$recherche}%");
            });
        }

        // Filtre `ids` : le web récupère plusieurs fiches en un seul appel.
        if ($request->filled('ids')) {
            $ids = collect(explode(',', (string) $request->query('ids')))
                ->map(fn ($id) => trim($id))
                ->filter()
                ->all();

            $requete->whereIn('id', $ids);
        }

        foreach (['categorie', 'etat', 'structure_id', 'statut'] as $filtre) {
            if ($request->filled($filtre)) {
                $requete->where($filtre, $request->query($filtre));
            }
        }

        $biens = $requete->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 100));

        return response()->json($biens);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);

        $ministere = $request->attributes->get('utilisateur')['ministere_code'] ?? null;

        if (! $ministere) {
            return response()->json([
                'message' => 'Le compte n’est rattaché à aucun ministère : impossible de créer un code patrimoine.',
            ], 422);
        }

        // Le code est calculé ici, une seule fois, et jamais modifiable ensuite.
        // Il est affecté explicitement : il ne fait pas partie des champs de
        // `$fillable`, donc il ne peut pas être écrasé par un client.
        $bien = new Bien($donnees);
        $bien->code_unique = GenerateurCode::suivant($ministere);
        $bien->statut = 'en_service';
        $bien->save();

        Journal::ecrire(
            $request->attributes->get('utilisateur')['id'],
            'creation_bien',
            $bien->code_unique,
            $bien->designation,
        );

        return response()->json($bien, 201);
    }

    public function show(string $id)
    {
        return response()->json(Bien::findOrFail($id));
    }

    /**
     * Recherche par code patrimoine (scan du QR code, appel interne).
     */
    public function parCode(string $code)
    {
        $bien = Bien::where('code_unique', $code)->first();

        if (! $bien) {
            return response()->json(['message' => 'Aucun bien ne correspond à ce code.'], 404);
        }

        return response()->json($bien);
    }

    public function update(Request $request, string $id)
    {
        $bien = Bien::findOrFail($id);
        $donnees = $this->valider($request);

        // `code_unique` n'est pas dans les données validées : même si le client
        // l'envoie, il est ignoré. Le code reste immuable.
        $bien->update($donnees);

        Journal::ecrire(
            $request->attributes->get('utilisateur')['id'],
            'modification_bien',
            $bien->code_unique,
        );

        return response()->json($bien);
    }

    /**
     * Compteurs du tableau de bord (bonus).
     */
    public function stats()
    {
        $parEtat = Bien::selectRaw('etat, count(*) as total')
            ->groupBy('etat')
            ->pluck('total', 'etat');

        $parCategorie = Bien::selectRaw('categorie, count(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        // Croisement par structure : sert à l'onglet « Structures » de
        // l'administration, sans un appel par structure.
        $parStructure = Bien::selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $valeurParStructure = Bien::selectRaw('structure_id, sum(valeur_acquisition) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        return response()->json([
            'total' => Bien::count(),
            'en_service' => Bien::where('statut', 'en_service')->count(),
            'sortis' => Bien::where('statut', 'sorti')->count(),
            'valeur_totale' => (int) Bien::sum('valeur_acquisition'),
            'par_etat' => $parEtat,
            'par_categorie' => $parCategorie,
            'par_structure' => $parStructure,
            'valeur_par_structure' => $valeurParStructure,
        ]);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'categorie' => ['required', Rule::in(Bien::$categories)],
            'valeur_acquisition' => ['required', 'integer', 'min:0'],
            'date_acquisition' => ['required', 'date'],
            'etat' => ['required', Rule::in(Bien::$etats)],
            'localisation' => ['nullable', 'string', 'max:255'],
            'structure_id' => ['required', 'uuid'],
            'responsable_id' => ['nullable', 'uuid'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }
}
