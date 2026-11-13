@extends('layouts.auth')

@section('titre', 'Connexion')

@section('contenu')
    <h1 class="text-xl font-bold">Connexion</h1>
    <p class="mt-1 text-sm text-gray-600">Gestion électronique du patrimoine de l’État togolais.</p>

    <form method="POST" action="{{ route('connexion.post') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Adresse professionnelle</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-vert focus:ring-1 focus:ring-vert"
                   placeholder="prenom.nom@koffr.local">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-vert focus:ring-1 focus:ring-vert">
        </div>

        <button type="submit" class="w-full rounded-lg bg-vert px-4 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
            Se connecter
        </button>
    </form>

    <div class="mt-5 rounded-lg bg-gray-50 p-3 text-xs text-gray-700">
        <p class="font-semibold">Comptes de démonstration (mot de passe <code>password</code>)</p>
        <ul class="mt-1 space-y-0.5">
            <li><code>comptable@koffr.local</code> — comptable des matières</li>
            <li><code>agent@koffr.local</code> — agent du patrimoine</li>
            <li><code>controleur@koffr.local</code> — contrôleur</li>
        </ul>
    </div>
@endsection
