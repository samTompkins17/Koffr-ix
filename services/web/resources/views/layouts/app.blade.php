@php
    $utilisateur = session('utilisateur', []);
    $role = $utilisateur['role'] ?? null;
    $roleLibelle = config('koffr.roles')[$role] ?? $role;
    $ministere = config('koffr.ministeres')[$utilisateur['ministere_code'] ?? ''] ?? null;

    // Liens réellement codés, dans l'ordre du menu des maquettes.
    $liens = [
        ['route' => 'tableau', 'motif' => 'tableau', 'libelle' => 'Tableau de bord'],
        ['route' => 'biens.index', 'motif' => 'biens.*', 'libelle' => 'Biens'],
        ['route' => 'transferts.index', 'motif' => 'transferts.*', 'libelle' => 'Affectations et transferts'],
        ['route' => 'campagnes.index', 'motif' => 'campagnes.*', 'libelle' => 'Inventaire'],
    ];

    if (in_array($role, ['comptable_matieres', 'controleur'], true)) {
        $liens[] = ['route' => 'ecarts.index', 'motif' => 'ecarts.*', 'libelle' => 'Écarts et rapports'];
    }

    $liens[] = ['route' => 'carte.index', 'motif' => 'carte.*', 'libelle' => 'Carte'];

    if (in_array($role, ['admin', 'comptable_matieres', 'responsable_service'], true)) {
        $liens[] = ['route' => 'proces-verbaux.index', 'motif' => 'proces-verbaux.*', 'libelle' => 'Procès-verbaux'];
    }

    $liens[] = ['route' => 'vue-nationale.index', 'motif' => 'vue-nationale.*', 'libelle' => 'Vue nationale'];

    if ($role === 'agent_patrimoine') {
        $liens[] = ['route' => 'controles.scan', 'motif' => 'controles.*', 'libelle' => 'Contrôle par scan'];
    }

    if (in_array($role, ['admin', 'controleur'], true)) {
        $liens[] = ['route' => 'journal.index', 'motif' => 'journal.*', 'libelle' => 'Journal d’audit'];
    }

    // Tous les écrans du menu sont désormais réels ; la liste reste vide.
    $maquettes = [];
    $admin = $role === 'admin';
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
        :focus-visible { outline: 3px solid #006A4F; outline-offset: 2px; }
        @media print {
            aside, .sans-impression { display: none !important; }
            main { padding: 0 !important; max-width: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#F7F7F7] font-sans text-gray-900">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:rounded focus:bg-vert focus:px-3 focus:py-2 focus:text-white">Aller au contenu</a>

    <div class="flex min-h-screen">
        {{-- Barre latérale (maquette) --}}
        <aside class="hidden w-[280px] shrink-0 flex-col border-r border-gray-200 bg-white lg:flex">
            <div class="px-7 py-7">
                <a href="{{ route('tableau') }}" aria-label="KOFFR-IX, tableau de bord">@include('partials.logo')</a>
            </div>

            <nav class="flex-1 px-3" aria-label="Navigation principale">
                @include('partials.navigation')
            </nav>

            <div class="border-t border-gray-200 px-7 py-6 text-sm">
                <p class="font-bold">{{ $utilisateur['name'] ?? '' }}</p>
                <p class="text-gray-600">
                    {{ $roleLibelle }}@if ($ministere) · {{ $ministere }}@endif
                </p>
                <form method="POST" action="{{ route('deconnexion') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="font-medium text-vert underline">Se déconnecter</button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- En-tête mobile (la barre latérale est masquée sous 1024 px) --}}
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 lg:hidden">
                <a href="{{ route('tableau') }}">@include('partials.logo')</a>
                <form method="POST" action="{{ route('deconnexion') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-vert underline">Se déconnecter</button>
                </form>
            </header>
            <nav class="flex gap-1 overflow-x-auto border-b border-gray-200 bg-white px-3 py-2 text-sm lg:hidden" aria-label="Navigation">
                @foreach ($liens as $lien)
                    <a href="{{ route($lien['route']) }}"
                       class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs($lien['motif']) ? 'bg-green-50 font-semibold text-vert' : 'text-gray-700' }}">
                        {{ $lien['libelle'] }}
                    </a>
                @endforeach
                @if ($admin)
                    <a href="{{ route('admin.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('admin.*') ? 'bg-green-50 font-semibold text-vert' : 'text-gray-700' }}">Utilisateurs et rôles</a>
                @endif
            </nav>

            <main id="contenu" class="mx-auto w-full max-w-[1120px] flex-1 px-5 py-8 lg:px-10">
                @include('partials.alerte')
                @yield('contenu')
            </main>

            <footer class="px-5 pb-8 text-xs text-gray-500 lg:px-10">
                Maquette de démonstration · projet pédagogique, non officiel · données fictives
            </footer>
        </div>
    </div>
</body>
</html>
