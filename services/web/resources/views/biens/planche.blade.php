@extends('layouts.app')

@section('titre', 'Planche d’étiquettes')

@section('contenu')
    <a href="{{ route('biens.index') }}" class="text-sm font-semibold text-vert underline">Retour aux biens</a>
    <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">Planche d’impression d’étiquettes</h1>
    <p class="mt-2 text-gray-600">Format A4 · 12 étiquettes de 105 × 48 mm · QR code de chaque bien.</p>
    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            @foreach ($pages as $page)
                <section class="rounded-xl border border-gray-200 bg-white p-4 sm:p-6" aria-label="Planche {{ $loop->iteration }}">
                    <h2 class="mb-4 text-sm font-semibold text-gray-600">Planche {{ $loop->iteration }} sur {{ count($pages) }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($page as $bien)
                            <div class="flex min-h-36 items-center justify-between gap-2 rounded-lg border border-gray-400 p-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase">République Togolaise</p>
                                    <p class="mt-1 break-all font-mono text-xs font-bold">{{ $bien['code_unique'] }}</p>
                                    <p class="mt-1 break-words text-sm text-gray-600">{{ $bien['designation'] }}</p>
                                </div>
                                <img src="data:image/svg+xml;base64,{{ $bien['qr'] }}" alt="QR code {{ $bien['code_unique'] }}" class="h-20 w-20 shrink-0">
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <aside class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-bold">Options d’impression</h2>
            <p class="mt-5 font-semibold">Format papier</p>
            <p class="mt-2 rounded-lg border border-gray-400 p-3 text-sm">A4 · planche de 12 étiquettes</p>
            <p class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-vert"><strong>Lot sélectionné :</strong> {{ count($biens) }} bien(s) · {{ count($pages) }} planche(s).</p>
            <a href="{{ route('biens.etiquettes', ['ids' => $ids, 'format' => 'pdf']) }}" class="mt-4 block rounded-lg bg-vert px-4 py-3 text-center font-semibold text-white">Imprimer la planche (PDF)</a>
            <a href="{{ route('biens.etiquettes', ['ids' => $ids, 'format' => 'svg']) }}" class="mt-3 block rounded-lg border border-vert px-4 py-3 text-center text-sm font-semibold text-vert">Télécharger les planches vectorielles (ZIP)</a>
            <p class="mt-4 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-900"><strong>Conseil :</strong> imprimez à 100 %, sans ajustement à la page, sur un papier adhésif adapté. Vérifiez une première planche avant impression du lot.</p>
        </aside>
    </div>
@endsection
