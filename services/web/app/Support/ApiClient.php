<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Tous les appels du web passent par la passerelle, jamais directement
 * par un service. Le jeton de la session est envoyé en en-tête Bearer.
 */
class ApiClient
{
    public function __construct(private readonly ?string $jeton = null) {}

    public static function session(): self
    {
        return new self(session('jwt'));
    }

    public static function anonyme(): self
    {
        return new self(null);
    }

    public function get(string $chemin, array $parametres = []): Response
    {
        return $this->requete()->get($chemin, $parametres)
            ->onError(fn (Response $reponse) => abort_if(
                $reponse->status() === 403, 403, 'Votre rôle ne permet pas d’accéder à cette ressource.'
            ));
    }

    public function post(string $chemin, array $donnees = []): Response
    {
        return $this->requete()->post($chemin, $donnees)
            ->onError(fn (Response $reponse) => abort_if(
                $reponse->status() === 403, 403, 'Votre rôle ne permet pas d’accéder à cette ressource.'
            ));
    }

    public function put(string $chemin, array $donnees = []): Response
    {
        return $this->requete()->put($chemin, $donnees)
            ->onError(fn (Response $reponse) => abort_if(
                $reponse->status() === 403, 403, 'Votre rôle ne permet pas d’accéder à cette ressource.'
            ));
    }

    /**
     * Envoi multipart (photo de contrôle).
     */
    public function postAvecFichier(string $chemin, array $donnees, $fichier, string $champ = 'photo'): Response
    {
        return $this->requete()
            ->attach($champ, file_get_contents($fichier->getRealPath()), $fichier->getClientOriginalName())
            ->post($chemin, $donnees)
            ->onError(fn (Response $reponse) => abort_if(
                $reponse->status() === 403, 403, 'Votre rôle ne permet pas d’accéder à cette ressource.'
            ));
    }

    private function requete(): PendingRequest
    {
        $requete = Http::baseUrl(rtrim((string) config('services.gateway'), '/'))
            ->acceptJson()
            ->timeout(8);

        if ($this->jeton) {
            $requete = $requete->withToken($this->jeton);
        }

        return $requete;
    }
}
