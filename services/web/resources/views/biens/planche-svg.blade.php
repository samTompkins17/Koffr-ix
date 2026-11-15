<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="210mm" height="297mm" viewBox="0 0 210 297">
    <title>Planche d’étiquettes KOFFR-IX</title>
    <rect width="210" height="297" fill="white"/>
    @foreach ($biens as $bien)
        @php
            $x = ($loop->index % 2) * 105;
            $y = intdiv($loop->index, 2) * 48 + 4;
        @endphp
        <g transform="translate({{ $x }},{{ $y }})">
            <rect x="0.1" y="0.1" width="104.8" height="47.8" fill="white" stroke="#9ca3af" stroke-width="0.2"/>
            <text x="5" y="17" font-family="sans-serif" font-size="3" font-weight="bold">RÉPUBLIQUE TOGOLAISE</text>
            <text x="5" y="23" font-family="sans-serif" font-size="3.2" font-weight="bold">{{ $bien['code_unique'] }}</text>
            <text x="5" y="29" font-family="sans-serif" font-size="2.5">{{ \Illuminate\Support\Str::limit($bien['designation'], 34) }}</text>
            <image x="70" y="8" width="32" height="32" xlink:href="data:image/svg+xml;base64,{{ $bien['qr'] }}"/>
        </g>
    @endforeach
</svg>
