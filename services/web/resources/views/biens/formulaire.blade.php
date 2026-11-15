@extends('layouts.app')

@section('titre', $bien ? 'Modifier un bien' : 'Nouveau bien')

@section('contenu')
    @php
        $modification = $bien !== null;
        $valeur = fn ($champ, $defaut = '') => old($champ, $bien[$champ] ?? $defaut);
        $champs = ['designation' => 'Désignation', 'categorie' => 'Catégorie', 'structure_id' => 'Structure',
                   'valeur_acquisition' => 'Valeur d’acquisition', 'date_acquisition' => 'Date d’acquisition', 'etat' => 'État'];
        $enErreur = collect($champs)->filter(fn ($libelle, $champ) => $errors->has($champ))->values();
    @endphp

    <a href="{{ $modification ? route('biens.fiche', $bien['id']) : route('biens.index') }}" class="text-sm font-medium text-vert underline">
        ← Retour à la liste des biens
    </a>

    <h1 class="mt-2 text-3xl font-extrabold">{{ $modification ? 'Modifier le bien' : 'Nouveau bien' }}</h1>
    <p class="mt-1 text-gray-600">
        Les champs marqués d’une étoile sont obligatoires.
        {{ $modification ? 'Le code patrimoine reste définitif.' : 'Le code patrimoine est généré à l’enregistrement.' }}
    </p>

    @if ($enErreur->isNotEmpty())
        <div class="mt-5 rounded-lg bg-red-50 px-5 py-3 text-sm font-semibold text-rouge">
            {{ $enErreur->count() }} champ(s) à corriger avant d’enregistrer : {{ $enErreur->join(', ') }}.
        </div>
    @endif

    <form method="POST"
          action="{{ $modification ? route('biens.mettre-a-jour', $bien['id']) : route('biens.enregistrer') }}"
          class="mt-5 grid gap-6 lg:grid-cols-[2fr_1fr]">
        @csrf
        @if ($modification)
            @method('PUT')
        @endif

        <div class="rounded-xl border border-gray-200 bg-white px-6 py-6">
            <div>
                <label for="designation" class="mb-1.5 block text-sm font-semibold">Désignation *</label>
                <input id="designation" name="designation" required value="{{ $valeur('designation') }}"
                       class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $errors->has('designation') ? 'border-rouge' : 'border-gray-400' }}">
                @error('designation')
                    <p class="mt-1 text-sm text-rouge">Saisissez la désignation du bien.</p>
                @enderror
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="categorie" class="mb-1.5 block text-sm font-semibold">Catégorie *</label>
                    <select id="categorie" name="categorie" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        @foreach (config('koffr.categories') as $categorie)
                            <option value="{{ $categorie }}" @selected($valeur('categorie') === $categorie)>{{ $categorie }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="structure_id" class="mb-1.5 block text-sm font-semibold">Structure *</label>
                    <select id="structure_id" name="structure_id" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        @foreach ($toutesStructures as $structure)
                            <option value="{{ $structure['id'] }}" @selected($valeur('structure_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="valeur_acquisition" class="mb-1.5 block text-sm font-semibold">Valeur d’acquisition (FCFA) *</label>
                    <input id="valeur_acquisition" name="valeur_acquisition" type="number" min="0" step="1" required
                           value="{{ $valeur('valeur_acquisition', 0) }}"
                           class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $errors->has('valeur_acquisition') ? 'border-rouge' : 'border-gray-400' }}">
                    @error('valeur_acquisition')
                        <p class="mt-1 text-sm text-rouge">Entrez un montant en chiffres, par exemple 450 000.</p>
                    @enderror
                </div>
                <div>
                    <label for="date_acquisition" class="mb-1.5 block text-sm font-semibold">Date d’acquisition *</label>
                    <input id="date_acquisition" name="date_acquisition" type="date" required
                           value="{{ substr((string) $valeur('date_acquisition'), 0, 10) }}"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                </div>
            </div>

            <div class="mt-4">
                <label for="etat" class="mb-1.5 block text-sm font-semibold">État *</label>
                <select id="etat" name="etat" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @foreach (['bon' => '✓ Bon', 'usage' => '▲ Usagé', 'hors_service' => '● Hors service'] as $code => $libelle)
                        <option value="{{ $code }}" @selected($valeur('etat', 'bon') === $code)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4">
                <label for="localisation" class="mb-1.5 block text-sm font-semibold">Localisation</label>
                <input id="localisation" name="localisation" value="{{ $valeur('localisation') }}"
                       placeholder="Bâtiment, étage, bureau ou coordonnées GPS"
                       class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
            </div>

            @if ($modification)
                <div class="mt-4">
                    <p class="mb-1.5 text-sm font-semibold">Code patrimoine</p>
                    <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 font-mono text-sm text-gray-600">{{ $bien['code_unique'] }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-vert px-6 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
                    {{ $modification ? 'Enregistrer les modifications' : 'Enregistrer le bien' }}
                </button>
                <a href="{{ $modification ? route('biens.fiche', $bien['id']) : route('biens.index') }}"
                   class="rounded-lg border border-vert px-6 py-3 text-sm font-semibold text-vert hover:bg-green-50">Annuler</a>
            </div>
        </div>

        <aside class="rounded-xl border border-gray-200 bg-white px-6 py-6">
            <h2 class="text-lg font-bold">Aperçu de l’étiquette</h2>
            <div class="mt-4 rounded-lg border border-dashed border-gray-400 px-4 py-8 text-center">
                <p class="text-sm text-gray-500">QR code généré à l’enregistrement</p>
                <p class="mt-1 font-mono text-base font-bold">{{ $modification ? $bien['code_unique'] : 'PAT-SAN-2026-XXXX' }}</p>
            </div>
            <p class="mt-3 text-sm text-gray-600">Le code ne pourra plus être modifié ensuite.</p>
        </aside>
    </form>
@endsection
