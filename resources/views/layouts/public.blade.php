{{-- resources/views/layouts/public.blade.php --}}
@php
    $menu = [
        ['route' => 'direktori.index', 'active' => ['direktori.*'],         'label' => 'Semua Brand'],
        ['route' => 'godigital.index', 'active' => ['godigital.*'],         'label' => 'Go Digital'],
        ['route' => 'goglobal.index',  'active' => ['goglobal.*'],          'label' => 'Go Global'],
        ['route' => 'tentang.index',   'active' => ['tentang.*'],           'label' => 'Tentang Kami'],
        ['route' => 'berita.index',    'active' => ['berita.*'],            'label' => 'Berita'],
        ['route' => 'kemitraan.index', 'active' => ['kemitraan.*'],         'label' => 'Kemitraan'],
    ];
    $ctaUrl   = auth()->check() ? route('dashboard') : route('login');
    $ctaLabel = auth()->check() ? 'Dashboard' : 'Login Admin';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f2a52">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>@yield('title', 'UMKMLinked.ID — Direktori UMKM Kalimantan Barat')</title>
    <meta name="description" content="@yield('description', 'Direktori UMKM terintegrasi Provinsi Kalimantan Barat')">
    @yield('meta')

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite([
        'resources/css/publik.css',
        'resources/css/umkm-card.css',
        'resources/css/direktori-layout.css',
        'resources/js/direktori.js',
    ])
</head>
<body class="ib-public @yield('body_class')">

<a href="#konten" class="ib-skip">Lewati ke konten</a>

<header class="ib-nav">
    <div class="ib-wrap ib-nav__inner">
        <a href="{{ route('home') }}" class="ib-nav__logo" aria-label="UMKMLinked — ke beranda"
           @if (request()->routeIs('home')) aria-current="page" @endif>
            <picture>
                <source srcset="{{ asset('logo-umkmlinked.webp') }}" type="image/webp">
                <img src="{{ asset('logo-umkmlinked.png') }}" alt="UMKMLinked" width="480" height="147" class="ib-nav__logo-img">
            </picture>
        </a>

        <nav aria-label="Menu utama">
            <ul class="ib-nav__menu" id="nav-menu">
                @foreach ($menu as $m)
                    @php $aktif = request()->routeIs(...$m['active']); @endphp
                    <li>
                        <a href="{{ route($m['route']) }}" @class(['ib-nav__link', 'ib-nav__link--active' => $aktif])
                           @if ($aktif) aria-current="page" @endif>{{ $m['label'] }}</a>
                    </li>
                @endforeach
                <li class="ib-nav__menu-cta"><a href="{{ $ctaUrl }}" class="ib-nav__cta">{{ $ctaLabel }}</a></li>
            </ul>
        </nav>

        <a href="{{ $ctaUrl }}" class="ib-nav__cta">{{ $ctaLabel }}</a>

        <button type="button" class="ib-nav__burger" id="nav-burger"
                aria-controls="nav-menu" aria-expanded="false" aria-label="Buka menu">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>
</header>

<div id="konten" tabindex="-1">
    @yield('content')
</div>

<footer class="ib-footer">
    <div class="ib-wrap">
        <div class="ib-footer__grid">
            <div>
                <p class="ib-footer__logo">UMKMLinked<span>.ID</span></p>
                <p class="ib-footer__tagline">
                    Direktori dan dashboard UMKM binaan KPw Bank Indonesia Provinsi Kalimantan Barat —
                    dari Sambas hingga Ketapang, dari Pontianak hingga Kapuas Hulu.
                </p>
            </div>
            <nav class="ib-footer__links" aria-label="Tautan footer">
                <a href="{{ route('tentang.index') }}">Tentang Kami</a>
                <a href="{{ route('kemitraan.index') }}">Kemitraan</a>
                <a href="{{ route('berita.index') }}">Berita</a>
            </nav>
        </div>
        <p class="ib-footer__copy">© {{ date('Y') }} UMKMLinked.ID · Bank Indonesia Wilayah Kalimantan Barat</p>
    </div>
</footer>

</body>
</html>
