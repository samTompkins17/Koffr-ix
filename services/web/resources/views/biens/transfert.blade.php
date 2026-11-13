@extends('layouts.app')

@section('titre', 'Affectation et transfert')

@section('contenu')
    <nav class="mb-3 text-sm text-gray-600">
        <a href="{{ route('biens.fiche', $bien['id']) }}" class="text-vert underline">{{ $bien['code_unique'] }}</a>
        <span aria-hidden="true">/</span>
        <span>Affectation et transfert</span>
    </nav>

    <h1 class="mb-1 text-2xl font-bold">Affectation et transfert</h1>
    <p class="mb-5 text-sm text-gray-600">{{ $bien['designation'] }} · {{ $bien['code_unique'] }}</p>

    <form method="POST" action="{{ route('biens.transfert.post', $bien['id']) }}"
          class="max-w-2xl space-y-4 rounded-xl border border-gray-200 bg-white p-5">
        @csrf

        <div>
            <label for="type" class="mb-1 block text-sm font-medium">Type de mouvement *</label>
            <select id="type" name="type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="transfert" @selected(old('type') === 'transfert')>Transfert (changement de ministère ou de service)</option>
                <option value="affectation" @selected(old('type') === 'affectation')>Affectation (mise à disposition)</option>
            </select>
        </div>

        <div>
            <label for="structure_destination_id" class="mb-1 block text-sm font-medium">Structure de destination *</label>
            <select id="structure_destination_id" name="structure_destination_id" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Choisir une structure —</option>
                @foreach ($toutesStructures as $structure)
                    <option value="{{ $structure['id'] }}" @selected(old('structure_destination_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="motif" class="mb-1 block text-sm font-medium">Motif *</label>
            <textarea id="motif" name="motif" rows="3" required
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                      placeholder="Réorganisation du service, nouvelle affectation…">{{ old('motif') }}</textarea>
        </div>

        <p class="rounded-lg bg-gray-50 p-3 text-xs text-gray-600">
            Le mouvement est enregistré et validé immédiatement : la structure du bien change dans le même temps.
            L’historique est en ajout seul.
        </p>

        <div class="flex gap-2 pt-1">
            <button type="submit" class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
                Enregistrer le mouvement
            </button>
            <a href="{{ route('biens.fiche', $bien['id']) }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold hover:bg-gray-50">Annuler</a>
        </div>
    </form>
@endsection
