{{--
    Panel filter bersama untuk Direktori, Go Digital, Go Global.
    Slot default = filter khusus halaman; klasifikasi & kabupaten selalu ada.
--}}
@props(['action', 'placeholder' => 'Cari UMKM atau produk...', 'kabupatenList' => collect()])

@php
    // Pertahankan filter aktif saat mencari — hanya nilai yang lolos whitelist FilterUmkm
    $filterAktif = collect(\App\Support\FilterUmkm::dari(request()))
        ->except('q')
        ->map(fn ($v) => (string) $v);

    $kosong       = \App\Models\Umkm::KABUPATEN_KOSONG;
    $kabupatenUrut = collect($kabupatenList)->reject(fn ($k) => $k === $kosong)->values();
    $adaKosong    = collect($kabupatenList)->contains($kosong);
@endphp

<aside class="ib-sidebar" aria-label="Filter pencarian">
    <button type="button" class="ib-mobile-filter-toggle" id="mobile-filter-toggle"
            aria-expanded="false" aria-controls="sidebar-panel">
        <span>Filter pencarian @if ($filterAktif->isNotEmpty() || (\App\Support\FilterUmkm::nilai('q') !== null))<small>· {{ $filterAktif->count() + ((\App\Support\FilterUmkm::nilai('q') !== null) ? 1 : 0) }} aktif</small>@endif</span>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
    </button>

    <div class="ib-sidebar__panel" id="sidebar-panel">
        <div class="ib-sidebar__head">
            <div>
                <h2 class="ib-sidebar__title">Filter pencarian</h2>
                <p class="ib-sidebar__sub">Sesuaikan kriteria UMKM</p>
            </div>
            <button type="button" class="ib-reset-btn" data-reset-filter>Reset</button>
        </div>

        <form method="GET" action="{{ $action }}" role="search">
            @foreach ($filterAktif as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="ib-search">
                <label for="filter-q" class="ib-sr-only">Kata kunci</label>
                <input type="search" id="filter-q" name="q" value="{{ \App\Support\FilterUmkm::nilai('q') }}"
                       placeholder="{{ $placeholder }}" maxlength="100" autocomplete="off">
                <button type="submit" aria-label="Cari">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z"/></svg>
                </button>
            </div>
        </form>

        {{ $slot }}

        <fieldset class="ib-filter-group">
            <legend class="ib-section-title">Klasifikasi</legend>
            @foreach (['unggulan' => 'Unggulan', 'berkembang' => 'Berkembang', 'dasar' => 'Dasar'] as $val => $lbl)
                <label class="ib-checkbox">
                    <input type="radio" name="klas_r" data-filter-key="klasifikasi" data-filter-value="{{ $val }}"
                           @checked(\App\Support\FilterUmkm::nilai('klasifikasi') === $val)>
                    <span>{{ $lbl }}</span>
                </label>
            @endforeach
        </fieldset>

        <div class="ib-divider"></div>

        <label for="filter-kabupaten" class="ib-section-title" style="display:block">Kabupaten/Kota</label>
        <select id="filter-kabupaten" data-filter-select="kabupaten" class="ib-select">
            <option value="">Semua wilayah</option>
            @foreach ($kabupatenUrut as $kab)
                <option value="{{ $kab }}" @selected(\App\Support\FilterUmkm::nilai('kabupaten') === $kab)>{{ $kab }}</option>
            @endforeach
            @if ($adaKosong)
                <option value="{{ $kosong }}" @selected(\App\Support\FilterUmkm::nilai('kabupaten') === $kosong)>Wilayah belum terdata</option>
            @endif
        </select>
    </div>
</aside>
