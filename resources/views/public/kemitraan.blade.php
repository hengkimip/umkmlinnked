@extends('layouts.public')

@section('title', 'Kemitraan — UMKMLinked.ID')
@section('description', 'Peluang kemitraan dengan UMKM binaan Bank Indonesia Kalimantan Barat bagi lembaga keuangan, offtaker, akademisi, dan LSM.')

@section('content')
@php
    $mitra = [
        [
            'judul' => 'Lembaga Keuangan',
            'isi'   => 'Salurkan akses pembiayaan kepada UMKM yang profil dan klasifikasinya telah terverifikasi.',
            'icon'  => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.33A48.36 48.36 0 0 0 12 9.75c-2.55 0-5.06.2-7.5.58V21M3 21h18M12 6.75h.01v.01H12v-.01Z',
        ],
        [
            'judul' => 'Perusahaan & Offtaker',
            'isi'   => 'Temukan pemasok lokal yang andal untuk rantai pasok, oleh-oleh khas, hingga produk ekspor.',
            'icon'  => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.38c0-.62.5-1.12 1.13-1.12h3.75c.62 0 1.12.5 1.12 1.13V21',
        ],
        [
            'judul' => 'Akademisi & LSM',
            'isi'   => 'Kolaborasi riset, pendampingan, dan peningkatan kapasitas UMKM di 14 kabupaten/kota.',
            'icon'  => 'M4.26 10.15A50.6 50.6 0 0 0 2.66 15.06 60.4 60.4 0 0 1 12 20.9a60.4 60.4 0 0 1 9.34-5.84 50.6 50.6 0 0 0-1.6-4.91m-15.48 0a50.57 50.57 0 0 0-2.66-.95A59.9 59.9 0 0 1 12 3.49a59.9 59.9 0 0 1 10.4 5.71c-.9.29-1.79.61-2.66.95m-15.48 0A50.7 50.7 0 0 1 12 13.49a50.7 50.7 0 0 1 7.74-3.34M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.68A55.38 55.38 0 0 1 12 8.44m-7.01 11.56A5.97 5.97 0 0 0 6.75 15.75v-1.5',
        ],
    ];
@endphp

<div class="ib-body">

    @include('public.partials.hero', [
        'variant' => 'ib-page-hero',
        'badge'   => 'Kolaborasi strategis',
        'title'   => 'Program Kemitraan',
        'desc'    => 'Buka peluang kolaborasi bersama UMKM binaan Bank Indonesia Kalimantan Barat — dari pembiayaan, rantai pasok, hingga pendampingan.',
    ])

    <section class="ib-page-section" aria-labelledby="mitra-title">
        <div class="ib-section-intro">
            <p class="ib-section-eyebrow">Siapa yang bisa bermitra</p>
            <h2 id="mitra-title" class="ib-section-heading">Tiga jalur kolaborasi</h2>
            <p>Pilih jalur yang paling sesuai dengan peran lembaga Anda dalam ekosistem UMKM.</p>
        </div>

        <div class="ib-feature-grid">
            @foreach ($mitra as $m)
                <article class="ib-content-card">
                    <div class="ib-icon-box" aria-hidden="true">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                        </svg>
                    </div>
                    <h3>{{ $m['judul'] }}</h3>
                    <p>{{ $m['isi'] }}</p>
                </article>
            @endforeach
        </div>

        {{-- Formulir belum terhubung ke backend: semua isian dinonaktifkan
             agar tidak ada data pribadi yang diketik namun tidak terkirim. --}}
        <div class="ib-form-card">
            <div class="ib-form-card__head">
                <h2>Ajukan kemitraan</h2>
                <p>Isi data berikut dan tim kami akan menghubungi Anda.</p>
            </div>

            <div class="ib-info-banner" role="status" style="margin-bottom:24px">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.01v.01H12v-.01Z"/></svg>
                <span>Formulir daring segera aktif. Sementara ini, silakan hubungi Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat.</span>
            </div>

            <form class="ib-form-grid" aria-describedby="form-status">
                <div class="ib-form-group">
                    <label for="mitra-instansi" class="ib-form-label">Nama instansi / perusahaan</label>
                    <input id="mitra-instansi" type="text" class="ib-form-input" placeholder="PT Contoh Maju" disabled>
                </div>
                <div class="ib-form-group">
                    <label for="mitra-kontak" class="ib-form-label">Nama kontak</label>
                    <input id="mitra-kontak" type="text" class="ib-form-input" placeholder="Budi Santoso" autocomplete="name" disabled>
                </div>
                <div class="ib-form-group">
                    <label for="mitra-email" class="ib-form-label">Email</label>
                    <input id="mitra-email" type="email" class="ib-form-input" placeholder="kontak@perusahaan.co.id" autocomplete="email" disabled>
                </div>
                <div class="ib-form-group">
                    <label for="mitra-jenis" class="ib-form-label">Jenis kemitraan</label>
                    <select id="mitra-jenis" class="ib-form-select" disabled>
                        <option value="">Pilih jenis kemitraan</option>
                        <option>Lembaga keuangan / pembiayaan</option>
                        <option>Offtaker / pembeli produk UMKM</option>
                        <option>Pendampingan & pelatihan</option>
                        <option>Riset & akademik</option>
                        <option>Lainnya</option>
                    </select>
                </div>
                <div class="ib-form-group ib-form-group--full">
                    <label for="mitra-pesan" class="ib-form-label">Pesan</label>
                    <textarea id="mitra-pesan" rows="4" class="ib-form-textarea" placeholder="Ceritakan rencana kemitraan Anda..." disabled></textarea>
                </div>
                <div class="ib-form-group--full">
                    <button type="submit" class="ib-btn-primary" disabled>Kirim permohonan kemitraan</button>
                    <p id="form-status" class="ib-sr-only">Formulir belum aktif.</p>
                </div>
            </form>
        </div>
    </section>

</div>
@endsection
