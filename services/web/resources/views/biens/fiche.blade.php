@extends('layouts.app')

@section('titre', $bien['code_unique'])

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $peutModifier = in_array($role, ['admin', 'comptable_matieres']);
        $peutTransferer = in_array($role, ['comptable_matieres', 'responsable_service']);
    @endphp

    <nav class="mb-3 text-sm text-gray-600" aria-label="Fil d’Ariane">
        <a href="{{ route('biens.index') }}" class="text-vert underline">Biens</a>
        <span aria-hidden="true">/</span>
        <span>{{ $bien['code_unique'] }}</span>
    </nav>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $bien['designation'] }}</h1>
            <p class="font-mono text-sm text-gray-600">{{ $bien['code_unique'] }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @include('partials.badge-etat', ['etat' => $bien['etat']])
                @include('partials.badge-etat', ['etat' => $bien['statut']])
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('biens.etiquette', $bien['id']) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                Étiquette QR
            </a>
            @if ($peutModifier)
                <a href="{{ route('biens.modifier', $bien['id']) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50">
                    Modifier
                </a>
            @endif
            @if ($peutTransferer && $bien['statut'] === 'en_service')
                <a href="{{ route('biens.transfert', $bien['id']) }}" class="rounded-lg bg-vert px-4 py-2 text-sm font-semibold text-white hover:bg-vert/90">
                    Affecter / Transférer
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-700">Fiche du bien</h2>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase text-gray-500">Catégorie</dt>
                    <dd class="font-medium">{{ $bien['categorie'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Valeur d’acquisition</dt>
                    <dd class="font-medium">{{ number_format((int) $bien['valeur_acquisition'], 0, ',', ' ') }} FCFA</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Date d’acquisition</dt>
                    <dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($bien['date_acquisition'])->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Structure de rattachement</dt>
                    <dd class="font-medium">{{ $structures[$bien['structure_id']] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Responsable</dt>
                    <dd class="font-medium">{{ $personnes[$bien['responsable_id'] ?? ''] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Localisation</dt>
                    <dd class="font-medium">{{ $bien['localisation'] ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 text-center">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-700">QR code</h2>
            <img src="{{ route('biens.qrcode', $bien['id']) }}" alt="QR code du bien {{ $bien['code_unique'] }}"
                 class="mx-auto h-40 w-40">
            <p class="mt-2 text-xs text-gray-600">
                Vérification publique : <span class="font-mono">{{ url('/verifier/'.$bien['code_unique']) }}</span>
            </p>
        </section>
    </div>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white">
        <h2 class="border-b border-gray-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-gray-700">
            Historique des mouvements
        </h2>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Origine → Destination</th>
                    <th class="px-4 py-2">Motif</th>
                    <th class="px-4 py-2">Auteur</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mouvements as $mouvement)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($mouvement['date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 font-medium capitalize">{{ $mouvement['type'] }}</td>
                        <td class="px-4 py-2 text-gray-600">
                            {{ $structures[$mouvement['structure_origine_id'] ?? ''] ?? '—' }}
                            @if ($mouvement['structure_destination_id'])
                                → {{ $structures[$mouvement['structure_destination_id']] ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-600">{{ $mouvement['motif'] }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $personnes[$mouvement['auteur_id']] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun mouvement enregistré : le bien est dans sa structure d’origine.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="border-t border-gray-100 px-4 py-2 text-xs text-gray-500">
            Historique en ajout seul : un mouvement ne peut être ni modifié ni supprimé.
        </p>
    </section>
@endsection
