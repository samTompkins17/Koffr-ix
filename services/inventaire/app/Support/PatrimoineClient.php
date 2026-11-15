<?php

namespace App\Support;

use App\Exceptions\PatrimoineIndisponible;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Appel interne inventaire → patrimoine.
 * Le JWT reçu du client est transmis tel quel ; le délai maximal est de 3 s.
 * Une panne de patrimoine ne casse que le parcours de contrôle par scan.
 */
class PatrimoineClient
{
    public function __construct(private readonly string $jeton) {}

    public function bienParCode(string $code): ?array
    {
        return $this->appeler('/api/biens/code/'.rawurlencode($code));
    }

    public function bienParId(string $id): ?array
    {
        return $this->appeler('/api/biens/'.$id);
    }

    private function appeler(string $chemin): ?array
    {
        try {
            $reponse = Http::baseUrl(rtrim((string) config('services.patrimoine_url'), '/'))
                ->timeout(3)
                ->acceptJson()
                ->withToken($this->jeton)
                ->get($chemin);
        } catch (ConnectionException) {
            throw new PatrimoineIndisponible;
        }

        if ($reponse->status() === 404) {
            return null;
        }

        if (! $reponse->successful()) {
            throw new PatrimoineIndisponible;
        }

        return $reponse->json();
    }
}
