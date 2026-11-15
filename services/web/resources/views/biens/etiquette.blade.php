@extends('layouts.app')

@section('titre', 'Étiquette')

@section('contenu')
    <div class="sans-impression mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Étiquette du bien</h1>
            <p class="text-sm text-gray-600">À coller sur le bien. Format 80 × 50 mm.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                Imprimer
            </button>
            <a href="{{ route('biens.fiche', $bien['id']) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                Retour à la fiche
            </a>
        </div>
    </div>

    <div class="mx-auto w-[80mm] rounded-lg border border-gray-300 bg-white p-3">
        <div class="flex gap-3">
            <img src="{{ route('biens.qrcode', $bien['id']) }}" alt="QR code {{ $bien['code_unique'] }}" class="h-[30mm] w-[30mm]">
            <div class="min-w-0 flex-1 text-[9pt] leading-tight">
                <p class="text-[7pt] font-bold uppercase text-vert">République Togolaise</p>
                <p class="my-1 font-mono text-[11pt] font-extrabold">{{ $bien['code_unique'] }}</p>
                <p class="font-medium">{{ \Illuminate\Support\Str::limit($bien['designation'], 48) }}</p>
                <p class="text-gray-600">{{ $bien['categorie'] }}</p>
                <p class="mt-1 text-[6pt] text-gray-500">Bien de l’État · vérification : {{ url('/verifier/'.$bien['code_unique']) }}</p>
            </div>
        </div>
    </div>
@endsection
