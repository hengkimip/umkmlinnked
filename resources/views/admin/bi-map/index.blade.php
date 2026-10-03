<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="bi-data-url" content="{{ route('admin.peta-interaktif.data') }}">
    <title>Peta Interaktif UMKM - KPw BI Kalimantan Barat</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif; }</style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @vite(['resources/css/bi-map.css', 'resources/js/bi-map.js'])
</head>

<body x-data="umkmApp()" @load="init()" x-cloak class="h-screen flex flex-col overflow-hidden bg-gray-50">

    {{-- ==================== HEADER ==================== --}}
    <header class="bg-white border-b border-slate-200 px-6 py-3">
        <div class="flex items-center justify-between gap-6">
            
            {{-- LEFT: LOGO --}}
            <div class="flex items-center gap-3 min-w-max">
                <a href="{{ route('admin.dashboard') }}" title="Ke dashboard admin">
                    <picture>
                        <source srcset="{{ asset('logo-umkmlinked.webp') }}" type="image/webp">
                        <img src="{{ asset('logo-umkmlinked.png') }}" alt="UMKMLinked" width="480" height="147" class="h-10 w-auto">
                    </picture>
                </a>
            </div>

            {{-- CENTER: SEARCH & FILTERS --}}
            <div class="flex-1 flex gap-3 items-center">
                <input x-model="searchQuery" type="text" placeholder="Cari wilayah atau nama UMKM..." 
                    class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white min-w-0">
                
                {{-- WILAYAH DROPDOWN --}}
                <select x-model="selectedKabupaten" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 bg-white hover:border-slate-300 cursor-pointer min-w-max">
                    <option value="">Wilayah</option>
                    <template x-for="kab in kabupatens" :key="kab">
                        <option :value="kab" x-text="kab"></option>
                    </template>
                </select>

                {{-- TIER DROPDOWN --}}
                <select x-model="selectedTier" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 bg-white hover:border-slate-300 cursor-pointer min-w-max">
                    <template x-for="tier in tiers" :key="tier.value">
                        <option :value="tier.value" x-text="tier.label"></option>
                    </template>
                </select>

                {{-- RESET BUTTON --}}
                <button @click="resetMap()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-all whitespace-nowrap">
                    Reset
                </button>

                {{-- LANGUAGE FLAG --}}
                <div class="flex items-center gap-2 px-3 py-2 border-l border-slate-200 min-w-max">
                    <img src="https://flagcdn.com/id.svg" alt="ID" class="w-5 h-3 rounded">
                    <span class="text-sm font-bold text-slate-700 hidden sm:block">ID</span>
                </div>
            </div>

            {{-- RIGHT: USER INFO --}}
            <div class="hidden sm:flex items-center gap-3 min-w-max">
                <a href="{{ route('admin.dashboard') }}"
                   class="px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-all whitespace-nowrap">
                    Panel Admin
                </a>
                <div class="flex flex-col items-end">
                    <span class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</span>
                    <span class="text-[9px] text-green-600 font-black uppercase">{{ auth()->user()->roleLabel() }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar" aria-label="Keluar"
                        class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center border border-blue-100 text-blue-700 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ==================== MAIN CONTENT ==================== --}}
    <div class="flex flex-1 overflow-hidden relative">

        {{-- ==================== SIDEBAR ==================== --}}
        <aside :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-80 opacity-100'" class="transition-all duration-300 bg-white border-r border-slate-200 flex flex-col overflow-hidden shadow-sm">
            
            {{-- TAB NAVIGATION --}}
            <nav class="flex-shrink-0 p-4 pb-2 flex gap-2 border-b border-slate-100">
                <button @click="activeTab = 'dashboard'"
                    :class="activeTab === 'dashboard' ? 'bg-[#003066] text-white' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase">
                    Dashboard
                </button>
                <button @click="activeTab = 'database'"
                    :class="activeTab === 'database' ? 'bg-[#003066] text-white' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase">
                    Database
                </button>
                <button @click="activeTab = 'program'"
                    :class="activeTab === 'program' ? 'bg-[#003066] text-white' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-3 py-3 rounded-lg transition-all font-bold text-xs uppercase">
                    Program
                </button>
            </nav>

            {{-- CONTENT AREA --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-5">

                {{-- LOADING --}}
                <div x-show="isLoading" class="flex flex-col items-center justify-center py-16 text-center space-y-3">
                    <div class="w-10 h-10 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                    <p class="text-xs text-slate-400 font-bold">Memuat data UMKM...</p>
                </div>

                <template x-if="!isLoading">
                    <div>
                        {{-- ==================== TAB 1: DASHBOARD ==================== --}}
                        <div x-show="activeTab === 'dashboard'" class="space-y-5">
                            
                            {{-- WELCOME TEXT / STATUS WILAYAH --}}
                            <template x-if="!selectedCity">
                                {{-- WELCOME VIEW --}}
                                <div class="space-y-5">
                                    <div class="text-center mb-6">
                                        <h2 class="text-2xl font-black text-[#003066] mb-2">UMKMLinked</h2>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-widest">Platform Strategis UMKM Kalimantan Barat</p>
                                    </div>

                                    <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
                                        <p><span class="font-bold text-[#003066]">Kalimantan Barat</span> bukan sekadar wilayah dengan ribuan pelaku usaha, tetapi ruang tumbuh bagi keberagaman produk, kreativitas, dan potensi UMKM. Dari pangan, kerajinan, fesyen, hingga usaha berbasis potensi daerah, setiap UMKM membawa cerita, keterampilan, dan potensi ekonomi lokal.</p>

                                        <p>Untuk menghubungkan potensi tersebut dengan peluang pengembangan yang lebih luas, hadir <span class="font-bold text-[#003066]">UMKMLINKED</span>, platform digital yang dirancang untuk memetakan, mengelola, dan memperkuat ekosistem UMKM Kalimantan Barat.</p>

                                        <p><span class="font-bold text-blue-600">UMKMLINKED</span> menghadirkan data UMKM yang terintegrasi, mulai dari profil pelaku usaha, produk, lokasi, legalitas, kapasitas produksi, pemasaran, hingga perkembangan usaha. Melalui data yang terstruktur, setiap UMKM dapat dipahami berdasarkan karakteristik, potensi, tingkat perkembangan, serta kebutuhan pengembangannya.</p>

                                        <p>Lebih dari sekadar direktori, <span class="font-bold text-blue-600">UMKMLINKED</span> menjadi ruang penghubung antara data, potensi, program, dan peluang kolaborasi. Platform ini membantu pemangku kepentingan memahami kondisi UMKM di berbagai kabupaten dan kota, sekaligus membuka ruang keterhubungan antara pelaku usaha, pemerintah, pendamping, dan mitra.</p>

                                        <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded">
                                            <p class="font-bold text-blue-900 text-sm italic">
                                                "Dari data menjadi wawasan, dari wawasan menjadi peluang, dan dari peluang menjadi kolaborasi untuk UMKM Kalimantan Barat."
                                            </p>
                                        </div>
                                    </div>

                                    {{-- CTA --}}
                                    <div class="bg-[#003066] text-white p-4 rounded-lg text-center">
                                        <p class="text-xs font-bold uppercase mb-2">Mulai Eksplorasi</p>
                                        <p class="text-sm">Klik marker pada peta atau gunakan filter Wilayah untuk melihat data UMKM</p>
                                    </div>

                                    {{-- STATS --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg text-center border border-blue-200">
                                            <p class="text-2xl font-black text-[#003066]" x-text="databaseUMKM.length"></p>
                                            <p class="text-xs font-bold text-slate-600">Total UMKM</p>
                                        </div>
                                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg text-center border border-green-200">
                                            <p class="text-2xl font-black text-green-700" x-text="kabupatens.length"></p>
                                            <p class="text-xs font-bold text-slate-600">Kabupaten/Kota</p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- CITY SELECTED VIEW --}}
                            <template x-if="selectedCity">
                                <div class="space-y-5">
                                    <h2 class="text-lg font-black text-slate-900">Status Wilayah</h2>
                                    
                                    <div class="bg-[#003066] p-5 rounded-2xl text-white shadow-lg space-y-4">
                                        <div>
                                            <p class="text-xs text-blue-300 font-bold uppercase mb-1">Kabupaten/Kota</p>
                                            <h3 class="text-lg font-black" x-text="selectedCity.name"></h3>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="bg-white/10 p-3 rounded-lg text-center">
                                                <p class="text-[8px] font-bold opacity-70 mb-1">DASAR</p>
                                                <p class="text-xl font-black" x-text="currentStats.dasar || 0"></p>
                                            </div>
                                            <div class="bg-white/10 p-3 rounded-lg text-center">
                                                <p class="text-[8px] font-bold opacity-70 mb-1">KEMBANG</p>
                                                <p class="text-xl font-black" x-text="currentStats.berkembang || 0"></p>
                                            </div>
                                            <div class="bg-white/10 p-3 rounded-lg text-center">
                                                <p class="text-[8px] font-bold opacity-70 mb-1">UNGGUL</p>
                                                <p class="text-xl font-black" x-text="currentStats.unggulan || 0"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- ==================== TAB 2: DATABASE UMKM ==================== --}}
                        <div x-show="activeTab === 'database'" class="space-y-4">
                            <h2 class="text-lg font-black text-slate-900">Daftar UMKM</h2>
                            
                            {{-- FILTER INFO --}}
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                <p class="text-xs text-blue-600 font-bold">📊 Filter Aktif:</p>
                                <div class="text-sm font-black text-blue-900 mt-1">
                                    <template x-if="!selectedKabupaten && !selectedTier">
                                        <p>Menampilkan Semua UMKM</p>
                                    </template>
                                    <template x-if="selectedKabupaten || selectedTier">
                                        <div class="space-y-1">
                                            <template x-if="selectedKabupaten">
                                                <p x-text="'Wilayah: ' + selectedKabupaten"></p>
                                            </template>
                                            <template x-if="selectedTier">
                                                <p x-text="'Tier: ' + selectedTier"></p>
                                            </template>
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
                                    <div class="bg-white border-2 border-slate-200 rounded-lg p-3 hover:shadow-md hover:border-blue-300 transition-all">
                                        
                                        {{-- NO, NAMA, & STATUS --}}
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="flex-shrink-0 w-6 h-6 bg-[#003066] text-white rounded-full flex items-center justify-center text-[10px] font-bold" x-text="(index + 1)"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-slate-900 line-clamp-2" x-text="umkm.nama"></p>
                                            </div>
                                            <span :class="{
                                                'bg-green-100 text-green-700': umkm.status === 'Unggulan',
                                                'bg-blue-100 text-blue-700': umkm.status === 'Berkembang',
                                                'bg-slate-100 text-slate-600': umkm.status === 'Dasar'
                                            }" class="text-[8px] font-black px-2 py-0.5 rounded-full flex-shrink-0 whitespace-nowrap" x-text="umkm.status"></span>
                                        </div>

                                        {{-- LOKASI & SEKTOR --}}
                                        <div class="space-y-1 text-[9px] text-slate-600 mb-2">
                                            <template x-if="umkm.kecamatan">
                                                <p><span class="font-bold">📍 Kec:</span> <span x-text="umkm.kecamatan"></span></p>
                                            </template>
                                            <p><span class="font-bold">🏢 Sektor:</span> <span x-text="umkm.sektor"></span></p>
                                            <template x-if="umkm.sub_sektor">
                                                <p><span class="font-bold">Sub:</span> <span x-text="umkm.sub_sektor"></span></p>
                                            </template>
                                        </div>

                                        {{-- ALAMAT --}}
                                        <template x-if="umkm.alamat">
                                            <div class="bg-slate-50 p-2 rounded mb-2 border border-slate-100">
                                                <p class="text-[8px] text-slate-700 font-bold mb-1">Alamat:</p>
                                                <p class="text-[8px] text-slate-600 line-clamp-2" x-text="umkm.alamat"></p>
                                            </div>
                                        </template>

                                        {{-- CONTACT INFO --}}
                                        <div class="space-y-1 text-[8px] text-slate-600 mb-2 pb-2 border-b border-slate-100">
                                            <template x-if="umkm.telepon">
                                                <p><span class="font-bold">☎️</span> <a :href="'tel:' + umkm.telepon" class="text-blue-600 hover:underline" x-text="umkm.telepon"></a></p>
                                            </template>
                                            <template x-if="umkm.whatsapp">
                                                <p><span class="font-bold">💬</span> <a :href="'https://wa.me/' + umkm.whatsapp" target="_blank" class="text-green-600 hover:underline" x-text="umkm.whatsapp"></a></p>
                                            </template>
                                            <template x-if="umkm.email">
                                                <p><span class="font-bold">✉️</span> <a :href="'mailto:' + umkm.email" class="text-blue-600 hover:underline text-[7px]" x-text="umkm.email"></a></p>
                                            </template>
                                            <template x-if="umkm.website">
                                                <p><span class="font-bold">🌐</span> <a :href="umkm.website" target="_blank" class="text-blue-600 hover:underline text-[7px] line-clamp-1" x-text="umkm.website"></a></p>
                                            </template>
                                        </div>

                                        {{-- STATS --}}
                                        <div class="flex flex-wrap gap-2 text-[8px] font-bold">
                                            <template x-if="umkm.tenaga_kerja">
                                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded" x-text="'👥 ' + umkm.tenaga_kerja + ' TK'"></span>
                                            </template>
                                            <template x-if="umkm.tahun_berdiri">
                                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded" x-text="'📅 ' + umkm.tahun_berdiri"></span>
                                            </template>
                                            <span :class="{
                                                'bg-green-100 text-green-700': umkm.skor >= 75,
                                                'bg-blue-100 text-blue-700': umkm.skor >= 45 && umkm.skor < 75,
                                                'bg-gray-100 text-gray-700': umkm.skor < 45
                                            }" class="px-2 py-1 rounded" x-text="'⭐ ' + umkm.skor"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- ==================== TAB 3: PROGRAM ==================== --}}
                        <div x-show="activeTab === 'program'" class="space-y-4">
                            <h2 class="text-lg font-black text-slate-900">Program Rekomendasi</h2>
                            <p class="text-sm text-slate-500 py-8 text-center">Pilih wilayah untuk melihat program yang relevan</p>
                        </div>
                    </div>
                </template>

            </div>

            {{-- FOOTER STATS --}}
            <div class="border-t border-slate-100 p-4 bg-blue-50">
                <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Total Terdata</p>
                <p class="text-2xl font-black text-blue-900" x-text="databaseUMKM.length + ' UMKM'"></p>
            </div>

        </aside>

        {{-- ==================== TOGGLE SIDEBAR BUTTON ==================== --}}
        <button @click="toggleSidebar()" 
            :class="sidebarCollapsed ? 'left-0' : 'left-80'"
            class="toggle-sidebar-btn transition-all duration-300 fixed top-1/2 -translate-y-1/2 z-[999] bg-white border-2 border-slate-200 border-l-0 rounded-r-lg p-2 hover:bg-slate-100 active:scale-95">
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

            {{-- TOTAL TERDATA --}}
            <div class="absolute bottom-8 left-6 z-[500]">
                <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Total Terdata</p>
                <p class="text-3xl font-black text-[#003066]" x-text="databaseUMKM.length"></p>
            </div>

            {{-- FOCUS KALBAR --}}
            <div class="absolute bottom-8 right-6 z-[500]">
                <button @click="focusKalbar()" 
                    class="px-6 py-3 bg-[#003066] text-white rounded-lg text-sm font-black uppercase tracking-widest shadow-lg hover:bg-[#002550] active:scale-95 transition-all">
                    Focus Kalbar
                </button>
            </div>

        </main>

    </div>

    {{-- FOOTER --}}
    <footer class="bg-white border-t border-slate-200 px-6 py-3 text-xs text-slate-500 flex justify-between">
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