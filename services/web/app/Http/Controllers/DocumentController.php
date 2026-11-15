<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends ControleurKoffr
{
    public function store(Request $request, string $bienId)
    {
        abort_unless($this->roleAutorise(['admin', 'comptable_matieres']), 403, 'Seuls le comptable et l’administrateur peuvent ajouter un document.');

        $donnees = $request->validate([
            'type' => ['required', 'in:facture,acte,titre,photo,autre'],
            'fichier' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
            'fichier.mimes' => 'Choisissez un document PDF, JPG ou PNG.',
            'fichier.extensions' => 'Choisissez un document PDF, JPG ou PNG.',
        ]);

        $reponse = $this->api()->postAvecFichier(
            '/api/patrimoine/biens/'.$bienId.'/documents',
            ['type' => $donnees['type']],
            $request->file('fichier'),
            'fichier',
        );

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }
        if ($reponse->status() === 422) {
            return back()->withInput($request->only('type'))->withErrors($reponse->json('errors', []));
        }
        abort_if($reponse->status() === 404, 404, 'Bien introuvable.');
        if (! $reponse->successful()) {
            return back()->withInput($request->only('type'))->with('erreur', 'Le document n’a pas pu être ajouté. Veuillez réessayer.');
        }

        return redirect()->route('biens.fiche', $bienId)->with('succes', 'Document ajouté au bien. Il ne peut plus être modifié ni supprimé.');
    }

    public function telecharger(string $bienId, string $documentId)
    {
        $reponse = $this->api()->get('/api/patrimoine/biens/'.$bienId.'/documents/'.$documentId);
        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }
        abort_if($reponse->status() === 404, 404, 'Document introuvable.');
        abort_unless($reponse->successful(), 503, 'Le document est momentanément indisponible.');

        return response($reponse->body(), 200, [
            'Content-Type' => $reponse->header('Content-Type'),
            'Content-Disposition' => $reponse->header('Content-Disposition'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
