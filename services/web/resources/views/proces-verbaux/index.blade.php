@extends('layouts.app')

@section('titre', 'Procès-verbaux')

@section('contenu')
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Procès-verbaux</h1>
        <p class="mt-1 text-gray-600">
            PV générés à la demande depuis les données enregistrées, puis imprimables. Rien n’est stocké.
        </p>
    </div>

    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-200 px-5 py-4 text-lg font-bold">PV d’inventaire</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                <tr class="border-b border-gray-200">
                    <th class="px-5 py-3">Campagne</th>
                    <th class="px-5 py-3">Structure</th>
                    <th class="px-5 py-3">Période</th>
                    <th class="px-5 py-3 text-right">Contrôles</th>
                    <th class="px-5 py-3 text-right">Écarts</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campagnes as $campagne)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-5 py-3 font-semibold">{{ $campagne['nom'] }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $structures[$campagne['structure_id']] ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                            → {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $campagne['controles_count'] ?? 0 }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ ($campagne['ecarts_count'] ?? 0) > 0 ? 'font-semibold text-rouge' : '' }}">
                            {{ $campagne['ecarts_count'] ?? 0 }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('proces-verbaux.inventaire', $campagne['id']) }}" class="font-semibold text-vert underline">
                                Générer le PV
                            </a>
                            <a href="{{ route('proces-verbaux.inventaire', ['campagne' => $campagne['id'], 'format' => 'pdf']) }}" class="ml-3 font-semibold text-vert underline">Télécharger PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Aucune campagne : aucun PV d’inventaire à générer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-200 px-5 py-4 text-lg font-bold">PV de sortie</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                <tr class="border-b border-gray-200">
                    <th class="px-5 py-3">Code</th>
                    <th class="px-5 py-3">Désignation</th>
                    <th class="px-5 py-3">Structure</th>
                    <th class="px-5 py-3 text-right">Valeur (FCFA)</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sortis as $bien)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-5 py-3 font-mono text-xs font-semibold">{{ $bien['code_unique'] }}</td>
                        <td class="px-5 py-3">{{ $bien['designation'] }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $structures[$bien['structure_id']] ?? '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ number_format((int) $bien['valeur_acquisition'], 0, ',', ' ') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('proces-verbaux.sortie', $bien['id']) }}" class="font-semibold text-vert underline">
                                Générer le PV
                            </a>
                            <a href="{{ route('proces-verbaux.sortie', ['bien' => $bien['id'], 'format' => 'pdf']) }}" class="ml-3 font-semibold text-vert underline">Télécharger PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">Aucun bien sorti : aucun PV de sortie à générer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
