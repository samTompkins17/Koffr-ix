<?php

namespace App\Http\Controllers;

use App\Support\ExportFichier;
use Illuminate\Http\Request;
use ZipArchive;

class EtiquetteController extends ControleurKoffr
{
    public function index(Request $request)
    {
        $donnees = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'uuid', 'distinct'],
            'format' => ['nullable', 'in:pdf,svg'],
        ], ['ids.required' => 'Sélectionnez au moins un bien pour préparer une planche.']);

        $reponse = $this->api()->get('/api/patrimoine/biens', [
            'ids' => implode(',', $donnees['ids']),
            'per_page' => 100,
        ]);
        if ($reponse->status() === 401) {
            return $this->sessionExpiree();
        }
        abort_unless($reponse->successful(), 503, 'Les étiquettes sont momentanément indisponibles.');
        $parId = collect($reponse->json('data'))->keyBy('id');
        abort_unless($parId->count() === count($donnees['ids']), 404, 'Un bien sélectionné est introuvable.');
        $biens = [];

        foreach ($donnees['ids'] as $id) {
            $qr = $this->api()->get('/api/patrimoine/biens/'.$id.'/qrcode');
            if ($qr->status() === 401) {
                return $this->sessionExpiree();
            }
            abort_unless($qr->successful(), 503, 'Un QR code est momentanément indisponible.');
            $biens[] = $parId[$id] + ['qr' => base64_encode($qr->body())];
        }

        $pages = array_chunk($biens, 12);
        if (($donnees['format'] ?? null) === 'pdf') {
            return ExportFichier::pdf('biens.planche-pdf', ['pages' => $pages], 'etiquettes-koffr-ix.pdf');
        }
        if (($donnees['format'] ?? null) === 'svg') {
            $chemin = tempnam(sys_get_temp_dir(), 'etiquettes-');
            try {
                $zip = new ZipArchive;
                if ($zip->open($chemin, ZipArchive::OVERWRITE) !== true) {
                    abort(500, 'La planche vectorielle n’a pas pu être générée.');
                }
                foreach ($pages as $numero => $page) {
                    $zip->addFromString('planche-'.($numero + 1).'.svg', view('biens.planche-svg', ['biens' => $page])->render());
                }
                $zip->close();
                $contenu = file_get_contents($chemin);
            } finally {
                if (is_file($chemin)) {
                    unlink($chemin);
                }
            }

            return response($contenu, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="etiquettes-koffr-ix-svg.zip"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return view('biens.planche', ['biens' => $biens, 'pages' => $pages, 'ids' => $donnees['ids']]);
    }
}
