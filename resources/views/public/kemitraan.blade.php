@extends('layouts.public')

@section('title', 'Kemitraan — UMKMLinked.ID')

@section('content')

<div class="ib-body">

    {{-- HERO --}}
    <div class="ib-page-hero">
        <div class="ib-page-hero__content">
            <span class="ib-page-hero__badge">Kolaborasi Strategis</span>
            <h1 class="ib-page-hero__title">Program Kemitraan</h1>
            <p class="ib-page-hero__desc">
                UMKMLinked.ID membuka peluang kemitraan bagi lembaga, perusahaan,
                dan instansi yang ingin berkolaborasi dalam pengembangan UMKM
                Kalimantan Barat.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">

        {{-- KATEGORI KEMITRAAN --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-14">
            <div class="bg-white rounded-2xl border border-gray-100 p-7 text-center shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-14 h-14 mx-auto mb-4 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #0a1f3d15, #d4a01720);">
                    🏦
                </div>
                <h3 class="font-bold text-[#0f2a52] mb-2">Lembaga Keuangan</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Berikan akses pembiayaan kepada UMKM yang telah terverifikasi
                    melalui platform kami.
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-7 text-center shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-14 h-14 mx-auto mb-4 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #0a1f3d15, #d4a01720);">
                    🏢
                </div>
                <h3 class="font-bold text-[#0f2a52] mb-2">Perusahaan & Offtaker</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Temukan mitra UMKM lokal yang andal sebagai pemasok produk
                    atau mitra distribusi.
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-7 text-center shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-14 h-14 mx-auto mb-4 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #0a1f3d15, #d4a01720);">
                    🎓
                </div>
                <h3 class="font-bold text-[#0f2a52] mb-2">Akademisi & LSM</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Kolaborasi riset, pendampingan, dan pengembangan kapasitas
                    UMKM di Kalimantan Barat.
                </p>
            </div>
        </div>

        {{-- FORM KONTAK KEMITRAAN --}}
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                {{-- Header form dengan aksen gradient tipis --}}
                <div class="px-8 pt-8 pb-2 text-center"
                     style="background: linear-gradient(180deg, #0d94880a, transparent);">
                    <h2 class="text-lg font-bold text-[#0f2a52]">
                        Hubungi Kami untuk Kemitraan
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Isi form berikut dan tim kami akan segera menghubungi Anda
                    </p>
                </div>

                <div class="p-8 pt-6 space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1.5 font-medium">Nama Instansi / Perusahaan</label>
                        <input type="text" placeholder="PT. Contoh Maju"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm
                                   focus:ring-2 focus:ring-[#0d9488] focus:border-transparent
                                   outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1.5 font-medium">Nama Kontak Person</label>
                        <input type="text" placeholder="Budi Santoso"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm
                                   focus:ring-2 focus:ring-[#0d9488] focus:border-transparent
                                   outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1.5 font-medium">Email</label>
                        <input type="email" placeholder="kontak@perusahaan.com"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm
                                   focus:ring-2 focus:ring-[#0d9488] focus:border-transparent
                                   outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1.5 font-medium">Jenis Kemitraan yang Diminati</label>
                        <select class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm
                                       focus:ring-2 focus:ring-[#0d9488] focus:border-transparent
                                       outline-none transition cursor-pointer">
                            <option value="">Pilih jenis kemitraan</option>
                            <option>Lembaga Keuangan / Pembiayaan</option>
                            <option>Offtaker / Pembeli Produk UMKM</option>
                            <option>Pendampingan & Pelatihan</option>
                            <option>Riset & Akademik</option>
                            <option>Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1.5 font-medium">Pesan</label>
                        <textarea rows="4" placeholder="Ceritakan rencana kemitraan Anda..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm
                                   focus:ring-2 focus:ring-[#0d9488] focus:border-transparent
                                   outline-none transition resize-none">
                        </textarea>
                    </div>

                    <button type="button"
                        class="w-full text-white py-3.5 rounded-xl text-sm font-semibold
                               transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #0f2a52, #0a1f3d);">
                        Kirim Permohonan Kemitraan
                    </button>

                    <p class="text-xs text-gray-400 text-center pt-1">
                        Form ini belum terhubung ke backend. Akan diaktifkan pada tahap berikutnya.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection