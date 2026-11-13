@extends('layouts.mobile')

@section('titre', 'Contrôle par scan')

@section('entete', 'Contrôle par scan')
@section('sous-titre', 'Scannez l’étiquette ou saisissez le code du bien')

@section('contenu')
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold">Scanner l’étiquette</h2>
        <p class="mt-1 text-sm text-gray-600">
            Le QR code est collé sur le bien. La caméra s’ouvre dans votre application de lecture.
        </p>

        <button type="button" id="bouton-camera"
                class="mt-4 w-full rounded-lg bg-vert px-4 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
            Ouvrir la caméra
        </button>

        <div id="zone-camera" hidden class="mt-4 overflow-hidden rounded-lg bg-black">
            <video id="video" class="h-56 w-full object-cover" playsinline muted></video>
            <p id="erreur-camera" class="hidden bg-red-50 px-3 py-2 text-xs text-rouge"></p>
        </div>
    </section>

    <div class="mt-4 rounded-lg border border-jaune bg-yellow-50 px-4 py-3 text-sm text-gray-800">
        <p class="font-semibold">▲ Étiquette abîmée ?</p>
        <p class="mt-1">Saisissez le code inscrit sous le QR code.</p>
    </div>

    <form method="POST" action="{{ route('controles.rechercher') }}" class="mt-4 rounded-xl bg-white p-5 shadow-sm">
        @csrf
        <label for="code" class="mb-1.5 block text-sm font-semibold">Code patrimoine *</label>
        <input id="code" name="code" required value="{{ old('code') }}" placeholder="PAT-SAN-2026-0412"
               class="w-full rounded-lg border border-gray-400 px-3 py-3 font-mono text-sm uppercase">
        @error('code')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror

        <button type="submit" class="mt-4 w-full rounded-lg bg-vert px-4 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
            Rechercher la fiche du bien
        </button>
        <button type="button" id="retour-scanner" class="mt-2 w-full rounded-lg px-4 py-3 text-sm font-semibold text-vert underline">
            Retourner au scanner caméra
        </button>
    </form>

    <script>
        // Caméra : flux local uniquement, aucune image n’est transmise.
        const video = document.getElementById('video');
        const zone = document.getElementById('zone-camera');
        const erreur = document.getElementById('erreur-camera');
        let flux = null;

        document.getElementById('bouton-camera').addEventListener('click', async () => {
            zone.hidden = false;
            if (flux) return;
            try {
                flux = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = flux;
                await video.play();
            } catch (e) {
                erreur.hidden = false;
                erreur.textContent = 'Caméra indisponible. Saisissez le code sous l’étiquette.';
            }
        });

        document.getElementById('retour-scanner').addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('pagehide', () => flux?.getTracks().forEach((piste) => piste.stop()));
    </script>
@endsection
