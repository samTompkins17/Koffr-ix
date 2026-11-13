@extends('layouts.public')

@section('titre', 'Connexion')

@section('accroche')
    <h1 class="text-4xl font-extrabold leading-tight lg:text-5xl">
        Chaque bien public,<br>
        <span class="text-vert">identifié, localisé, tracé.</span>
    </h1>

    <ul class="mt-8 space-y-4 text-lg text-gray-700">
        <li>Un code unique et un QR code imprimable pour chaque bien.</li>
        <li>Un historique en ajout seul : aucune ligne ne peut être supprimée.</li>
        <li>Inventaire par scan : seuls les écarts sont signalés.</li>
    </ul>
@endsection

@section('contenu')
    <h2 class="text-2xl font-bold">Connexion</h2>

    <form method="POST" action="{{ route('connexion.post') }}" class="mt-5 space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Adresse email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   placeholder="prenom.nom@ministere.tg"
                   class="w-full rounded-lg border border-gray-400 px-3 py-3 text-sm focus:border-vert focus:ring-1 focus:ring-vert">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="Votre mot de passe"
                   class="w-full rounded-lg border border-gray-400 px-3 py-3 text-sm focus:border-vert focus:ring-1 focus:ring-vert">
        </div>

        <button type="submit" class="w-full rounded-lg bg-vert px-4 py-3 text-base font-bold text-white hover:bg-[#00543F]">
            Se connecter
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">Comptes de démonstration</p>

    <div class="mt-3 grid gap-2 sm:grid-cols-3">
        @foreach ([
            ['comptable@koffr.local', 'Comptable des matières'],
            ['agent@koffr.local', 'Agent du patrimoine'],
            ['controleur@koffr.local', 'Contrôleur'],
        ] as [$email, $libelle])
            <button type="button"
                    data-email="{{ $email }}"
                    class="compte-demo rounded-lg border border-vert px-3 py-2 text-sm font-semibold text-vert hover:bg-green-50">
                {{ $libelle }}
            </button>
        @endforeach
    </div>

    <p class="mt-6 text-center">
        <a href="{{ route('public.formulaire') }}" class="text-sm font-medium text-vert underline">
            Vérifier un bien de l’État (sans connexion)
        </a>
    </p>

    <script>
        // Les comptes de démonstration préremplissent le formulaire (mot de passe commun).
        document.querySelectorAll('.compte-demo').forEach((bouton) => {
            bouton.addEventListener('click', () => {
                document.getElementById('email').value = bouton.dataset.email;
                document.getElementById('password').value = 'password';
                document.getElementById('password').focus();
            });
        });
    </script>
@endsection
