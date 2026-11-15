@extends('layouts.app')

@section('titre', 'Tableau de bord')

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $valeur = $valeurTotale >= 1000000000
            ? number_format($valeurTotale / 1000000000, 1, ',', ' ').' Md'
            : number_format($valeurTotale, 0, ',', ' ').' FCFA';
        $typesMouvement = ['affectation' => 'Affectation', 'transfert' => 'Transfert', 'maintenance' => 'Maintenance', 'sortie' => 'Sortie'];
    @endphp

    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Tableau de bord</h1>
            <p class="mt-1 text-gray-600">Ministère de la Santé · données de démonstration</p>
        </div>
        @if (in_array($role, ['admin', 'comptable_matieres']))
            <a href="{{ route('biens.creer') }}" class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#00543F]">
                + Nouveau bien
            </a>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Biens enregistrés</p>
            <p class="mt-1 text-4xl font-extrabold">{{ number_format($totalBiens, 0, ',', ' ') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Valeur d’acquisition totale</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $valeur }}</p>
            <p class="text-sm text-gray-500">FCFA, hors amortissements</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <p class="text-sm text-gray-600">Taux d’inventaire</p>
            <p class="mt-1 text-4xl font-extrabold text-vert">{{ $tauxInventaire }} %</p>
            <p class="text-sm text-gray-500">{{ count($campagnes) }} campagne(s) d’inventaire</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-gray-200 bg-white px-6 py-5">
        <p class="text-sm text-gray-600">Écarts ouverts</p>
        <p class="mt-1 text-4xl font-extrabold text-rouge">{{ $ecartsOuverts }}</p>
        @if (in_array($role, ['comptable_matieres', 'controleur']))
            <a href="{{ route('ecarts.index') }}" class="mt-1 inline-block text-sm font-medium text-vert underline">Voir la liste des écarts</a>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Biens par catégorie</h2>
            <div class="mt-4 space-y-4">
                @forelse ($categories as $categorie => $nombre)
                    <div>
                        <div class="flex items-baseline justify-between text-sm">
                            <span>{{ $categorie }}</span>
                            <span class="font-semibold">{{ $nombre }}</span>
                        </div>
                        <div class="mt-1 h-2 w-full rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-vert" style="width: {{ max(4, round($nombre / $maximumCategorie * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucun bien enregistré.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Derniers mouvements</h2>
            <p class="text-sm text-gray-500">Historique en ajout seul</p>

            <table class="mt-3 w-full text-sm">
                <tbody>
                    @forelse ($mouvements as $mouvement)
                        <tr class="border-t border-gray-100">
                            <td class="py-3 pr-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($mouvement['date'])->format('d/m/Y') }}</td>
                            <td class="py-3 pr-3">
                                <a href="{{ route('biens.fiche', $mouvement['bien_id']) }}" class="font-mono text-xs font-semibold text-vert underline">
                                    {{ $mouvement['bien_code'] ?? $mouvement['bien_id'] }}
                                </a>
                            </td>
                            <td class="py-3 pr-3">
                                {{ $typesMouvement[$mouvement['type']] ?? $mouvement['type'] }}
                                <span class="block text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($mouvement['motif'], 40) }}</span>
                            </td>
                            <td class="py-3 pr-3 text-gray-600">{{ $personnes[$mouvement['auteur_id']] ?? '—' }}</td>
                            <td class="py-3 whitespace-nowrap text-xs font-semibold">
                                @php $statut = $mouvement['statut_effectif'] ?? $mouvement['statut_validation']; @endphp
                                @if ($statut === 'en_attente')
                                    <span class="text-amber-700"><span aria-hidden="true">●</span> En attente</span>
                                @elseif ($statut === 'refuse')
                                    <span class="text-rouge"><span aria-hidden="true">✕</span> Refusé</span>
                                @else
                                    <span class="text-vert"><span aria-hidden="true">✓</span> Validé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-6 text-center text-gray-500">Aucun mouvement enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
