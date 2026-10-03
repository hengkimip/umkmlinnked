@extends('layouts.public')

@section('title', 'Detail Berita — UMKMLinked.ID')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <a href="{{ route('berita.index') }}"
       class="text-sm text-green-700 hover:underline">← Kembali ke Berita</a>

    <h1 class="text-2xl font-bold text-gray-800 mt-4 mb-2">
        Judul Berita
    </h1>
    <p class="text-xs text-gray-400 mb-6">{{ now()->format('d M Y') }} · Admin UMKMLinked.ID</p>

    <div class="aspect-video bg-gradient-to-br from-green-50 to-green-100
                rounded-2xl flex items-center justify-center text-6xl mb-8">
        📰
    </div>

    <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed">
        <p>Konten berita akan tampil di sini setelah fitur manajemen berita diimplementasikan
           di panel admin.</p>
    </div>

</div>
@endsection