@extends('layouts.admin')

@php
    $baru  = ! $berita->exists;
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('title', $baru ? 'Tulis Berita' : 'Ubah Berita')
@section('heading', $baru ? 'Tulis Berita' : 'Ubah Berita')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('superadmin.berita.index') }}" class="text-sm font-medium text-navy-700 hover:underline">← Kembali ke daftar berita</a>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $baru ? route('superadmin.berita.store') : route('superadmin.berita.update', $berita) }}"
          class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"
          x-data="{ status: @js(old('status', $berita->status)) }">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        @if ($errors->any())
            <ul class="list-inside list-disc rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        @endif

        <div>
            <label for="judul" class="{{ $label }}">Judul</label>
            <input id="judul" name="judul" value="{{ old('judul', $berita->judul) }}" required maxlength="255" class="{{ $input }}">
            @unless ($baru)
                <p class="mt-1 text-xs text-slate-500">Alamat: {{ route('berita.show', $berita->slug) }}</p>
            @endunless
        </div>

        <div>
            <label for="ringkasan" class="{{ $label }}">Ringkasan <span class="font-normal text-slate-400">(opsional, maks. 300 karakter)</span></label>
            <textarea id="ringkasan" name="ringkasan" rows="2" maxlength="300" class="{{ $input }}"
                      placeholder="Tampil di kartu berita. Bila kosong, diambil dari awal isi berita.">{{ old('ringkasan', $berita->ringkasan) }}</textarea>
        </div>

        <div>
            <label for="isi" class="{{ $label }}">Isi berita</label>
            <textarea id="isi" name="isi" rows="14" required maxlength="50000" class="{{ $input }}">{{ old('isi', $berita->isi) }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Teks biasa. Pisahkan paragraf dengan satu baris kosong.</p>
        </div>

        <div>
            <label for="gambar" class="{{ $label }}">Gambar utama <span class="font-normal text-slate-400">(opsional, JPG/PNG/WEBP, maks. 2 MB)</span></label>
            @if ($berita->gambarUrl())
                <div class="mb-2 flex items-center gap-3">
                    <img src="{{ $berita->gambarUrl() }}" alt="" class="h-20 w-32 rounded-lg object-cover">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="hapus_gambar" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-600">
                        Hapus gambar
                    </label>
                </div>
            @endif
            <input id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="status" class="{{ $label }}">Status</label>
                <select id="status" name="status" x-model="status" class="{{ $input }}">
                    @foreach (\App\Models\Berita::STATUS as $kode => $nama)
                        <option value="{{ $kode }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="status === 'terbit'">
                <label for="terbit_pada" class="{{ $label }}">Tanggal terbit <span class="font-normal text-slate-400">(kosong = sekarang)</span></label>
                <input id="terbit_pada" type="datetime-local" name="terbit_pada" class="{{ $input }}"
                       value="{{ old('terbit_pada', $berita->terbit_pada?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-800">
                <span x-text="status === 'terbit' ? 'Simpan & terbitkan' : 'Simpan draf'">Simpan</span>
            </button>
            <a href="{{ route('superadmin.berita.index') }}" class="text-sm font-medium text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
@endsection
