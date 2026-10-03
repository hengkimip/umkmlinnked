@extends('layouts.public')

@section('title', 'Berita — UMKMLinked.ID')
@section('description', 'Kabar terbaru seputar UMKM, program inkubasi, dan mitra binaan Bank Indonesia Kalimantan Barat.')

@section('content')

<div class="ib-body">

    @include('public.partials.hero', [
        'variant' => 'ib-page-hero',
        'badge'   => 'Kabar terkini',
        'title'   => 'Berita & Artikel',
        'desc'    => 'Informasi seputar perkembangan UMKM, program inkubasi, dan capaian mitra binaan Bank Indonesia Kalimantan Barat.',
    ])

    <section class="ib-page-section" aria-labelledby="berita-title">
        <h2 id="berita-title" class="ib-sr-only">Daftar berita</h2>

        {{-- Konten berita belum dikelola dari admin (FR-13). Tampilkan keadaan
             kosong yang jujur, bukan artikel contoh yang menyesatkan. --}}
        <div class="ib-empty" style="max-width:720px;margin:0 auto">
            <div class="ib-empty__icon" aria-hidden="true">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.38c.62 0 1.12.5 1.12 1.13V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.88c0-.63-.5-1.13-1.13-1.13H4.13C3.5 3.75 3 4.25 3 4.88V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>
            </div>
            <p class="ib-empty__title">Berita segera hadir</p>
            <p class="ib-empty__desc">
                Kabar program inkubasi, pelatihan, dan kisah sukses UMKM Kalimantan Barat akan dipublikasikan di sini.
                Sambil menunggu, jelajahi produk UMKM binaan.
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center">
                <a href="{{ route('direktori.index') }}" class="ib-btn">Jelajahi direktori</a>
                <a href="{{ route('kemitraan.index') }}" class="ib-btn ib-btn--ghost">Program kemitraan</a>
            </div>
        </div>
    </section>

</div>
@endsection
