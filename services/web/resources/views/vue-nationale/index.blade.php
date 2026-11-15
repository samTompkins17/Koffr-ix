@extends('layouts.app')

@section('titre', 'Vue nationale')

@section('contenu')
    @php
        $enMilliards = fn (int $montant) => $montant >= 1000000000
            ? number_format($montant / 1000000000, 2, ',', ' ').' Md FCFA'
            : number_format($montant, 0, ',', ' ').' FCFA';
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Vue nationale</h1>
        <p class="mt-1 text-gray-600">
            Agrégation en lecture seule par ministère, à partir des trois services.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Biens de l’État</p>
            <p class="mt-1 text-3xl font-extrabold">{{ number_format($totalBiens, 0, ',', ' ') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Valeur d’acquisition</p>
            <p class="mt-1 text-2xl font-extrabold">{{ $enMilliards($valeurTotale) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Biens contrôlés</p>
            <p class="mt-1 text-3xl font-extrabold text-vert">{{ number_format($totalControles, 0, ',', ' ') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Écarts ouverts</p>
            <p class="mt-1 text-3xl font-extrabold text-rouge">{{ number_format($totalEcarts, 0, ',', ' ') }}</p>
        </div>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-200 px-6 py-4 text-lg font-bold">Par ministère</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-6 py-4">Ministère</th>
                        <th class="px-6 py-4 text-right">Biens</th>
                        <th class="px-6 py-4 text-right">Valeur</th>
                        <th class="px-6 py-4 text-right">Contrôlés</th>
                        <th class="px-6 py-4 text-right">Écarts</th>
                        <th class="px-6 py-4 text-right">Taux d’inventaire</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-6 py-4">
                                <p class="font-semibold">{{ $ligne['nom'] }}</p>
                                <p class="text-xs text-gray-500">Code {{ $ligne['code'] }}</p>
                            </td>
                            <td class="px-6 py-4 text-right tabular-nums">{{ number_format($ligne['biens'], 0, ',', ' ') }}</td>
                            <td class="px-6 py-4 text-right tabular-nums">{{ number_format($ligne['valeur'], 0, ',', ' ') }}</td>
                            <td class="px-6 py-4 text-right tabular-nums">{{ number_format($ligne['controles'], 0, ',', ' ') }}</td>
                            <td class="px-6 py-4 text-right tabular-nums {{ $ligne['ecarts'] > 0 ? 'font-semibold text-rouge' : '' }}">
                                {{ number_format($ligne['ecarts'], 0, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-semibold {{ $ligne['taux'] >= 70 ? 'text-vert' : ($ligne['taux'] >= 40 ? 'text-amber-700' : 'text-rouge') }}">
                                    {{ $ligne['taux'] }} %
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Aucun ministère enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <p class="mt-3 text-xs text-gray-500">
        Le taux compare les constats d’inventaire de l’année au nombre de biens rattachés, hiérarchie des structures comprise.
    </p>
@endsection
