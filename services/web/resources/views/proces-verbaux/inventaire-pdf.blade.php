@extends('layouts.impression')

@section('titre-pv', 'Procès-verbal d’inventaire physique')
@section('reference', 'PV-INV-'.substr((string) $campagne['id'], 0, 8))


@section('contenu')
    @php
        $etats = ['bon' => 'Bon', 'usage' => 'Usagé', 'hors_service' => 'Hors service', 'introuvable' => 'Introuvable'];
    @endphp
    <p><strong>Campagne :</strong> {{ $campagne['nom'] }}</p>
    <p><strong>Structure :</strong> {{ $structures[$campagne['structure_id']] ?? '—' }}</p>
    <p><strong>Période :</strong>
        du {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
        au {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
        · {{ $campagne['statut'] === 'ouverte' ? 'Ouverte' : 'Clôturée' }}
    </p>
    <p style="padding: 10px; border: 1px solid #D9DEDF;">
        Biens contrôlés : <strong>{{ $compteurs['controles'] }}</strong> ·
        Écarts constatés : <strong>{{ $compteurs['ecarts'] }}</strong> ·
        Biens introuvables : <strong>{{ $compteurs['introuvables'] }}</strong>
    </p>
    <h2 style="font-size: 13px; margin-top: 20px;">Constats enregistrés</h2>
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8px;">
        <thead style="display: table-header-group;">
            <tr>
                @foreach (['Code', 'Désignation', 'État fiche', 'État constaté', 'Écart', 'Agent', 'Date'] as $titre)
                    <th style="padding: 5px; border-bottom: 1px solid #6B7280; text-align: left;">{{ $titre }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($controles as $controle)
                @php
                    $bienControle = $biens[$controle['bien_id']];
                    $cellules = [
                        $bienControle['code_unique'], $bienControle['designation'],
                        $etats[$controle['etat_fiche']] ?? $controle['etat_fiche'],
                        $etats[$controle['etat_constate']] ?? $controle['etat_constate'],
                        $controle['ecart_detecte'] ? 'Oui' : 'Non',
                        $personnes[$controle['agent_id']] ?? '—',
                        \Illuminate\Support\Carbon::parse($controle['date'])->format('d/m/Y'),
                    ];
                @endphp
                <tr style="page-break-inside: avoid;">
                    @foreach ($cellules as $cellule)
                        <td style="padding: 5px; border: 1px solid #D1D5DB; vertical-align: top; overflow-wrap: break-word;">{{ $cellule }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="7" style="padding: 12px; text-align: center;">Aucun constat enregistré pour cette campagne.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p style="margin-top: 16px; color: #4B5563; font-size: 9px;">
        Le présent procès-verbal atteste des constats physiques consignés dans la campagne ci-dessus.
        Il est établi à partir des données de démonstration et ne constitue pas un document officiel.
    </p>
    <table style="width: 100%; margin-top: 30px; font-size: 9px; page-break-inside: avoid;">
        <tr>
            @foreach (['Le comptable des matières', 'Le responsable de la structure', 'Le contrôleur'] as $signataire)
                <td style="width: 33%; padding: 6px; vertical-align: top;">
                    <strong>{{ $signataire }}</strong>
                    <p>Nom, date et signature</p>
                    <p style="margin-top: 40px; border-top: 1px solid #6B7280;"></p>
                </td>
            @endforeach
        </tr>
    </table>
@endsection
