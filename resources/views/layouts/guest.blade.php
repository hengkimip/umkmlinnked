<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title ?? 'Masuk' }} — UMKMLinked.ID</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-slate-50">
        <div class="min-h-screen grid lg:grid-cols-2">

            {{-- Panel merek (desktop) --}}
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-navy-900 to-navy-950 p-12 text-white">
                <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-gold-500/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-sky-400/10 blur-3xl"></div>
                {{-- Motif belah ketupat/corak insang & siluet Radakng–Tugu Khatulistiwa–Kapuas --}}
                <div class="kalbar-motif" aria-hidden="true"></div>
                <div class="kalbar-skyline" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="relative text-xl font-bold tracking-tight">
                    UMKMLinked<span class="text-gold-400">.ID</span>
                </a>

                <div class="relative max-w-md">
                    <span class="inline-flex items-center gap-2 rounded-full border border-gold-400/50 bg-gold-400/10 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-gold-400">
                        <span class="h-1.5 w-1.5 rotate-45 bg-gold-400"></span>
                        Panel pengelola
                    </span>
                    <h1 class="mt-5 text-[2.5rem] font-bold leading-[1.1] tracking-[-0.04em]">
                        Direktori &amp; dashboard UMKM Kalimantan Barat
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-300">
                        Kelola data UMKM binaan, impor data dari Excel, dan pantau tingkat kematangan usaha
                        di 14 kabupaten/kota.
                    </p>
                </div>

                <p class="relative text-xs text-slate-300">
                    © {{ date('Y') }} Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat
                </p>
            </aside>

            {{-- Panel formulir --}}
            <main class="flex flex-col items-center justify-center px-4 py-10 sm:px-6">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-8 flex justify-center">
                        <picture>
                            <source srcset="{{ asset('logo-umkmlinked.webp') }}" type="image/webp">
                            <img src="{{ asset('logo-umkmlinked.png') }}" alt="UMKMLinked.ID" width="480" height="147" class="h-14 w-auto">
                        </picture>
                    </a>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        <a href="{{ route('home') }}" class="font-medium text-navy-700 hover:text-navy-900 hover:underline">
                            &larr; Kembali ke direktori UMKM
                        </a>
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>
