@extends('layouts.app')

@section('titre', 'Utilisateurs, rôles et structures')

@section('contenu')
    @php
        $onglets = ['utilisateurs' => 'Utilisateurs', 'roles' => 'Rôles', 'structures' => 'Structures'];
        $colonnes = [
            'admin' => 'Admin',
            'comptable_matieres' => 'Comptable matières',
            'agent_patrimoine' => 'Agent de patrimoine',
            'responsable_service' => 'Responsable de service',
            'controleur' => 'Contrôleur',
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Utilisateurs, rôles et structures</h1>
            <p class="mt-1 text-gray-600">Réservé à l’administrateur. Les agents se limitent à l’identité professionnelle.</p>
        </div>

        <a href="{{ route('admin.index', ['onglet' => 'utilisateurs']) }}#creer-compte"
           class="rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#00543F]">
            + Créer un compte
        </a>
    </div>

    <nav class="mb-5 flex flex-wrap gap-6 border-b border-gray-300 text-sm" aria-label="Sections de l’administration">
        @foreach ($onglets as $code => $libelle)
            <a href="{{ route('admin.index', ['onglet' => $code]) }}"
               class="-mb-px border-b-2 px-1 pb-3 {{ $onglet === $code ? 'border-vert font-semibold text-vert' : 'border-transparent text-gray-600 hover:text-vert' }}"
               @if ($onglet === $code) aria-current="page" @endif>
                {{ $libelle }}
            </a>
        @endforeach
    </nav>

    @if ($onglet === 'utilisateurs')
        <section id="creer-compte" class="mb-5 rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Créer un compte</h2>
            <p class="text-sm text-gray-600">Le compte peut se connecter dès sa création. Le mot de passe est haché, jamais journalisé.</p>

            <form method="POST" action="{{ route('admin.comptes') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold">Nom *</label>
                    <input id="name" name="name" required value="{{ old('name') }}" placeholder="Prénom N."
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('name')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold">Adresse email *</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="prenom.nom@ministere.tg"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('email')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold">Mot de passe provisoire *</label>
                    <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('password')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="role" class="mb-1.5 block text-sm font-semibold">Rôle *</label>
                    <select id="role" name="role" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        @foreach ($roles as $code => $libelle)
                            <option value="{{ $code }}" @selected(old('role') === $code)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="structure_id" class="mb-1.5 block text-sm font-semibold">Structure *</label>
                    <select id="structure_id" name="structure_id" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        @foreach ($structures as $structure)
                            <option value="{{ $structure['id'] }}" @selected(old('structure_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                        @endforeach
                    </select>
                    @error('structure_id')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-vert px-6 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
                        Créer le compte
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Nom</th>
                            <th class="px-6 py-4">Rôle</th>
                            <th class="px-6 py-4">Structure</th>
                            <th class="px-6 py-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comptes as $compte)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4 font-semibold">{{ $compte['name'] }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $roles[$compte['role']] ?? $compte['role'] }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $compte['structure_nom'] ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($compte['actif'])
                                        <span class="inline-flex items-center gap-1 font-semibold text-vert"><span aria-hidden="true">✓</span> Actif</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-rouge"><span aria-hidden="true">✕</span> Désactivé</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Aucun compte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($onglet === 'roles')
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Fonctionnalité</th>
                            @foreach ($colonnes as $libelle)
                                <th class="px-4 py-4 text-center">{{ $libelle }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matrice as $ligne)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4">{{ $ligne['fonctionnalite'] }}</td>
                                @foreach (array_keys($colonnes) as $role)
                                    @php $droit = $ligne['droits'][$role] ?? false; @endphp
                                    <td class="px-4 py-4 text-center">
                                        @if ($droit === true)
                                            <span class="font-semibold text-vert"><span aria-hidden="true">✓</span><span class="sr-only">autorisé</span></span>
                                        @elseif ($droit === 'lecture')
                                            <span class="font-semibold text-vert">Lecture</span>
                                        @else
                                            <span class="text-gray-400" aria-label="non autorisé">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="mt-3 text-xs text-gray-500">
            Les droits affichés correspondent aux contrôles appliqués par les services.
        </p>
    @endif

    @if ($onglet === 'structures')
        <section id="creer-structure" class="mb-5 rounded-xl border border-gray-200 bg-white px-6 py-5">
            <h2 class="text-lg font-bold">Créer une structure</h2>
            <p class="text-sm text-gray-600">Ministère, service rattaché ou établissement. Le code d’un ministère sert de préfixe aux codes patrimoine.</p>

            <form method="POST" action="{{ route('admin.structures') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="nom" class="mb-1.5 block text-sm font-semibold">Nom *</label>
                    <input id="nom" name="nom" required value="{{ old('nom') }}" placeholder="Direction des affaires financières"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                    @error('nom')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="code" class="mb-1.5 block text-sm font-semibold">Code *</label>
                    <input id="code" name="code" required value="{{ old('code') }}" placeholder="SAN-DAF"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 font-mono text-sm">
                    @error('code')<p class="mt-1 text-sm text-rouge">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="type" class="mb-1.5 block text-sm font-semibold">Type *</label>
                    <select id="type" name="type" required class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        <option value="ministere" @selected(old('type') === 'ministere')>Ministère</option>
                        <option value="service" @selected(old('type', 'service') === 'service')>Service</option>
                        <option value="etablissement" @selected(old('type') === 'etablissement')>Établissement</option>
                    </select>
                </div>
                <div>
                    <label for="region" class="mb-1.5 block text-sm font-semibold">Région</label>
                    <input id="region" name="region" value="{{ old('region') }}" placeholder="Maritime"
                           class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="parent_id" class="mb-1.5 block text-sm font-semibold">Structure parente</label>
                    <select id="parent_id" name="parent_id" class="w-full rounded-lg border border-gray-400 px-3 py-2.5 text-sm">
                        <option value="">— Aucune (ministère de rattachement) —</option>
                        @foreach ($structures as $structure)
                            <option value="{{ $structure['id'] }}" @selected(old('parent_id') === $structure['id'])>{{ $structure['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-vert px-6 py-3 text-sm font-bold text-white hover:bg-[#00543F]">
                        Créer la structure
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-4">Structure et niveau</th>
                            <th class="px-6 py-4 text-right">Biens rattachés</th>
                            <th class="px-6 py-4">Responsable</th>
                            <th class="px-6 py-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($structures as $structure)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-6 py-4">
                                    <p class="font-semibold">{{ $structure['nom'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $structure['type'] ?: 'Structure' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums">
                                    {{ number_format((int) ($parStructure[$structure['id']] ?? 0), 0, ',', ' ') }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $responsables[$structure['id']] ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 font-semibold text-vert"><span aria-hidden="true">✓</span> Active</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Aucune structure.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
