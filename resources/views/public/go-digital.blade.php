@extends('layouts.public')
@section('title', 'Go Digital — UMKMLinked.ID')
@section('content')

<div class="ib-body">

{{-- HERO --}}
<div class="ib-hero">
    <div class="ib-hero__overlay"></div>
    <div class="ib-hero__content">
        <span class="ib-hero__badge">Ekosistem Terkurasi</span>
        <h1 class="ib-hero__title">UMKM Siap Go Digital</h1>
        <p class="ib-hero__desc">
            Eksplorasi ekosistem produk artisan unggulan yang telah terkurasi
            dan siap bersaing di pasar nasional maupun global.
        </p>
    </div>
</div>

{{-- STATS BAR --}}
<div class="ib-stats">
    <div class="ib-stats__grid">
        <div>
            <p class="ib-stats__number ib-stats__number--gold">{{ $stats['total'] }}</p>
            <p class="ib-stats__label">Total UMKM</p>
        </div>
        <div>
            <p class="ib-stats__number">{{ $stats['tokopedia'] }} <span class="icon">🌍</span></p>
            <p class="ib-stats__label">Tokopedia</p>
        </div>
        <div>
            <p class="ib-stats__number">{{ $stats['shopee'] }} <span class="icon">✅</span></p>
            <p class="ib-stats__label">Shopee</p>
        </div>
        <div>
            <p class="ib-stats__number">{{ $stats['instagram'] }} <span class="icon">👑</span></p>
            <p class="ib-stats__label">Instagram</p>
        </div>
        <div>
            <p class="ib-stats__number">{{ $stats['whatsapp'] }} <span class="icon">📍</span></p>
            <p class="ib-stats__label">WhatsApp</p>
        </div>
    </div>
</div>

