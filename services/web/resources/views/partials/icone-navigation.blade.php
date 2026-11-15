@php($classe = $classe ?? 'h-6 w-6')
<svg class="{{ $classe }}" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($icone)
        @case('tableau')
            <rect x="3" y="3" width="6" height="6" rx="2"/>
            <rect x="15" y="3" width="6" height="6" rx="2"/>
            <rect x="3" y="15" width="6" height="6" rx="2"/>
            <rect x="15" y="15" width="6" height="6" rx="2"/>
            @break
        @case('bien')
            <path d="m12 2 9 5v10l-9 5-9-5V7l9-5Z"/>
            <path d="m3 7 9 5 9-5M12 12v10"/>
            @break
        @case('transfert')
            <path d="M3 7h18m-5-5 5 5-5 5M21 17H3m5-5-5 5 5 5"/>
            @break
        @case('inventaire')
            <path d="M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/>
            <rect x="9" y="2" width="6" height="4" rx="2"/>
            <path d="m9 14 2 2 4-4"/>
            @break
        @case('ecart')
            <path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.1l-8-14a2 2 0 0 0-3.4 0Z"/>
            <path d="M12 9v4m0 4h.01"/>
            @break
        @case('carte')
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
            @break
        @case('document')
            <path d="M20 13V8l-6-6H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h6"/>
            <path d="M14 2v6h6M8 12h4M8 16h2"/>
            <path d="m19 14 3 3-5 5h-3v-3l5-5Z"/>
            @break
        @case('monde')
            <circle cx="12" cy="12" r="10"/>
            <ellipse cx="12" cy="12" rx="4" ry="10"/>
            <path d="M3 8h18M3 16h18"/>
            @break
        @case('scan')
            <path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3m0 8v3a2 2 0 0 1-2 2h-3m-8 0H5a2 2 0 0 1-2-2v-3M7 12h10"/>
            @break
        @case('journal')
            <path d="M4 19.5V4a2 2 0 0 1 2-2h14v20H6a2.5 2.5 0 0 1 0-5h14M9 7h6M9 11h6"/>
            @break
        @case('utilisateurs')
            <circle cx="9" cy="8" r="4"/>
            <path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2M16 4a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.9"/>
            @break
    @endswitch
</svg>
