@extends('layouts.public')
@php
    // ---------- Data tampilan (hanya data publik, NFR-02) ----------
    $produkList = $umkm->produk;
    $utama      = $produkList->first();

    // URL gambar hanya dipakai bila bentuknya aman untuk atribut & CSS url()
    $amanUrl = fn (?string $u) => $u && preg_match('#^(https?://|/)[^\s\'"()<>\\\\]+$#', $u) ? $u : null;

    $fotoUtama = $amanUrl($utama?->foto_final) ?? $amanUrl($umkm->foto_usaha ? Storage::url($umkm->foto_usaha) : null);

    $galeri = $produkList
        ->map(fn ($p) => [
            'src'   => $amanUrl($p->foto_final), 'kecil' => $amanUrl($p->foto_kecil), 'nama' => $p->nama_produk, 'harga' => $p->harga,
            'desk'  => $p->deskripsi, 'badge' => $p->badge, 'label' => $p->badge_label,
            'wa'    => $umkm->waPesanLink($p->nama_produk),
        ])
        ->filter(fn ($g) => $g['src'])
        ->values();

    $lokasi  = $umkm->kabupaten && $umkm->kabupaten !== \App\Models\Umkm::KABUPATEN_KOSONG ? $umkm->kabupaten : null;
    $harga   = $produkList->pluck('harga')->filter(fn ($h) => $h > 0);
    $rp      = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $teksHarga = match (true) {
        $harga->isEmpty()             => null,
        $harga->min() == $harga->max() => $rp($harga->min()),
        default                       => $rp($harga->min()) . ' – ' . $rp($harga->max()),
    };

    $inisial = collect(preg_split('/\s+/', trim($umkm->nama_usaha)))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $jangkauan = [
        'lokal' => 'Lokal', 'regional' => 'Regional', 'nasional' => 'Nasional', 'ekspor' => 'Ekspor',
    ][$umkm->pemasaran?->jangkauan_pasar] ?? null;

    $fakta = array_filter([
        ['icon' => 'tag',      'label' => 'Sektor',          'nilai' => $umkm->sektor_label],
        $lokasi ? ['icon' => 'map-pin', 'label' => 'Lokasi', 'nilai' => $lokasi] : null,
        $umkm->tahun_berdiri ? ['icon' => 'calendar', 'label' => 'Berdiri sejak', 'nilai' => $umkm->tahun_berdiri] : null,
        $umkm->jumlah_tenaga_kerja ? ['icon' => 'users', 'label' => 'Tenaga kerja', 'nilai' => $umkm->jumlah_tenaga_kerja . ' orang'] : null,
        $jangkauan ? ['icon' => 'globe', 'label' => 'Jangkauan pasar', 'nilai' => $jangkauan] : null,
    ]);

    $leg = $umkm->legalitas;
    $sertifikat = array_keys(array_filter([
        'NIB'   => $leg?->nomor_nib,
        'SIUP'  => $leg?->nomor_siup,
        'NPWP'  => $leg?->nomor_npwp,
        'Halal' => $leg?->nomor_halal,
        'BPOM'  => $leg?->nomor_bpom,
        'PIRT'  => $leg?->nomor_pirt,
        'SNI'   => $leg?->nomor_sni,
    ]));

    $kanal = array_filter([
        ['label' => 'Tokopedia', 'warna' => '#03ac0e', 'url' => $umkm->marketplaceUrl('tokopedia')],
        ['label' => 'Shopee',    'warna' => '#ee4d2d', 'url' => $umkm->marketplaceUrl('shopee')],
        ['label' => 'Instagram', 'warna' => '#d62976', 'url' => $umkm->instagramUrl()],
        ['label' => 'Facebook',  'warna' => '#1877f2', 'url' => $umkm->facebookUrl()],
        ['label' => 'Website',   'warna' => '#1a4180', 'url' => $umkm->websiteUrl()],
    ], fn ($k) => $k['url']);

    $waUtama = $umkm->waPesanLink($utama?->nama_produk);

    $deskripsi = $umkm->deskripsi ?: trim(
        "{$umkm->nama_usaha} adalah usaha " . mb_strtolower($umkm->sektor_label)
        . ($lokasi ? " asal {$lokasi}, Kalimantan Barat" : ' di Kalimantan Barat')
        . ($umkm->tahun_berdiri ? " yang berdiri sejak {$umkm->tahun_berdiri}" : '') . '.'
        . ($utama ? " Produk unggulannya, {$utama->nama_produk}, dapat dipesan langsung kepada pemilik usaha" . ($umkm->wa_link ? ' melalui WhatsApp.' : '.') : '')
    );

    $skor = max(0, min(100, (int) $umkm->skor_total));
    $urlHalaman = url()->current();
