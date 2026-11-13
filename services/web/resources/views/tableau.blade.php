@extends('layouts.app')

@section('titre', 'Tableau de bord')

@section('contenu')
    @php $role = session('utilisateur.role'); @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Tableau de bord</h1>
            <p class="text-sm text-gray-600">Patrimoine de l’État · ministère de la Santé</p>
        </div>
        @if (in_array($role, ['admin', 'comptable_matieres']))
            <a href="{{ route('biens.creer') }}" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                + Nouveau bien
            </a>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Biens enregistrés</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">En service</p>
            <p class="mt-1 text-3xl font-extrabold text-vert">{{ $stats['en_service'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Valeur totale</p>
            <p class="mt-1 text-xl font-extrabold">{{ number_format($stats['valeur_totale'] ?? 0, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Campagnes</p>
            <p class="mt-1 text-3xl font-extrabold">{{ count($campagnes) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white">
            <h2 class="border-b border-gray-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-gray-700">Derniers biens enregistrés</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Code</th>
                        <th class="px-4 py-2">Désignation</th>
                        <th class="px-4 py-2">Structure</th>
                        <th class="px-4 py-2">État</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($biens as $bien)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2 font-mono text-xs">
                                <a href="{{ route('biens.fiche', $bien['id']) }}" class="text-vert underline">{{ $bien['code_unique'] }}</a>
                            </td>
                            <td class="px-4 py-2">{{ $bien['designation'] }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $structures[$bien['structure_id']] ?? '—' }}</td>
                            <td class="px-4 py-2">@include('partials.badge-etat', ['etat' => $bien['etat']])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun bien enregistré pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white">
            <h2 class="border-b border-gray-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-gray-700">Campagnes d’inventaire</h2>
            <ul class="divide-y divide-gray-100 text-sm">
                @forelse ($campagnes as $campagne)
                    <li class="px-4 py-3">
                        <a href="{{ route('campagnes.fiche', $campagne['id']) }}" class="font-semibold text-vert underline">{{ $campagne['nom'] }}</a>
                        <p class="text-xs text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                            → {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                        </p>
                        <div class="mt-1">@include('partials.badge-etat', ['etat' => $campagne['statut']])</div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500">Aucune campagne ouverte.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
