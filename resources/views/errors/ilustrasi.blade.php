{{--
    Ilustrasi maskot (inline SVG, tanpa berkas luar). $jenis: kunci | stop | cari | rusak | umum
--}}
@php
    $label = [
        'kunci' => 'Maskot UMKMLinked memegang kunci di depan pintu terkunci',
        'stop'  => 'Maskot UMKMLinked memegang rambu berhenti',
        'cari'  => 'Maskot UMKMLinked mencari dengan kaca pembesar',
        'rusak' => 'Maskot UMKMLinked memperbaiki laptop yang rusak',
        'umum'  => 'Maskot UMKMLinked mengangkat bahu kebingungan',
    ][$jenis] ?? 'Maskot UMKMLinked';
@endphp
<svg class="ilustrasi" viewBox="0 0 360 300" role="img" aria-label="{{ $label }}" xmlns="http://www.w3.org/2000/svg">
    {{-- Bayangan --}}
    <ellipse cx="200" cy="276" rx="104" ry="12" fill="#06142b" opacity=".35"/>

    {{-- Properti di belakang maskot --}}
    @switch($jenis)
        @case('kunci')
            {{-- Pintu terkunci --}}
            <rect x="28" y="96" width="78" height="172" rx="8" fill="#1a4180" stroke="#cfe0ff" stroke-opacity=".5" stroke-width="3"/>
            <rect x="40" y="110" width="54" height="64" rx="4" fill="none" stroke="#cfe0ff" stroke-opacity=".35" stroke-width="2"/>
            <rect x="40" y="186" width="54" height="68" rx="4" fill="none" stroke="#cfe0ff" stroke-opacity=".35" stroke-width="2"/>
            <path d="M58 168v-12a9 9 0 0 1 18 0v12" fill="none" stroke="#ffe08a" stroke-width="5" stroke-linecap="round"/>
            <rect x="52" y="166" width="30" height="24" rx="5" fill="#e8b923"/>
            <circle cx="67" cy="176" r="3.5" fill="#0a1f3d"/><path d="M67 178v6" stroke="#0a1f3d" stroke-width="3" stroke-linecap="round"/>
            @break
        @case('cari')
            {{-- Tanda tanya melayang & peta --}}
            <text x="64" y="96" font-size="44" font-weight="800" fill="#ffe08a" opacity=".85" font-family="inherit">?</text>
            <text x="292" y="70" font-size="34" font-weight="800" fill="#cfe0ff" opacity=".7" font-family="inherit">?</text>
            <text x="300" y="150" font-size="26" font-weight="800" fill="#ffe08a" opacity=".6" font-family="inherit">?</text>
            <g transform="rotate(-8 60 220)">
                <path d="M24 196l26-8 26 8 26-8v64l-26 8-26-8-26 8z" fill="#cfe0ff" opacity=".9"/>
                <path d="M50 188v64M76 196v64" stroke="#1a4180" stroke-width="2" opacity=".5"/>
                <path d="M34 238q14-20 30-10t30-16" fill="none" stroke="#ef4444" stroke-width="3" stroke-dasharray="5 5" stroke-linecap="round"/>
                <path d="M88 206l8 8m0-8-8 8" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
            </g>
            @break
        @case('rusak')
            {{-- Laptop rusak berasap --}}
            <g transform="translate(18 150)">
                <path d="M16 10h92a8 8 0 0 1 8 8v66H8V18a8 8 0 0 1 8-8z" fill="#1a4180" stroke="#cfe0ff" stroke-opacity=".6" stroke-width="3"/>
                <path d="M-4 84h132l-8 18H4z" fill="#cfe0ff" opacity=".85"/>
                <path d="M40 38l12 12m0-12-12 12M72 38l12 12m0-12-12 12" stroke="#ffe08a" stroke-width="4" stroke-linecap="round"/>
                <path d="M46 70q16-10 32 0" fill="none" stroke="#ffe08a" stroke-width="4" stroke-linecap="round"/>
                <circle cx="40" cy="-14" r="12" fill="#cfe0ff" opacity=".35"/>
                <circle cx="58" cy="-30" r="16" fill="#cfe0ff" opacity=".25"/>
                <circle cx="80" cy="-48" r="20" fill="#cfe0ff" opacity=".18"/>
                <path d="M100 0l8-14 4 10 8-12" fill="none" stroke="#ffe08a" stroke-width="3" stroke-linejoin="round"/>
            </g>
            @break
        @case('umum')
            <circle cx="292" cy="78" r="30" fill="#fff" opacity=".95"/>
            <path d="M276 100l-10 18 22-12z" fill="#fff" opacity=".95"/>
            <text x="281" y="92" font-size="38" font-weight="800" fill="#1a4180" font-family="inherit">?</text>
            @break
    @endswitch

    {{-- Maskot --}}
    <g class="goyang">
        {{-- Tunas di kepala --}}
        <path d="M200 120q-2-26 10-38" fill="none" stroke="#3fae6a" stroke-width="5" stroke-linecap="round"/>
        <path d="M210 82q22-12 34 4q-20 10-34-4z" fill="#4cc27a"/>
        <path d="M208 92q-22-14-34 0q18 12 34 0z" fill="#3fae6a"/>

        {{-- Badan --}}
        <path d="M200 116c48 0 82 42 82 96v30a16 16 0 0 1-16 16H134a16 16 0 0 1-16-16v-30c0-54 34-96 82-96z" fill="#e8b923"/>
        <ellipse cx="200" cy="222" rx="50" ry="30" fill="#ffe08a" opacity=".55"/>
        <path d="M150 140q20-16 44-18" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" opacity=".45"/>
        {{-- Kaki --}}
        <rect x="150" y="252" width="26" height="18" rx="9" fill="#d4a017"/>
        <rect x="224" y="252" width="26" height="18" rx="9" fill="#d4a017"/>

        {{-- Wajah --}}
        @if ($jenis === 'rusak')
            {{-- Mata pusing --}}
            <path d="M168 176a8 8 0 1 1 8 8a4 4 0 1 1-4-4" fill="none" stroke="#0a1f3d" stroke-width="3.5" stroke-linecap="round"/>
            <path d="M224 176a8 8 0 1 1 8 8a4 4 0 1 1-4-4" fill="none" stroke="#0a1f3d" stroke-width="3.5" stroke-linecap="round"/>
            <path d="M184 212q16 -8 32 0" fill="none" stroke="#0a1f3d" stroke-width="4" stroke-linecap="round"/>
            {{-- Plester & keringat --}}
            <g transform="rotate(-20 236 136)"><rect x="216" y="128" width="40" height="14" rx="7" fill="#fff"/><path d="M232 131v8m8-8v8" stroke="#e8b923" stroke-width="2"/></g>
            <path d="M268 150q6 10 0 14q-6-4 0-14z" fill="#7cc4ff"/>
        @elseif ($jenis === 'cari')
            <g class="kedip"><ellipse cx="176" cy="180" rx="7" ry="10" fill="#0a1f3d"/><circle cx="178" cy="176" r="2.5" fill="#fff"/></g>
            <ellipse cx="226" cy="178" rx="14" ry="18" fill="#0a1f3d"/><circle cx="231" cy="171" r="5" fill="#fff"/>
            <ellipse cx="200" cy="214" rx="9" ry="7" fill="#0a1f3d"/>
        @else
            <g class="kedip">
                <ellipse cx="178" cy="180" rx="7.5" ry="10.5" fill="#0a1f3d"/><circle cx="180.5" cy="176" r="2.8" fill="#fff"/>
                <ellipse cx="222" cy="180" rx="7.5" ry="10.5" fill="#0a1f3d"/><circle cx="224.5" cy="176" r="2.8" fill="#fff"/>
            </g>
            @if ($jenis === 'stop')
                <path d="M166 160l16 6M234 160l-16 6" stroke="#0a1f3d" stroke-width="4" stroke-linecap="round"/>
                <path d="M188 214h24" stroke="#0a1f3d" stroke-width="4" stroke-linecap="round"/>
            @elseif ($jenis === 'umum')
                <path d="M188 212q12 -6 24 2" fill="none" stroke="#0a1f3d" stroke-width="4" stroke-linecap="round"/>
            @else
                <path d="M186 206q14 14 28 0" fill="none" stroke="#0a1f3d" stroke-width="4" stroke-linecap="round"/>
            @endif
        @endif
        <circle cx="160" cy="200" r="8" fill="#ff8fa3" opacity=".75"/>
        <circle cx="240" cy="200" r="8" fill="#ff8fa3" opacity=".75"/>

        {{-- Tangan & barang yang dipegang --}}
        @switch($jenis)
            @case('kunci')
                <path d="M124 204q-22 -4 -28 -20" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M276 204q24 -6 30 -28" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <g transform="rotate(-35 314 160)">
                    <circle cx="314" cy="150" r="15" fill="none" stroke="#ffe08a" stroke-width="7"/>
                    <path d="M314 165v44m0-14h10m-10 10h8" stroke="#ffe08a" stroke-width="7" stroke-linecap="round"/>
                </g>
                @break
            @case('stop')
                <path d="M124 206q-20 2 -26 18" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M278 196q18 -24 22 -52" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M300 144v-20" stroke="#cfe0ff" stroke-width="6" stroke-linecap="round"/>
                <polygon points="286,48 314,48 334,68 334,96 314,116 286,116 266,96 266,68" fill="#ef4444" stroke="#fff" stroke-width="5"/>
                <text x="300" y="89" text-anchor="middle" font-size="19" font-weight="800" fill="#fff" font-family="inherit">STOP</text>
                @break
            @case('cari')
                <path d="M124 206q-20 2 -26 18" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M276 210q20 -8 18 -26" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <circle cx="230" cy="178" r="30" fill="#cfe0ff" fill-opacity=".25" stroke="#1a4180" stroke-width="8"/>
                <path d="M254 200l38 30" stroke="#1a4180" stroke-width="12" stroke-linecap="round"/>
                @break
            @case('rusak')
                <path d="M124 204q-26 -2 -36 -18" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M276 206q22 6 28 -14" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <g transform="rotate(30 306 170)">
                    <path d="M306 150v46" stroke="#cfe0ff" stroke-width="9" stroke-linecap="round"/>
                    <path d="M294 140a14 14 0 1 0 24 0l-6 8h-12z" fill="#cfe0ff"/>
                </g>
                @break
            @default
                {{-- Mengangkat bahu --}}
                <path d="M124 200q-24 -10 -30 -34" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
                <path d="M276 200q24 -10 30 -34" fill="none" stroke="#e8b923" stroke-width="14" stroke-linecap="round"/>
        @endswitch
    </g>
</svg>
