@extends('layouts.public')

@section('titre', 'Vérifier un bien')

@section('accroche')
    <h1 class="text-4xl font-extrabold leading-tight lg:text-5xl">
        Vérification publique<br>
        <span class="text-vert">du patrimoine public.</span>
    </h1>
    <p class="mt-8 max-w-lg text-lg text-gray-700">
        Contrôlez l’authenticité et l’appartenance d’un bien immatriculé de la République Togolaise.
        Aucune connexion requise.
    </p>
@endsection

@section('contenu')
    <h2 class="text-2xl font-bold">Vérifier un bien</h2>

    <form method="POST" action="{{ route('public.rechercher') }}" class="mt-5 space-y-4">
        @csrf
        <div>
            <label for="code" class="mb-1.5 block text-sm font-semibold">Code patrimoine officiel *</label>
            <input id="code" name="code" value="{{ $code }}" required
                   placeholder="PAT-SAN-2026-0412"
                   class="w-full rounded-lg border border-gray-400 px-3 py-3 font-mono text-sm uppercase focus:border-vert focus:ring-1 focus:ring-vert">
        </div>
        <button type="submit" class="w-full rounded-lg bg-vert px-4 py-3 text-base font-bold text-white hover:bg-[#00543F]">
            Vérifier l’authenticité du bien
        </button>
    </form>

    @if (! empty($indisponible))
        <div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">▲ Service momentanément indisponible</p>
            <p class="mt-1">La vérification ne peut pas aboutir pour le moment. Réessayez dans quelques instants.</p>
        </div>
    @elseif ($trouve === true)
        <div class="mt-5 rounded-lg bg-green-50 p-4 text-sm text-gray-800">
            <p class="flex items-center gap-2 font-bold text-vert">
                <span aria-hidden="true">✓</span> Bien officiel de l’État togolais : valide
            </p>
            <p class="mt-3">Catégorie : <span class="font-semibold">{{ $bien['categorie'] }}</span></p>
            <p>Propriétaire légal : <span class="font-semibold">{{ $ministere ?? $bien['ministere_code'] }}</span></p>
            <p>Immatriculation : <span class="font-mono font-semibold">{{ $bien['immatriculation'] }}</span></p>
        </div>
        <p class="mt-3 text-xs text-gray-500">
            Cette page ne communique ni la valeur, ni le responsable, ni la localisation du bien.
        </p>
    @elseif ($trouve === false)
        <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-rouge">
            <p class="flex items-center gap-2 font-bold">
                <span aria-hidden="true">✕</span> Code non reconnu
            </p>
            <p class="mt-1">Le code <span class="font-mono">{{ $code }}</span> ne correspond à aucun bien enregistré.</p>
        </div>
    @endif

    <p class="mt-6 text-center">
        <a href="{{ route('connexion') }}" class="text-sm font-medium text-vert underline">
            Retour à l’espace de connexion des agents
        </a>
    </p>
@endsection
