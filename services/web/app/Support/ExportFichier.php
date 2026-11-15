<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options as OptionsPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/** Génération complète avant envoi : aucun téléchargement partiel en cas d'échec. */
class ExportFichier
{
    /** La vue reçoit toujours pdf=true ; le nom inclut l'extension .pdf. */
    public static function pdf(string $vue, array $donnees, string $nom, string $orientation = 'portrait'): Response
    {
        $dossier = self::dossierTemporaire();

        try {
            $options = new OptionsPdf;
            $options->setDefaultFont('DejaVu Sans');
            $options->setIsRemoteEnabled(false);
            $options->setIsPhpEnabled(false);
            $options->setIsJavascriptEnabled(false);
            $options->setChroot(public_path());
            $options->setTempDir($dossier);
            $options->setFontCache($dossier);

            $pdf = new Dompdf($options);
            $pdf->setPaper('A4', $orientation);
            $pdf->loadHtml(view($vue, array_merge($donnees, ['pdf' => true]))->render(), 'UTF-8');
            $pdf->render();

            return self::telechargement($pdf->output(), $nom, 'application/pdf');
        } finally {
            File::deleteDirectory($dossier);
        }
    }

    private static function dossierTemporaire(): string
    {
        // Un dossier privé par génération, y compris pour les fichiers internes des bibliothèques.
        $dossier = sys_get_temp_dir().'/koffr-export-'.bin2hex(random_bytes(16));
        abort_unless(mkdir($dossier, 0700), 503, 'Le fichier ne peut pas être généré.');

        return $dossier;
    }

    private static function telechargement(string $contenu, string $nom, string $type): Response
    {
        return response($contenu, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $nom, Str::ascii($nom)),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
