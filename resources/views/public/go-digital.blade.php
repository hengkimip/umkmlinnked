@extends('layouts.public')
@section('title', 'Go Digital — UMKMLinked.ID')
@section('description', 'UMKM Kalimantan Barat yang sudah hadir di marketplace dan media sosial.')
@section('content')

<div class="ib-body">

    @include('public.partials.hero', [
        'badge' => 'Go Digital',
        'title' => 'UMKM Siap Go Digital',
        'desc'  => 'UMKM Kalimantan Barat yang telah hadir di marketplace, media sosial, dan WhatsApp Bisnis — siap menjangkau pembeli di seluruh Indonesia.',
    ])

    @include('public.partials.stats', ['stats' => $stats])

    <div class="ib-container">

        <x-public.filter-panel :action="route('godigital.index')" placeholder="Cari UMKM Go Digital..." :kabupaten-list="$kabupatenList">
            <fieldset class="ib-filter-group">
                <legend class="ib-section-title">Platform digital</legend>
                @foreach ($platformList as $key => $label)
                    <label class="ib-checkbox">
                        <input type="checkbox" data-filter-key="platform" data-filter-value="{{ $key }}" @checked(request('platform') === $key)>
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

            @include('public.partials.trending', ['trending' => $trending, 'badge' => 'digital'])

            <h2 class="ib-result-title">Ditemukan <strong>{{ number_format($umkm->total(), 0, ',', '.') }}</strong> brand</h2>

            @if ($umkm->isEmpty())
                <div class="ib-empty">
                    <div class="ib-empty__icon" aria-hidden="true">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                    </div>
                    <p class="ib-empty__title">Belum ada UMKM Go Digital yang cocok</p>
                    <p class="ib-empty__desc">Coba kata kunci lain atau longgarkan filter pencarian.</p>
                    <a href="{{ route('godigital.index') }}" class="ib-empty__btn">Reset filter</a>
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
