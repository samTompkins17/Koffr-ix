<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Étiquette — {{ $bien->code_unique }}</title>
    <style>
        /* Étiquette imprimable : 80 mm × 50 mm, sobre et lisible. */
        @page { size: 80mm 50mm; margin: 0; }
        body { font-family: Inter, Arial, sans-serif; margin: 0; color: #111; }
        .etiquette { width: 76mm; padding: 2mm; display: flex; gap: 3mm; }
        .qr { width: 30mm; }
        .qr img { width: 30mm; height: 30mm; }
        .infos { font-size: 8pt; line-height: 1.3; }
        .pays { font-size: 7pt; font-weight: 700; color: #006A4F; }
        .code { font-size: 11pt; font-weight: 800; letter-spacing: .5px; margin: 1mm 0; }
        .mention { font-size: 6pt; color: #444; margin-top: 1mm; }
        @media screen { body { background: #eee; padding: 10px; } .etiquette { background: #fff; } }
    </style>
</head>
<body>
    <div class="etiquette">
        <div class="qr"><img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR code {{ $bien->code_unique }}"></div>
        <div class="infos">
            <div class="pays">République Togolaise</div>
            <div class="code">{{ $bien->code_unique }}</div>
            <div>{{ \Illuminate\Support\Str::limit($bien->designation, 48) }}</div>
            <div>{{ $bien->categorie }}</div>
            <div class="mention">Bien de l’État · vérification : {{ $url }}</div>
        </div>
    </div>
</body>
</html>
