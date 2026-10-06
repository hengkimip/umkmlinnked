{{-- Teks sambutan peta: tab Dashboard (admin) & tab Database saat belum ada pilihan (pengunjung) --}}
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
        <p class="text-sm">Klik label kota/kabupaten pada peta atau pilih <strong>Wilayah</strong> untuk memunculkan cabang UMKM, lalu klik nama UMKM
            {{ $untukAdmin ? 'untuk detail & rekomendasi program KPw BI.' : 'untuk membuka halaman UMKM tersebut.' }}</p>
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
