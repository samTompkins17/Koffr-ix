@extends('layouts.app')

@section('titre', 'Écarts')

@section('contenu')
    <nav class="mb-3 text-sm text-gray-600">
        <a href="{{ route('campagnes.fiche', $campagne['id']) }}" class="text-vert underline">{{ $campagne['nom'] }}</a>
        <span aria-hidden="true">/</span>
        <span>Écarts</span>
    </nav>

    <h1 class="mb-1 text-2xl font-bold">Écarts d’inventaire</h1>
    <p class="mb-5 text-sm text-gray-600">
        Campagne « {{ $campagne['nom'] }} » · {{ count($ecarts) }} écart(s). Liste en lecture seule.
    </p>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">Bien</th>
                    <th class="px-4 py-3">État de la fiche</th>
                    <th class="px-4 py-3">État constaté</th>
                    <th class="px-4 py-3">Écart</th>
                    <th class="px-4 py-3">Constaté par</th>
                    <th class="px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ecarts as $ecart)
                    @php $bienConcerne = $biens[$ecart['bien_id']] ?? null; @endphp
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            @if ($bienConcerne)
                                <a href="{{ route('biens.fiche', $bienConcerne['id']) }}" class="font-mono text-xs font-semibold text-vert underline">{{ $bienConcerne['code_unique'] }}</a>
                                <span class="block text-gray-600">{{ $bienConcerne['designation'] }}</span>
                            @else
                                <span class="font-mono text-xs text-gray-500">{{ $ecart['bien_id'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $ecart['etat_fiche']])</td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $ecart['etat_constate']])</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full border border-rouge/30 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-rouge"><span aria-hidden="true">✕</span> Écart</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $personnes[$ecart['agent_id']] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($ecart['date'])->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Aucun écart détecté pour cette campagne.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="border-t border-gray-100 px-4 py-2 text-xs text-gray-500">
            Le traitement d’un écart est hors périmètre de cette démonstration : cette liste est en lecture seule.
        </p>
    </div>
@endsection
