<?php

namespace App\Http\Controllers;

use App\Support\ApiClient;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function formulaire()
    {
        if (session()->has('jwt')) {
            return redirect()->route('tableau');
        }

        return view('auth.connexion');
    }

    public function connexion(Request $request)
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Le web n'appelle que le service auth, via la passerelle.
        $reponse = ApiClient::anonyme()->post('/api/auth/login', $identifiants);

        if ($reponse->status() === 401) {
            return back()
                ->withInput($request->only('email'))
                ->with('erreur', 'Identifiants incorrects.');
        }

        if (! $reponse->successful()) {
            return back()
                ->withInput($request->only('email'))
                ->with('erreur', 'Connexion impossible pour le moment. Réessayez.');
        }

        $donnees = $reponse->json();

        // Le JWT reste côté serveur, dans la session.
        session([
            'jwt' => $donnees['token'],
            'utilisateur' => $donnees['utilisateur'],
            'expire_le' => now()->addSeconds($donnees['expires_in']),
        ]);

        return redirect()->intended(route('tableau'));
    }

    public function deconnexion()
    {
        ApiClient::session()->post('/api/auth/logout');
        session()->flush();

        return redirect()->route('connexion')->with('succes', 'Vous êtes déconnecté.');
    }
}
