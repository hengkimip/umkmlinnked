@php
    /** @var \App\Models\User $authUser */
    $authUser = auth()->user();

    $menu = array_filter([
        [
            // Peta interaktif (/peta-interaktif) — untuk Super Admin & Admin OPD, di atas Dashboard
            'route' => 'superadmin.peta-interaktif', 'match' => 'superadmin.peta-interaktif*', 'label' => 'Peta Interaktif',
            'icon'  => 'M9 6.75V15m6-6v8.25m.5 3.75 4.88-2.44A1 1 0 0 0 21 18.4V4.62a1 1 0 0 0-1.38-.93l-4.12 2.06a1 1 0 0 1-.9 0L9.4 3.2a1 1 0 0 0-.9 0L3.62 5.64A1 1 0 0 0 3 6.53V20.4a1 1 0 0 0 1.38.93l4.12-2.06a1 1 0 0 1 .9 0l5.2 2.6a1 1 0 0 0 .9 0Z',
        ],
        [
            // Super Admin: /superadmin/dashboard · Admin OPD: /admin/dashboard
            'route' => $authUser->isSuperAdmin() ? 'superadmin.dashboard' : 'admin.dashboard',
            'match' => $authUser->isSuperAdmin() ? 'superadmin.dashboard' : 'admin.dashboard', 'label' => 'Dashboard',
            'icon'  => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        ],
        [
            'route' => 'admin.profil-umkm.index', 'match' => 'admin.profil-umkm.*', 'label' => 'Kelola Profil UMKM',
            'icon'  => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z',
        ],
        [
            'route' => 'admin.import.index', 'match' => 'admin.import.*', 'label' => 'Import Data',
            'icon'  => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5',
        ],
        $authUser->isSuperAdmin() ? [
            // Akses OPD, batas admin per OPD, akun Super Admin (kuota Bank Indonesia)
            'route' => 'superadmin.akses.index', 'match' => 'superadmin.akses.*', 'label' => 'Kelola Akses',
            'icon'  => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        ] : null,
        [
            // Antrean review kemungkinan duplikat (lihat DeteksiDuplikatService)
            'route' => 'admin.duplikat.index', 'match' => 'admin.duplikat.*', 'label' => 'Antrean Duplikat',
            'badge' => \App\Models\UmkmDuplikat::terlihatOleh($authUser)->menunggu()->count(),
            'icon'  => 'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75',
        ],
        [
            'route' => 'admin.produk.upload-foto', 'match' => 'admin.produk.*', 'label' => 'Foto Produk',
            'icon'  => 'm2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm9-13.5h.01v.01h-.01V7.5Zm.38 0a.38.38 0 1 1-.75 0 .38.38 0 0 1 .75 0Z',
        ],
    ]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel Admin') — UMKMLinked.ID</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased" x-data="{ nav: false }" @keydown.escape.window="nav = false">

{{-- Overlay mobile --}}
<div x-show="nav" x-cloak x-transition.opacity @click="nav = false"
     class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

{{-- ==================== SIDEBAR ==================== --}}
<aside :class="nav && '!translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col overflow-hidden bg-gradient-to-b from-navy-900 to-navy-950 text-white transition-transform duration-200 lg:translate-x-0">
    {{-- Motif belah ketupat & siluet Kalbar, sama dengan panel biru halaman login --}}
    <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-gold-500/10 blur-2xl" aria-hidden="true"></div>
    <div class="kalbar-motif" aria-hidden="true"></div>
    <div class="kalbar-skyline" aria-hidden="true"></div>

    <div class="relative flex h-16 items-center justify-between border-b border-white/10 px-5">
        <a href="{{ $authUser->dashboardUrl() }}" class="text-lg font-extrabold tracking-tight">
            UMKMLinked<span class="text-gold-400">.ID</span>
        </a>
        <button type="button" @click="nav = false" class="rounded p-1 text-slate-300 hover:text-white lg:hidden" aria-label="Tutup menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="relative flex-1 space-y-1 overflow-y-auto p-3 text-sm" aria-label="Menu admin">
        <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu</p>
        @foreach ($menu as $item)
            @php $aktif = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition',
                   'bg-white/10 text-white' => $aktif,
                   'text-slate-300 hover:bg-white/5 hover:text-white' => ! $aktif,
               ])
               @if ($aktif) aria-current="page" @endif>
                <svg class="h-5 w-5 flex-shrink-0 {{ $aktif ? 'text-gold-400' : '' }}" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
                @if (! empty($item['badge']))
                    <span class="ml-auto rounded-full bg-gold-500 px-2 py-0.5 text-[11px] font-bold text-navy-950"
                          title="{{ $item['badge'] }} menunggu keputusan">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach

        <p class="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Situs</p>
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            Lihat Website
        </a>
    </nav>

    <div class="relative border-t border-white/10 bg-navy-950/40 p-4 backdrop-blur-sm">
        <div class="mb-3 flex items-center gap-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gold-500 text-sm font-bold text-navy-950">
                {{ strtoupper(mb_substr($authUser->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ $authUser->name }}</p>
                <p class="truncate text-xs text-slate-400">
                    {{ $authUser->roleLabel() }}@if ($authUser->instansi()) · {{ $authUser->instansi() }}@endif
                </p>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}"
           @class([
               'mb-2 flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
               'bg-white/10 text-white' => request()->routeIs('profile.*'),
               'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('profile.*'),
           ])>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.03 5.91c-.57-.1-1.17.03-1.58.44L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.82c0-.6.24-1.17.66-1.59l6.4-6.4c.41-.41.54-1.01.44-1.58A6 6 0 1 1 21.75 8.25Z"/></svg>
            Profil &amp; kata sandi
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/15 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                Keluar
            </button>
        </form>
    </div>
</aside>

{{-- ==================== KONTEN ==================== --}}
<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
        <button type="button" @click="nav = true" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
        <div class="min-w-0">
            <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">@yield('heading', 'Panel Admin')</h1>
        </div>
        <span class="ml-auto hidden rounded-full bg-navy-900/5 px-3 py-1 text-xs font-semibold text-navy-800 sm:inline-block">
            {{ $authUser->roleLabel() }}
        </span>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:py-8">
        @if (session('success'))
            <div role="status" class="mb-6 flex gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
