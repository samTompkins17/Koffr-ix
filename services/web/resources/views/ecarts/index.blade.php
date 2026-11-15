@extends('layouts.app')

@section('titre', 'Écarts et rapports')

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $peutTraiter = $role === 'comptable_matieres';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Écarts et rapports</h1>
            <p class="mt-1 text-gray-600">Différences constatées entre la fiche et le terrain. Le contrôle reste en ajout seul.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('ecarts.export-pdf', array_filter(['campagne' => $campagneFiltree])) }}" class="rounded-lg border border-vert px-4 py-2.5 text-sm font-semibold text-vert hover:bg-green-50">Exporter en PDF</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Biens contrôlés</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $controles }} <span class="text-lg font-semibold text-gray-500">sur {{ number_format($totalBiens, 0, ',', ' ') }}</span></p>
            <p class="text-sm text-gray-500">{{ $taux }} % du parc</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Écarts ouverts</p>
            <p class="mt-1 text-3xl font-extrabold text-rouge">{{ collect($ecarts)->whereNull('traitement')->count() }}</p>
            <p class="text-sm text-gray-500">Sans décision documentée</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Écarts traités</p>
            <p class="mt-1 text-3xl font-extrabold text-vert">{{ collect($ecarts)->whereNotNull('traitement')->count() }}</p>
            <p class="text-sm text-gray-500">Décision du comptable</p>
        </div>
    </div>

    <form method="GET" action="{{ route('ecarts.index') }}" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4">
        <div class="min-w-[260px]">
            <label for="campagne" class="mb-1.5 block text-sm font-semibold">Campagne</label>
            <select id="campagne" name="campagne" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                <option value="">Toutes les campagnes</option>
                @foreach ($campagnes as $campagne)
                    <option value="{{ $campagne['id'] }}" @selected($campagneFiltree === $campagne['id'])>{{ $campagne['nom'] }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                <tr class="border-b border-gray-200">
                    <th class="px-5 py-4">Bien</th>
                    <th class="px-5 py-4">Désignation</th>
                    <th class="px-5 py-4">État fiche</th>
                    <th class="px-5 py-4">État constaté</th>
                    <th class="px-5 py-4">Agent</th>
                    <th class="px-5 py-4">Date</th>
                    <th class="px-5 py-4">Traitement</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ecarts as $ecart)
                    @php $bien = $biens[$ecart['bien_id']] ?? null; @endphp
                    <tr class="border-b border-gray-100 align-top">
                        <td class="px-5 py-4 font-mono text-xs">
                            @if ($bien)
                                <a href="{{ route('biens.fiche', $bien['id']) }}" class="font-semibold text-vert underline">{{ $bien['code_unique'] }}</a>
                            @else
                                {{ $ecart['bien_id'] }}
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold">{{ $bien['designation'] ?? '—' }}</td>
                        <td class="px-5 py-4">@include('partials.badge-etat', ['etat' => $ecart['etat_fiche']])</td>
                        <td class="px-5 py-4">@include('partials.badge-etat', ['etat' => $ecart['etat_constate']])</td>
                        <td class="px-5 py-4 text-gray-700">{{ $personnes[$ecart['agent_id']] ?? '—' }}</td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-600">{{ \Illuminate\Support\Carbon::parse($ecart['date'])->format('d/m/Y') }}</td>
                        <td class="px-5 py-4">
                            @if ($ecart['traitement'] ?? null)
                                <p class="font-semibold text-vert">
                                    <span aria-hidden="true">✓</span> {{ $actions[$ecart['traitement']['action']] ?? $ecart['traitement']['action'] }}
                                </p>
                                <p class="mt-1 max-w-[280px] text-xs text-gray-600">{{ $ecart['traitement']['commentaire'] }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $personnes[$ecart['traitement']['auteur_id']] ?? '—' }} ·
                                    {{ \Illuminate\Support\Carbon::parse($ecart['traitement']['date'])->format('d/m/Y') }}
                                </p>
                            @elseif ($peutTraiter)
                                <details class="max-w-[300px]">
                                    <summary class="cursor-pointer font-semibold text-vert underline">Traiter l’écart</summary>
                                    <form method="POST" action="{{ route('ecarts.traiter', $ecart['id']) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <label for="action-{{ $ecart['id'] }}" class="block text-xs font-semibold">Décision *</label>
                                        <select id="action-{{ $ecart['id'] }}" name="action" required class="w-full rounded border border-gray-400 px-2 py-1.5 text-xs">
                                            @foreach ($actions as $code => $libelle)
                                                <option value="{{ $code }}">{{ $libelle }}</option>
                                            @endforeach
                                        </select>
                                        <label for="commentaire-{{ $ecart['id'] }}" class="block text-xs font-semibold">Commentaire *</label>
                                        <textarea id="commentaire-{{ $ecart['id'] }}" name="commentaire" rows="2" required
                                                  class="w-full rounded border border-gray-400 px-2 py-1.5 text-xs"
                                                  placeholder="Décision prise, pièces justificatives…"></textarea>
                                        <button type="submit" class="w-full rounded bg-vert px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#00543F]">
                                            Enregistrer la décision
                                        </button>
                                    </form>
                                </details>
                            @else
                                <span class="text-xs font-semibold text-amber-700">● Non traité</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">Aucun écart pour ce filtre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-gray-500">
        Un écart traité reste visible : la décision est ajoutée au dossier, le constat d’origine n’est jamais modifié.
    </p>
@endsection
