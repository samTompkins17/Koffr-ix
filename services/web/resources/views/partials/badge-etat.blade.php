@php
    // Un état n'est jamais indiqué par la couleur seule : icône + texte.
    $informations = [
        'bon' => ['icone' => '✓', 'libelle' => 'Bon', 'classes' => 'bg-green-50 text-vert border-vert/30'],
        'usage' => ['icone' => '▲', 'libelle' => 'Usagé', 'classes' => 'bg-amber-50 text-amber-800 border-amber-300'],
        'hors_service' => ['icone' => '●', 'libelle' => 'Hors service', 'classes' => 'bg-red-50 text-rouge border-rouge/30'],
        'introuvable' => ['icone' => '✕', 'libelle' => 'Introuvable', 'classes' => 'bg-red-50 text-rouge border-rouge/30'],
        'sorti' => ['icone' => '↗', 'libelle' => 'Sorti', 'classes' => 'bg-gray-100 text-gray-700 border-gray-300'],
        'en_service' => ['icone' => '✓', 'libelle' => 'En service', 'classes' => 'bg-green-50 text-vert border-vert/30'],
        'ouverte' => ['icone' => '◉', 'libelle' => 'Ouverte', 'classes' => 'bg-green-50 text-vert border-vert/30'],
        'cloturee' => ['icone' => '■', 'libelle' => 'Clôturée', 'classes' => 'bg-gray-100 text-gray-700 border-gray-300'],
    ][$etat] ?? ['icone' => '•', 'libelle' => $etat, 'classes' => 'bg-gray-100 text-gray-700 border-gray-300'];
@endphp
<span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $informations['classes'] }}">
    <span aria-hidden="true">{{ $informations['icone'] }}</span>{{ $informations['libelle'] }}
</span>
