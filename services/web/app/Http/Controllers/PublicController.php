<?php

namespace App\Http\Controllers;

use App\Support\ApiClient;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Formulaire public de vérification (aucune connexion).
     */
    public function formulaire()
    {
        return view('public.verifier', ['trouve' => null, 'code' => null]);
    }

    public function rechercher(Request $request)
    {
        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        return redirect()->route('public.verifier', trim($donnees['code']));
    }

    /**
     * Résultat de la vérification : contenu du QR code.
     */
    public function verifier(string $code)
    {
        $reponse = ApiClient::anonyme()->get('/api/patrimoine/public/biens/'.rawurlencode($code));

        if ($reponse->status() === 404) {
            return response()->view('public.verifier', [
                'trouve' => false,
                'code' => $code,
            ], 404);
        }

        if (! $reponse->successful()) {
            return response()->view('public.verifier', [
                'trouve' => null,
                'code' => $code,
                'indisponible' => true,
            ], 503);
        }

        return view('public.verifier', [
            'trouve' => true,
            'code' => $code,
            'bien' => $reponse->json(),
            'ministere' => config('koffr.ministeres')[$reponse->json('ministere_code')] ?? null,
        ]);
    }
}
