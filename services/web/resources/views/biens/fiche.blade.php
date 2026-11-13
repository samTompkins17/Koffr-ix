@extends('layouts.app')

@section('titre', $bien['code_unique'])

@section('contenu')
    @php
        $role = session('utilisateur.role');
        $peutModifier = in_array($role, ['admin', 'comptable_matieres']);
        $peutMouvoir = in_array($role, ['comptable_matieres', 'responsable_service']) && $bien['statut'] === 'en_service';
        $libelles = ['affectation' => 'Affectation', 'transfert' => 'Transfert', 'maintenance' => 'Maintenance', 'sortie' => 'Sortie'];
    @endphp

    <a href="{{ route('biens.index') }}" class="text-sm font-medium text-vert underline">← Retour à la liste des biens</a>

    <div class="mb-6 mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">{{ $bien['designation'] }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-gray-600">
                <span class="font-mono">{{ $bien['code_unique'] }}</span>
                @include('partials.badge-etat', ['etat' => $bien['etat']])
                @include('partials.badge-etat', ['etat' => $bien['statut']])
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($peutModifier)
                <a href="{{ route('biens.modifier', $bien['id']) }}" class="rounded-lg border border-vert px-5 py-2.5 text-sm font-semibold text-vert hover:bg-green-50">
                    Modifier
                </a>
            @endif
            @if ($peutMouvoir)
                <a href="{{ route('transferts.index', ['bien' => $bien['id'], 'type' => 'sortie']) }}"
                   class="rounded-lg border border-rouge px-5 py-2.5 text-sm font-semibold text-rouge hover:bg-red-50">
                    Déclarer une sortie
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 rounded-xl border border-gray-200 bg-white px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="text-sm text-gray-500">Catégorie</p>
            <p class="font-bold">{{ $bien['categorie'] }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Structure</p>
            <p class="font-bold">{{ $structures[$bien['structure_id']] ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Responsable</p>
            <p class="font-bold">{{ $personnes[$bien['responsable_id'] ?? ''] ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Valeur</p>
            <p class="font-bold">{{ number_format((int) $bien['valeur_acquisition'], 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[380px_1fr]">
        <section class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Étiquette QR</h2>
            <div class="mt-4 flex justify-center">
                <img src="{{ route('biens.qrcode', $bien['id']) }}" alt="QR code du bien {{ $bien['code_unique'] }}"
                     class="h-56 w-56">
            </div>
            <p class="mt-3 text-center font-mono text-base font-bold">{{ $bien['code_unique'] }}</p>
            <p class="mt-1 text-center text-sm text-gray-600">Le scan ouvre cette fiche.</p>

            <div class="mt-5 flex gap-2">
                @if ($peutMouvoir)
                    <a href="{{ route('transferts.index', ['bien' => $bien['id']]) }}"
                       class="flex-1 rounded-lg bg-vert px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#00543F]">
                        Transférer le bien
                    </a>
                @endif
                <a href="{{ route('biens.etiquette', $bien['id']) }}"
                   class="flex-1 rounded-lg border border-vert px-4 py-2.5 text-center text-sm font-semibold text-vert hover:bg-green-50">
                    Imprimer
                </a>
            </div>

            <dl class="mt-5 space-y-2 border-t border-gray-100 pt-4 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Date d’acquisition</dt>
                    <dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($bien['date_acquisition'])->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Localisation</dt>
                    <dd class="font-medium text-right">{{ $bien['localisation'] ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Historique</h2>
            <p class="mt-1 text-sm text-gray-600">En ajout seul : aucune modification ni suppression.</p>

            <ol class="mt-5 space-y-5 border-l-2 border-gray-200 pl-5">
                @foreach ($mouvements as $mouvement)
                    <li class="relative">
                        <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-vert" aria-hidden="true"></span>
                        <p class="font-semibold">
                            {{ $libelles[$mouvement['type']] ?? $mouvement['type'] }}@if ($mouvement['structure_destination_id'])
                                : {{ $structures[$mouvement['structure_origine_id'] ?? ''] ?? '—' }} vers {{ $structures[$mouvement['structure_destination_id']] ?? '—' }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($mouvement['date'])->format('d/m/Y') }} · demandé par {{ $personnes[$mouvement['auteur_id']] ?? '—' }}
                        </p>
                        <p class="text-sm text-gray-500">{{ $mouvement['motif'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-vert"><span aria-hidden="true">✓</span> Validé</p>
                    </li>
                @endforeach

                <li class="relative">
                    <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-gray-300" aria-hidden="true"></span>
                    <p class="font-semibold">Création du bien et génération du code</p>
                    <p class="text-sm text-gray-600">
                        {{ \Illuminate\Support\Carbon::parse($bien['created_at'])->format('d/m/Y') }} · {{ $bien['code_unique'] }}
                    </p>
                </li>
            </ol>

            @if ($mouvements === [])
                <p class="mt-4 text-sm text-gray-500">Aucun mouvement : le bien est dans sa structure d’origine.</p>
            @endif
        </section>
    </div>
@endsection
