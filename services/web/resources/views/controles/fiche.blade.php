@extends('layouts.app')

@section('titre', 'Constat de contrôle')

@section('contenu')
    <div class="mx-auto max-w-md">
        <nav class="mb-3 text-sm text-gray-600">
            <a href="{{ route('controles.scan') }}" class="text-vert underline">← Scanner un autre bien</a>
        </nav>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Fiche du bien</p>
            <h1 class="text-lg font-bold">{{ $bien['designation'] }}</h1>
            <p class="font-mono text-sm text-gray-600">{{ $bien['code_unique'] }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @include('partials.badge-etat', ['etat' => $bien['etat']])
            </div>
            <dl class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-600">Catégorie</dt><dd class="font-medium">{{ $bien['categorie'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Localisation</dt><dd class="font-medium">{{ $bien['localisation'] ?: '—' }}</dd></div>
            </dl>
        </div>

        @if ($dernierControle)
            <div class="mt-3 rounded-xl border border-gray-200 bg-white p-4 text-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Dernier état connu</p>
                <p class="mt-1">
                    @include('partials.badge-etat', ['etat' => $dernierControle['etat_constate']])
                    <span class="ml-2 text-gray-600">{{ \Illuminate\Support\Carbon::parse($dernierControle['date'])->format('d/m/Y H:i') }}</span>
                </p>
            </div>
        @endif

        <form method="POST" action="{{ route('controles.enregistrer') }}" enctype="multipart/form-data"
              class="mt-4 space-y-4 rounded-xl border border-gray-200 bg-white p-4">
            @csrf
            <input type="hidden" name="bien_id" value="{{ $bien['id'] }}">
            <input type="hidden" name="code" value="{{ $bien['code_unique'] }}">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div>
                <label for="campagne_id" class="mb-1 block text-sm font-medium">Campagne *</label>
                <select id="campagne_id" name="campagne_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    @forelse ($campagnes as $campagne)
                        <option value="{{ $campagne['id'] }}">{{ $campagne['nom'] }}</option>
                    @empty
                        <option value="">Aucune campagne ouverte</option>
                    @endforelse
                </select>
            </div>

            <fieldset>
                <legend class="mb-2 text-sm font-medium">État constaté *</legend>
                <div class="space-y-2">
                    @foreach (['bon' => '✓ Bon', 'usage' => '▲ Usagé', 'hors_service' => '● Hors service', 'introuvable' => '✕ Introuvable'] as $code => $libelle)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                            <input type="radio" name="etat_constate" value="{{ $code }}" required @checked(old('etat_constate') === $code)>
                            <span>{{ $libelle }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="photo" class="mb-1 block text-sm font-medium">Photo (facultative)</label>
                <input id="photo" name="photo" type="file" accept="image/*" capture="environment"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <button type="button" id="localiser" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                Ajouter ma position (facultatif)
            </button>
            <p id="position" class="text-xs text-gray-600"></p>

            <button type="submit" class="w-full rounded-lg bg-vert px-4 py-3 text-sm font-semibold text-white hover:bg-vert/90">
                Enregistrer le constat
            </button>
            <p class="text-xs text-gray-500">
                Un seul contrôle par bien et par campagne. Un écart est détecté si l’état constaté diffère de la fiche.
            </p>
        </form>
    </div>

    <script>
        document.getElementById('localiser').addEventListener('click', () => {
            const info = document.getElementById('position');
            if (!navigator.geolocation) {
                info.textContent = 'Position indisponible sur cet appareil.';
                return;
            }
            info.textContent = 'Localisation en cours…';
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('latitude').value = position.coords.latitude.toFixed(7);
                    document.getElementById('longitude').value = position.coords.longitude.toFixed(7);
                    info.textContent = 'Position ajoutée au constat.';
                },
                () => { info.textContent = 'Position non obtenue. Le constat reste possible sans position.'; }
            );
        });
    </script>
@endsection
