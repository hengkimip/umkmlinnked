{{-- resources/views/layouts/public.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UMKMLinked.ID — Direktori UMKM Kalimantan Barat')</title>
    <meta name="description" content="@yield('description', 'Direktori UMKM terintegrasi Provinsi Kalimantan Barat')">
    @vite([
        'resources/css/app.css',
        'resources/css/umkm-card.css',
        'resources/css/direktori-layout.css',
        'resources/js/app.js',
        'resources/js/direktori.js',
    ])
</head>
<body class="bg-gray-50 font-sans antialiased">

{{-- NAVBAR TUNGGAL — dipindahkan dari direktori.blade.php, dipakai semua halaman --}}
<nav class="ib-nav">
    <a href="{{ route('home') }}" class="ib-nav__logo">UMKMLinked.id</a>
    <ul class="ib-nav__menu" id="nav-menu">
        <li><a href="{{ route('direktori.index') }}" class="ib-nav__link {{ request()->routeIs('direktori.*') || request()->routeIs('home') ? 'ib-nav__link--active' : '' }}">
       Semua Brand</a></li>
        <li><a href="{{ route('godigital.index') }}" class="ib-nav__link {{ request()->routeIs('godigital.*') ? 'ib-nav__link--active' : '' }}">Go Digital</a></li>
        <li><a href="{{ route('goglobal.index') }}" class="ib-nav__link {{ request()->routeIs('goglobal.*') ? 'ib-nav__link--active' : '' }}">Go Global</a></li>
        <li><a href="{{ route('berita.index') }}" class="ib-nav__link {{ request()->routeIs('berita.*') ? 'ib-nav__link--active' : '' }}">Berita</a></li>
        <li><a href="{{ route('kemitraan.index') }}" class="ib-nav__link {{ request()->routeIs('kemitraan.*') ? 'ib-nav__link--active' : '' }}">Kemitraan</a></li>
    </ul>
    <button class="ib-nav__burger" id="nav-burger">☰</button>
    @auth
        <a href="{{ route('admin.dashboard') }}" class="ib-nav__cta">Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="ib-nav__cta">Login Admin</a>
    @endauth
</nav>

@yield('content')

<footer class="ib-footer">
    <div class="ib-footer__grid">
        <div>
            <p class="ib-footer__logo">UMKMLinked.id</p>
            <p class="ib-footer__copy">© {{ date('Y') }} UMKMLinked.id — Bank Indonesia Wilayah Kalimantan Barat</p>
        </div>
        <div class="ib-footer__links">
            <a href="#">Kebijakan Privasi</a>
            <a href="#">Syarat & Ketentuan</a>
            <a href="#">Hubungi Kami</a>
        </div>
    </div>
</footer>

</body>
</html>