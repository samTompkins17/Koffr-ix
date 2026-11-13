@extends('layouts.app')

@section('titre', 'Écarts et rapports')

@section('contenu')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Écarts et rapports</h1>
            <p class="mt-1 text-gray-600">Différences constatées entre la fiche et le terrain. Lecture seule.</p>
        </div>
        <a href="{{ route('ecarts.export', array_filter(['campagne' => $campagneFiltree])) }}"
           class="rounded-lg border border-vert px-5 py-2.5 text-sm font-semibold text-vert hover:bg-green-50">
            Exporter en CSV
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Biens contrôlés</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $controles }} <span class="text-lg font-semibold text-gray-500">sur {{ number_format($totalBiens, 0, ',', ' ') }}</span></p>
            <p class="text-sm text-gray-500">{{ $taux }} % du parc</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Écarts ouverts</p>
            <p class="mt-1 text-3xl font-extrabold text-rouge">{{ count($ecarts) }}</p>
            <p class="text-sm text-gray-500">À traiter par le comptable des matières</p>
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
                </tr>
            </thead>
            <tbody>
                @forelse ($ecarts as $ecart)
                    @php $bien = $biens[$ecart['bien_id']] ?? null; @endphp
                    <tr class="border-b border-gray-100">
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
                        <td class="px-5 py-4 text-gray-600">{{ \Illuminate\Support\Carbon::parse($ecart['date'])->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">Aucun écart pour ce filtre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-gray-500">
        Le traitement d’un écart (régularisation, réforme) est décrit par les maquettes mais n’est pas implémenté dans cette démonstration.
    </p>
@endsection
