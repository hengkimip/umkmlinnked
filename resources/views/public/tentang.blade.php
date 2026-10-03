@extends('layouts.public')

@section('title', 'Tentang Kami — UMKMLinked.ID')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">

    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Tentang UMKMLinked.ID</h1>
        <p class="text-gray-500 max-w-2xl mx-auto">
            Platform direktori UMKM terintegrasi Provinsi Kalimantan Barat
            yang diinisiasi oleh Dinas Koperasi & UKM.
        </p>
    </div>

    {{-- Visi Misi --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
        <div class="bg-green-50 border border-green-100 rounded-2xl p-6">
            <div class="text-3xl mb-3">🎯</div>
            <h2 class="font-bold text-gray-800 mb-2">Visi</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Menjadi platform direktori UMKM terpercaya di Kalimantan Barat
                yang mendorong pertumbuhan ekonomi daerah melalui digitalisasi
                dan kolaborasi lintas sektor.
            </p>
        </div>
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6">
            <div class="text-3xl mb-3">🚀</div>
            <h2 class="font-bold text-gray-800 mb-2">Misi</h2>
            <ul class="text-sm text-gray-600 space-y-2 leading-relaxed">
                <li>✓ Menghimpun data UMKM secara komprehensif dan terverifikasi</li>
                <li>✓ Memfasilitasi koneksi antara UMKM, pembeli, dan investor</li>
                <li>✓ Mendukung kebijakan berbasis data bagi pemerintah daerah</li>
                <li>✓ Mendorong UMKM naik kelas melalui klasifikasi dan pendampingan</li>
            </ul>
        </div>
    </div>

    {{-- Fitur Platform --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Fitur Platform</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['icon' => '🔍', 'label' => 'Direktori & Search'],
                ['icon' => '📊', 'label' => 'Klasifikasi UMKM'],
                ['icon' => '🗺️', 'label' => 'Peta Sebaran'],
                ['icon' => '📋', 'label' => 'Data Terintegrasi'],
                ['icon' => '🤝', 'label' => 'Portal Kemitraan'],
                ['icon' => '📈', 'label' => 'Dashboard Admin'],
                ['icon' => '🔒', 'label' => 'Multi-role Akses'],
                ['icon' => '📱', 'label' => 'Mobile Friendly'],
            ] as $fitur)
            <div class="bg-white rounded-xl border p-4 text-center hover:shadow-sm transition">
                <div class="text-3xl mb-2">{{ $fitur['icon'] }}</div>
                <p class="text-xs font-medium text-gray-700">{{ $fitur['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Kontak --}}
    <div class="bg-green-700 text-white rounded-2xl p-8 text-center">
        <h2 class="text-xl font-bold mb-2">Hubungi Kami</h2>
        <p class="text-green-200 text-sm mb-6">
            Dinas Koperasi, Usaha Kecil dan Menengah Provinsi Kalimantan Barat
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-green-800 rounded-xl p-4">
                <p class="text-green-300 text-xs mb-1">Alamat</p>
                <p>Jl. Sutan Syahrir No.1, Pontianak</p>
            </div>
            <div class="bg-green-800 rounded-xl p-4">
                <p class="text-green-300 text-xs mb-1">Email</p>
                <p>info@umkmlinked.id</p>
            </div>
            <div class="bg-green-800 rounded-xl p-4">
                <p class="text-green-300 text-xs mb-1">Telepon</p>
                <p>(0561) 123456</p>
            </div>
        </div>
    </div>

</div>
@endsection