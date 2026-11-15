<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Étiquettes KOFFR-IX</title>
    <style>
        @page { size: A4; margin: 4mm 0 0; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #111827; }
        .planche { page-break-after: always; }
        .planche:last-child { page-break-after: auto; }
        table { width: 210mm; border-collapse: collapse; table-layout: fixed; }
        td { width: 105mm; height: 48mm; padding: 0; vertical-align: middle; }
        .etiquette { position: relative; height: 47.8mm; border: 0.1mm solid #9ca3af; }
        .texte { position: absolute; left: 5mm; top: 12mm; width: 58mm; }
        .republique { font-size: 8pt; font-weight: bold; }
        .code { margin-top: 2mm; font-size: 9pt; font-weight: bold; }
        .designation { margin-top: 1mm; font-size: 8pt; overflow-wrap: break-word; }
        img { position: absolute; right: 3mm; top: 8mm; width: 32mm; height: 32mm; }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <div class="planche">
        <table>
            @foreach (array_chunk($page, 2) as $ligne)
                <tr>
                    @foreach ($ligne as $bien)
                        <td><div class="etiquette">
                            <div class="texte">
                                <div class="republique">RÉPUBLIQUE TOGOLAISE</div>
                                <div class="code">{{ $bien['code_unique'] }}</div>
                                <div class="designation">{{ \Illuminate\Support\Str::limit($bien['designation'], 85) }}</div>
                            </div>
                            <img src="data:image/svg+xml;base64,{{ $bien['qr'] }}" alt="QR code">
                        </div></td>
                    @endforeach
                    @if (count($ligne) === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
