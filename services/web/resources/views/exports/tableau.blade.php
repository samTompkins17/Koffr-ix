<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }} · KOFFR-IX</title>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #171717;">
    <header style="border-bottom: 2px solid #006A4F; padding-bottom: 10px;">
        <p style="color: #006A4F; font-weight: bold;">République Togolaise · KOFFR-IX</p>
        <h1 style="font-size: 18px;">{{ $titre }}</h1>
        <p>Édité le {{ now()->format('d/m/Y à H:i') }} · {{ count($lignes) }} ligne(s)</p>
    </header>
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 16px;">
        <thead style="display: table-header-group;">
            <tr>
                @foreach ($colonnes as $colonne)
                    <th style="padding: 6px; border: 1px solid #006A4F; background: #006A4F; color: #FFFFFF; text-align: left; overflow-wrap: break-word;">{{ $colonne }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($lignes as $ligne)
                <tr style="page-break-inside: avoid;">
                    @foreach ($ligne as $valeur)
                        <td style="padding: 6px; border: 1px solid #D1D5DB; vertical-align: top; overflow-wrap: break-word;">{{ is_int($valeur) || is_float($valeur) ? number_format($valeur, 0, ',', ' ') : $valeur }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($colonnes) }}" style="padding: 16px; border: 1px solid #D1D5DB; text-align: center;">Aucune donnée pour les filtres sélectionnés.</td></tr>
            @endforelse
        </tbody>
    </table>
    <footer style="margin-top: 20px; padding-top: 8px; border-top: 1px solid #D1D5DB; font-size: 8px; color: #4B5563;">
    </footer>
</body>
</html>
