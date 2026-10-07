@extends('layouts.public')
@section('title', 'UMKMLinked.ID — Etalase Produk UMKM Kalimantan Barat')
@section('description', 'Etalase produk UMKM Binaan Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat: jelajahi semua brand UMKM dan produknya.')
@section('meta')
    @vite('resources/css/beranda.css')
@endsection

@section('content')
<div class="ib-body">

    {{-- ==================== HERO ==================== --}}
    <section class="ib-hero ib-home-hero" aria-labelledby="page-title">
        <div class="ib-hero__content">
            <span class="ib-hero__badge">UMKM Binaan Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat</span>
            <h1 id="page-title" class="ib-hero__title">Etalase produk UMKM Kalimantan Barat</h1>
            <p class="ib-hero__desc">
                Temukan produk lokal terkurasi — dari kuliner khas, fesyen, hingga kerajinan —
                dan hubungi penjualnya langsung.
            </p>

            <form action="{{ route('direktori.index') }}" method="GET" role="search" class="ib-home-search">
                <label for="home-q" class="ib-sr-only">Cari brand atau produk</label>
                <x-public.icon name="search" :size="20" />
                <input id="home-q" type="search" name="q" maxlength="100" autocomplete="off"
                       placeholder="Cari brand atau produk, mis. kopi, tenun, madu…">
                <button type="submit">Cari</button>
            </form>
        </div>
    </section>

    @include('public.partials.stats', ['stats' => $stats, 'satuBaris' => true])

    {{-- ==================== SEMUA BRAND: seluruh UMKM, 4 per baris, dibagi per halaman ==================== --}}
    <section class="ib-rail ib-rail--navy" id="semua-brand" aria-labelledby="rail-semua-brand" data-rotasi-grup>
        <div class="ib-wrap">
            <header class="ib-rail__head">
                <div class="ib-rail__title-group">
                    <span class="ib-rail__icon" aria-hidden="true"><x-public.icon name="sparkles" :size="22" :stroke="1.8" /></span>
                    <div>
                        <h2 id="rail-semua-brand" class="ib-rail__title">{{ $semuaBrand['judul'] }}</h2>
                        <p class="ib-rail__sub">{{ $semuaBrand['sub'] }}</p>
                    </div>
                </div>
                <div class="ib-rail__actions">
                    @if ($semuaBrand['baris']->count() > 1)
                        {{-- WCAG 2.2.2: konten yang bergerak otomatis harus bisa dijeda --}}
                        <button type="button" class="ib-rail__pause" data-rotasi-jeda aria-pressed="false"
                                aria-label="Jeda pergantian produk {{ $semuaBrand['judul'] }}" title="Jeda / lanjutkan pergantian produk">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="ib-rail__pause-ikon">
                                <rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>
                            </svg>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="ib-rail__play-ikon">
                                <path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5Z"/>
                            </svg>
                        </button>
                    @endif
                    <a href="{{ $semuaBrand['url'] }}" class="ib-rail__all">
                        Lihat semua <span>({{ number_format($semuaBrand['total'], 0, ',', '.') }})</span>
                        <x-public.icon name="arrow-right" :size="16" :stroke="2" />
                    </a>
                </div>
            </header>

            @if ($semuaBrand['baris']->isEmpty())
                <p class="ib-rail__empty">Belum ada UMKM yang ditampilkan.</p>
            @else
                {{-- 4 produk berjejer per baris (2 × 2 di layar kecil); baris yang terlihat berganti acak dalam halaman ini --}}
                <div class="ib-rail__rows">
                    @foreach ($semuaBrand['baris'] as $i => $isi)
                        <ul class="ib-rail__grid" aria-label="Produk {{ $semuaBrand['judul'] }}, baris {{ $i + 1 }}" aria-live="off"
                            data-rotasi-baris data-rotasi-jeda-awal="{{ ($i % 4) * 1300 }}">
                            @foreach ($isi as $u)
                                @include('public.partials.produk-card', ['u' => $u, 'lencana' => 'klasifikasi', 'eager' => $i === 0])
                            @endforeach
                        </ul>
                    @endforeach
                </div>

                @include('public.partials.pagination', ['paginator' => $paginator])
            @endif
        </div>
    </section>
</div>
@endsection
