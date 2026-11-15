@extends('layouts.impression')

@section('titre-pv', 'Procès-verbal de sortie de patrimoine')
@section('reference', 'PV-SORTIE-'.$bien['code_unique'])


@section('contenu')
    @php
        $fiche = [
            'Code patrimoine' => $bien['code_unique'],
            'Désignation' => $bien['designation'],
            'Catégorie' => $bien['categorie'],
            'Valeur d’acquisition' => number_format((int) $bien['valeur_acquisition'], 0, ',', "\u{00A0}").' FCFA',
            'Date d’acquisition' => \Illuminate\Support\Carbon::parse($bien['date_acquisition'])->format('d/m/Y'),
            'Structure d’origine' => $structures[$sortie['structure_origine_id'] ?? ''] ?? '—',
        ];
        $decision = [
            'Date de la sortie' => \Illuminate\Support\Carbon::parse($sortie['date'])->format('d/m/Y'),
            'Motif' => $sortie['motif'] ?: '—',
            'Décision prise par' => $personnes[$sortie['auteur_id']] ?? '—',
            'Statut du bien' => $bien['statut'] === 'sorti' ? 'Sorti de l’inventaire actif' : 'En service',
        ];
    @endphp
    @foreach (['Décision de sortie' => $decision] as $titre => $lignes)
        <h2 style="font-size: 13px; margin-top: 8px;">{{ $titre }}</h2>
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            @foreach ($lignes as $libelle => $valeur)
                <tr style="page-break-inside: avoid;">
                    <th style="width: 35%; padding: 8px; text-align: left; border-bottom: 1px solid #D1D5DB;">{{ $libelle }}</th>
                    <td style="padding: 8px; border-bottom: 1px solid #D1D5DB; overflow-wrap: break-word;">{{ $valeur }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach
    <p style="margin-top: 20px; color: #4B5563; font-size: 9px;">
        Le bien est retiré de l’inventaire actif. La ligne de mouvement correspondante reste en ajout seul :
        elle ne peut être ni modifiée ni supprimée..
    </p>
    <table style="width: 100%; margin-top: 30px; font-size: 9px; page-break-inside: avoid;">
        <tr>
            @foreach (['Le comptable des matières', 'Le responsable de la structure'] as $signataire)
                <td style="width: 50%; padding: 8px; vertical-align: top;">
                    <strong>{{ $signataire }}</strong>
                    <p>Nom, date et signature</p>
                    <p style="margin-top: 40px; border-top: 1px solid #6B7280;"></p>
                </td>
            @endforeach
        </tr>
    </table>
@endsection
