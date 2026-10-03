@extends('layouts.public')
@section('title', 'Go Global — UMKMLinked.ID')
@section('description', 'UMKM Kalimantan Barat dengan jangkauan pasar luas dan sertifikasi produk.')
@section('content')

<div class="ib-body">

    @include('public.partials.hero', [
        'badge' => 'Go Global',
        'title' => 'UMKM Siap Go Global',
        'desc'  => 'UMKM Kalimantan Barat dengan jangkauan pasar antarprovinsi hingga ekspor, didukung sertifikasi Halal, BPOM, dan SNI.',
    ])

    @include('public.partials.stats', ['stats' => $stats])

    <div class="ib-container">

        <x-public.filter-panel :action="route('goglobal.index')" placeholder="Cari UMKM Go Global..." :kabupaten-list="$kabupatenList">
            <fieldset class="ib-filter-group">
                <legend class="ib-section-title">Jangkauan pasar</legend>
                @foreach ($jangkauanList as $key => $label)
                    <label class="ib-checkbox">
                        <input type="checkbox" data-filter-key="jangkauan" data-filter-value="{{ $key }}" @checked(request('jangkauan') === $key)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="ib-divider"></div>

            <fieldset class="ib-filter-group">
                <legend class="ib-section-title">Sertifikasi produk</legend>
                @foreach ($sertifikasiList as $key => $label)
                    <label class="ib-checkbox">
                        <input type="checkbox" data-filter-key="sertifikasi" data-filter-value="{{ $key }}" @checked(request('sertifikasi') === $key)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="ib-divider"></div>

            <fieldset class="ib-filter-group">
                <legend class="ib-section-title">Sektor usaha</legend>
                @foreach ($kategoriList as $key => $s)
                    <label class="ib-checkbox">
                        <input type="checkbox" data-filter-key="sektor" data-filter-value="{{ $key }}" @checked(request('sektor') === $key)>
                        <span>{{ $s['label'] }}</span>
                        <small>{{ $s['count'] }}</small>
                    </label>
                @endforeach
            </fieldset>

            <div class="ib-divider"></div>
        </x-public.filter-panel>

        <main class="ib-main">

            @include('public.partials.trending', ['trending' => $trending, 'badge' => 'global'])

            <h2 class="ib-result-title">Ditemukan <strong>{{ number_format($umkm->total(), 0, ',', '.') }}</strong> brand</h2>

            @if ($umkm->isEmpty())
                <div class="ib-empty">
                    <div class="ib-empty__icon" aria-hidden="true">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18m0 18a9 9 0 1 1 0-18m0 18c2.5 0 4.5-4 4.5-9S14.5 3 12 3m0 18c-2.5 0-4.5-4-4.5-9S9.5 3 12 3M3.6 9h16.8M3.6 15h16.8"/></svg>
                    </div>
                    <p class="ib-empty__title">Belum ada UMKM Go Global yang cocok</p>
                    <p class="ib-empty__desc">Coba kata kunci lain atau longgarkan filter pencarian.</p>
                    <a href="{{ route('goglobal.index') }}" class="ib-empty__btn">Reset filter</a>
                </div>
            @else
                <div class="ib-grid">
                    @foreach ($umkm as $item)
                        @include('public.partials.umkm-card', ['item' => $item, 'badge' => 'global'])
                    @endforeach
                </div>

                @include('public.partials.pagination', ['paginator' => $umkm])
            @endif

        </main>
    </div>
</div>
@endsection
