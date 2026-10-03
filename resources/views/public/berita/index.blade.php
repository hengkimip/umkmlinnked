@extends('layouts.public')

@section('title', 'Berita — UMKMLinked.ID')

@section('content')

<div class="ib-body">

    {{-- HERO --}}
    <div class="ib-page-hero">
        <div class="ib-page-hero__content">
            <span class="ib-page-hero__badge">Update Terkini</span>
            <h1 class="ib-page-hero__title">Berita & Artikel</h1>
            <p class="ib-page-hero__desc">
                Informasi terkini seputar perkembangan UMKM, program inkubasi,
                dan capaian mitra binaan Bank Indonesia Kalimantan Barat.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">

        {{-- GRID ARTIKEL --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(range(1, 6) as $i)
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm
                        hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="aspect-video flex items-center justify-center text-4xl"
                     style="background: linear-gradient(135deg, #0a1f3d0d, #d4a0171a);">
                    📰
                </div>
                <div class="p-5">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                          style="background: #0d94880f; color: #0d9488;">
                        UMKM
                    </span>
                    <h3 class="font-bold text-[#0f2a52] mt-3 line-clamp-2 leading-snug">
                        Contoh Judul Berita UMKM Kalimantan Barat #{{ $i }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-2 line-clamp-2 leading-relaxed">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit.
                        Deskripsi singkat berita akan tampil di sini.
                    </p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50">
                        <span class="text-xs text-gray-400">
                            {{ now()->subDays($i)->format('d M Y') }}
                        </span>
                        <span class="text-xs font-semibold" style="color: #0d9488;">
                            Baca selengkapnya →
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- INFO PLACEHOLDER --}}
        <div class="mt-10 rounded-xl p-4 text-sm flex items-start gap-3"
             style="background: #d4a01712; border: 1px solid #d4a01740; color: #8a6a10;">
            <span class="text-base leading-none">ℹ️</span>
            <span>Konten berita akan dikelola melalui panel admin. Saat ini menampilkan data placeholder.</span>
        </div>

    </div>

</div>
@endsection