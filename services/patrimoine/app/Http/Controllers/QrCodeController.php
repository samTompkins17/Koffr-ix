<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    /**
     * Adresse publique encodée dans le QR code : page de vérification du web.
     */
    private function urlVerification(Bien $bien): string
    {
        return rtrim((string) config('app.public_verify_url'), '/').'/'.$bien->code_unique;
    }

    public function svg(string $id)
    {
        $bien = Bien::findOrFail($id);

        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->urlVerification($bien));

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * Étiquette imprimable (HTML + QR code en SVG).
     */
    public function etiquette(string $id)
    {
        $bien = Bien::findOrFail($id);
        $url = $this->urlVerification($bien);
        $qr = base64_encode(
            QrCode::format('svg')->size(220)->margin(0)->errorCorrection('M')->generate($url)
        );

        return view('etiquette', [
            'bien' => $bien,
            'qr' => $qr,
            'url' => $url,
        ]);
    }
}
