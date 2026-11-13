@extends('layouts.auth')

@section('titre', 'Session expirée')

@section('contenu')
    <h1 class="text-xl font-bold">Session expirée</h1>
    <p class="mt-2 text-sm text-gray-700">
        Pour des raisons de sécurité, votre session se ferme après 15 minutes d’inactivité.
        Reconnectez-vous pour continuer.
    </p>

    <a href="{{ route('connexion') }}"
       class="mt-5 block rounded-lg bg-vert px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-vert/90">
        Se reconnecter
    </a>
@endsection
