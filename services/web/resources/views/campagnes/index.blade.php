@extends('layouts.app')

@section('titre', 'Inventaire')

@section('contenu')
    @php $role = session('utilisateur.role'); @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Inventaire</h1>
        <p class="mt-1 text-gray-600">Campagnes de recensement physique des biens, par structure.</p>
    </div>

    <div class="space-y-4">
        @forelse ($campagnes as $campagne)
            @php
                $progression = min(100, (int) round(($campagne['controles_count'] ?? 0) / $totalBiens * 100));
                $enCours = $campagne['statut'] === 'en_cours';
            @endphp
            <a href="{{ route('campagnes.fiche', $campagne['id']) }}"
               class="block rounded-xl border border-gray-200 bg-white px-6 py-5 hover:border-vert">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-bold">{{ $campagne['nom'] }}</p>
                        <p class="text-sm text-gray-600">
                            {{ $structures[$campagne['structure_id']] ?? '—' }} ·
                            {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                            → {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold {{ $enCours ? 'border-jaune bg-yellow-50 text-gray-800' : 'border-vert/40 bg-green-50 text-vert' }}">
                            <span aria-hidden="true">{{ $enCours ? '●' : '✓' }}</span> {{ $enCours ? 'En cours' : 'Clôturée' }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-rouge/30 bg-red-50 px-3 py-1 text-xs font-semibold text-rouge">
                            <span aria-hidden="true">✕</span> {{ $campagne['ecarts_count'] ?? 0 }} écart(s)
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="h-2.5 w-full rounded-full bg-gray-200">
                        <div class="h-2.5 rounded-full {{ $enCours ? 'bg-vert' : 'bg-gray-500' }}" style="width: {{ max(2, $progression) }}%"></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $campagne['controles_count'] ?? 0 }} bien(s) contrôlé(s) · {{ $progression }} % du parc
                    </p>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-10 text-center text-gray-500">
                Aucune campagne d’inventaire n’a encore été ouverte.
            </div>
        @endforelse
    </div>

    @if ($role === 'comptable_matieres')
        <section class="mt-6 rounded-xl border border-gray-200 bg-white px-6 py-6">
            <h2 class="text-lg font-bold">Ouvrir une campagne</h2>
            <p class="text-sm text-gray-600">Les agents ne peuvent contrôler que les biens pendant la période ouverte.</p>

            <form method="POST" action="{{ route('campagnes.enregistrer') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label for="nom" class="mb-1.5 block text-sm font-semibold">Nom de la campagne *</label>
                    <input id="nom" name="nom" required value="{{ old('nom') }}" placeholder="Inventaire 2026"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('nom')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_debut" class="mb-1.5 block text-sm font-semibold">Date de début *</label>
                    <input id="date_debut" name="date_debut" type="date" required value="{{ old('date_debut') }}"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('date_debut')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_fin" class="mb-1.5 block text-sm font-semibold">Date de fin *</label>
                    <input id="date_fin" name="date_fin" type="date" required value="{{ old('date_fin') }}"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('date_fin')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="structure_id" class="mb-1.5 block text-sm font-semibold">Structure concernée *</label>
                    <select id="structure_id" name="structure_id" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        @foreach ($toutesStructures as $structure)
                            <option value="{{ $structure['id'] }}" @selected(old('structure_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                        @endforeach
                    </select>
                    @error('structure_id')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-vert px-6 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
                        Ouvrir la campagne
                    </button>
                </div>
            </form>
        </section>
    @endif
@endsection
