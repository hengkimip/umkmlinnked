@extends('layouts.public')

@section('title', 'Tentang Kami — UMKMLinked.ID')
@section('description', 'UMKMLinked.ID: direktori dan dashboard UMKM Kalimantan Barat yang memetakan, mengklasifikasikan, dan menghubungkan UMKM dengan program pengembangan.')

@php
    $misi = [
        'Menghimpun data UMKM secara komprehensif dan terverifikasi',
        'Memfasilitasi koneksi antara UMKM, pembeli, dan investor',
        'Mendukung kebijakan berbasis data bagi pemerintah daerah',
        'Mendorong UMKM naik kelas melalui klasifikasi dan pendampingan',
    ];

    $fitur = [
        ['icon' => 'search',       'judul' => 'Direktori & pencarian', 'isi' => 'Temukan UMKM berdasarkan produk, sektor, dan wilayah.'],
        ['icon' => 'chart',        'judul' => 'Klasifikasi UMKM',      'isi' => 'Skor lima aspek: Dasar, Berkembang, hingga Unggulan.'],
        ['icon' => 'map',          'judul' => 'Peta sebaran',          'isi' => 'Pemetaan UMKM di 14 kabupaten/kota untuk pemantauan.'],
        ['icon' => 'database',     'judul' => 'Data terintegrasi',     'isi' => 'Satu sumber data lintas OPD pembina UMKM.'],
        ['icon' => 'handshake',    'judul' => 'Portal kemitraan',      'isi' => 'Jembatan UMKM dengan lembaga keuangan dan offtaker.'],
        ['icon' => 'presentation', 'judul' => 'Dashboard admin',       'isi' => 'Statistik wilayah, sektor, dan klasifikasi.'],
        ['icon' => 'lock',         'judul' => 'Akses berbasis peran',  'isi' => 'Data keuangan & pribadi hanya untuk pengelola.'],
        ['icon' => 'phone-mobile', 'judul' => 'Ramah perangkat seluler', 'isi' => 'Nyaman dibuka di ponsel, ringan di jaringan 4G.'],
    ];

    $kontak = [
        ['icon' => 'map-pin',  'label' => 'Alamat',  'nilai' => 'Jl. Sutan Syahrir No.1, Pontianak'],
        ['icon' => 'envelope', 'label' => 'Email',   'nilai' => 'info@umkmlinked.id'],
        ['icon' => 'phone',    'label' => 'Telepon', 'nilai' => '(0561) 123456'],
    ];
@endphp

@section('content')
<div class="ib-body">

    @include('public.partials.hero', [
        'variant' => 'ib-page-hero',
        'badge'   => 'Tentang kami',
        'title'   => 'Menghubungkan UMKM Kalbar dengan peluang',
        'desc'    => 'UMKMLinked.ID adalah direktori sekaligus dashboard UMKM Kalimantan Barat yang diinisiasi KPw Bank Indonesia Provinsi Kalimantan Barat bersama OPD pembina UMKM.',
    ])

    @include('public.partials.stats', ['stats' => $stats])

    <div class="ib-wrap ib-about">

        {{-- Visi & misi --}}
        <section aria-labelledby="vm-title">
            <div class="ib-section-intro">
                <p class="ib-section-eyebrow">Arah kami</p>
                <h2 id="vm-title" class="ib-section-heading">Visi & misi</h2>
            </div>
            <div class="ib-vm">
                <article class="ib-vm__card ib-vm__card--dark">
                    <div class="ib-icon-box ib-icon-box--dark"><x-public.icon name="eye" :size="24" /></div>
                    <h3>Visi</h3>
                    <p>
                        Menjadi platform direktori UMKM terpercaya di Kalimantan Barat yang mendorong
                        pertumbuhan ekonomi daerah melalui digitalisasi dan kolaborasi lintas sektor.
                    </p>
                </article>
                <article class="ib-vm__card ib-vm__card--light">
                    <div class="ib-icon-box"><x-public.icon name="rocket" :size="24" /></div>
                    <h3>Misi</h3>
                    <ul class="ib-checklist">
                        @foreach ($misi as $m)
                            <li><x-public.icon name="check-circle" :size="20" />{{ $m }}</li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </section>

        {{-- Fitur platform --}}
        <section aria-labelledby="fitur-title">
            <div class="ib-section-intro">
                <p class="ib-section-eyebrow">Yang kami sediakan</p>
                <h2 id="fitur-title" class="ib-section-heading">Fitur platform</h2>
                <p>Dirancang untuk publik yang mencari produk lokal dan pengelola yang membina UMKM.</p>
            </div>
            <div class="ib-feature-grid ib-feature-grid--4">
                @foreach ($fitur as $f)
                    <article class="ib-content-card">
                        <div class="ib-icon-box"><x-public.icon :name="$f['icon']" :size="24" /></div>
                        <h3>{{ $f['judul'] }}</h3>
                        <p>{{ $f['isi'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Hubungi kami --}}
        <section class="ib-contact" aria-labelledby="kontak-title">
            <h2 id="kontak-title">Hubungi kami</h2>
            <p>Dinas Koperasi, Usaha Kecil dan Menengah Provinsi Kalimantan Barat</p>
            <dl class="ib-contact__grid">
                @foreach ($kontak as $k)
                    <div class="ib-contact__item">
                        <div class="ib-icon-box ib-icon-box--dark"><x-public.icon :name="$k['icon']" :size="22" /></div>
                        <div>
                            <dt>{{ $k['label'] }}</dt>
                            <dd>{{ $k['nilai'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
            <div class="ib-contact__cta">
                <a href="{{ route('direktori.index') }}" class="ib-btn ib-btn--gold">Jelajahi direktori</a>
                <a href="{{ route('kemitraan.index') }}" class="ib-btn ib-btn--on-dark">Program kemitraan</a>
            </div>
        </section>
    </div>
</div>
@endsection
