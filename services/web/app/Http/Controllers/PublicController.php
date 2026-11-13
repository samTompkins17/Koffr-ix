<?php

namespace App\Http\Controllers;

use App\Support\ApiClient;

class PublicController extends Controller
{
    /**
     * Page de vérification d'un bien, sans connexion (contenu du QR code).
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
                'trouve' => false,
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