@endphp

@section('title', $umkm->nama_usaha . ' — UMKMLinked.ID')
@section('description', \Illuminate\Support\Str::limit($deskripsi, 155))
@section('body_class', $umkm->wa_link ? 'ib-has-buybar' : '')

@section('meta')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $umkm->nama_usaha }} — UMKMLinked.ID">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($deskripsi, 155) }}">
    <meta property="og:url" content="{{ $urlHalaman }}">
    @if ($fotoUtama)
        <meta property="og:image" content="{{ str_starts_with($fotoUtama, 'http') ? $fotoUtama : url($fotoUtama) }}">
    @endif
@endsection

@section('content')
<div class="ib-body">

    {{-- ==================== HERO (motif & siluet sama dengan halaman awal) ==================== --}}
    <section class="ib-page-hero" aria-labelledby="page-title">
        <div class="ib-page-hero__content">
            <nav aria-label="Breadcrumb">
                <ol class="ib-crumbs">
                    <li><a href="{{ route('direktori.index') }}">Direktori</a></li>
                    <li><a href="{{ route('direktori.index', ['sektor' => $umkm->sektor]) }}">{{ $umkm->sektor_label }}</a></li>
                    <li aria-current="page">{{ \Illuminate\Support\Str::limit($umkm->nama_usaha, 40) }}</li>
                </ol>
            </nav>

            <span class="ib-page-hero__badge">UMKM binaan Bank Indonesia</span>
            <h1 id="page-title" class="ib-page-hero__title">{{ $umkm->nama_usaha }}</h1>

            <div class="ib-chips">
                @if (in_array($umkm->klasifikasi, ['unggulan', 'berkembang'], true))
                    <span class="ib-chip ib-chip--gold"><x-public.icon name="sparkles" :size="16" />{{ ucfirst($umkm->klasifikasi) }}</span>
                @endif
                <span class="ib-chip"><x-public.icon name="tag" :size="16" />{{ $umkm->sektor_label }}</span>
                @if ($lokasi)
                    <span class="ib-chip"><x-public.icon name="map-pin" :size="16" />{{ $lokasi }}</span>
                @endif
                @if (in_array('Halal', $sertifikat, true))
                    <span class="ib-chip"><x-public.icon name="shield-check" :size="16" />Halal</span>
                @endif
            </div>
        </div>
    </section>

    <div class="ib-wrap ib-detail">

        {{-- ==================== KOLOM KIRI ==================== --}}
        <div class="ib-detail__main">

            {{-- Galeri produk --}}
            <section class="ib-gallery" aria-label="Foto produk" data-gallery>
                <div class="ib-gallery__stage" data-gallery-stage @if ($fotoUtama) style="--img: url('{{ $fotoUtama }}')" @endif>
                    @if ($fotoUtama)
                        <img src="{{ $fotoUtama }}" alt="{{ $utama?->nama_produk ?? $umkm->nama_usaha }}"
                             class="ib-gallery__img" width="714" height="1280" decoding="async" fetchpriority="high"
                             referrerpolicy="no-referrer" data-gallery-img>
                    @else
                        <div class="ib-card__placeholder" aria-hidden="true">{{ $inisial ?: 'U' }}</div>
                    @endif
                </div>

                @if ($utama)
                    <div class="ib-gallery__caption">
                        <div>
                            <span class="ib-badge ib-badge--{{ $utama->badge }}" data-gallery-badge @if (! $utama->badge_label) hidden @endif>{{ $utama->badge_label }}</span>
                            <p class="ib-gallery__name" data-gallery-name>{{ $utama->nama_produk }}</p>
                            <p class="ib-gallery__price" data-gallery-price>
                                @if ($utama->harga > 0)
                                    <strong>{{ $rp($utama->harga) }}</strong>
                                @else
                                    Harga dapat ditanyakan langsung ke penjual
                                @endif
                            </p>
                            <p class="ib-gallery__desc" data-gallery-desc @if (! $utama->deskripsi) hidden @endif>{{ $utama->deskripsi }}</p>
                        </div>
                        @if ($waUtama)
                            <a href="{{ $waUtama }}" class="ib-btn-wa" style="width:auto" target="_blank" rel="noopener noreferrer" data-gallery-wa>
                                @include('public.partials.wa-icon', ['size' => 18])
                                Pesan produk ini
                            </a>
                        @endif
                    </div>
                @endif

                @can('update', $umkm)
                    <div class="ib-gallery__admin">
                        <a href="{{ route('admin.produk.upload-foto', ['umkm' => $umkm->id]) }}" class="ib-btn ib-btn--ghost">
                            Kelola foto &amp; data produk
                        </a>
                    </div>
                @endcan

                @if ($galeri->count() > 1)
                    <div class="ib-thumbs" role="group" aria-label="Pilih foto produk">
                        @foreach ($galeri as $i => $g)
                            <button type="button" class="ib-thumb" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}"
                                    aria-label="Lihat {{ $g['nama'] }}"
                                    data-thumb-src="{{ $g['src'] }}" data-thumb-name="{{ $g['nama'] }}"
                                    data-thumb-price="{{ $g['harga'] > 0 ? $rp($g['harga']) : '' }}"
                                    data-thumb-desc="{{ $g['desk'] }}"
                                    data-thumb-badge="{{ $g['badge'] }}" data-thumb-badge-label="{{ $g['label'] }}"
                                    data-thumb-wa="{{ $g['wa'] }}">
                                <img src="{{ $g['kecil'] ?? $g['src'] }}" alt="" width="64" height="64" loading="lazy" referrerpolicy="no-referrer">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Tentang usaha --}}
            <section class="ib-panel" aria-labelledby="tentang-title">
                <h2 id="tentang-title" class="ib-panel__title">Tentang usaha</h2>
                <p>{{ $deskripsi }}</p>

                <dl class="ib-facts">
                    @foreach ($fakta as $f)
                        <div class="ib-fact">
                            <span class="ib-fact__icon"><x-public.icon :name="$f['icon']" :size="18" /></span>
                            <div>
                                <dt>{{ $f['label'] }}</dt>
                                <dd>{{ $f['nilai'] }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- Produk --}}
            @if ($produkList->isNotEmpty())
                <section class="ib-panel" aria-labelledby="produk-title">
                    <h2 id="produk-title" class="ib-panel__title">Produk ({{ $produkList->count() }})</h2>
                    <div @class(["ib-products", "ib-products--single" => $produkList->count() === 1])>
                        @foreach ($produkList as $p)
                            @php $pFoto = $amanUrl($p->foto_kecil); @endphp
                            <article class="ib-product">
                                <div class="ib-product__photo">
                                    @if ($pFoto)
                                        <img src="{{ $pFoto }}" alt="{{ $p->nama_produk }}" width="400" height="500"
                                             loading="lazy" decoding="async" referrerpolicy="no-referrer">
                                    @else
                                        <div class="ib-card__placeholder" aria-hidden="true">{{ $inisial ?: 'U' }}</div>
                                    @endif
                                    @if ($p->badge_label)
                                        <span class="ib-badge ib-badge--{{ $p->badge }} ib-badge--float">{{ $p->badge_label }}</span>
                                    @endif
                                </div>
                                <div class="ib-product__body">
                                    <h3 class="ib-product__name">{{ $p->nama_produk }}</h3>
                                    <p class="ib-product__price">
                                        @if ($p->harga > 0) <strong>{{ $rp($p->harga) }}</strong> @else Tanya harga @endif
                                    </p>
                                    @if ($p->deskripsi)
                                        <p class="ib-product__desc">{{ $p->deskripsi }}</p>
                                    @endif
                                    @if ($umkm->wa_link)
                                        <a href="{{ $umkm->waPesanLink($p->nama_produk) }}" class="ib-btn-wa" target="_blank" rel="noopener noreferrer"
                                           aria-label="Pesan {{ $p->nama_produk }} via WhatsApp">
                                            @include('public.partials.wa-icon', ['size' => 16])
                                            Pesan
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Legalitas & sertifikasi --}}
            <section class="ib-panel" aria-labelledby="legal-title">
                <h2 id="legal-title" class="ib-panel__title">Legalitas & sertifikasi</h2>
                @if ($sertifikat)
                    <ul class="ib-certs" style="list-style:none;margin:0;padding:0">
                        @foreach ($sertifikat as $s)
                            <li class="ib-cert"><x-public.icon name="shield-check" :size="20" />{{ $s }}</li>
                        @endforeach
                    </ul>
                    <p class="ib-note">Status legalitas tercatat oleh OPD pembina UMKM. Nomor dokumen tidak ditampilkan untuk publik.</p>
                @else
                    <p>Data legalitas belum tersedia. Tanyakan langsung kepada pemilik usaha.</p>
                @endif
            </section>
        </div>

        {{-- ==================== KOLOM KANAN: PESAN & KONTAK ==================== --}}
        <aside class="ib-order" aria-label="Pesan dan kontak">
            <div>
                <p class="ib-order__eyebrow">Pesan langsung ke penjual</p>
                <p class="ib-order__name">{{ $umkm->nama_usaha }}</p>
                <p class="ib-order__price">
                    {{ $teksHarga ? 'Harga ' . $teksHarga : 'Harga & ketersediaan via WhatsApp' }}
                </p>
            </div>

            @if ($waUtama)
                <a href="{{ $waUtama }}" class="ib-btn-wa" target="_blank" rel="noopener noreferrer">
                    @include('public.partials.wa-icon', ['size' => 20])
                    Pesan via WhatsApp
                </a>
            @endif

            @if ($kanal)
                <div>
                    <p class="ib-order__eyebrow" style="margin-bottom:8px">Juga tersedia di</p>
                    <div class="ib-channels">
                        @foreach ($kanal as $k)
                            <a href="{{ $k['url'] }}" class="ib-channel" target="_blank" rel="noopener noreferrer nofollow">
                                <span class="ib-channel__dot" style="background:{{ $k['warna'] }}"></span>
                                {{ $k['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <ul class="ib-trust">
                <li><x-public.icon name="check-circle" :size="18" />UMKM binaan KPw Bank Indonesia Kalimantan Barat</li>
                <li><x-public.icon name="check-circle" :size="18" />Transaksi langsung dengan pemilik usaha</li>
                @if ($sertifikat)
                    <li><x-public.icon name="check-circle" :size="18" />Memiliki {{ implode(', ', $sertifikat) }}</li>
                @endif
            </ul>

            @if ($umkm->alamat_usaha && $umkm->alamat_usaha !== '-')
                <p class="ib-address"><x-public.icon name="map-pin" :size="18" />{{ $umkm->alamat_usaha }}</p>
            @endif

            <div class="ib-score">
                <div class="ib-score__head">
                    <span>Skor kesiapan usaha</span>
                    <strong>{{ $skor }}<span style="color:var(--muted);font-weight:500">/100</span></strong>
                </div>
                <div class="ib-score__bar" role="img" aria-label="Skor {{ $skor }} dari 100"><span style="width:{{ $skor }}%"></span></div>
            </div>

            <div class="ib-share">
                <button type="button" class="ib-btn ib-btn--ghost" data-share data-share-title="{{ $umkm->nama_usaha }}" data-share-url="{{ $urlHalaman }}">
                    <x-public.icon name="share" :size="18" />
                    <span data-share-label>Bagikan</span>
                </button>
                <a class="ib-btn ib-btn--ghost" target="_blank" rel="noopener noreferrer"
                   href="https://wa.me/?text={{ rawurlencode($umkm->nama_usaha . ' — produk UMKM Kalimantan Barat: ' . $urlHalaman) }}">
                    @include('public.partials.wa-icon', ['size' => 18, 'fill' => 'currentColor'])
                    Teruskan
                </a>
            </div>
        </aside>
    </div>

    {{-- ==================== UMKM SEJENIS ==================== --}}
    @if ($related->isNotEmpty())
        <section class="ib-wrap ib-related" aria-labelledby="related-title">
            <div class="ib-section-head">
                <div>
                    <p class="ib-section-eyebrow">Masih di sektor {{ mb_strtolower($umkm->sektor_label) }}</p>
                    <h2 id="related-title" class="ib-section-heading">UMKM sejenis lainnya</h2>
                </div>
                <a href="{{ route('direktori.index', ['sektor' => $umkm->sektor]) }}" class="ib-btn ib-btn--ghost" style="height:40px;font-size:var(--fs-14)">Lihat semua</a>
            </div>
            <div class="ib-grid">
                @foreach ($related as $item)
                    @include('public.partials.umkm-card', ['item' => $item, 'badge' => 'digital'])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Bilah pesan menempel di bawah (mobile) --}}
    @if ($waUtama)
        <div class="ib-buybar">
            <p class="ib-buybar__name">{{ $umkm->nama_usaha }}</p>
            <a href="{{ $waUtama }}" class="ib-btn-wa" target="_blank" rel="noopener noreferrer">
                @include('public.partials.wa-icon', ['size' => 18])
                Pesan
            </a>
        </div>
    @endif
</div>
@endsection
