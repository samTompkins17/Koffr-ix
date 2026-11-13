<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') · KOFFR-IX</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { vert: '#006A4F', rouge: '#D11135', jaune: '#FFCF11' },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            } },
        };
    </script>
    <style>
        @page { size: A4; margin: 14mm; }
        @media print {
            .sans-impression { display: none !important; }
            body { background: #fff; }
            .feuille { box-shadow: none !important; border: 0 !important; margin: 0 !important; width: auto !important; }
        }
    </style>
</head>
<body class="bg-[#F4F4F4] font-sans text-gray-900">
    <div class="sans-impression mx-auto flex max-w-[210mm] items-center justify-between gap-3 px-4 py-4">
        <a href="@yield('retour')" class="text-sm font-medium text-vert underline">← @yield('retour-libelle', 'Retour')</a>
        <button type="button" onclick="window.print()"
                class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#00543F]">
            Imprimer
        </button>
    </div>

    <main class="feuille mx-auto mb-10 w-[210mm] bg-white p-12 shadow-sm">
        <header class="flex items-start justify-between gap-6 border-b-2 border-vert pb-4">
            <div class="flex items-center gap-4">
                <img src="/blason-republique-togolaise.svg" alt="République Togolaise" style="height: 22mm;" class="w-auto">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-vert">République Togolaise</p>
                    <p class="text-sm text-gray-700">Ministère de la Santé</p>
                    <p class="mt-1 text-sm font-bold">KOFFR-IX · Gestion du patrimoine de l’État</p>
                </div>
            </div>
            <div class="text-right text-xs text-gray-600">
                <p>Référence : @yield('reference', 'PV-DEMO')</p>
                <p>Édité le {{ now()->format('d/m/Y à H:i') }}</p>
            </div>
        </header>

        <h1 class="mt-6 text-2xl font-extrabold">@yield('titre-pv')</h1>
        @yield('contenu')

        <footer class="mt-10 border-t border-gray-300 pt-3 text-[10px] text-gray-500">
            Maquette de démonstration · projet pédagogique, non officiel · données fictives
        </footer>
    </main>
</body>
</html>
