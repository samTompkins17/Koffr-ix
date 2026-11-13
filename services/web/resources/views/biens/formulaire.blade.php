@extends('layouts.app')

@section('titre', $bien ? 'Modifier un bien' : 'Nouveau bien')

@section('contenu')
    @php
        $modification = $bien !== null;
        $valeur = fn ($champ, $defaut = '') => old($champ, $bien[$champ] ?? $defaut);
    @endphp

    <nav class="mb-3 text-sm text-gray-600">
        <a href="{{ route('biens.index') }}" class="text-vert underline">Biens</a>
        <span aria-hidden="true">/</span>
        <span>{{ $modification ? 'Modifier' : 'Nouveau bien' }}</span>
    </nav>

    <h1 class="mb-5 text-2xl font-bold">{{ $modification ? 'Modifier le bien' : 'Nouveau bien' }}</h1>

    <form method="POST"
          action="{{ $modification ? route('biens.mettre-a-jour', $bien['id']) : route('biens.enregistrer') }}"
          class="max-w-3xl space-y-4 rounded-xl border border-gray-200 bg-white p-5">
        @csrf
        @if ($modification)
            @method('PUT')
        @endif

        @if ($modification)
            <div>
                <label class="mb-1 block text-sm font-medium">Code patrimoine</label>
                <input value="{{ $bien['code_unique'] }}" readonly
                       class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-sm text-gray-600">
                <p class="mt-1 text-xs text-gray-500">Le code patrimoine est définitif : il ne peut pas être modifié.</p>
            </div>
        @endif

        <div>
            <label for="designation" class="mb-1 block text-sm font-medium">Désignation *</label>
            <input id="designation" name="designation" required value="{{ $valeur('designation') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="categorie" class="mb-1 block text-sm font-medium">Catégorie *</label>
                <select id="categorie" name="categorie" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (config('koffr.categories') as $categorie)
                        <option value="{{ $categorie }}" @selected($valeur('categorie') === $categorie)>{{ $categorie }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="structure_id" class="mb-1 block text-sm font-medium">Structure de rattachement *</label>
                <select id="structure_id" name="structure_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($toutesStructures as $structure)
                        <option value="{{ $structure['id'] }}" @selected($valeur('structure_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="valeur_acquisition" class="mb-1 block text-sm font-medium">Valeur d’acquisition (FCFA) *</label>
                <input id="valeur_acquisition" name="valeur_acquisition" type="number" min="0" step="1" required
                       value="{{ $valeur('valeur_acquisition', 0) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="date_acquisition" class="mb-1 block text-sm font-medium">Date d’acquisition *</label>
                <input id="date_acquisition" name="date_acquisition" type="date" required
                       value="{{ substr((string) $valeur('date_acquisition'), 0, 10) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="etat" class="mb-1 block text-sm font-medium">État *</label>
                <select id="etat" name="etat" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['bon' => '✓ Bon', 'usage' => '▲ Usagé', 'hors_service' => '● Hors service'] as $code => $libelle)
                        <option value="{{ $code }}" @selected($valeur('etat', 'bon') === $code)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="localisation" class="mb-1 block text-sm font-medium">Localisation</label>
                <input id="localisation" name="localisation" value="{{ $valeur('localisation') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Bureau 2, Services généraux">
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
                {{ $modification ? 'Enregistrer les modifications' : 'Créer le bien' }}
            </button>
            <a href="{{ $modification ? route('biens.fiche', $bien['id']) : route('biens.index') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold hover:bg-gray-50">Annuler</a>
        </div>
    </form>
@endsection
