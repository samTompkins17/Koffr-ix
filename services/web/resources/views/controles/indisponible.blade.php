@extends('layouts.app')

@section('titre', 'Service indisponible')

@section('contenu')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5">
            <h1 class="flex items-center gap-2 text-lg font-bold text-amber-900">
                <span aria-hidden="true">▲</span> Fiche indisponible pour le moment
            </h1>
            <p class="mt-2 text-sm text-amber-900">
                Le service patrimoine ne répond pas. Le contrôle par scan est le seul parcours dépendant de
                ce service : la liste des biens, les campagnes et les écarts restent accessibles.
            </p>
            <a href="{{ route('controles.scan') }}" class="mt-4 inline-block rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                Réessayer
            </a>
        </div>
    </div>
@endsection
