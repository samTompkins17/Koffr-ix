<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'Procès-verbal') · KOFFR-IX</title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #17202A; background: #F4F4F4; font-family: Georgia, 'Times New Roman', 'DejaVu Sans', serif; font-size: 10pt; line-height: 1.45; }
        .barre-outils { max-width: 210mm; margin: 0 auto; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; font-family: Arial, sans-serif; }
        .barre-outils a, .barre-outils button { color: #006A4F; }
        .feuille { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 18mm; background: #fff; box-shadow: 0 1px 8px #0002; }
        .entete-officiel { text-align: center; }
        .blason { display: block; height: 22mm; min-height: 22mm; width: auto; max-width: 45mm; margin: 0 auto 2mm; }
        .republique { margin: 0; font-size: 8pt; font-variant: small-caps; letter-spacing: .04em; }
        .filet-officiel { width: 12mm; margin: 2mm auto 5mm; border: 0; border-top: 1px solid #006A4F; }
        .titre-document { margin: 0; text-align: center; font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .reference-document { margin: 2mm 0 7mm; color: #45515B; text-align: center; font-size: 9pt; }
        .introduction { margin: 0 0 6mm; text-align: justify; }
        table { width: 100%; border-collapse: collapse; }
        th { font-weight: bold; text-align: left; }
        th, td { padding: 2.2mm 1.5mm; border-bottom: .25mm solid #D9DEDF; vertical-align: top; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .pied-page { margin-top: 8mm; font-size: 8pt; }
        .mention-officielle { margin-top: 8mm; color: #69737D; font-size: 7pt; text-align: center; }
        .edition { margin-top: 2mm; color: #69737D; font-size: 7pt; text-align: center; }
        @media print {
            body { background: #fff; }
            .sans-impression { display: none !important; }
            .feuille { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
    @if (empty($pdf))
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>tailwind.config={theme:{extend:{colors:{vert:'#006A4F',rouge:'#D11135',jaune:'#FFCF11'},fontFamily:{sans:['Inter','system-ui','sans-serif']}}}};</script>
    @endif
</head>
<body>
    @if (empty($pdf))
        <div class="sans-impression barre-outils">
            <a href="@yield('retour')">← @yield('retour-libelle', 'Retour')</a>
            @hasSection('pdf-url')
                <a href="@yield('pdf-url')">Télécharger PDF</a>
            @endif
            <button type="button" onclick="window.print()">Imprimer</button>
        </div>
    @endif

    <main class="feuille">
        <header class="entete-officiel">
            <img src="{{ !empty($pdf) ? public_path('blason-republique-togolaise.svg') : asset('blason-republique-togolaise.svg') }}" alt="Blason officiel de la République Togolaise" class="blason">
            <p class="republique">RÉPUBLIQUE TOGOLAISE</p>
            <hr class="filet-officiel">
            <h1 class="titre-document">@yield('titre-pv')</h1>
            <p class="reference-document">N° @yield('reference', 'PV-DEMO')</p>
        </header>

        @hasSection('introduction')
            <p class="introduction">@yield('introduction')</p>
        @endif
        @yield('contenu')

        <footer class="pied-page">
            <p>Fait à Lomé, le {{ now()->format('d/m/Y') }}</p>
            <p class="mention-officielle">Document de démonstration, non officiel.</p>
            <p class="edition">Édité par KOFFR-IX le {{ now()->format('d/m/Y à H:i') }}</p>
        </footer>
    </main>
</body>
</html>
