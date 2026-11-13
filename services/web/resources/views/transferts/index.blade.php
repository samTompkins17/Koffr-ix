@extends('layouts.app')

@section('titre', 'Affectations et transferts')

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $typeChoisi = old('type', request()->query('type', 'transfert'));
        $types = [
            'affectation' => 'Affectation',
            'transfert' => 'Transfert',
            'maintenance' => 'Maintenance',
            'sortie' => 'Sortie',
        ];
        $libellesTypes = $types;
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Affectations et transferts</h1>
        <p class="mt-1 text-gray-600">Chaque mouvement est ajouté à l’historique du bien avec sa date et son auteur.</p>
    </div>

    @if (count($enAttente) > 0)
        <section class="mb-6 overflow-hidden rounded-xl border border-jaune bg-white">
            <div class="border-b border-gray-200 bg-yellow-50 px-6 py-4">
                <h2 class="text-lg font-bold">En attente de validation ({{ count($enAttente) }})</h2>
                <p class="text-sm text-gray-700">
                    La structure du bien ne change qu’après la décision du responsable de service.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-3">Bien</th>
                            <th class="px-6 py-3">Mouvement</th>
                            <th class="px-6 py-3">Motif</th>
                            <th class="px-6 py-3">Demandé par</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Décision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enAttente as $mouvement)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4">
                                    <a href="{{ route('biens.fiche', $mouvement['bien_id']) }}" class="font-mono text-xs font-semibold text-vert underline">
                                        {{ $mouvement['bien_code'] ?? $mouvement['bien_id'] }}
                                    </a>
                                    <span class="block">{{ $mouvement['bien_designation'] ?? '' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold">{{ $libellesTypes[$mouvement['type']] ?? $mouvement['type'] }}</span>
                                    <span class="block text-xs text-gray-600">
                                        {{ $structures[$mouvement['structure_origine_id'] ?? ''] ?? '—' }}
                                        → {{ $structures[$mouvement['structure_destination_id'] ?? ''] ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $mouvement['motif'] }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $personnes[$mouvement['auteur_id']] ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ \Illuminate\Support\Carbon::parse($mouvement['date'])->format('d/m/Y') }}</td>
                                <td class="px-6 py-4">
                                    @if ($role === 'responsable_service')
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('transferts.valider', $mouvement['id']) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-vert px-3 py-2 text-xs font-semibold text-white hover:bg-[#00543F]">
                                                    Valider
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('transferts.refuser', $mouvement['id']) }}">
                                                @csrf
                                                <input type="hidden" name="motif" value="Refusé par le responsable de service">
                                                <button type="submit" class="rounded-lg border border-rouge px-3 py-2 text-xs font-semibold text-rouge hover:bg-red-50">
                                                    Refuser
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="flex justify-end text-xs font-semibold text-amber-700">● En attente</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

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
                Une affectation ou un transfert attend la validation du responsable : la structure du bien ne change qu’à ce moment-là.
                Une sortie et une maintenance sont enregistrées immédiatement.
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
                <li>✓ Une affectation ou un transfert passe en attente de validation.</li>
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
