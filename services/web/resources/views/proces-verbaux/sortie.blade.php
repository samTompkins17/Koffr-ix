@extends('layouts.impression')

@section('titre', 'PV de sortie')
@section('titre-pv', 'Procès-verbal de sortie de patrimoine')
@section('reference', 'PV-SORTIE-'.$bien['code_unique'])
@section('retour', route('proces-verbaux.index'))
@section('retour-libelle', 'Retour aux procès-verbaux')

@section('contenu')
    <h2 class="mt-6 text-base font-bold">Bien concerné</h2>
    <table class="mt-2 w-full text-sm">
        <tbody>
            <tr class="border-b border-gray-200">
                <th class="w-1/3 py-2 text-left font-semibold">Code patrimoine</th>
                <td class="py-2 font-mono">{{ $bien['code_unique'] }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Désignation</th>
                <td class="py-2">{{ $bien['designation'] }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Catégorie</th>
                <td class="py-2">{{ $bien['categorie'] }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Valeur d’acquisition</th>
                <td class="py-2">{{ number_format((int) $bien['valeur_acquisition'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Date d’acquisition</th>
                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($bien['date_acquisition'])->format('d/m/Y') }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Structure d’origine</th>
                <td class="py-2">{{ $structures[$sortie['structure_origine_id'] ?? ''] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="mt-7 text-base font-bold">Décision de sortie</h2>
    <table class="mt-2 w-full text-sm">
        <tbody>
            <tr class="border-b border-gray-200">
                <th class="w-1/3 py-2 text-left font-semibold">Date de la sortie</th>
                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($sortie['date'])->format('d/m/Y') }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Motif</th>
                <td class="py-2">{{ $sortie['motif'] ?: '—' }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Décision prise par</th>
                <td class="py-2">{{ $personnes[$sortie['auteur_id']] ?? '—' }}</td>
            </tr>
            <tr class="border-b border-gray-200">
                <th class="py-2 text-left font-semibold">Statut du bien</th>
                <td class="py-2">{{ $bien['statut'] === 'sorti' ? 'Sorti de l’inventaire actif' : 'En service' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="mt-5 text-[11px] text-gray-600">
        Le bien est retiré de l’inventaire actif. La ligne de mouvement correspondante reste en ajout seul :
        elle ne peut être ni modifiée ni supprimée. Document de démonstration, non officiel.
    </p>

    <div class="mt-10 grid grid-cols-2 gap-10 text-xs">
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
    </div>
@endsection
