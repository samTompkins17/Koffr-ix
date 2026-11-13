@extends('layouts.app')

@section('titre', 'Ouvrir une campagne')

@section('contenu')
    <nav class="mb-3 text-sm text-gray-600">
        <a href="{{ route('campagnes.index') }}" class="text-vert underline">Campagnes</a>
        <span aria-hidden="true">/</span>
        <span>Ouvrir</span>
    </nav>

    <h1 class="mb-5 text-2xl font-bold">Ouvrir une campagne d’inventaire</h1>

    <form method="POST" action="{{ route('campagnes.enregistrer') }}"
          class="max-w-2xl space-y-4 rounded-xl border border-gray-200 bg-white p-5">
        @csrf

        <div>
            <label for="nom" class="mb-1 block text-sm font-medium">Nom de la campagne *</label>
            <input id="nom" name="nom" required value="{{ old('nom') }}" placeholder="Inventaire 2026"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="date_debut" class="mb-1 block text-sm font-medium">Date de début *</label>
                <input id="date_debut" name="date_debut" type="date" required value="{{ old('date_debut') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="date_fin" class="mb-1 block text-sm font-medium">Date de fin *</label>
                <input id="date_fin" name="date_fin" type="date" required value="{{ old('date_fin') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label for="structure_id" class="mb-1 block text-sm font-medium">Structure concernée *</label>
            <select id="structure_id" name="structure_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach ($toutesStructures as $structure)
                    <option value="{{ $structure['id'] }}" @selected(old('structure_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2 pt-1">
            <button type="submit" class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
                Ouvrir la campagne
            </button>
            <a href="{{ route('campagnes.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold hover:bg-gray-50">Annuler</a>
        </div>
    </form>
@endsection
