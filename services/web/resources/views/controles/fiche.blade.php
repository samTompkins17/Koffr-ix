@extends('layouts.mobile')

@section('titre', 'Constat de contrôle')

@section('entete', 'Bien reconnu')
@section('sous-titre', $bien['code_unique'])

@section('contenu')
    @php
        $etats = ['bon' => 'Bon', 'usage' => 'Usagé', 'hors_service' => 'Hors service', 'introuvable' => 'Introuvable'];
        $etatChoisi = old('etat_constate', $bien['etat']);
        $libelleFiche = $etats[$bien['etat']] ?? $bien['etat'];
        $intituleEcart = [
            'bon' => 'le bien est déclaré bon sur la fiche mais constaté autrement',
            'usage' => 'le bien est déclaré usagé sur la fiche',
            'hors_service' => 'le bien est déclaré hors service sur la fiche',
            'introuvable' => 'le bien était en service sur la fiche',
        ];
    @endphp

    <section class="rounded-xl bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-vert">Bien reconnu · {{ $bien['code_unique'] }}</p>
        <h2 class="mt-1 text-lg font-bold">{{ $bien['designation'] }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            Fiche : état {{ $libelleFiche }} · {{ $structures[$bien['structure_id']] ?? '—' }}
        </p>
        @if ($dernierControle)
            <p class="mt-1 text-xs text-gray-500">
                Dernier contrôle le {{ \Illuminate\Support\Carbon::parse($dernierControle['date'])->format('d/m/Y') }}.
            </p>
        @endif
    </section>

    <form method="POST" action="{{ route('controles.enregistrer') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
        @csrf
        <input type="hidden" name="bien_id" value="{{ $bien['id'] }}">
        <input type="hidden" name="code" value="{{ $bien['code_unique'] }}">
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <label for="campagne_id" class="mb-1.5 block text-sm font-semibold">Campagne d’inventaire *</label>
            <select id="campagne_id" name="campagne_id" required class="w-full rounded-lg border border-gray-400 px-3 py-3 text-sm">
                @foreach ($campagnes as $campagne)
                    <option value="{{ $campagne['id'] }}" @selected(old('campagne_id') === $campagne['id'])>
                        {{ $campagne['nom'] }}
                    </option>
                @endforeach
            </select>
            @error('campagne_id')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
            @if ($campagnes === [])
                <p class="mt-2 text-sm text-rouge">Aucune campagne ouverte : demandez au comptable d’en ouvrir une.</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <fieldset>
                <legend class="mb-2 text-sm font-semibold">État constaté *</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($etats as $code => $libelle)
                        <label class="cursor-pointer rounded-lg border px-3 py-3 text-center text-sm font-semibold {{ $etatChoisi === $code ? 'border-vert bg-green-50 text-vert' : 'border-gray-300' }}"
                               data-etat="{{ $code }}">
                            <input type="radio" name="etat_constate" value="{{ $code }}" class="sr-only" @checked($etatChoisi === $code) required>
                            {{ $libelle }}
                        </label>
                    @endforeach
                </div>
                @error('etat_constate')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
            </fieldset>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <button type="button" id="bouton-position"
                        class="rounded-lg border border-vert px-3 py-2.5 text-sm font-semibold text-vert hover:bg-green-50">
                    Position captée
                </button>
                <label class="cursor-pointer rounded-lg border border-vert px-3 py-2.5 text-center text-sm font-semibold text-vert hover:bg-green-50">
                    Ajouter une photo
                    <input type="file" name="photo" accept="image/*" capture="environment" class="sr-only">
                </label>
            </div>
            <p id="etat-position" class="mt-2 text-xs text-gray-500">Position GPS non captée.</p>
            @error('photo')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
        </section>

        <div id="encart-ecart" hidden class="rounded-xl border border-rouge bg-red-50 p-4 text-sm text-gray-800">
            <p class="font-bold text-rouge">✕ Écart détecté</p>
            <p class="mt-1">
                L’état constaté diffère de la fiche : <span id="detail-ecart"></span>.
                L’écart sera ajouté à la liste des écarts.
            </p>
        </div>

        <div class="fixed inset-x-0 bottom-0 border-t border-gray-200 bg-white px-4 py-3">
            <button type="submit" @disabled($campagnes === [])
                    class="mx-auto block w-full max-w-md rounded-lg bg-vert px-4 py-3.5 text-sm font-bold text-white hover:bg-[#00543F] disabled:cursor-not-allowed disabled:bg-gray-300">
                Enregistrer le contrôle
            </button>
        </div>
    </form>

    <script>
        const etatFiche = @json($bien['etat']);
        const intitules = @json($intituleEcart);
        const encart = document.getElementById('encart-ecart');
        const detail = document.getElementById('detail-ecart');

        function majEcart() {
            const choisi = document.querySelector('input[name="etat_constate"]:checked')?.value;
            const different = choisi && choisi !== etatFiche;
            encart.hidden = !different;
            if (different) detail.textContent = intitules[etatFiche] ?? 'états différents';

            document.querySelectorAll('[data-etat]').forEach((label) => {
                const actif = label.dataset.etat === choisi;
                label.classList.toggle('border-vert', actif);
                label.classList.toggle('bg-green-50', actif);
                label.classList.toggle('text-vert', actif);
                label.classList.toggle('border-gray-300', !actif);
            });
        }

        document.querySelectorAll('input[name="etat_constate"]').forEach((radio) => {
            radio.addEventListener('change', majEcart);
        });
        majEcart();

        // Position captée sur place, jamais envoyée sans action de l'agent.
        document.getElementById('bouton-position').addEventListener('click', () => {
            const message = document.getElementById('etat-position');
            if (!navigator.geolocation) {
                message.textContent = 'Position indisponible sur cet appareil.';
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                    document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                    message.textContent = `Position captée : ${position.coords.latitude.toFixed(5)}, ${position.coords.longitude.toFixed(5)}`;
                    message.className = 'mt-2 text-xs font-semibold text-vert';
                },
                () => { message.textContent = 'Position refusée ou indisponible.'; },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    </script>
@endsection
