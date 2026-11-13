@extends('layouts.app')

@section('titre', 'Utilisateurs, rôles et structures')

@section('contenu')
    @php
        $onglets = ['utilisateurs' => 'Utilisateurs', 'roles' => 'Rôles', 'structures' => 'Structures'];
        $colonnes = [
            'admin' => 'Admin',
            'comptable_matieres' => 'Comptable matières',
            'agent_patrimoine' => 'Agent de patrimoine',
            'responsable_service' => 'Responsable de service',
            'controleur' => 'Contrôleur',
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Utilisateurs, rôles et structures</h1>
            <p class="mt-1 text-gray-600">Réservé à l’administrateur. Les agents se limitent à l’identité professionnelle.</p>
        </div>

        {{-- Création de comptes : maquette seulement (AGENTS.md §2). --}}
        <button type="button" disabled aria-disabled="true" title="Fonction de maquette, non implémentée"
                class="cursor-not-allowed rounded-lg bg-vert/60 px-5 py-2.5 text-sm font-semibold text-white">
            + Créer un compte
        </button>
    </div>

    <nav class="mb-5 flex flex-wrap gap-6 border-b border-gray-300 text-sm" aria-label="Sections de l’administration">
        @foreach ($onglets as $code => $libelle)
            <a href="{{ route('admin.index', ['onglet' => $code]) }}"
               class="-mb-px border-b-2 px-1 pb-3 {{ $onglet === $code ? 'border-vert font-semibold text-vert' : 'border-transparent text-gray-600 hover:text-vert' }}"
               @if ($onglet === $code) aria-current="page" @endif>
                {{ $libelle }}
            </a>
        @endforeach
    </nav>

    @if ($onglet === 'utilisateurs')
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Nom</th>
                            <th class="px-6 py-4">Rôle</th>
                            <th class="px-6 py-4">Structure</th>
                            <th class="px-6 py-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comptes as $compte)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4 font-semibold">{{ $compte['name'] }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $roles[$compte['role']] ?? $compte['role'] }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $compte['structure_nom'] ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($compte['actif'])
                                        <span class="inline-flex items-center gap-1 font-semibold text-vert"><span aria-hidden="true">✓</span> Actif</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-rouge"><span aria-hidden="true">✕</span> Désactivé</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Aucun compte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($onglet === 'roles')
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Fonctionnalité</th>
                            @foreach ($colonnes as $libelle)
                                <th class="px-4 py-4 text-center">{{ $libelle }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matrice as $ligne)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4">
                                    {{ $ligne['fonctionnalite'] }}@if ($ligne['maquette'])<span class="text-gray-400" title="Droit prévu par la maquette, écran non codé"> †</span>@endif
                                </td>
                                @foreach (array_keys($colonnes) as $role)
                                    @php $droit = $ligne['droits'][$role] ?? false; @endphp
                                    <td class="px-4 py-4 text-center">
                                        @if ($droit === true)
                                            <span class="font-semibold text-vert"><span aria-hidden="true">✓</span><span class="sr-only">autorisé</span></span>
                                        @elseif ($droit === 'lecture')
                                            <span class="font-semibold text-vert">Lecture</span>
                                        @else
                                            <span class="text-gray-400" aria-label="non autorisé">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="mt-3 text-xs text-gray-500">
            † Droit prévu par les maquettes, dont l’écran n’est pas codé dans cette démonstration.
        </p>
    @endif

    @if ($onglet === 'structures')
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Structure et niveau</th>
                            <th class="px-6 py-4 text-right">Biens rattachés</th>
                            <th class="px-6 py-4">Responsable</th>
                            <th class="px-6 py-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($structures as $structure)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4">
                                    <p class="font-semibold">{{ $structure['nom'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $structure['type'] ?: 'Structure' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums">
                                    {{ number_format((int) ($parStructure[$structure['id']] ?? 0), 0, ',', ' ') }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $responsables[$structure['id']] ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 font-semibold text-vert"><span aria-hidden="true">✓</span> Active</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Aucune structure.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
