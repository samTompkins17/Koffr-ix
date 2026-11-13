<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Controle;
use App\Support\Journal;
use App\Support\PatrimoineClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControleController extends Controller
{
    /**
     * Fiche du bien (lue dans patrimoine) + dernier état connu.
     * Endpoint utilisé par l'écran de scan.
     */
    public function parCode(Request $request, string $code)
    {
        $client = new PatrimoineClient((string) $request->bearerToken());
        $bien = $client->bienParCode($code);

        if (! $bien) {
            return response()->json(['message' => 'Aucun bien ne correspond à ce code.'], 404);
        }

        return response()->json([
            'bien' => $bien,
            'dernier_controle' => Controle::where('bien_id', $bien['id'])
                ->orderByDesc('date')
                ->first(),
            'campagnes' => Campagne::where('statut', 'ouverte')->orderByDesc('date_debut')->get(),
        ]);
    }

    /**
     * Enregistre un constat et détecte l'écart en comparant l'état de la fiche
     * (copié depuis patrimoine) et l'état constaté sur le terrain.
     */
    public function store(Request $request)
    {
        $donnees = $request->validate([
            'campagne_id' => ['required', 'uuid'],
            'bien_id' => ['required', 'uuid'],
            'etat_constate' => ['required', Rule::in(Controle::$etatsConstates)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $campagne = Campagne::find($donnees['campagne_id']);

        if (! $campagne) {
            return response()->json(['message' => 'Campagne introuvable.'], 404);
        }

        if ($campagne->statut !== 'ouverte') {
            return response()->json(['message' => 'Cette campagne est clôturée.'], 409);
        }

        // Un seul contrôle par bien et par campagne.
        $dejaControle = Controle::where('campagne_id', $campagne->id)
            ->where('bien_id', $donnees['bien_id'])
            ->exists();

        if ($dejaControle) {
            return response()->json([
                'message' => 'Ce bien a déjà été contrôlé dans cette campagne.',
            ], 409);
        }

        $acteur = $request->attributes->get('utilisateur');
        $client = new PatrimoineClient((string) $request->bearerToken());
        $bien = $client->bienParId($donnees['bien_id']);

        if (! $bien) {
            return response()->json(['message' => 'Bien introuvable dans le service patrimoine.'], 404);
        }

        $photoChemin = $request->hasFile('photo')
            ? $request->file('photo')->store('', 'controles')
            : null;

        $controle = Controle::create([
            'campagne_id' => $campagne->id,
            'bien_id' => $bien['id'],
            'etat_fiche' => $bien['etat'],
            'etat_constate' => $donnees['etat_constate'],
            // Écart dès que l'état constaté diffère de la fiche (ou bien introuvable).
            'ecart_detecte' => $donnees['etat_constate'] !== $bien['etat'],
            'latitude' => $donnees['latitude'] ?? null,
            'longitude' => $donnees['longitude'] ?? null,
            'photo_chemin' => $photoChemin,
            'agent_id' => $acteur['id'],
            'date' => now(),
        ]);

        Journal::ecrire($acteur['id'], 'controle_bien', $bien['code_unique'], 'État constaté : '.$controle->etat_constate);

        return response()->json($controle, 201);
    }

    public function parCampagne(string $campagneId)
    {
        Campagne::findOrFail($campagneId);

        return response()->json(
            Controle::where('campagne_id', $campagneId)->orderByDesc('date')->get()
        );
    }

    /**
     * Liste des écarts : lecture seule (comptable et contrôleur).
     */
    public function ecarts(string $campagneId)
    {
        Campagne::findOrFail($campagneId);

        return response()->json(
            Controle::where('campagne_id', $campagneId)
                ->where('ecart_detecte', true)
                ->orderByDesc('date')
                ->get()
        );
    }
}
