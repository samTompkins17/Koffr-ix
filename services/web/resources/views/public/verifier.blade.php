@extends('layouts.auth')

@section('titre', 'Vérifier un bien')

@section('contenu')
    <h1 class="text-xl font-bold">Vérification d’un bien de l’État</h1>

    @if (! empty($indisponible))
        <p class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            ▲ Le service de vérification est momentanément indisponible. Réessayez dans quelques instants.
        </p>
    @elseif ($trouve)
        <div class="mt-4 rounded-lg border border-vert/30 bg-green-50 p-4">
            <p class="flex items-center gap-2 font-semibold text-vert">
                <span aria-hidden="true">✓</span> Bien de l’État enregistré
            </p>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Immatriculation</dt>
                    <dd class="font-mono font-semibold">{{ $bien['immatriculation'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Catégorie</dt>
                    <dd class="font-medium">{{ $bien['categorie'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Ministère de rattachement</dt>
                    <dd class="font-medium">{{ $ministere ?? $bien['ministere_code'] }}</dd>
                </div>
            </dl>
        </div>
        <p class="mt-4 text-xs text-gray-600">
            Cette page publique ne communique ni la valeur, ni le responsable, ni la localisation du bien.
        </p>
    @else
        <div class="mt-4 rounded-lg border border-rouge/30 bg-red-50 p-4 text-sm text-rouge">
            <p class="flex items-center gap-2 font-semibold"><span aria-hidden="true">✕</span> Code non reconnu</p>
            <p class="mt-1">Le code <span class="font-mono">{{ $code }}</span> ne correspond à aucun bien enregistré.</p>
        </div>
    @endif

    <a href="{{ url('/') }}" class="mt-5 block rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold hover:bg-gray-50">
        Accéder à KOFFR-IX
    </a>
@endsection
