@extends('layouts.app')

@section('titre', 'Campagnes d’inventaire')

@section('contenu')
    @php $role = session('utilisateur.role'); @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Campagnes d’inventaire</h1>
            <p class="text-sm text-gray-600">Un bien ne peut être contrôlé qu’une fois par campagne.</p>
        </div>
        @if ($role === 'comptable_matieres')
            <a href="{{ route('campagnes.creer') }}" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                + Ouvrir une campagne
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">Campagne</th>
                    <th class="px-4 py-3">Période</th>
                    <th class="px-4 py-3">Structure</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campagnes as $campagne)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 font-semibold">{{ $campagne['nom'] }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                            → {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $structures[$campagne['structure_id']] ?? '—' }}</td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $campagne['statut']])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('campagnes.fiche', $campagne['id']) }}" class="text-vert underline">Ouvrir</a>
                            @if (in_array($role, ['comptable_matieres', 'controleur']))
                                <span aria-hidden="true" class="text-gray-300">|</span>
                                <a href="{{ route('campagnes.ecarts', $campagne['id']) }}" class="text-vert underline">Écarts</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Aucune campagne d’inventaire n’a encore été ouverte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
