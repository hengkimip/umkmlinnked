{{--
    Tampilan halaman galat (401, 403, 404, 500, dst.): biru penuh layar dengan motif & siluet
    Kalimantan Barat seperti header, ilustrasi lucu, dan pesan dalam bahasa masyarakat.
    Sengaja mandiri (CSS & SVG inline, tanpa Vite/database/sesi) agar tetap tampil saat sistem bermasalah.
    Variabel: $kode, $teknis, $pesan, $ilustrasi, $tombol (opsional: 'masuk' | 'muat-ulang')
--}}
@php
    $svg = fn (string $f) => 'data:image/svg+xml;base64,' . base64_encode((string) @file_get_contents(resource_path("images/{$f}")));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f2a52">
    <title>{{ $kode }} · {{ $teknis }} — UMKMLinked.ID</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            min-height: 100vh; min-height: 100dvh;
            display: flex; flex-direction: column;
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #fff;
            background: radial-gradient(120% 90% at 15% 0%, #1a4180 0%, #0f2a52 45%, #0a1f3d 100%);
            position: relative; overflow-x: hidden;
        }
        /* Motif & siluet Kalbar — sama dengan header di berbagai halaman */
        .motif {
            position: fixed; inset: 0; pointer-events: none;
            background: url("{{ $svg('kalbar-pattern.svg') }}") repeat; background-size: 96px;
            opacity: .12;
            -webkit-mask-image: linear-gradient(160deg, #000 0%, rgba(0,0,0,.35) 70%);
                    mask-image: linear-gradient(160deg, #000 0%, rgba(0,0,0,.35) 70%);
        }
        .siluet {
            position: fixed; left: 0; right: 0; bottom: -1px; height: 30vh; max-height: 260px; pointer-events: none;
            background: url("{{ $svg('kalbar-skyline.svg') }}") no-repeat center bottom / cover;
            opacity: .14;
        }
        header, main, footer { position: relative; z-index: 1; }
        header { padding: 20px clamp(16px, 4vw, 40px); }
        header img { height: 34px; width: auto; filter: brightness(0) invert(1); opacity: .95; }
        main {
            flex: 1; display: grid; align-items: center; gap: clamp(16px, 4vw, 56px);
            grid-template-columns: 1fr; justify-items: center; text-align: center;
            width: 100%; max-width: 1080px; margin: 0 auto; padding: 8px clamp(16px, 4vw, 40px) 40px;
        }
        @media (min-width: 860px) {
            main { grid-template-columns: 1.05fr 1fr; justify-items: start; text-align: left; }
        }
        .ilustrasi { width: min(100%, 420px); height: auto; filter: drop-shadow(0 24px 40px rgba(5, 15, 35, .45)); }
        .ilustrasi .goyang { transform-origin: 50% 90%; animation: goyang 3.2s ease-in-out infinite; }
        .ilustrasi .kedip { animation: kedip 4s infinite; transform-origin: center; transform-box: fill-box; }
        @keyframes goyang { 0%, 100% { transform: rotate(-2deg) translateY(0); } 50% { transform: rotate(2deg) translateY(-6px); } }
        @keyframes kedip { 0%, 92%, 100% { transform: scaleY(1); } 95% { transform: scaleY(.1); } }
        @media (prefers-reduced-motion: reduce) { .ilustrasi .goyang, .ilustrasi .kedip { animation: none; } }
        .kode {
            margin: 0; font-weight: 800; letter-spacing: -.04em; line-height: .9;
            font-size: clamp(5.5rem, 18vw, 9.5rem);
            background: linear-gradient(180deg, #ffe08a 0%, #e8b923 55%, #d4a017 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .teknis {
            display: inline-block; margin: 14px 0 0; padding: 5px 12px; border-radius: 999px;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
            font-size: .78rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #cfe0ff;
        }
        .pesan { margin: 18px 0 0; max-width: 30ch; font-size: clamp(1.25rem, 3.4vw, 1.75rem); font-weight: 800; line-height: 1.3; }
        .aksi { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; justify-content: inherit; }
        @media (max-width: 859px) { .aksi { justify-content: center; } .pesan { margin-inline: auto; } }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; height: 46px; padding: 0 22px; border-radius: 999px;
            font: 600 .95rem/1 inherit; text-decoration: none; cursor: pointer; border: 1px solid transparent;
            transition: transform .15s, background .15s;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:focus-visible { outline: 3px solid #e8b923; outline-offset: 3px; }
        .btn-utama { background: #e8b923; color: #0a1f3d; }
        .btn-utama:hover { background: #ffd04a; }
        .btn-garis { background: transparent; color: #fff; border-color: rgba(255,255,255,.4); }
        .btn-garis:hover { background: rgba(255,255,255,.1); }
        footer { padding: 18px; text-align: center; font-size: .78rem; color: rgba(255,255,255,.6); }
    </style>
</head>
<body>
    <div class="motif" aria-hidden="true"></div>
    <div class="siluet" aria-hidden="true"></div>

    <header>
        <a href="{{ url('/') }}" aria-label="UMKMLinked — ke beranda">
            <img src="{{ asset('logo-umkmlinked.png') }}" alt="UMKMLinked" width="480" height="147">
        </a>
    </header>

    <main>
        <div>
            @include('errors.ilustrasi', ['jenis' => $ilustrasi])
        </div>
        <div>
            <p class="kode" aria-hidden="true">{{ $kode }}</p>
            <p class="teknis">{{ $kode }} · {{ $teknis }}</p>
            <h1 class="pesan">{{ $pesan }}</h1>
            <div class="aksi">
                @if (($tombol ?? null) === 'masuk')
                    <a href="{{ url('/login') }}" class="btn btn-utama">Masuk</a>
                @elseif (($tombol ?? null) === 'muat-ulang')
                    <button type="button" class="btn btn-utama" onclick="location.reload()">Coba lagi</button>
                @endif
                <a href="{{ url('/') }}" @class(['btn', 'btn-utama' => empty($tombol), 'btn-garis' => ! empty($tombol)])>Ke beranda</a>
                <button type="button" class="btn btn-garis" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')">
                    Halaman sebelumnya
                </button>
            </div>
        </div>
    </main>

    <footer>UMKMLinked.ID · Bank Indonesia Wilayah Kalimantan Barat</footer>
</body>
</html>
