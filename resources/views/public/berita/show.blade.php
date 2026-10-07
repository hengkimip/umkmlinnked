@extends('layouts.public')

@section('title', $berita->judul . ' — Berita UMKMLinked.ID')
@section('description', $berita->cuplikan(160))
@section('meta')
    @vite('resources/css/berita.css')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $berita->judul }}">
    <meta property="og:description" content="{{ $berita->cuplikan(160) }}">
    @if ($berita->gambarUrl())
        <meta property="og:image" content="{{ $berita->gambarUrl() }}">
    @endif
@endsection

@section('content')
<div class="ib-body">
    <article class="ib-artikel" aria-labelledby="page-title">
        <a href="{{ route('berita.index') }}" class="ib-artikel__kembali">← Kembali ke Berita</a>

        <header class="ib-artikel__head">
            <span class="ib-artikel__label">Berita official</span>
            <h1 id="page-title" class="ib-artikel__judul">{{ $berita->judul }}</h1>
            <p class="ib-artikel__meta">
                <time datetime="{{ $berita->terbit_pada->toIso8601String() }}">{{ $berita->terbit_pada->translatedFormat('d F Y') }}</time>
                · Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat
            </p>
        </header>

        @if ($berita->gambarUrl())
            <img class="ib-artikel__gambar" src="{{ $berita->gambarUrl() }}" alt="" width="1200" height="675" fetchpriority="high">
        @endif

        <div class="ib-artikel__isi">
            @foreach ($berita->paragraf() as $p)
                <p>{!! nl2br(e($p)) !!}</p>
            @endforeach
        </div>
    </article>

    @if ($lainnya->isNotEmpty())
        <section class="ib-page-section ib-berita-lain" aria-labelledby="berita-lain-title">
            <h2 id="berita-lain-title" class="ib-berita-lain__judul">Berita lainnya</h2>
            <div class="ib-berita-grid">
                @foreach ($lainnya as $b)
                    @include('public.berita._kartu', ['b' => $b])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
