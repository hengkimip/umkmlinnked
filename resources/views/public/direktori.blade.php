@extends('layouts.public')
@section('title', 'Semua Brand — UMKMLinked.ID')
@section('description', 'Direktori UMKM binaan Bank Indonesia Kalimantan Barat: temukan produk, lokasi, dan kontak WhatsApp usaha.')
@section('content')

<div class="ib-body">

    @include('public.partials.hero', [
        'badge' => 'Ekosistem terkurasi',
        'title' => 'Inkubator Bank Indonesia',
        'desc'  => 'Eksplorasi produk unggulan UMKM Kalimantan Barat yang telah terkurasi dan siap bersaing di pasar nasional maupun global.',
    ])

    @include('public.partials.stats', ['stats' => $stats, 'satuBaris' => true])

    <div class="ib-container">

        <x-public.filter-panel :action="url()->current()" placeholder="Cari brand atau produk..." :kabupaten-list="$kabupatenList">
            {{-- Sektor Usaha · Platform Digital · Jangkauan Pasar · Sertifikasi Produk
                 (boleh mencentang beberapa pilihan; pilihan & jumlah sama dengan Peta Interaktif) --}}
            @foreach ($filterTag as $grup => $def)
                @php $dipilih = \App\Support\FilterUmkm::daftar($grup); @endphp
                <fieldset class="ib-filter-group">
                    <legend class="ib-section-title">{{ $def['judul'] }}</legend>
                    @foreach ($def['opsi'] as $kode => $label)
                        <label class="ib-checkbox">
                            <input type="checkbox" data-filter-multi="{{ $grup }}" data-filter-value="{{ $kode }}" @checked(in_array($kode, $dipilih, true))>
                            <span>{{ $label }}</span>
                            <small>{{ $jumlahTag[$grup][$kode] ?? 0 }}</small>
                        </label>
                    @endforeach
                </fieldset>

                <div class="ib-divider"></div>
            @endforeach

            <fieldset class="ib-filter-group">
                <legend class="ib-section-title">Rentang harga produk</legend>
                <div class="ib-price-row">
                    <div>
                        <label for="harga_min" class="ib-price-label">Minimum (Rp)</label>
                        <input type="number" id="harga_min" min="0" step="1000" inputmode="numeric" value="{{ \App\Support\FilterUmkm::nilai('harga_min') }}" class="ib-price-input">
                    </div>
                    <div>
                        <label for="harga_max" class="ib-price-label">Maksimum (Rp)</label>
                        <input type="number" id="harga_max" min="0" step="1000" inputmode="numeric" value="{{ \App\Support\FilterUmkm::nilai('harga_max') }}" class="ib-price-input">
                    </div>
                </div>
                <button type="button" id="apply-harga-btn" class="ib-apply-btn">Terapkan harga</button>
            </fieldset>

            <div class="ib-divider"></div>
        </x-public.filter-panel>

        <main class="ib-main">

            {{-- Hasil pencarian langsung di atas (bagian "Pilihan teratas · Trending" ditiadakan) --}}
            <h2 class="ib-result-title">Ditemukan <strong>{{ number_format($umkm->total(), 0, ',', '.') }}</strong> brand</h2>

            @if ($umkm->isEmpty())
                <div class="ib-empty">
                    <div class="ib-empty__icon" aria-hidden="true">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z"/></svg>
                    </div>
                    <p class="ib-empty__title">Belum ada brand yang cocok</p>
                    <p class="ib-empty__desc">Coba kata kunci lain atau longgarkan filter pencarian.</p>
                    <a href="{{ route('direktori.index') }}" class="ib-empty__btn">Reset filter</a>
                </div>
            @else
                <div class="ib-grid">
                    @foreach ($umkm as $item)
                        @include('public.partials.umkm-card', ['item' => $item, 'badge' => 'digital'])
                    @endforeach
                </div>

                @include('public.partials.pagination', ['paginator' => $umkm])
            @endif

        </main>
    </div>
</div>
@endsection
