@extends('layouts.public')

@section('titre', 'Session expirée')

@section('accroche')
    <h1 class="text-4xl font-extrabold leading-tight">
        Session <span class="text-vert">expirée.</span>
    </h1>
    <p class="mt-6 text-lg text-gray-700">
        Pour des raisons de sécurité, votre session se ferme après 15 minutes.
        Reconnectez-vous pour continuer.
    </p>
@endsection

@section('contenu')
    <h2 class="text-2xl font-bold">Session expirée</h2>
    <p class="mt-2 text-sm text-gray-600">
        Votre jeton d’accès a expiré. Aucune donnée n’a été perdue.
    </p>

    <a href="{{ route('connexion') }}"
       class="mt-6 block rounded-lg bg-vert px-4 py-3 text-center text-base font-bold text-white hover:bg-[#00543F]">
        Se reconnecter
    </a>
@endsection
