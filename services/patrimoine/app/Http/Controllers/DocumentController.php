<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Document;
use App\Support\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function index(string $id): JsonResponse
    {
        $bien = Bien::findOrFail($id);

        return response()->json($bien->documents()->get());
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $bien = Bien::findOrFail($id);
        $donnees = $request->validate([
            'type' => ['required', Rule::in(Document::$types)],
            'fichier' => [
                'required',
                'file',
                'max:10240',
                // Vérifier le contenu ET l'extension fournie : pas de SVG, HTML ou script.
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'extensions:pdf,jpg,jpeg,png',
            ],
        ]);

        $fichier = $request->file('fichier');
        $acteur = $request->attributes->get('utilisateur');
        $chemin = null;

        try {
            // Nom aléatoire, disque explicitement privé : aucun lien public n'est créé.
            $chemin = $fichier->store('documents', ['disk' => 'local', 'visibility' => 'private']);
            if (! $chemin) {
                throw new RuntimeException('Échec du stockage du document.');
            }

            $document = DB::transaction(function () use ($bien, $donnees, $fichier, $acteur, $chemin) {
                $document = Document::create([
                    'bien_id' => $bien->id,
                    'type' => $donnees['type'],
                    'nom_original' => $this->nettoyerNom($fichier),
                    'chemin' => $chemin,
                    // Ne jamais faire confiance au MIME ni aux métadonnées envoyés par le client.
                    'mime_type' => $fichier->getMimeType(),
                    'taille' => $fichier->getSize(),
                    'auteur_id' => $acteur['id'],
                ]);

                Journal::ecrire($acteur['id'], 'document_ajoute', $bien->id, 'Document '.$document->id.' ('.$document->type.').');

                return $document;
            });
        } catch (Throwable $exception) {
            // Le disque n'est pas transactionnel : retirer le fichier orphelin en cas d'échec.
            if ($chemin) {
                Storage::disk('local')->delete($chemin);
            }
            report($exception);

            return response()->json(['message' => 'Le document n’a pas pu être enregistré.'], 500);
        }

        return response()->json($document, 201);
    }

    public function download(string $bienId, string $documentId): StreamedResponse
    {
        // La recherche par la relation empêche de télécharger la pièce d'un autre bien.
        $bien = Bien::findOrFail($bienId);
        $document = $bien->documents()->findOrFail($documentId);
        $disque = Storage::disk('local');

        abort_unless($disque->exists($document->chemin), 404, 'Document introuvable.');

        return $disque->download($document->chemin, $document->nom_original, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function nettoyerNom(UploadedFile $fichier): string
    {
        $nom = basename(str_replace('\\', '/', $fichier->getClientOriginalName()));
        // Conserver un nom lisible, sans séparateur, guillemet ni caractère de contrôle d'en-tête.
        $nom = preg_replace('/[^\p{L}\p{N} ._()-]/u', '', $nom) ?? '';
        $nom = trim(mb_substr($nom, 0, 255), ' .');

        return $nom !== '' ? $nom : 'document.'.$fichier->extension();
    }
}
