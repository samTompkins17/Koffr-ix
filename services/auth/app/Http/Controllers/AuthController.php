<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use App\Models\User;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Message générique : on ne dit jamais lequel des deux champs est faux.
        $refus = response()->json(['message' => 'Identifiants incorrects.'], 401);

        $domaine = trim((string) config('app.allowed_email_domain'));
        if ($domaine !== '' && ! str_ends_with($donnees['email'], '@'.$domaine)) {
            Journal::ecrire(null, 'connexion_refusee', $donnees['email'], 'Domaine e-mail non autorisé');

            return $refus;
        }

        $utilisateur = User::where('email', $donnees['email'])->first();

        if (! $utilisateur || ! $utilisateur->actif || ! Hash::check($donnees['password'], $utilisateur->password)) {
            Journal::ecrire($utilisateur?->id, 'connexion_refusee', $donnees['email'], 'Identifiants incorrects');

            return $refus;
        }

        // Le jeton porte l'identité utile aux autres services.
        $jeton = auth('api')->claims($this->claims($utilisateur))->login($utilisateur);

        Journal::ecrire($utilisateur->id, 'connexion', $utilisateur->email);

        return response()->json([
            'token' => $jeton,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'utilisateur' => $this->profil($utilisateur),
        ]);
    }

    public function me(Request $request)
    {
        $id = $request->attributes->get('utilisateur')['id'];
        $utilisateur = User::with('structure')->find($id);

        if (! $utilisateur) {
            return response()->json(['message' => 'Compte introuvable.'], 401);
        }

        return response()->json($this->profil($utilisateur));
    }

    public function refresh()
    {
        try {
            $jeton = auth('api')->refresh();
        } catch (\Throwable) {
            return response()->json(['message' => 'Session expirée. Veuillez vous reconnecter.'], 401);
        }

        return response()->json([
            'token' => $jeton,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
        ]);
    }

    public function logout(Request $request)
    {
        $utilisateur = $request->attributes->get('utilisateur');

        try {
            // Blackliste le jeton courant (durée de vie courte : 15 minutes).
            auth('api')->logout();
        } catch (\Throwable) {
            // Un jeton déjà expiré n'empêche pas de se déconnecter côté web.
        }

        Journal::ecrire($utilisateur['id'], 'deconnexion', $utilisateur['nom']);

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }

    public function structures(Request $request)
    {
        $requete = Structure::query()->orderBy('nom');

        $ids = $this->ids($request);
        if ($ids !== []) {
            $requete->whereIn('id', $ids);
        }

        // Identité professionnelle seulement : id, nom, code.
        return $requete->get()->map(fn (Structure $s) => [
            'id' => $s->id,
            'nom' => $s->nom,
            'code' => $s->code,
        ]);
    }

    public function users(Request $request)
    {
        $ids = $this->ids($request);

        if ($ids === []) {
            return [];
        }

        // Aucune donnée sensible : ni e-mail, ni rôle, ni mot de passe.
        return User::whereIn('id', $ids)->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
        ]);
    }

    /**
     * Annuaire de l'administrateur : identité professionnelle (nom, rôle,
     * structure, activité). Pas d'adresse e-mail, pas de mot de passe.
     * La création de comptes reste hors démonstration.
     */
    public function annuaire()
    {
        $comptes = User::query()->with('structure')->orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->role,
            'structure_id' => $u->structure_id,
            'structure_nom' => $u->structure?->nom,
            'actif' => (bool) $u->actif,
        ]);

        $structures = Structure::query()->orderBy('nom')->get()->map(fn (Structure $s) => [
            'id' => $s->id,
            'nom' => $s->nom,
            'code' => $s->code,
            'type' => $s->type,
            'region' => $s->region,
        ]);

        return ['comptes' => $comptes, 'structures' => $structures];
    }

    private function ids(Request $request): array
    {
        $ids = $request->query('ids', '');

        return collect(explode(',', (string) $ids))
            ->map(fn ($id) => trim($id))
            ->filter()
            ->values()
            ->all();
    }

    private function claims(User $utilisateur): array
    {
        return [
            'name' => $utilisateur->name,
            'role' => $utilisateur->role,
            'structure_id' => $utilisateur->structure_id,
            'ministere_code' => $utilisateur->ministereCode(),
        ];
    }

    private function profil(User $utilisateur): array
    {
        return [
            'id' => $utilisateur->id,
            'name' => $utilisateur->name,
            'email' => $utilisateur->email,
            'role' => $utilisateur->role,
            'structure_id' => $utilisateur->structure_id,
            'structure_nom' => $utilisateur->structure?->nom,
            'ministere_code' => $utilisateur->ministereCode(),
        ];
    }
}
