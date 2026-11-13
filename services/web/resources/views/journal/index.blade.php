@extends('layouts.app')

@section('titre', 'Journal d’audit')

@php
    $libellesServices = [
        'auth' => 'Service authentification (connexions, comptes)',
        'patrimoine' => 'Service patrimoine (biens, mouvements)',
        'inventaire' => 'Service inventaire (campagnes, contrôles)',
    ];
    $libellesActions = [
        'connexion' => 'Connexion',
        'deconnexion' => 'Déconnexion',
        'acces_refuse' => 'Accès refusé',
        'creation_compte' => 'Création de compte',
        'bien_cree' => 'Bien créé',
        'bien_modifie' => 'Bien modifié',
        'mouvement_transfert' => 'Transfert',
        'mouvement_affectation' => 'Affectation',
        'mouvement_maintenance' => 'Maintenance',
        'mouvement_sortie' => 'Sortie',
        'controle_enregistre' => 'Contrôle enregistré',
        'campagne_ouverte' => 'Campagne ouverte',
    ];
@endphp

@section('contenu')
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Journal d’audit</h1>
        <p class="mt-1 text-gray-600">
            Traçabilité en ajout seul. Chaque service tient son propre journal ; ils ne sont jamais fusionnés.
        </p>
    </div>

    <form method="GET" action="{{ route('journal.index') }}" class="mb-6 flex gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4">
        <div class="flex-1">
            <label for="action" class="mb-1.5 block text-sm font-semibold">Filtrer par action</label>
            <input id="action" name="action" value="{{ $filtre }}" placeholder="Ex. : connexion, transfert"
                   class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
        </div>
        <button type="submit" class="mt-6 rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#00543F]">
            Filtrer
        </button>
    </form>

    @foreach ($entrees as $service => $liste)
        <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <h2 class="border-b border-gray-200 px-5 py-4 text-sm font-bold uppercase tracking-wide text-gray-600">
                {{ $libellesServices[$service] }} ({{ count($liste) }})
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Auteur</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Objet</th>
                            <th class="px-5 py-3">Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($liste as $entree)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-600">
                                    {{ \Carbon\Carbon::parse($entree['date'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-3 font-semibold">{{ $auteurs[$entree['utilisateur_id']] ?? 'Système' }}</td>
                                <td class="px-5 py-3">
                                    <span class="font-semibold text-vert">{{ $libellesActions[$entree['action']] ?? $entree['action'] }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $entree['objet'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $entree['detail'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">Aucune entrée {{ $filtre ? 'pour ce filtre' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endsection
