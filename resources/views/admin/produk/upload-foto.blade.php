@extends('layouts.admin')

@section('title', 'Upload Foto Produk')
@section('heading', 'Upload Foto Produk')

@section('content')
<div class="mx-auto max-w-3xl"
     x-data="uploadFoto({ listUrl: @js(route('admin.produk.list', ['umkmId' => '__ID__'])), maks: {{ $maksProduk }}, awal: @js(old('umkm_id')) })"
     x-init="init()">

    <p class="mb-6 text-sm text-slate-500">
        Unggah 1–{{ $maksProduk }} foto produk per UMKM. Foto pertama menjadi foto utama di halaman direktori.
    </p>

    <form method="POST" action="{{ route('admin.produk.upload-foto.store') }}" enctype="multipart/form-data"
          @submit="submit($event)"
          class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            {{-- UMKM --}}
            <div class="sm:col-span-2">
                <label for="umkm-select" class="mb-1.5 block text-sm font-medium text-slate-700">
                    UMKM <span class="text-red-500">*</span>
                </label>
                <select name="umkm_id" id="umkm-select" required x-model="umkmId" @change="muatProduk()"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                    <option value="">— Pilih UMKM —</option>
                    @foreach ($daftarUmkm as $umkm)
                        <option value="{{ $umkm->id }}" @selected(old('umkm_id') == $umkm->id)>
                            {{ $umkm->nama_usaha }} — {{ $umkm->kabupaten }}
                        </option>
                    @endforeach
                </select>
                @if ($daftarUmkm->isEmpty())
                    <p class="mt-1.5 text-xs text-amber-700">Belum ada UMKM pada wilayah Anda. Import data terlebih dahulu.</p>
                @endif
                @error('umkm_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Nama produk --}}
            <div>
                <label for="nama_produk" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama produk <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nama_produk" name="nama_produk" value="{{ old('nama_produk') }}" required maxlength="255"
                       placeholder="Contoh: Kopi Arabika Kalbar"
                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                @error('nama_produk') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Harga --}}
            <div>
                <label for="harga" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Harga <span class="font-normal text-slate-400">(opsional)</span>
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                    <input type="number" id="harga" name="harga" value="{{ old('harga') }}" min="0" step="1" inputmode="numeric"
                           placeholder="85000"
                           class="w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                </div>
                @error('harga') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Foto --}}
            <div class="sm:col-span-2">
                <p class="mb-1.5 block text-sm font-medium text-slate-700">
                    Foto produk <span class="text-red-500">*</span>
                </p>

                <label for="foto-input"
                       @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drop($event)"
                       :class="drag ? 'border-navy-700 bg-navy-900/5' : 'border-slate-300 bg-slate-50 hover:border-navy-700'"
                       class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition">
                    <svg class="mb-2 h-9 w-9 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.83 6.18A2.31 2.31 0 0 1 5.2 7.25c-.38.05-.76.11-1.13.18C3.02 7.6 2.25 8.51 2.25 9.57V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.57c0-1.06-.77-1.97-1.82-2.14a47.6 47.6 0 0 0-1.13-.18 2.31 2.31 0 0 1-1.64-1.07l-.82-1.31a2.19 2.19 0 0 0-1.74-1.03 48.77 48.77 0 0 0-5.2 0 2.19 2.19 0 0 0-1.74 1.03l-.82 1.31ZM16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                    <p class="text-sm font-medium text-slate-700">Klik atau seret foto ke sini</p>
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP · maks. 2 MB per foto · maks. <span x-text="sisa()"></span> foto</p>
                </label>
                <input type="file" id="foto-input" name="foto[]" x-ref="input" multiple
                       accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pilih($event.target.files)">

                <p x-show="clientError" x-text="clientError" x-cloak class="mt-2 text-sm text-red-600"></p>
                @error('foto') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('foto.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                <div x-show="previews.length" x-cloak class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                    <template x-for="(p, i) in previews" :key="p.url">
                        <div class="relative aspect-square overflow-hidden rounded-lg border-2"
                             :class="i === 0 ? 'border-green-600' : 'border-slate-200'">
                            <img :src="p.url" :alt="p.name" class="h-full w-full object-cover">
                            <span x-show="i === 0" class="absolute left-1 top-1 rounded bg-green-600 px-1.5 py-0.5 text-[10px] font-bold text-white">UTAMA</span>
                            <button type="button" @click="hapus(i)"
                                    class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white hover:bg-black/80"
                                    :aria-label="'Hapus foto ' + (i + 1)">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <p x-show="previews.length" x-cloak class="mt-2 text-xs text-slate-500"
                   x-text="previews.length + ' foto dipilih.'"></p>
            </div>
        </div>

        <button type="submit" :disabled="loading"
                class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 py-3 text-sm font-semibold text-white transition hover:bg-green-800 disabled:cursor-wait disabled:opacity-70">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4z"/>
            </svg>
            <span x-text="loading ? 'Menyimpan...' : 'Simpan Foto Produk'">Simpan Foto Produk</span>
        </button>
    </form>

    {{-- Produk yang sudah ada --}}
    <section x-show="umkmId" x-cloak class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Produk yang sudah ada</h2>
            <span class="text-xs text-slate-500" x-text="produk.length + ' / ' + maks"></span>
        </div>

        <p x-show="memuat" class="py-4 text-center text-sm text-slate-400">Memuat...</p>
        <p x-show="!memuat && !produk.length" class="py-4 text-center text-sm text-slate-400">Belum ada produk untuk UMKM ini.</p>

        <ul x-show="!memuat && produk.length" class="divide-y divide-slate-100">
            <template x-for="p in produk" :key="p.id">
                <li class="flex items-center gap-3 py-3">
                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        <img x-show="p.foto_url" :src="p.foto_url" :alt="p.nama_produk" class="h-full w-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-900" x-text="p.nama_produk"></p>
                        <p class="text-xs text-slate-500">
                            Urutan <span x-text="p.urutan ?? '-'"></span>
                            <span x-show="p.is_unggulan" class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 font-medium text-amber-800">Unggulan</span>
                        </p>
                    </div>
                </li>
            </template>
        </ul>
    </section>
</div>
@endsection

@push('scripts')
<script>
function uploadFoto({ listUrl, maks, awal }) {
    const MAKS_UKURAN = 2 * 1024 * 1024;
    const TIPE = ['image/jpeg', 'image/png', 'image/webp'];

    return {
        umkmId: awal ? String(awal) : '', produk: [], memuat: false,
        files: [], previews: [], drag: false, loading: false, clientError: '', maks,

        init() { if (this.umkmId) this.muatProduk(); },

        sisa() { return Math.max(0, this.maks - (this.umkmId ? this.produk.length : 0)); },

        async muatProduk() {
            this.produk = [];
            if (!this.umkmId) return;
            this.memuat = true;
            try {
                const r = await fetch(listUrl.replace('__ID__', encodeURIComponent(this.umkmId)), {
                    headers: { 'Accept': 'application/json' },
                });
                this.produk = r.ok ? await r.json() : [];
            } finally {
                this.memuat = false;
            }
        },

        pilih(list) {
            this.clientError = '';
            const masuk = Array.from(list || []);
            const valid = masuk.filter(f => TIPE.includes(f.type) && f.size <= MAKS_UKURAN);
            if (valid.length < masuk.length) {
                this.clientError = 'Sebagian file dilewati: hanya JPG/PNG/WEBP dengan ukuran maks. 2 MB.';
            }
            const batas = this.sisa();
            if (valid.length > batas) {
                this.clientError = `Hanya ${batas} foto yang dapat ditambahkan untuk UMKM ini.`;
            }
            this.files = valid.slice(0, batas);
            this.sinkron();
        },

        drop(e) { this.drag = false; this.pilih(e.dataTransfer.files); },

        hapus(i) { this.files.splice(i, 1); this.sinkron(); },

        sinkron() {
            this.previews.forEach(p => URL.revokeObjectURL(p.url));
            this.previews = this.files.map(f => ({ name: f.name, url: URL.createObjectURL(f) }));
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            this.$refs.input.files = dt.files;
        },

        submit(e) {
            if (!this.files.length) {
                e.preventDefault();
                this.clientError = 'Pilih minimal 1 foto.';
                return;
            }
            this.loading = true;
        },
    };
}
</script>
@endpush
