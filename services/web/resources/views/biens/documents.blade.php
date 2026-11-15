@php
    $typesDocuments = ['facture' => 'Facture d’achat', 'acte' => 'Acte', 'titre' => 'Titre de propriété', 'photo' => 'Photo', 'autre' => 'Autre document'];
@endphp
<section class="mt-6 rounded-xl border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="documents-titre">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="documents-titre" class="text-lg font-bold">Documents joints</h2>
            <p class="mt-1 text-sm text-gray-600">Pièces justificatives en ajout seul. Consultation réservée aux utilisateurs connectés.</p>
        </div>
        @if ($peutModifier)
            <button type="button" id="ouvrir-document" class="min-h-[44px] rounded-lg bg-vert px-5 py-2.5 text-sm font-semibold text-white">Ajouter un document</button>
        @endif
    </div>
    @if ($documentsIndisponibles)
        <p class="mt-5 text-sm text-rouge" role="alert">Les documents sont momentanément indisponibles.</p>
    @else
        <ul class="mt-4 divide-y divide-gray-200">
            @forelse ($documents as $document)
                <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <p class="break-all font-semibold">{{ $document['nom_original'] }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $typesDocuments[$document['type']] ?? $document['type'] }} · {{ number_format($document['taille'] / 1024, 1, ',', ' ') }} Ko</p>
                        <p class="text-sm text-gray-500">Ajouté le {{ \Illuminate\Support\Carbon::parse($document['created_at'])->format('d/m/Y') }} par {{ $personnes[$document['auteur_id']] ?? '—' }}</p>
                    </div>
                    <a class="min-h-[44px] rounded-lg border border-vert px-4 py-2.5 text-sm font-semibold text-vert" href="{{ route('documents.telecharger', [$bien['id'], $document['id']]) }}">Télécharger<span class="sr-only"> {{ $document['nom_original'] }}</span></a>
                </li>
            @empty
                <li class="py-6 text-sm text-gray-600">Aucun document joint à ce bien.</li>
            @endforelse
        </ul>
    @endif
</section>

@if ($peutModifier)
    <dialog id="ajout-document" aria-labelledby="ajout-document-titre" class="w-[calc(100%_-_2rem)] max-w-2xl rounded-2xl bg-white p-5 text-gray-900 shadow-xl backdrop:bg-gray-900/50 sm:p-8">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="ajout-document-titre" class="text-2xl font-bold">Ajouter un document</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $bien['designation'] }} · {{ $bien['code_unique'] }}</p>
            </div>
            <button type="button" data-fermer-document aria-label="Fermer" class="min-h-[44px] min-w-[44px] rounded-lg bg-gray-100 text-xl">×</button>
        </div>
        <form method="POST" action="{{ route('documents.store', $bien['id']) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @if ($errors->has('type') || $errors->has('fichier'))
                <div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-rouge">{{ $errors->first('type') ?: $errors->first('fichier') }} Sélectionnez à nouveau le fichier.</div>
            @endif
            <div>
                <label for="document-type" class="mb-2 block font-semibold">Type de document *</label>
                <select id="document-type" name="type" required class="w-full rounded-lg border border-gray-400 px-3 py-3">
                    @foreach ($typesDocuments as $code => $libelle)
                        <option value="{{ $code }}" @selected(old('type', 'facture') === $code)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rounded-lg border-2 border-dashed border-gray-400 p-5">
                <label for="document-fichier" class="mb-3 block font-semibold">Choisir un fichier *</label>
                <input id="document-fichier" type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required aria-describedby="document-format" class="w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-4 file:py-3 file:font-semibold file:text-vert">
                <p id="document-format" class="mt-3 text-sm text-gray-600">PDF, JPG ou PNG · 10 Mo maximum</p>
            </div>
            <p class="rounded-lg bg-green-50 p-4 text-sm text-vert">Le document est ajouté à l’historique du bien : il ne pourra pas être modifié ni supprimé.</p>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="min-h-[44px] rounded-lg bg-vert px-5 py-3 font-semibold text-white">Ajouter le document</button>
                <button type="button" data-fermer-document class="min-h-[44px] rounded-lg border border-gray-400 px-5 py-3 font-semibold">Annuler</button>
            </div>
        </form>
    </dialog>
    <script>
        const dialogueDocument = document.getElementById('ajout-document');
        document.getElementById('ouvrir-document').addEventListener('click', () => dialogueDocument.showModal());
        dialogueDocument.querySelectorAll('[data-fermer-document]').forEach(bouton => bouton.addEventListener('click', () => dialogueDocument.close()));
        @if ($errors->has('type') || $errors->has('fichier'))
            dialogueDocument.showModal();
        @endif
    </script>
@endif
