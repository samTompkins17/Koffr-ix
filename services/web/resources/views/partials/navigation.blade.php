{{-- Menu latéral : reçoit $liens (route, motif, libelle) calculé par le layout selon le rôle. --}}
@foreach ($liens as $lien)
    <a href="{{ route($lien['route']) }}"
       class="mb-1 block rounded-lg px-4 py-3 text-[15px] {{ request()->routeIs($lien['motif']) ? 'bg-green-50 font-semibold text-vert' : 'text-gray-700 hover:bg-gray-50' }}">
        {{ $lien['libelle'] }}
    </a>
@endforeach
