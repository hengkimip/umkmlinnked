@extends('layouts.admin')

@section('title', 'Berita Official')
@section('heading', 'Berita Official')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-500">
            Berita berstatus <strong class="text-slate-700">Terbit</strong> tampil di halaman publik
            <a href="{{ route('berita.index') }}" data-situs-publik class="font-medium text-navy-700 underline">Berita</a>
            mulai tanggal terbitnya. Draf hanya terlihat di sini.
        </p>
        <a href="{{ route('superadmin.berita.create') }}" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-800">
            + Tulis Berita
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @forelse ($berita as $b)
            <div class="flex flex-wrap items-start gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:px-6">
                @if ($b->gambarUrl())
                    <img src="{{ $b->gambarUrl() }}" alt="" class="h-16 w-24 flex-shrink-0 rounded-lg object-cover" loading="lazy">
                @else
                    <div class="flex h-16 w-24 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">Tanpa gambar</div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">{{ $b->judul }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        @if ($b->sudahTampil())
                            <span class="rounded-full bg-green-100 px-2 py-0.5 font-semibold text-green-800">Terbit</span>
                            {{ $b->terbit_pada->translatedFormat('d F Y H:i') }}
                        @elseif ($b->status === 'terbit')
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">Terjadwal</span>
                            {{ $b->terbit_pada->translatedFormat('d F Y H:i') }}
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 font-semibold text-slate-600">Draf</span>
                            diubah {{ $b->updated_at->translatedFormat('d F Y H:i') }}
                        @endif
                        · {{ $b->penulis?->name ?? 'Super Admin' }}
                    </p>
                    <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $b->cuplikan(160) }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    @if ($b->sudahTampil())
                        <a href="{{ route('berita.show', $b->slug) }}" data-situs-publik class="rounded-lg border border-slate-300 px-3 py-1.5 font-medium text-slate-700 transition hover:bg-slate-50">Lihat</a>
                    @endif
                    <a href="{{ route('superadmin.berita.edit', $b) }}" class="rounded-lg bg-navy-800 px-3 py-1.5 font-medium text-white transition hover:bg-navy-900">Ubah</a>
                    <form method="POST" action="{{ route('superadmin.berita.destroy', $b) }}"
                          onsubmit="return confirm(@js("Hapus berita \"{$b->judul}\"? Tindakan ini tidak dapat dibatalkan."))">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-700 transition hover:bg-red-50">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <p class="font-semibold text-slate-800">Belum ada berita</p>
                <p class="mt-1 text-sm text-slate-500">Tulis berita official pertama untuk ditampilkan di halaman Berita.</p>
                <a href="{{ route('superadmin.berita.create') }}" class="mt-4 inline-block rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-800">+ Tulis Berita</a>
            </div>
        @endforelse
    </div>

    {{ $berita->links() }}
</div>
@endsection
