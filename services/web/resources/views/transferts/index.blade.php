@extends('layouts.app')

@section('titre', 'Affectations et transferts')

@section('contenu')
    @php
        $typeChoisi = old('type', request()->query('type', 'transfert'));
        $types = [
            'affectation' => 'Affectation',
            'transfert' => 'Transfert',
            'maintenance' => 'Maintenance',
            'sortie' => 'Sortie',
        ];
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Affectations et transferts</h1>
        <p class="mt-1 text-gray-600">Chaque mouvement est ajouté à l’historique du bien avec sa date et son auteur.</p>
    </div>

    <form method="GET" action="{{ route('transferts.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white px-6 py-5">
        <label for="bien_choisi" class="mb-1.5 block text-sm font-semibold">Bien concerné *</label>
        <select id="bien_choisi" name="bien" onchange="this.form.submit()" required
                class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
            <option value="">Choisir un bien…</option>
            @foreach ($biens as $bien)
                <option value="{{ $bien['id'] }}" @selected(($bienSelectionne['id'] ?? null) === $bien['id'])>
                    {{ $bien['code_unique'] }} · {{ $bien['designation'] }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($bienSelectionne)
        <div class="mb-4 rounded-lg border border-gray-300 bg-gray-50 px-5 py-3 text-sm">
            <span class="font-semibold">Bien concerné :</span>
            {{ $bienSelectionne['designation'] }} ({{ $bienSelectionne['code_unique'] }}) ·
            {{ $structures[$bienSelectionne['structure_id']] ?? '—' }} ·
            {{ number_format((int) $bienSelectionne['valeur_acquisition'], 0, ',', ' ') }} FCFA
        </div>
    @endif

    <form method="POST" action="{{ route('transferts.store') }}" class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        @csrf
        <input type="hidden" name="bien_id" value="{{ $bienSelectionne['id'] ?? '' }}">

        <div class="rounded-xl border border-gray-200 bg-white px-6 py-6">
            <fieldset>
                <legend class="mb-2 text-sm font-semibold">Type de mouvement *</legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($types as $code => $libelle)
                        <label class="cursor-pointer rounded-lg border px-3 py-3 text-center text-sm font-semibold has-[:checked]:border-vert has-[:checked]:bg-green-50 has-[:checked]:text-vert {{ $typeChoisi === $code ? 'border-gray-400' : 'border-gray-300' }}">
                            <input type="radio" name="type" value="{{ $code }}" class="sr-only" @checked($typeChoisi === $code)>
                            {{ $libelle }}
                        </label>
                    @endforeach
                </div>
                @error('type')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
            </fieldset>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="structure_destination_id" class="mb-1.5 block text-sm font-semibold">Structure de destination</label>
                    <select id="structure_destination_id" name="structure_destination_id"
                            class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        <option value="">— Aucune (maintenance sur place) —</option>
                        @foreach ($toutesStructures as $structure)
                            <option value="{{ $structure['id'] }}" @selected(old('structure_destination_id', $bienSelectionne['structure_id'] ?? null) === $structure['id'])>
                                {{ $structure['nom'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('structure_destination_id')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="motif" class="mb-1.5 block text-sm font-semibold">Motif</label>
                    <input id="motif" name="motif" value="{{ old('motif') }}"
                           placeholder="Réaffectation du service, mise au rebut…"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('motif')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-gray-800">
                Le mouvement est enregistré immédiatement et la structure du bien est mise à jour. Une sortie passe le bien en « sorti ».
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" @disabled(! $bienSelectionne)
                        class="rounded-lg bg-vert px-6 py-3 text-sm font-bold text-white hover:bg-[#00543F] disabled:cursor-not-allowed disabled:bg-gray-300">
                    Enregistrer le mouvement
                </button>
                @if ($bienSelectionne)
                    <a href="{{ route('biens.fiche', $bienSelectionne['id']) }}"
                       class="rounded-lg border border-vert px-6 py-3 text-sm font-semibold text-vert hover:bg-green-50">Annuler</a>
                @endif
            </div>
        </div>

        <aside class="rounded-xl border border-gray-200 bg-white px-6 py-6 text-sm">
            <h2 class="text-lg font-bold">Après enregistrement</h2>
            <ul class="mt-3 space-y-2 text-gray-700">
                <li>✓ Une ligne d’historique datée et signée est ajoutée au bien.</li>
                <li>✓ La structure du bien change pour une affectation ou un transfert.</li>
                <li>✓ Une sortie retire le bien de l’inventaire actif.</li>
                <li>✓ Aucune ligne ne peut être supprimée ni modifiée.</li>
            </ul>

            @if ($bienSelectionne)
                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-gray-500">Étiquette</p>
                    <a href="{{ route('biens.etiquette', $bienSelectionne['id']) }}" class="font-semibold text-vert underline">
                        Imprimer le QR code
                    </a>
                </div>
            @endif
        </aside>
    </form>
@endsection
