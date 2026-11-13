{{-- Menu latéral (maquette) : reçoit $liens, $maquettes et $admin depuis le layout. --}}
@foreach ($liens as $lien)
    <a href="{{ route($lien['route']) }}"
       class="mb-1 block rounded-lg px-4 py-3 text-[15px] {{ request()->routeIs($lien['motif']) ? 'bg-green-50 font-semibold text-vert' : 'text-gray-700 hover:bg-gray-50' }}">
        {{ $lien['libelle'] }}
    </a>
@endforeach

{{-- Écrans présents dans les maquettes mais hors périmètre (AGENTS.md §2) :
     affichés pour situer le produit, sans lien ni action. --}}
@foreach ($maquettes as $libelle)
    <div class="mb-1 flex items-center justify-between gap-2 rounded-lg px-4 py-3 text-[15px] text-gray-400"
         title="Écran de maquette, non implémenté dans cette démonstration">
        <span>{{ $libelle }}</span>
        <span class="rounded border border-gray-300 px-1.5 py-0.5 text-[10px] uppercase tracking-wide">maquette</span>
    </div>
@endforeach

@if ($admin)
    <p class="mb-1 mt-6 px-4 text-xs font-bold uppercase tracking-wide text-gray-500">Administration</p>

    <a href="{{ route('admin.index') }}"
       class="mb-1 block rounded-lg px-4 py-3 text-[15px] {{ request()->routeIs('admin.*') ? 'bg-green-50 font-semibold text-vert' : 'text-gray-700 hover:bg-gray-50' }}">
        Utilisateurs et rôles
    </a>

    <div class="mb-1 flex items-center justify-between gap-2 rounded-lg px-4 py-3 text-[15px] text-gray-400"
         title="Écran de maquette, non implémenté dans cette démonstration">
        <span>Journal d’audit</span>
        <span class="rounded border border-gray-300 px-1.5 py-0.5 text-[10px] uppercase tracking-wide">maquette</span>
    </div>
@endif
