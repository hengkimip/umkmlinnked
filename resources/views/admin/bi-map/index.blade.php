<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="bi-data-url" content="{{ route('superadmin.peta-interaktif.data') }}">
    <meta name="kalbar-geojson-url" content="{{ asset('geo/kalbar.geojson') }}">
    <title>Peta Interaktif UMKM - KPw BI Kalimantan Barat</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif; }</style>

    {{-- Leaflet: integritas berkas CDN dicek (SRI); skrip dimuat tanpa memblokir render --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    {{-- Tailwind hasil build (dulu Tailwind CDN yang menyusun CSS di browser) --}}
    @vite(['resources/css/peta-tailwind.css', 'resources/css/bi-map.css', 'resources/js/bi-map.js'])
</head>

<body x-data="umkmApp()" @load="init()" x-cloak class="h-screen supports-[height:100dvh]:h-dvh flex flex-col overflow-hidden bg-gray-50">

    {{-- ==================== HEADER ==================== --}}
    {{-- Desktop (lg+): satu baris — logo · cari · wilayah · 4 filter · reset · semua brand · akun.
         HP/tablet: baris 1 logo · cari · tombol Filter · akun; baris 2 (dibuka tombol Filter) berisi filter. --}}
    <header class="relative z-[1100] bg-white border-b border-slate-200 px-3 py-2 sm:px-4 sm:py-3">
        <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">

            {{-- LOGO --}}
            <a href="{{ $dashboardUrl ?? route('home') }}" title="{{ $dashboardUrl ? 'Ke dashboard admin' : 'Ke beranda' }}" class="mr-1 flex-shrink-0">
                <picture>
                    <source srcset="{{ asset('logo-umkmlinked.webp') }}" type="image/webp">
                    <img src="{{ asset('logo-umkmlinked.png') }}" alt="UMKMLinked" width="480" height="147" class="h-7 w-auto sm:h-9">
                </picture>
            </a>

            {{-- SEARCH --}}
            <input x-model="searchQuery" type="search" placeholder="Cari wilayah atau nama UMKM..." aria-label="Cari wilayah atau nama UMKM"
                class="flex-1 min-w-0 lg:min-w-[8rem] px-3 py-2 border border-slate-200 rounded-lg text-[13px] focus:ring-2 focus:ring-blue-500 outline-none bg-white">

            {{-- TOMBOL FILTER (HP/tablet saja) --}}
            <button type="button" @click="filterBuka = !filterBuka" :aria-expanded="filterBuka" aria-controls="filter-bar"
                    :class="filterBuka || jumlahFilterAktif || selectedKabupaten ? 'border-[#003066] bg-blue-50 text-[#003066]' : 'border-slate-200 bg-white text-slate-700'"
                    class="lg:hidden flex flex-shrink-0 items-center gap-1.5 px-3 py-2 border rounded-lg text-[13px] font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                <span class="hidden sm:inline">Filter</span>
                <span x-show="jumlahFilterAktif + (selectedKabupaten ? 1 : 0)" x-text="jumlahFilterAktif + (selectedKabupaten ? 1 : 0)"
                      class="min-w-5 rounded-full bg-[#003066] px-1.5 text-center text-[11px] text-white"></span>
            </button>

            {{-- BARIS FILTER: selalu tampil di desktop; di HP/tablet dibuka lewat tombol Filter --}}
            <div id="filter-bar" :class="filterBuka ? 'flex' : 'hidden lg:flex'"
                 class="order-last w-full flex-wrap items-center gap-2 lg:order-none lg:w-auto lg:flex-nowrap">

                {{-- WILAYAH DROPDOWN --}}
                <select x-model="selectedKabupaten" aria-label="Wilayah"
                        class="flex-shrink-0 px-3 py-2 border border-slate-200 rounded-lg text-[13px] font-bold text-slate-700 bg-white hover:border-slate-300 cursor-pointer">
                    <option value="">Wilayah</option>
                    <template x-for="kab in kabupatens" :key="kab">
                        <option :value="kab" x-text="kab"></option>
                    </template>
                </select>

                {{-- SEKTOR / PLATFORM / JANGKAUAN / SERTIFIKASI (kotak centang) --}}
                @foreach ($filterPeta as $grup => $def)
                    <div x-data="{ buka: false }" @click.outside="buka = false" @keydown.escape="buka = false" class="relative flex-shrink-0">
                        <button type="button" @click="buka = !buka" :aria-expanded="buka"
                                :class="filter.{{ $grup }}.length ? 'border-[#003066] bg-blue-50 text-[#003066]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                class="flex items-center gap-1.5 px-3 py-2 border rounded-lg text-[13px] font-bold whitespace-nowrap transition-all">
                            {{ $def['judul'] }}
                            <span x-show="filter.{{ $grup }}.length" x-text="filter.{{ $grup }}.length"
                                  class="min-w-5 rounded-full bg-[#003066] px-1.5 text-center text-[11px] text-white"></span>
                            <svg class="w-4 h-4 transition-transform" :class="buka && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="buka" x-transition.opacity x-cloak
                             class="fixed inset-x-3 mt-1 max-h-[60vh] overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl sm:absolute sm:inset-x-auto {{ $loop->last ? 'sm:right-0' : 'sm:left-0' }} sm:top-full sm:w-72 sm:max-h-none sm:overflow-visible">
                            @foreach ($def['opsi'] as $kode => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="checkbox" value="{{ $kode }}" x-model="filter.{{ $grup }}"
                                           class="h-4 w-4 rounded border-slate-300 accent-[#003066]">
                                    <span class="flex-1">{{ $label }}</span>
                                    <span class="text-xs font-bold text-slate-400" x-text="hitungOpsi.{{ $grup }}?.{{ $kode }} ?? 0"></span>
                                </label>
                            @endforeach
                            <button type="button" x-show="filter.{{ $grup }}.length" @click="filter.{{ $grup }} = []"
                                    class="mt-1 w-full rounded-md px-2 py-1.5 text-left text-xs font-bold text-blue-700 hover:bg-blue-50">
                                Hapus pilihan
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- RESET BUTTON --}}
                <button @click="resetMap()" class="flex-shrink-0 px-3 py-2 text-[13px] font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-all whitespace-nowrap">
                    Reset
                </button>

                {{-- BERANDA (halaman publik, di tab yang sama) --}}
                <a href="{{ route('home') }}"
                   class="flex-shrink-0 px-3 py-2 bg-[#003066] text-white rounded-lg text-[13px] font-bold hover:bg-[#002550] transition-all whitespace-nowrap">
                    Beranda
                </a>
            </div>

            {{-- KELUAR (sudah login) / LOGIN ADMIN (pengunjung) --}}
            @guest
                <a href="{{ route('login') }}"
                   class="flex-shrink-0 px-3 py-2 border border-slate-200 rounded-lg text-[13px] font-bold text-slate-700 hover:bg-slate-100 transition-all whitespace-nowrap">
                    Login<span class="hidden sm:inline"> Admin</span>
                </a>
            @else
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" title="Keluar" aria-label="Keluar"
                    class="w-9 h-9 bg-blue-50 rounded-full flex items-center justify-center border border-blue-100 text-blue-700 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                </button>
            </form>
            @endguest
        </div>
    </header>

    {{-- ==================== MAIN CONTENT ==================== --}}
    <div class="flex flex-1 overflow-hidden relative">

        {{-- ==================== SIDEBAR ==================== --}}
        {{-- Desktop: di samping peta. HP/tablet: panel melayang di atas peta (maks 20rem / 85% layar) --}}
        <aside :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-[min(20rem,85vw)] lg:w-80 opacity-100'"
               class="absolute inset-y-0 left-0 z-[1002] lg:relative lg:z-auto h-full flex-shrink-0 transition-all duration-300 bg-white border-r border-slate-200 flex flex-col overflow-hidden shadow-xl lg:shadow-sm">
            
            {{-- TAB NAVIGATION --}}
            <nav class="flex-shrink-0 p-4 pb-2 flex gap-2 border-b border-slate-100">
                @if ($dashboardUrl) {{-- khusus Super Admin & Admin OPD yang login --}}
                <a href="{{ $dashboardUrl }}" title="Buka dashboard admin"
                   class="flex flex-1 items-center justify-center gap-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase text-slate-500 hover:bg-slate-50 hover:text-[#003066]">
                    Dashboard
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
                @endif
                <button @click="activeTab = 'database'"
                    :class="activeTab === 'database' ? 'bg-[#003066] text-white' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase">
                    Database
                </button>
                @if ($lengkap) {{-- program BI = jawaban kuesioner, khusus Super Admin --}}
                <button @click="activeTab = 'program'"
                    :class="activeTab === 'program' ? 'bg-[#003066] text-white' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase">
                    Program
                </button>
                @endif
            </nav>

            {{-- CONTENT AREA --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-5">

                {{-- LOADING --}}
                <div x-show="isLoading" class="flex flex-col items-center justify-center py-16 text-center space-y-3">
                    <div class="w-10 h-10 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                    <p class="text-xs text-slate-400 font-bold">Memuat data UMKM...</p>
                </div>

                <div x-show="!isLoading && loadError" x-cloak role="alert"
                     class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700" x-text="loadError"></div>

                @if ($lengkap)
                <div x-show="!isLoading && tidakDiketahui > 0" x-cloak
                     class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <p class="font-bold" x-text="tidakDiketahui + ' UMKM belum memiliki kabupaten/kota'"></p>
                    <p class="mt-1">Tidak tampil pada label peta. Cantumkan nama kabupaten/kota pada alamat usaha di
                        <a href="{{ route('admin.profil-umkm.index') }}" class="font-bold underline">Kelola Profil UMKM</a>.</p>
                </div>
                @endif

                <template x-if="!isLoading">
                    <div>
                        {{-- ==================== TAB 2: DATABASE UMKM ==================== --}}
                        <div x-show="activeTab === 'database'" class="space-y-4">
                            {{-- Saat masuk / Reset (belum ada pilihan) tampil teks sambutan --}}
                            <template x-if="tampilSambutan">
                                <div class="space-y-5">
                                    @include('admin.bi-map._sambutan', ['untukAdmin' => $lengkap])
                                    <button type="button" @click="lihatDaftar = true"
                                            class="w-full rounded-lg border-2 border-[#003066] px-4 py-2.5 text-sm font-bold text-[#003066] transition-all hover:bg-blue-50">
                                        Lihat daftar semua UMKM
                                    </button>
                                </div>
                            </template>

                            <div x-show="!tampilSambutan" class="space-y-4">
                            <h2 class="text-lg font-black text-slate-900">Daftar UMKM</h2>
                            
                            {{-- FILTER INFO --}}
                            <template x-if="selectedCity">
                                {{-- Kota/kabupaten dipilih dari peta --}}
                                <div class="rounded-xl bg-[#003066] p-4 text-white shadow-md">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Kota / Kabupaten terpilih</p>
                                    <p class="mt-0.5 text-lg font-black leading-tight" x-text="selectedCity.name"></p>
                                    <p class="text-xs text-blue-200" x-text="'Ibu kota: ' + selectedCity.ibukota"></p>
                                    <p class="mt-2 inline-block rounded-full bg-white/15 px-2.5 py-1 text-xs font-bold"
                                       x-text="filteredTable.length + ' UMKM dari database'"></p>
                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        <div class="bg-white/10 p-2 rounded-lg text-center">
                                            <p class="text-[8px] font-bold opacity-70">DASAR</p>
                                            <p class="text-lg font-black" x-text="currentStats.dasar || 0"></p>
                                        </div>
                                        <div class="bg-white/10 p-2 rounded-lg text-center">
                                            <p class="text-[8px] font-bold opacity-70">BERKEMBANG</p>
                                            <p class="text-lg font-black" x-text="currentStats.berkembang || 0"></p>
                                        </div>
                                        <div class="bg-white/10 p-2 rounded-lg text-center">
                                            <p class="text-[8px] font-bold opacity-70">UNGGULAN</p>
                                            <p class="text-lg font-black" x-text="currentStats.unggulan || 0"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!selectedCity" class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                <p class="text-xs text-blue-600 font-bold">📊 Filter Aktif:</p>
                                <div class="text-sm font-black text-blue-900 mt-1">
                                    <template x-if="!selectedKabupaten && !jumlahFilterAktif">
                                        <p>Menampilkan Semua UMKM</p>
                                    </template>
                                    <template x-if="selectedKabupaten || jumlahFilterAktif">
                                        <div class="space-y-1">
                                            <template x-if="selectedKabupaten">
                                                <p x-text="'Wilayah: ' + selectedKabupaten"></p>
                                            </template>
                                            @foreach ($filterPeta as $grup => $def)
                                                <template x-if="filter.{{ $grup }}.length">
                                                    <p x-text="@js($def['judul'] . ': ') + filter.{{ $grup }}.map((k) => @js($def['opsi'])[k]).join(', ')"></p>
                                                </template>
                                            @endforeach
                                        </div>
                                    </template>
                                </div>
                                <p class="text-xs text-blue-500 font-bold mt-2" x-text="'Total: ' + filteredTable.length + ' UMKM'"></p>
                            </div>

                            {{-- SEARCH BAR --}}
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input x-model="searchQuery" type="text" placeholder="Cari UMKM..." 
                                    class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>

                            {{-- UMKM LIST --}}
                            <div class="space-y-2">
                                <template x-if="filteredTable.length === 0">
                                    <div class="text-center py-8">
                                        <p class="text-xs text-slate-400 font-bold">Tidak ada UMKM sesuai filter</p>
                                    </div>
                                </template>

                                <template x-for="(umkm, index) in filteredTable" :key="umkm.id">
                                    <div class="bg-white border-2 border-slate-200 rounded-xl p-3.5 hover:shadow-md hover:border-blue-300 transition-all">

                                        {{-- NO, NAMA, & STATUS — nama UMKM dibuat menonjol --}}
                                        <div class="flex items-start gap-2.5 mb-2">
                                            <span class="flex-shrink-0 w-7 h-7 bg-[#003066] text-white rounded-full flex items-center justify-center text-xs font-bold" x-text="(index + 1)"></span>
                                            <div class="flex-1 min-w-0">
                                                <a :href="umkm.url || umkm.url_publik"
                                                   class="block break-words text-[15px] font-extrabold leading-snug text-[#003066] hover:text-blue-700 hover:underline"
                                                   x-text="umkm.nama"></a>
                                                <p class="mt-0.5 text-xs font-semibold text-slate-500">
                                                    <span x-text="umkm.sektor"></span><span x-show="umkm.kecamatan" x-text="' · Kec. ' + umkm.kecamatan"></span>
                                                </p>
                                            </div>
                                            <span :class="{
                                                'bg-green-100 text-green-700': umkm.status === 'Unggulan',
                                                'bg-blue-100 text-blue-700': umkm.status === 'Berkembang',
                                                'bg-slate-100 text-slate-600': umkm.status === 'Dasar'
                                            }" class="text-[10px] font-black px-2 py-0.5 rounded-full flex-shrink-0 whitespace-nowrap" x-text="umkm.status"></span>
                                        </div>

                                        {{-- ALAMAT — dibuat menonjol --}}
                                        <div class="mb-2.5 flex gap-2 rounded-lg border-l-4 border-amber-400 bg-amber-50 px-3 py-2">
                                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Alamat</p>
                                                <p class="break-words text-[13px] font-semibold leading-snug text-slate-800"
                                                   :class="!umkm.alamat && 'italic font-normal text-slate-400'"
                                                   x-text="umkm.alamat || 'Belum diisi'"></p>
                                            </div>
                                        </div>

                                        {{-- LOKASI: pin (cari nama jalan di peta) & Google Maps --}}
                                        <div x-show="umkm.alamat" class="-mt-1 mb-2.5 flex flex-wrap items-center gap-1.5">
                                            <button type="button" x-show="namaJalan(umkm.alamat)" @click="cariJalan(umkm)"
                                                    :disabled="cariJalanId !== null"
                                                    :title="'Cari ' + namaJalan(umkm.alamat) + ' di peta'"
                                                    class="inline-flex max-w-full items-center gap-1 rounded-md border border-amber-300 bg-white px-2 py-1 text-[11px] font-bold text-amber-700 transition-all hover:bg-amber-50 disabled:cursor-wait disabled:opacity-60">
                                                <svg x-show="cariJalanId !== umkm.id" class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
                                                </svg>
                                                <span x-show="cariJalanId === umkm.id" class="h-3 w-3 flex-shrink-0 animate-spin rounded-full border-2 border-amber-200 border-t-amber-600"></span>
                                                <span class="truncate" x-text="cariJalanId === umkm.id ? 'Mencari…' : namaJalan(umkm.alamat)"></span>
                                            </button>
                                            <a :href="gmapUrl(umkm)" target="_blank" rel="noopener noreferrer" title="Buka alamat di Google Maps"
                                               class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] font-bold text-slate-700 transition-all hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
                                                </svg>
                                                Google Maps
                                            </a>
                                        </div>

                                        {{-- CONTACT INFO --}}
                                        <div class="space-y-1 text-xs text-slate-600 mb-2.5 pb-2.5 border-b border-slate-100">
                                            <template x-if="umkm.whatsapp">
                                                <p><span class="font-bold">💬</span> <a :href="'https://wa.me/' + umkm.whatsapp" target="_blank" rel="noopener noreferrer" class="font-semibold text-green-700 hover:underline" x-text="umkm.whatsapp"></a></p>
                                            </template>
                                            <template x-if="umkm.email">
                                                <p class="truncate"><span class="font-bold">✉️</span> <a :href="'mailto:' + umkm.email" class="text-blue-600 hover:underline" x-text="umkm.email"></a></p>
                                            </template>
                                            <template x-if="umkm.website">
                                                <p class="truncate"><span class="font-bold">🌐</span> <a :href="umkm.website" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline" x-text="umkm.website"></a></p>
                                            </template>
                                        </div>

                                        {{-- STATS --}}
                                        <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                                            <template x-if="umkm.tenaga_kerja">
                                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded" x-text="'👥 ' + umkm.tenaga_kerja + ' TK'"></span>
                                            </template>
                                            <template x-if="umkm.tahun_berdiri">
                                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded" x-text="'📅 ' + umkm.tahun_berdiri"></span>
                                            </template>
                                            <template x-if="umkm.skor != null"> {{-- skor: khusus Super Admin --}}
                                            <span :class="{
                                                'bg-green-100 text-green-700': umkm.skor >= 75,
                                                'bg-blue-100 text-blue-700': umkm.skor >= 45 && umkm.skor < 75,
                                                'bg-gray-100 text-gray-700': umkm.skor < 45
                                            }" class="px-2 py-1 rounded" x-text="'⭐ ' + umkm.skor"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            </div>
                        </div>

                        @if ($lengkap)
                        {{-- ==================== TAB 3: PROGRAM ==================== --}}
                        <div x-show="activeTab === 'program'" class="space-y-4">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">Program Rekomendasi</h2>
                                <p class="mt-1 text-xs text-slate-500">Referensi dari program Bank Indonesia yang pernah dituliskan UMKM pada profilnya.</p>
                            </div>

                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                <p class="text-xs font-bold text-blue-900"
                                   x-text="(selectedCity ? selectedCity.name : (selectedKabupaten || 'Seluruh Kalimantan Barat')) + (jumlahFilterAktif ? ' · terfilter' : '')"></p>
                                <p class="mt-1 text-xs font-bold text-blue-500"
                                   x-text="programReferensi.length + ' program dari ' + filteredDatabase.filter((u) => u.program?.length).length + ' UMKM'"></p>
                            </div>

                            <template x-if="programReferensi.length === 0">
                                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center">
                                    <p class="text-xs font-bold text-slate-500">Belum ada program yang dituliskan UMKM di cakupan ini.</p>
                                    <p class="mt-1 text-xs text-slate-400">Isi kolom "Program yang pernah diikuti dari Bank Indonesia" di
                                        <a href="{{ route('admin.profil-umkm.index') }}" class="font-bold text-blue-600 underline">Kelola Profil UMKM</a> atau lewat import Excel.</p>
                                </div>
                            </template>

                            <div class="space-y-2">
                                <template x-for="p in programReferensi" :key="p.nama">
                                    <div x-data="{ buka: false }" class="rounded-xl border-2 border-slate-200 bg-white hover:border-blue-300 transition-all">
                                        <button type="button" @click="buka = !buka" :aria-expanded="buka"
                                                class="flex w-full items-start gap-2.5 p-3 text-left">
                                            <span class="flex-1 text-sm font-extrabold leading-snug text-[#003066]" x-text="p.nama"></span>
                                            <span class="flex-shrink-0 rounded-full bg-[#003066] px-2 py-0.5 text-[10px] font-black text-white"
                                                  x-text="p.umkm.length + ' UMKM'"></span>
                                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-slate-400 transition-transform" :class="buka && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <ul x-show="buka" x-cloak class="space-y-1 border-t border-slate-100 px-3 py-2">
                                            <template x-for="u in p.umkm" :key="u.id">
                                                <li class="text-xs">
                                                    <a :href="u.url" target="_blank" rel="noopener" class="font-semibold text-slate-700 hover:text-blue-700 hover:underline" x-text="u.nama"></a>
                                                    <span class="text-slate-400" x-text="' · ' + u.kab"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @endif
                    </div>
                </template>

            </div>

            {{-- FOOTER STATS --}}
            <div class="border-t border-slate-100 p-4 bg-blue-50">
                <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Total Terdata</p>
                <p class="text-2xl font-black text-blue-900" x-text="databaseUMKM.length + ' UMKM'"></p>
            </div>

        </aside>

        {{-- Latar redup (HP/tablet) saat panel terbuka — ketuk untuk menutup --}}
        <div x-show="!sidebarCollapsed" x-transition.opacity x-cloak @click="toggleSidebar()"
             class="lg:hidden absolute inset-0 z-[1001] bg-slate-900/30" aria-hidden="true"></div>

        {{-- ==================== TOGGLE SIDEBAR BUTTON ==================== --}}
        {{-- Ikut tepi sidebar; saat sidebar ditutup menempel di tepi kiri layar --}}
        <button type="button" @click="toggleSidebar()"
            :class="sidebarCollapsed ? 'left-0' : 'left-[min(20rem,85vw)] lg:left-80'"
            :title="sidebarCollapsed ? 'Buka panel' : 'Tutup panel'" :aria-label="sidebarCollapsed ? 'Buka panel' : 'Tutup panel'"
            :aria-expanded="!sidebarCollapsed"
            class="toggle-sidebar-btn transition-all duration-300 fixed top-1/2 -translate-y-1/2 z-[1003] bg-white border-2 border-slate-200 border-l-0 rounded-r-lg p-2 hover:bg-slate-100 active:scale-95">
            <svg class="w-6 h-6 text-[#003066] transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        {{-- ==================== MAP AREA ==================== --}}
        <main class="flex-1 relative h-full w-full min-w-0">
            <div id="map" class="w-full h-full"></div>

            {{-- LEGEND --}}
            <div class="absolute top-6 right-6 bg-white p-4 rounded-lg shadow-lg z-[500] border border-slate-200 hidden md:block pointer-events-auto">
                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-3">Keterangan Level</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-[10px] font-bold text-slate-700">Unggulan (≥75)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-[10px] font-bold text-slate-700">Berkembang (45-74)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-slate-400"></div>
                        <span class="text-[10px] font-bold text-slate-700">Dasar (<45)</span>
                    </div>
                </div>
            </div>

            {{-- PESAN PENCARIAN JALAN --}}
            <div x-show="pesanPeta" x-transition.opacity x-cloak role="status"
                 class="absolute left-1/2 top-4 z-[600] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-800 shadow-lg"
                 x-text="pesanPeta"></div>

            {{-- TOTAL TERDATA --}}
            <div class="absolute bottom-4 left-4 z-[500] sm:bottom-8 sm:left-6">
                <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Total Terdata</p>
                <p class="text-2xl font-black text-[#003066] sm:text-3xl" x-text="databaseUMKM.length"></p>
            </div>

            {{-- FOCUS KALBAR --}}
            <div class="absolute bottom-6 right-3 z-[500] sm:bottom-8 sm:right-6">
                <button @click="focusKalbar()"
                    class="px-3 py-2 bg-[#003066] text-white rounded-lg text-xs font-black uppercase tracking-wider shadow-lg hover:bg-[#002550] active:scale-95 transition-all sm:px-6 sm:py-3 sm:text-sm sm:tracking-widest">
                    Focus Kalbar
                </button>
            </div>

        </main>

    </div>

    {{-- FOOTER --}}
    <footer class="hidden sm:flex bg-white border-t border-slate-200 px-6 py-3 text-xs text-slate-500 justify-between">
        <span>© 2026 UMKMLinked - Platform Strategis UMKM Indonesia</span>
        <div class="flex gap-4">
            <a href="#" class="hover:text-slate-700">Privacy</a>
            <a href="#" class="hover:text-slate-700">Terms</a>
            <button class="px-3 py-1 bg-slate-800 text-white rounded text-[10px] font-bold">Support</button>
        </div>
    </footer>

    {{-- Leaflet sudah dimuat di <head>; Alpine dijalankan oleh resources/js/bi-map.js --}}

</body>
</html>