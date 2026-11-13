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
<body class="min-h-screen bg-white font-sans text-gray-900">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Colonne de gauche : identité et message --}}
        <section class="flex flex-col justify-between px-6 py-8 lg:px-16 lg:py-10">
            <div>
                <div class="flex flex-wrap items-center gap-6">
                    {{-- Bloc blason officiel : 22 mm minimum, fond blanc. --}}
                    <img src="/blason-republique-togolaise.svg" alt="République Togolaise"
                         style="height: 24mm;" class="w-auto">
                    <div class="hidden h-16 w-px bg-gray-300 sm:block" aria-hidden="true"></div>
                    <div>
                        @include('partials.logo')
                        <p class="mt-1 text-sm text-gray-600">Gestion électronique du patrimoine de l’État</p>
                    </div>
                </div>

                <div class="mt-12 max-w-xl">
                    @yield('accroche')
                </div>
            </div>

            <p class="mt-10 text-xs text-gray-500">
                Maquette de démonstration · projet pédagogique, non officiel · données fictives
            </p>
        </section>

        {{-- Colonne de droite : formulaire --}}
        <section class="flex items-center justify-center bg-[#F4F4F4] px-4 py-10 lg:px-10">
            <div class="w-full max-w-[530px] rounded-2xl bg-white p-7 shadow-sm ring-1 ring-gray-100 lg:p-9">
                @include('partials.alerte')
                @yield('contenu')
            </div>
        </section>
    </div>
</body>
</html>
