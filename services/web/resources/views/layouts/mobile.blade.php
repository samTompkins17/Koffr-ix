<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'Contrôle par scan') · KOFFR-IX</title>
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
</head>
<body class="min-h-screen bg-[#F4F4F4] pb-24 font-sans text-gray-900">
    {{-- En-tête vert avec filet jaune (maquette mobile) --}}
    <header class="bg-vert px-5 pb-4 pt-5 text-white shadow-sm border-b-4 border-jaune">
        <div class="mx-auto max-w-md">
            <a href="{{ route('tableau') }}" class="text-sm text-white/80 underline">← Tableau de bord</a>
            <h1 class="mt-1 text-xl font-bold">@yield('entete', 'Contrôle par scan')</h1>
            <p class="text-sm text-white/90">@yield('sous-titre', 'Inventaire en cours')</p>
        </div>
    </header>

    <main class="mx-auto max-w-md px-4 py-5">
        @include('partials.alerte')
        @yield('contenu')
    </main>
</body>
</html>
