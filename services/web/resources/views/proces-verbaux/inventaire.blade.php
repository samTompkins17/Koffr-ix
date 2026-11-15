@extends('layouts.impression')

@section('titre', 'PV d’inventaire')
@section('titre-pv', 'Procès-verbal d’inventaire physique')
@section('reference', 'PV-INV-'.substr((string) $campagne['id'], 0, 8))

@section('retour', route('proces-verbaux.index'))
@section('retour-libelle', 'Retour aux procès-verbaux')
@section('pdf-url', route('proces-verbaux.inventaire', ['campagne' => $campagne['id'], 'format' => 'pdf']))

@section('contenu')
    @php
        $etats = ['bon' => 'Bon', 'usage' => 'Usagé', 'hors_service' => 'Hors service', 'introuvable' => 'Introuvable'];
    @endphp

    <table class="mt-5 w-full text-sm">
        <tbody>
            <tr>
                <th class="w-1/3 py-1 text-left font-semibold">Campagne</th>
                <td class="py-1">{{ $campagne['nom'] }}</td>
            </tr>
            <tr>
                <th class="py-1 text-left font-semibold">Structure</th>
                <td class="py-1">{{ $structures[$campagne['structure_id']] ?? '—' }}</td>
            </tr>
            <tr>
                <th class="py-1 text-left font-semibold">Période</th>
                <td class="py-1">
                    du {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                    au {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <th class="py-1 text-left font-semibold">Statut de la campagne</th>
                <td class="py-1">{{ $campagne['statut'] === 'ouverte' ? 'Ouverte' : 'Clôturée' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="mt-6 grid grid-cols-3 gap-3 text-sm">
        <div class="rounded border border-gray-300 px-3 py-2">
            <p class="text-xs text-gray-600">Biens contrôlés</p>
            <p class="text-xl font-bold">{{ $compteurs['controles'] }}</p>
        </div>
        <div class="rounded border border-gray-300 px-3 py-2">
            <p class="text-xs text-gray-600">Écarts constatés</p>
            <p class="text-xl font-bold">{{ $compteurs['ecarts'] }}</p>
        </div>
        <div class="rounded border border-gray-300 px-3 py-2">
            <p class="text-xs text-gray-600">Biens introuvables</p>
            <p class="text-xl font-bold">{{ $compteurs['introuvables'] }}</p>
        </div>
    </div>

    <h2 class="mt-7 text-base font-bold">Constats enregistrés</h2>

    <table class="mt-2 w-full border-collapse text-xs">
        <thead>
            <tr class="border-y border-gray-400 text-left">
                <th class="py-2 pr-2">Code</th>
                <th class="py-2 pr-2">Désignation</th>
                <th class="py-2 pr-2">État fiche</th>
                <th class="py-2 pr-2">État constaté</th>
                <th class="py-2 pr-2">Écart</th>
                <th class="py-2 pr-2">Agent</th>
                <th class="py-2">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($controles as $controle)
                @php $bienControle = $biens[$controle['bien_id']] ?? null; @endphp
                <tr class="border-b border-gray-200">
                    <td class="py-1.5 pr-2 font-mono">{{ $bienControle['code_unique'] ?? $controle['bien_id'] }}</td>
                    <td class="py-1.5 pr-2">{{ $bienControle['designation'] ?? '—' }}</td>
                    <td class="py-1.5 pr-2">{{ $etats[$controle['etat_fiche']] ?? $controle['etat_fiche'] }}</td>
                    <td class="py-1.5 pr-2">{{ $etats[$controle['etat_constate']] ?? $controle['etat_constate'] }}</td>
                    <td class="py-1.5 pr-2">{{ $controle['ecart_detecte'] ? 'Oui' : 'Non' }}</td>
                    <td class="py-1.5 pr-2">{{ $personnes[$controle['agent_id']] ?? '—' }}</td>
                    <td class="py-1.5">{{ \Illuminate\Support\Carbon::parse($controle['date'])->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-4 text-center text-gray-500">Aucun constat enregistré pour cette campagne.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="mt-4 text-[11px] text-gray-600">
        Le présent procès-verbal atteste des constats physiques consignés dans la campagne ci-dessus.
        Il est établi à partir des données de démonstration et ne constitue pas un document officiel.
    </p>

    <div class="mt-10 grid grid-cols-3 gap-6 text-xs">
        <div>
            <p class="font-semibold">Le comptable des matières</p>
            <p class="mt-1 text-gray-600">Nom, date et signature</p>
            <div class="mt-8 border-t border-gray-500"></div>
        </div>
        <div>
            <p class="font-semibold">Le responsable de la structure</p>
            <p class="mt-1 text-gray-600">Nom, date et signature</p>
            <div class="mt-8 border-t border-gray-500"></div>
        </div>
        <div>
            <p class="font-semibold">Le contrôleur</p>
            <p class="mt-1 text-gray-600">Nom, date et signature</p>
            <div class="mt-8 border-t border-gray-500"></div>
        </div>
    </div>
@endsection
