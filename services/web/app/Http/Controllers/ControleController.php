<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ControleController extends ControleurKoffr
{
    public function scan()
    {
        if (! $this->roleAutorise(['agent_patrimoine'])) {
            abort(403, 'Le scan de contrôle est réservé aux agents du patrimoine.');
        }

        return view('controles.scan');
    }

    /**
     * Reçoit le code (scan caméra ou saisie manuelle) et ouvre la fiche de contrôle.
     */
    public function rechercher(Request $request)
    {
        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Saisissez un code patrimoine ou scannez une étiquette.',
        ]);

        return redirect()->route('controles.fiche', trim($donnees['code']));
    }

    public function fiche(string $code)
    {
        $reponse = $this->api()->get('/api/inventaire/controles/bien/'.rawurlencode($code));

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 404) {
            return redirect()->route('controles.scan')
                ->with('erreur', 'Code non reconnu. Vérifiez l’étiquette ou saisissez le code à la main.');
        }

        if ($reponse->status() === 503) {
            return view('controles.indisponible');
        }

        if ($reponse->status() === 403) {
            abort(403, 'Le scan de contrôle est réservé aux agents du patrimoine.');
        }

        if (! $reponse->successful()) {
            return redirect()->route('controles.scan')->with('erreur', $this->messageErreur($reponse));
        }

        $donnees = $reponse->json();

        return view('controles.fiche', [
            'bien' => $donnees['bien'],
            'dernierControle' => $donnees['dernier_controle'] ?? null,
            'campagnes' => $donnees['campagnes'] ?? [],
            'structures' => $this->noms('/api/auth/structures', [$donnees['bien']['structure_id'] ?? null]),
        ]);
    }

    public function enregistrer(Request $request)
    {
        if (! $this->roleAutorise(['agent_patrimoine'])) {
            abort(403, 'Le scan de contrôle est réservé aux agents du patrimoine.');
        }

        $donnees = $request->validate([
            'campagne_id' => ['required', 'uuid'],
            'bien_id' => ['required', 'uuid'],
            'etat_constate' => ['required', 'in:bon,usage,hors_service,introuvable'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'code' => ['required', 'string'],
        ], [
            'etat_constate.required' => 'Indiquez l’état constaté sur place.',
        ]);

        $code = $donnees['code'];
        unset($donnees['code']);

        $reponse = $request->hasFile('photo')
            ? $this->api()->postAvecFichier('/api/inventaire/controles', $donnees, $request->file('photo'))
            : $this->api()->post('/api/inventaire/controles', $donnees);

        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }

        if ($reponse->status() === 409) {
            return redirect()->route('campagnes.fiche', $donnees['campagne_id'])
                ->with('erreur', 'Ce bien a déjà été contrôlé dans cette campagne.');
        }

        if ($reponse->status() === 503) {
            return view('controles.indisponible');
        }

        if ($reponse->status() === 422) {
            return back()->withInput()->withErrors($reponse->json('errors') ?? []);
        }

        if (! $reponse->successful()) {
            return redirect()->route('controles.fiche', $code)
                ->with('erreur', $this->messageErreur($reponse));
        }

        $message = $reponse->json('ecart_detecte')
            ? 'Constat enregistré. Un écart a été détecté et ajouté à la liste des écarts.'
            : 'Constat enregistré. Aucun écart détecté.';

        return redirect()->route('campagnes.fiche', $donnees['campagne_id'])->with('succes', $message);
    }
}
