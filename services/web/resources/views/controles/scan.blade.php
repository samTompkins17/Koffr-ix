@extends('layouts.app')

@section('titre', 'Contrôle par scan')

@section('contenu')
    {{-- Écran conçu d'abord pour 375 px de large. --}}
    <div class="mx-auto max-w-md">
        <h1 class="text-xl font-bold">Contrôle par scan</h1>
        <p class="mb-4 text-sm text-gray-600">Scannez le QR code de l’étiquette, ou saisissez le code à la main.</p>

        <div id="zone-camera" class="mb-4 rounded-xl border border-gray-200 bg-white p-3">
            <div id="lecteur" class="mx-auto w-full overflow-hidden rounded-lg bg-gray-900" style="min-height: 220px;"></div>
            <div class="mt-3 flex gap-2">
                <button type="button" id="demarrer" class="flex-1 rounded-lg bg-vert px-4 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
                    Activer la caméra
                </button>
                <button type="button" id="arreter" class="hidden flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold hover:bg-gray-50">
                    Arrêter
                </button>
            </div>
            <p id="message-camera" class="mt-2 text-xs text-gray-600">
                La caméra nécessite HTTPS ou localhost. En cas de difficulté, utilisez la saisie manuelle.
            </p>
        </div>

        <form method="POST" action="{{ route('controles.rechercher') }}" class="rounded-xl border border-gray-200 bg-white p-4">
            @csrf
            <h2 class="text-sm font-bold">Étiquette abîmée : saisie manuelle</h2>
            <label for="code" class="mt-2 mb-1 block text-xs font-medium text-gray-700">Code patrimoine</label>
            <input id="code" name="code" value="{{ old('code') }}" required autocomplete="off"
                   placeholder="PAT-SAN-2026-0412" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm uppercase">
            <button type="submit" class="mt-3 w-full rounded-lg bg-vert px-4 py-2.5 text-sm font-semibold text-white hover:bg-vert/90">
                Rechercher le bien
            </button>
        </form>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        // Scan par caméra : html5-qrcode. En cas d'échec (pas de caméra, HTTP),
        // la saisie manuelle reste toujours disponible.
        const zone = document.getElementById('lecteur');
        const boutonDemarrer = document.getElementById('demarrer');
        const boutonArreter = document.getElementById('arreter');
        const message = document.getElementById('message-camera');
        const champCode = document.getElementById('code');
        let lecteur = null;

        boutonDemarrer.addEventListener('click', async () => {
            try {
                lecteur = new Html5Qrcode('lecteur');
                await lecteur.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    (texte) => {
                        // Le QR code contient l'URL de vérification : on en extrait le code.
                        const code = texte.split('/').filter(Boolean).pop();
                        champCode.value = decodeURIComponent(code).toUpperCase();
                        lecteur.stop().catch(() => {});
                        boutonDemarrer.classList.remove('hidden');
                        boutonArreter.classList.add('hidden');
                        document.querySelector('form[action="{{ route('controles.rechercher') }}"]').submit();
                    }
                );
                boutonDemarrer.classList.add('hidden');
                boutonArreter.classList.remove('hidden');
                message.textContent = 'Caméra active : présentez le QR code dans le cadre.';
            } catch (erreur) {
                message.textContent = 'Caméra indisponible (' + erreur.message + '). Utilisez la saisie manuelle.';
            }
        });

        boutonArreter.addEventListener('click', async () => {
            if (lecteur) { await lecteur.stop().catch(() => {}); }
            boutonDemarrer.classList.remove('hidden');
            boutonArreter.classList.add('hidden');
            message.textContent = 'Caméra arrêtée.';
        });
    </script>
@endsection
