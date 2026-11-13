@extends('layouts.app')

@section('titre', $campagne['nom'])

@section('contenu')
    @php $role = session('utilisateur.role'); @endphp

    <nav class="mb-3 text-sm text-gray-600">
        <a href="{{ route('campagnes.index') }}" class="text-vert underline">Campagnes</a>
        <span aria-hidden="true">/</span>
        <span>{{ $campagne['nom'] }}</span>
    </nav>

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $campagne['nom'] }}</h1>
            <p class="text-sm text-gray-600">
                {{ \Illuminate\Support\Carbon::parse($campagne['date_debut'])->format('d/m/Y') }}
                → {{ \Illuminate\Support\Carbon::parse($campagne['date_fin'])->format('d/m/Y') }}
                · {{ $structures[$campagne['structure_id']] ?? '—' }}
            </p>
            <div class="mt-2">@include('partials.badge-etat', ['etat' => $campagne['statut']])</div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($role === 'agent_patrimoine')
                <a href="{{ route('controles.scan') }}" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                    Contrôler par scan
                </a>
            @endif
            @if (in_array($role, ['comptable_matieres', 'controleur']))
                <a href="{{ route('campagnes.ecarts', $campagne['id']) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                    Voir les écarts
                </a>
            @endif
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Biens contrôlés</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $compteurs['controles'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Écarts détectés</p>
            <p class="mt-1 text-3xl font-extrabold text-rouge">{{ $compteurs['ecarts'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Biens introuvables</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $compteurs['introuvables'] }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-gray-700">Constats de contrôle</h2>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">Bien</th>
                    <th class="px-4 py-3">État de la fiche</th>
                    <th class="px-4 py-3">État constaté</th>
                    <th class="px-4 py-3">Écart</th>
                    <th class="px-4 py-3">Agent</th>
                    <th class="px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($controles as $controle)
                    @php $bienControle = $biens[$controle['bien_id']] ?? null; @endphp
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            @if ($bienControle)
                                <a href="{{ route('biens.fiche', $bienControle['id']) }}" class="font-mono text-xs font-semibold text-vert underline">{{ $bienControle['code_unique'] }}</a>
                                <span class="block text-gray-600">{{ $bienControle['designation'] }}</span>
                            @else
                                <span class="font-mono text-xs text-gray-500">{{ $controle['bien_id'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $controle['etat_fiche']])</td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $controle['etat_constate']])</td>
                        <td class="px-4 py-3">
                            @if ($controle['ecart_detecte'])
                                <span class="inline-flex items-center gap-1 rounded-full border border-rouge/30 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-rouge"><span aria-hidden="true">✕</span> Écart</span>
                            @else
                                <span class="text-xs text-gray-500">Aucun</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $personnes[$controle['agent_id']] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($controle['date'])->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Aucun contrôle enregistré pour cette campagne.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
