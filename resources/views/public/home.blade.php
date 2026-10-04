@extends('layouts.public')
@section('title', 'UMKMLinked.ID — Etalase Produk UMKM Kalimantan Barat')
@section('description', 'Etalase produk UMKM binaan KPw Bank Indonesia Kalimantan Barat: semua brand, UMKM Go Digital, dan UMKM Go Global.')
@section('meta')
    @vite('resources/css/beranda.css')
@endsection

@section('content')
<div class="ib-body">

    {{-- ==================== HERO ==================== --}}
    <section class="ib-hero ib-home-hero" aria-labelledby="page-title">
        <div class="ib-hero__content">
            <span class="ib-hero__badge">UMKM binaan KPw Bank Indonesia Kalimantan Barat</span>
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

            <nav class="ib-home-chips" aria-label="Lompat ke kategori">
                @foreach ($baris as $b)
                    <a href="#{{ $b['kunci'] }}" class="ib-home-chip ib-home-chip--{{ $b['tema'] }}">
                        <x-public.icon :name="$b['ikon']" :size="16" />
                        {{ $b['judul'] }}
                        <span>{{ number_format($b['total'], 0, ',', '.') }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    @include('public.partials.stats', ['stats' => $stats])

    {{-- ==================== BARIS PRODUK PER KATEGORI ==================== --}}
    @foreach ($baris as $i => $b)
        <section class="ib-rail ib-rail--{{ $b['tema'] }}" id="{{ $b['kunci'] }}" aria-labelledby="rail-{{ $b['kunci'] }}">
            <div class="ib-wrap">
                <header class="ib-rail__head">
                    <div class="ib-rail__title-group">
                        <span class="ib-rail__icon" aria-hidden="true"><x-public.icon :name="$b['ikon']" :size="22" :stroke="1.8" /></span>
                        <div>
                            <p class="ib-rail__eyebrow">Kategori {{ $i + 1 }}</p>
                            <h2 id="rail-{{ $b['kunci'] }}" class="ib-rail__title">{{ $b['judul'] }}</h2>
                            <p class="ib-rail__sub">{{ $b['sub'] }}</p>
                        </div>
                    </div>
                    <div class="ib-rail__actions">
                        @if ($b['cadangan']->isNotEmpty())
                            {{-- WCAG 2.2.2: konten yang bergerak otomatis harus bisa dijeda --}}
                            <button type="button" class="ib-rail__pause" data-rotasi-jeda aria-pressed="false"
                                    aria-label="Jeda pergantian produk {{ $b['judul'] }}" title="Jeda / lanjutkan pergantian produk">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="ib-rail__pause-ikon">
                                    <rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>
                                </svg>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="ib-rail__play-ikon">
                                    <path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5Z"/>
                                </svg>
                            </button>
                        @endif
                        <a href="{{ $b['url'] }}" class="ib-rail__all">
                            Lihat semua <span>({{ number_format($b['total'], 0, ',', '.') }})</span>
                            <x-public.icon name="arrow-right" :size="16" :stroke="2" />
                        </a>
                    </div>
                </header>

            @if ($b['umkm']->isEmpty())
                <p class="ib-rail__empty">Belum ada UMKM pada kategori ini.</p>
            @else
                {{-- 4 produk berjejer (2 × 2 di layar kecil); berganti acak dari kartu cadangan --}}
                <ul class="ib-rail__grid" aria-label="Produk {{ $b['judul'] }}" aria-live="off"
                    @if ($b['cadangan']->isNotEmpty()) data-rotasi data-rotasi-jeda-awal="{{ $i * 1700 }}" @endif>
                    @foreach ($b['umkm'] as $u)
                        @include('public.partials.produk-card', ['u' => $u, 'lencana' => $b['lencana'], 'eager' => $i === 0])
                    @endforeach
                </ul>

                {{-- Kartu cadangan: isi <template> tidak dirender & gambarnya tidak diunduh sampai ditampilkan --}}
                @if ($b['cadangan']->isNotEmpty())
                    <template data-rotasi-cadangan>
                        @foreach ($b['cadangan'] as $u)
                            @include('public.partials.produk-card', ['u' => $u, 'lencana' => $b['lencana']])
                        @endforeach
                    </template>
                @endif
            @endif
            </div>
        </section>
    @endforeach
</div>
@endsection
