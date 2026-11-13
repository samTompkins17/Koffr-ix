@extends('layouts.app')

@section('titre', 'Biens')

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $debut = (($biens['current_page'] ?? 1) - 1) * ($biens['per_page'] ?? 15) + 1;
        $fin = min(($biens['current_page'] ?? 1) * ($biens['per_page'] ?? 15), $biens['total'] ?? 0);
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Biens</h1>
            <p class="mt-1 text-gray-600">Retrouvez un bien par son code, sa désignation, sa catégorie ou sa structure.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('biens.export') }}" class="rounded-lg border border-vert px-4 py-2.5 text-sm font-semibold text-vert hover:bg-green-50">
                Exporter en CSV
            </a>
            @if (in_array($role, ['admin', 'comptable_matieres']))
                <a href="{{ route('biens.creer') }}" class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#00543F]">
                    + Nouveau bien
                </a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('biens.index') }}" class="mb-6 grid gap-4 rounded-xl border border-gray-200 bg-white px-5 py-5 md:grid-cols-[2fr_1fr_1fr]">
        <div>
            <label for="q" class="mb-1.5 block text-sm font-semibold">Recherche</label>
            <input id="q" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Désignation ou code patrimoine"
                   class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label for="categorie" class="mb-1.5 block text-sm font-semibold">Catégorie</label>
            <select id="categorie" name="categorie" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                <option value="">Toutes</option>
                @foreach (config('koffr.categories') as $categorie)
                    <option value="{{ $categorie }}" @selected(($filtres['categorie'] ?? '') === $categorie)>{{ $categorie }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="etat" class="mb-1.5 block text-sm font-semibold">État</label>
            <select id="etat" name="etat" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                <option value="">Tous</option>
                @foreach (['bon' => '✓ Bon', 'usage' => '▲ Usagé', 'hors_service' => '● Hors service'] as $code => $libelle)
                    <option value="{{ $code }}" @selected(($filtres['etat'] ?? '') === $code)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                <tr class="border-b border-gray-200">
                    <th class="px-5 py-4">Code patrimoine</th>
                    <th class="px-5 py-4">Désignation</th>
                    <th class="px-5 py-4">Catégorie</th>
                    <th class="px-5 py-4">Structure</th>
                    <th class="px-5 py-4">État</th>
                    <th class="px-5 py-4 text-right">Valeur (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($biens['data'] as $bien)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-4 font-mono text-xs">
                            <a href="{{ route('biens.fiche', $bien['id']) }}" class="font-semibold text-vert underline">{{ $bien['code_unique'] }}</a>
                        </td>
                        <td class="px-5 py-4 font-semibold">{{ $bien['designation'] }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $bien['categorie'] }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $structures[$bien['structure_id']] ?? '—' }}</td>
                        <td class="px-5 py-4">@include('partials.badge-etat', ['etat' => $bien['etat']])</td>
                        <td class="px-5 py-4 text-right tabular-nums">{{ number_format((int) $bien['valeur_acquisition'], 0, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-500">Aucun bien ne correspond à ces critères.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600">
        <p>
            @if (($biens['total'] ?? 0) > 0)
                Affichage de {{ $debut }} à {{ $fin }} sur {{ number_format($biens['total'], 0, ',', ' ') }} biens
            @else
                Aucun bien affiché
            @endif
        </p>
        <div class="flex gap-2">
            @if (($biens['current_page'] ?? 1) > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $biens['current_page'] - 1]) }}"
                   class="rounded-lg border border-vert px-4 py-2 font-semibold text-vert hover:bg-green-50">Précédent</a>
            @endif
            @if (($biens['current_page'] ?? 1) < ($biens['last_page'] ?? 1))
                <a href="{{ request()->fullUrlWithQuery(['page' => $biens['current_page'] + 1]) }}"
                   class="rounded-lg border border-vert px-4 py-2 font-semibold text-vert hover:bg-green-50">Suivant</a>
            @endif
        </div>
    </div>
@endsection
