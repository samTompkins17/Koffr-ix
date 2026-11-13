<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'KOFFR-IX') · KOFFR-IX</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { vert: '#006A4F', rouge: '#D11135', jaune: '#FFCF11' },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                },
            },
        };
    </script>
    <style>
        :focus-visible { outline: 3px solid #006A4F; outline-offset: 2px; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-900">
    <main class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            {{-- Bloc blason officiel : hauteur minimale 22 mm, jamais sur fond vert ou noir. --}}
            <div class="mb-6 flex justify-center">
                <img src="/blason-republique-togolaise.svg" alt="République Togolaise" style="height: 22mm;" class="w-auto">
            </div>

            <div class="mb-6 flex justify-center">@include('partials.logo')</div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                @include('partials.alerte')
                @yield('contenu')
            </div>

            <p class="mt-6 text-center text-xs text-gray-600">
                Maquette de démonstration · projet pédagogique, non officiel · données fictives
            </p>
        </div>
    </main>
</body>
</html>
