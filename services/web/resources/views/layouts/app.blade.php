@php
    $utilisateur = session('utilisateur', []);
    $role = $utilisateur['role'] ?? null;
@endphp
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
        /* Charte : contraste ≥ 4,5 et tailles lisibles. */
        :focus-visible { outline: 3px solid #006A4F; outline-offset: 2px; }
        @media print {
            header, footer, .sans-impression { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:rounded focus:bg-vert focus:px-3 focus:py-2 focus:text-white">Aller au contenu</a>

    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('tableau') }}" class="flex items-center gap-3" aria-label="KOFFR-IX, tableau de bord">
                @include('partials.logo')
            </a>

            <nav class="flex flex-wrap items-center gap-1 text-sm font-medium" aria-label="Navigation principale">
                <a href="{{ route('tableau') }}" class="rounded px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('tableau') ? 'text-vert' : 'text-gray-700' }}">Tableau de bord</a>
                <a href="{{ route('biens.index') }}" class="rounded px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('biens.*') ? 'text-vert' : 'text-gray-700' }}">Biens</a>
                <a href="{{ route('campagnes.index') }}" class="rounded px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('campagnes.*') ? 'text-vert' : 'text-gray-700' }}">Inventaire</a>
                @if ($role === 'agent_patrimoine')
                    <a href="{{ route('controles.scan') }}" class="rounded px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('controles.*') ? 'text-vert' : 'text-gray-700' }}">Contrôle par scan</a>
                @endif
            </nav>

            <div class="flex items-center gap-3">
                <div class="text-right leading-tight">
                    <p class="text-sm font-semibold">{{ $utilisateur['name'] ?? '' }}</p>
                    <p class="text-xs text-gray-600">{{ config('koffr.roles')[$role] ?? $role }}</p>
                </div>
                <form method="POST" action="{{ route('deconnexion') }}">
                    @csrf
                    <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-100">Se déconnecter</button>
                </form>
            </div>
        </div>
    </header>

    <main id="contenu" class="mx-auto w-full max-w-6xl px-4 py-6">
        @include('partials.alerte')
        @yield('contenu')
    </main>

    <footer class="mt-auto border-t border-gray-200 bg-white px-4 py-4 text-center text-xs text-gray-600">
        Maquette de démonstration · projet pédagogique, non officiel · données fictives
    </footer>
</body>
</html>