<div class="ib-container">

    {{-- SIDEBAR --}}
    <aside class="ib-sidebar">

        <button class="ib-mobile-filter-toggle" id="mobile-filter-toggle">
            <span>🔍 Filter Pencarian</span>
            <span data-toggle-icon>▼</span>
        </button>

        <div class="ib-sidebar__panel" id="sidebar-panel">

            <p class="ib-sidebar__title">Filter Pencarian</p>
            <p class="ib-sidebar__sub">Sesuaikan kriteria UMKM</p>

            <button class="ib-reset-btn" data-reset-filter>Reset Filter</button>

            <form method="GET" action="{{ route('godigital.index') }}">
                <div class="ib-search">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari UMKM Go Digital...">
                    <button type="submit">
                        <svg width="15" height="15" fill="none" stroke="#6b7280" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
            <a href="{{ route('godigital.index') }}" class="ib-reset-link">Reset Filter</a>

            <p class="ib-section-title">Platform Digital</p>
            @foreach($platformList as $key => $label)
            <label class="ib-checkbox">
                <input type="checkbox" data-filter-key="platform" data-filter-value="{{ $key }}"
                    {{ request('platform') === $key ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach

            <div class="ib-divider"></div>
            <p class="ib-section-title">Kategori Produk</p>
            @foreach($kategoriList as $key => $label)
            <label class="ib-checkbox">
                <input type="checkbox" data-filter-key="sektor" data-filter-value="{{ $key }}"
                    {{ request('sektor') === $key ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach

            <div class="ib-divider"></div>
            <p class="ib-section-title">Klasifikasi</p>
            @foreach(['unggulan' => '⭐ Unggulan', 'berkembang' => '📈 Berkembang', 'dasar' => '🌱 Dasar'] as $val => $lbl)
            <label class="ib-checkbox">
                <input type="radio" name="klas_r" data-filter-key="klasifikasi" data-filter-value="{{ $val }}"
                    {{ request('klasifikasi') === $val ? 'checked' : '' }}>
                <span>{{ $lbl }}</span>
            </label>
            @endforeach

            <div class="ib-divider"></div>
            <p class="ib-section-title">Kabupaten/Kota</p>
            <select data-filter-select="kabupaten" class="ib-select">
                <option value="">Semua Wilayah</option>
                @foreach($kabupatenList as $kab)
                <option value="{{ $kab }}" {{ request('kabupaten') === $kab ? 'selected' : '' }}>{{ $kab }}</option>
                @endforeach
            </select>

        </div>
    </aside>

    {{-- KONTEN UTAMA --}}
    <main class="ib-main">

        {{-- TRENDING --}}
        @if($trending->isNotEmpty())
        <p class="ib-section-eyebrow">🔥 Trending</p>
        <div class="ib-trending-viewport">
            <div class="ib-trending-track" id="trending-track">
                @foreach($trending as $item)
                    @php
                        $tProduk = $item->produkUnggulan->first() ?? $item->produk->first();
                        $tFoto   = $tProduk?->foto_final;
                        if (!$tFoto && $item->foto_usaha) {
                            $tFoto = Storage::url($item->foto_usaha);
                        }
                    @endphp
                    <div class="ib-trending-item">
                        <a href="{{ route('direktori.show', $item->slug) }}" class="ib-card">
                            <div class="ib-card__photo">
                                <div class="ib-card__photo-inner">
                                    @if($tFoto)
                                        <img src="{{ $tFoto }}" alt="{{ $item->nama_usaha }}" loading="lazy">
                                    @else
                                        <div class="ib-card__placeholder">🏪</div>
                                    @endif

                                    @if($item->tokopedia || $item->shopee || $item->instagram)
                                    <div class="ib-card__badges">
                                        @if($item->tokopedia)<span class="ib-badge ib-badge--tokped">Tokped</span>@endif
                                        @if($item->shopee)<span class="ib-badge ib-badge--shopee">Shopee</span>@endif
                                        @if($item->instagram)<span class="ib-badge ib-badge--ig">IG</span>@endif
                                    </div>
                                    @endif

                                    @if($item->whatsapp)
                                    <span class="ib-card__wa" data-wa-link="{{ $item->wa_link }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.115.549 4.099 1.508 5.826L0 24l6.335-1.484A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.366l-.36-.214-3.727.872.936-3.619-.235-.372A9.818 9.818 0 1112 21.818z"/>
                                        </svg>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="ib-card__info">
                                <p class="ib-card__name">{{ $item->nama_usaha }}</p>
                                <p class="ib-card__sektor">{{ ucfirst($item->sektor) }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            <button data-slider-prev class="ib-slider-btn ib-slider-btn--left">‹</button>
            <button data-slider-next class="ib-slider-btn ib-slider-btn--right">›</button>
        </div>
        @endif

        {{-- RESULT TITLE --}}
        <p class="ib-result-title">Ditemukan <strong>{{ $umkm->total() }}</strong> brand</p>

        {{-- GRID --}}
        @if($umkm->isEmpty())
        <div class="ib-empty">
            <p class="ib-empty__icon">📱</p>
            <p class="ib-empty__title">Tidak ada UMKM Go Digital yang ditemukan</p>
            <p class="ib-empty__desc">Coba ubah filter atau reset pencarian</p>
            <a href="{{ route('godigital.index') }}" class="ib-empty__btn">Reset Filter</a>
        </div>
        @else
        <div class="ib-grid">
            @foreach($umkm as $item)
                @php
                    $pProduk = $item->produkUnggulan->first() ?? $item->produk->first();
                    $pFoto   = $pProduk?->foto_final;
                    if (!$pFoto && $item->foto_usaha) {
                        $pFoto = Storage::url($item->foto_usaha);
                    }
                @endphp
                <a href="{{ route('direktori.show', $item->slug) }}" class="ib-card">
                    <div class="ib-card__photo">
                        <div class="ib-card__photo-inner">
                            @if($pFoto)
                                <img src="{{ $pFoto }}" alt="{{ $item->nama_usaha }}" loading="lazy">
                            @else
                                <div class="ib-card__placeholder">🏪</div>
                            @endif

                            @if($item->klasifikasi === 'berkembang')
                                <span class="ib-card__klasifikasi">📈 Berkembang</span>
                            @elseif($item->klasifikasi === 'unggulan')
                                <span class="ib-card__klasifikasi">⭐ Unggulan</span>
                            @else
                                @if($item->tokopedia || $item->shopee || $item->instagram)
                                <div class="ib-card__badges">
                                    @if($item->tokopedia)<span class="ib-badge ib-badge--tokped">Tokped</span>@endif
                                    @if($item->shopee)<span class="ib-badge ib-badge--shopee">Shopee</span>@endif
                                    @if($item->instagram)<span class="ib-badge ib-badge--ig">IG</span>@endif
                                </div>
                                @endif
                            @endif

                            @if($item->whatsapp)
                            <span class="ib-card__wa" data-wa-link="{{ $item->wa_link }}">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="white">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.115.549 4.099 1.508 5.826L0 24l6.335-1.484A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.366l-.36-.214-3.727.872.936-3.619-.235-.372A9.818 9.818 0 1112 21.818z"/>
                                </svg>
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="ib-card__info">
                        <p class="ib-card__name">{{ $item->nama_usaha }}</p>
                        <p class="ib-card__sektor">{{ ucfirst($item->sektor) }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- PAGINATION --}}
        <div class="ib-pagination">
            <p class="ib-pagination__info">
                Showing {{ $umkm->firstItem() }} — {{ $umkm->lastItem() }} of {{ $umkm->total() }} curated items
            </p>
            <nav class="ib-pagination__nav">
                @if($umkm->onFirstPage())
                    <span class="ib-page-btn ib-page-btn--disabled">‹</span>
                @else
                    <a href="{{ $umkm->previousPageUrl() }}" class="ib-page-btn">‹</a>
                @endif

                @foreach($umkm->getUrlRange(1, $umkm->lastPage()) as $page => $url)
                    @if($page == $umkm->currentPage())
                        <span class="ib-page-btn ib-page-btn--active">{{ $page }}</span>
                    @elseif($page == 1 || $page == $umkm->lastPage() || abs($page - $umkm->currentPage()) <= 1)
                        <a href="{{ $url }}" class="ib-page-btn">{{ $page }}</a>
                    @elseif(abs($page - $umkm->currentPage()) == 2)
                        <span class="ib-page-btn ib-page-btn--dots">…</span>
                    @endif
                @endforeach

                @if($umkm->hasMorePages())
                    <a href="{{ $umkm->nextPageUrl() }}" class="ib-page-btn">›</a>
                @else
                    <span class="ib-page-btn ib-page-btn--disabled">›</span>
                @endif
            </nav>
        </div>
        @endif

    </main>
</div>



</div>
@endsection