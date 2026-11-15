@extends('layouts.app')

@section('titre', 'Accès refusé')

@section('contenu')
    @php
        $roleCourant = session('utilisateur.role');
        $libelleRoleCourant = config('koffr.roles')[$roleCourant] ?? $roleCourant ?? 'Non renseigné';
    @endphp

    <div class="flex min-h-[65vh] items-center justify-center">
        <section aria-labelledby="titre-acces-refuse" class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 text-center sm:p-10">
            <p class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-sm font-semibold text-rouge">
                <svg aria-hidden="true" class="h-2 w-2" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4" /></svg>
                Accès refusé
            </p>
            <h1 id="titre-acces-refuse" class="mt-5 text-2xl font-bold tracking-tight sm:text-3xl">Vous n’avez pas accès à cette page</h1>
            <p class="mt-5 text-gray-600">Votre rôle actuel : <strong class="font-semibold">{{ $libelleRoleCourant }}</strong>.</p>
            <p class="mt-2 break-words text-gray-600">{{ $exception->getMessage() ?: 'Votre rôle ne permet pas d’accéder à cette ressource.' }}</p>
            <a href="{{ route('tableau') }}" class="mt-6 inline-flex min-h-[44px] items-center justify-center rounded-lg bg-vert px-6 py-3 font-semibold text-white hover:bg-vert/90">
                Retour au tableau de bord
            </a>
        </section>
    </div>
@endsection
