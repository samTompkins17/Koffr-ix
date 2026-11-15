{{-- Messages de retour : succès, erreur, erreurs de validation. --}}
@if (session('succes'))
    <div role="status" class="mb-4 rounded-lg border border-vert/30 bg-green-50 px-4 py-3 text-sm text-vert">
        <span class="font-semibold">✓</span> {{ session('succes') }}
    </div>
@endif

@if (session('erreur'))
    <div role="alert" class="mb-4 rounded-lg border border-rouge/30 bg-red-50 px-4 py-3 text-sm text-rouge">
        <span class="font-semibold">!</span> {{ session('erreur') }}
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-4 rounded-lg border border-rouge/30 bg-red-50 px-4 py-3 text-sm text-rouge">
        <p class="font-semibold">Vérifiez les champs suivants :</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
