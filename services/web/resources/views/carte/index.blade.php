@extends('layouts.app')

@section('titre', 'Carte')

@section('contenu')
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Carte des biens</h1>
        <p class="mt-1 text-gray-600">
            Biens géolocalisés lors de leur création, d’un contrôle ou d’une modification.
            {{ count($points) }} bien(s) localisé(s) sur {{ $totalBiens }} enregistré(s).
        </p>
    </div>

    @if (count($points) === 0)
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-10 text-center text-gray-600">
            Aucun bien ne porte encore de coordonnées.
            <span class="mt-1 block text-sm text-gray-500">
                Les coordonnées sont saisies à la création du bien ou captées lors d’un contrôle par scan.
            </span>
        </div>
    @else
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <div id="carte" class="h-[540px] w-full rounded-xl border border-gray-200 bg-white"></div>

        <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <h2 class="border-b border-gray-200 px-5 py-4 text-lg font-bold">Biens localisés</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-gray-600">
                    <tr class="border-b border-gray-200">
                        <th class="px-5 py-3">Code</th>
                        <th class="px-5 py-3">Désignation</th>
                        <th class="px-5 py-3">Localisation</th>
                        <th class="px-5 py-3 text-right">Coordonnées</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($points as $point)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-5 py-3 font-mono text-xs">
                                <a href="{{ $point['url'] }}" class="font-semibold text-vert underline">{{ $point['code'] }}</a>
                            </td>
                            <td class="px-5 py-3">{{ $point['designation'] }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $point['localisation'] ?: '—' }}</td>
                            <td class="px-5 py-3 text-right font-mono text-xs text-gray-600">
                                {{ number_format($point['latitude'], 4, ',', ' ') }}, {{ number_format($point['longitude'], 4, ',', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            // Les données viennent du HTML rendu par le serveur, jamais d'un tiers.
            const points = @json($points);
            const carte = L.map('carte').setView([6.1319, 1.2228], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap',
            }).addTo(carte);

            points.forEach((point) => {
                const lien = document.createElement('a');
                lien.href = point.url;
                lien.textContent = point.code;
                lien.className = 'font-semibold';

                const contenu = document.createElement('div');
                contenu.append(lien, document.createElement('br'), document.createTextNode(point.designation));
                if (point.localisation) {
                    contenu.append(document.createElement('br'), document.createTextNode(point.localisation));
                }

                L.marker([point.latitude, point.longitude]).addTo(carte).bindPopup(contenu);
            });
        </script>
    @endif
@endsection
