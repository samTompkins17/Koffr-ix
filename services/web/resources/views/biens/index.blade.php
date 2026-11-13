@extends('layouts.app')

@section('titre', 'Biens')

@section('contenu')
    @php $role = session('utilisateur.role'); @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Biens</h1>
            <p class="text-sm text-gray-600">{{ $biens['total'] ?? 0 }} bien(s) enregistré(s)</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('biens.export') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                Exporter en CSV
            </a>
            @if (in_array($role, ['admin', 'comptable_matieres']))
                <a href="{{ route('biens.creer') }}" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                    + Nouveau bien
                </a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('biens.index') }}" class="mb-5 grid gap-3 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-5">
        <div class="md:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-gray-700">Recherche (désignation ou code)</label>
            <input id="q" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="PAT-SAN-2026-0412"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="categorie" class="mb-1 block text-xs font-medium text-gray-700">Catégorie</label>
            <select id="categorie" name="categorie" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach (config('koffr.categories') as $categorie)
                    <option value="{{ $categorie }}" @selected(($filtres['categorie'] ?? '') === $categorie)>{{ $categorie }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="etat" class="mb-1 block text-xs font-medium text-gray-700">État</label>
            <select id="etat" name="etat" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach (['bon' => 'Bon', 'usage' => 'Usagé', 'hors_service' => 'Hors service'] as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(($filtres['etat'] ?? '') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="w-full rounded-lg bg-vert px-3 py-2 text-sm font-semibold text-white hover:bg-vert/90">Filtrer</button>
            <a href="{{ route('biens.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">Effacer</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">Code patrimoine</th>
                    <th class="px-4 py-3">Désignation</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Structure</th>
                    <th class="px-4 py-3">État</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($biens['data'] as $bien)
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('biens.fiche', $bien['id']) }}" class="font-semibold text-vert underline">{{ $bien['code_unique'] }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $bien['designation'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $bien['categorie'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $structures[$bien['structure_id']] ?? '—' }}</td>
                        <td class="px-4 py-3">@include('partials.badge-etat', ['etat' => $bien['etat']])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">Aucun bien ne correspond à ces critères.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (($biens['last_page'] ?? 1) > 1)
        <nav class="mt-4 flex items-center justify-center gap-2 text-sm" aria-label="Pagination">
            @if (($biens['current_page'] ?? 1) > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $biens['current_page'] - 1]) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5">← Précédent</a>
            @endif
            <span class="text-gray-600">Page {{ $biens['current_page'] }} sur {{ $biens['last_page'] }}</span>
            @if (($biens['current_page'] ?? 1) < ($biens['last_page'] ?? 1))
                <a href="{{ request()->fullUrlWithQuery(['page' => $biens['current_page'] + 1]) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5">Suivant →</a>
            @endif
        </nav>
    @endif
@endsection
