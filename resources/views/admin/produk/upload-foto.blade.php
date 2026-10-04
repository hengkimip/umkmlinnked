@extends('layouts.admin')

@section('title', 'Upload Foto Produk')
@section('heading', 'Upload Foto Produk')

@php
    $warnaBadge = [
        'unggulan'    => 'bg-amber-100 text-amber-800 ring-amber-300',
        'terbaru'     => 'bg-sky-100 text-sky-800 ring-sky-300',
        'promo'       => 'bg-red-100 text-red-700 ring-red-300',
        'terlaris'    => 'bg-green-100 text-green-800 ring-green-300',
        'rekomendasi' => 'bg-violet-100 text-violet-800 ring-violet-300',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-3xl"
     x-data="uploadFoto({
        listUrl: @js(route('admin.produk.list', ['umkmId' => '__ID__'])),
        produkUrl: @js(route('admin.produk.update', ['produk' => '__ID__'])),
        maks: {{ $maksProduk }},
        awal: @js($umkmAwal),
        badges: @js($badges),
        warna: @js($warnaBadge),
        badgeLama: @js(old('badge', [])),
     })"
     x-init="init()">

    <p class="mb-6 text-sm text-slate-500">
        Unggah 1–{{ $maksProduk }} foto produk per UMKM. Foto berbadge <strong class="font-semibold text-amber-700">Unggulan</strong>
        menjadi foto utama di halaman direktori.
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
                        <option value="{{ $umkm->id }}" @selected($umkmAwal == $umkm->id)>
                            {{ $umkm->nama_usaha }} — {{ $umkm->kabupaten }}
                        </option>
                    @endforeach
                </select>
                @if ($daftarUmkm->isEmpty())
                    <p class="mt-1.5 text-xs text-amber-700">Belum ada UMKM pada wilayah Anda. Import data terlebih dahulu.</p>
                @endif
                @error('umkm_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Badge produk: muncul setelah UMKM dipilih --}}
            <fieldset x-show="umkmId" x-cloak x-transition.opacity class="sm:col-span-2">
                <legend class="mb-1.5 block text-sm font-medium text-slate-700">
                    Kategori badge produk <span class="font-normal text-slate-400">(status/promosi)</span>
                </legend>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="setBadgeSemua('')"
                            :class="badgeSemua === '' ? 'bg-navy-800 text-white ring-navy-800' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'"
                            class="rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 transition">
                        Tanpa badge
                    </button>
                    <template x-for="(label, kode) in badges" :key="kode">
                        <button type="button" @click="setBadgeSemua(kode)"
                                :aria-pressed="badgeSemua === kode"
                                :class="badgeSemua === kode ? warna[kode] + ' ring-2' : 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50'"
                                class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                                x-text="label"></button>
                    </template>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">
                    Berlaku untuk semua foto yang dipilih dan dapat diubah per foto di bawah.
                    <strong class="font-semibold text-amber-700">Unggulan</strong> hanya untuk satu foto dan otomatis menjadi foto utama.
                </p>
                @error('badge.*') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>

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

                <div x-show="previews.length" x-cloak class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <template x-for="(p, i) in previews" :key="p.url">
                        <div class="flex flex-col gap-1.5">
                            <div class="relative aspect-square overflow-hidden rounded-lg border-2"
                                 :class="i === utamaIndex() ? 'border-amber-500' : 'border-slate-200'">
                                <img :src="p.url" :alt="p.name" class="h-full w-full object-cover">
                                <span x-show="i === utamaIndex()" class="absolute left-1 top-1 rounded bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">UTAMA</span>
                                <span x-show="p.badge && p.badge !== 'unggulan'" :class="warna[p.badge]"
                                      class="absolute bottom-1 left-1 rounded px-1.5 py-0.5 text-[10px] font-bold" x-text="badges[p.badge]"></span>
                                <button type="button" @click="hapus(i)"
                                        class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white hover:bg-black/80"
                                        :aria-label="'Hapus foto ' + (i + 1)">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <label class="sr-only" :for="'badge-' + i" x-text="'Badge foto ' + (i + 1)"></label>
                            <select :id="'badge-' + i" :name="'badge[' + i + ']'" :value="p.badge" @change="setBadge(i, $event.target.value)"
                                    class="w-full rounded-md border-slate-300 py-1 text-xs shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                <option value="">Tanpa badge</option>
                                <template x-for="(label, kode) in badges" :key="kode">
                                    <option :value="kode" :selected="p.badge === kode" x-text="label"></option>
                                </template>
                            </select>
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

    {{-- Produk yang sudah ada: ubah data, hapus foto, hapus keterangan --}}
    <section x-show="umkmId" x-cloak class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="mb-1 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Produk yang sudah ada</h2>
            <span class="text-xs text-slate-500" x-text="produk.length + ' / ' + maks"></span>
        </div>
        <p class="mb-3 text-xs text-slate-500">Data ini yang tampil saat foto produk diklik di halaman direktori.</p>

        <div x-show="pesan" x-cloak role="status"
             :class="pesanGagal ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-800'"
             class="mb-3 rounded-lg border px-3 py-2 text-sm" x-text="pesan"></div>

        <p x-show="memuat" class="py-4 text-center text-sm text-slate-400">Memuat...</p>
        <p x-show="!memuat && !produk.length" class="py-4 text-center text-sm text-slate-400">Belum ada produk untuk UMKM ini.</p>

        <ul x-show="!memuat && produk.length" class="divide-y divide-slate-100">
            <template x-for="p in produk" :key="p.id">
                <li class="py-3">
                    <div class="flex items-start gap-3">
                        <div class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            <img x-show="p.foto_url" :src="p.foto_url" :alt="p.nama_produk" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                            <span x-show="!p.foto_url" class="flex h-full items-center justify-center text-[10px] text-slate-400">Tanpa foto</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900" x-text="p.nama_produk"></p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                <span x-text="p.harga > 0 ? rupiah(p.harga) : 'Tanpa harga'"></span>
                                <span x-show="p.is_unggulan" class="rounded bg-amber-500 px-1.5 py-0.5 font-bold text-white">Foto utama</span>
                                <span x-show="p.badge" :class="warna[p.badge]" class="rounded px-1.5 py-0.5 font-medium" x-text="badges[p.badge]"></span>
                            </p>
                            <p x-show="p.deskripsi" class="mt-1 line-clamp-2 text-xs text-slate-600" x-text="p.deskripsi"></p>
                            <p x-show="!p.deskripsi" class="mt-1 text-xs italic text-slate-400">Belum ada keterangan.</p>

                            <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                                <button type="button" @click="mulaiUbah(p)" :disabled="sibuk"
                                        class="rounded-md bg-navy-800 px-2.5 py-1 text-white transition hover:bg-navy-900 disabled:opacity-60">Ubah data</button>
                                <button type="button" x-show="p.foto_url" @click="aksi(p, 'foto', 'Hapus foto produk ini? Data produk tetap disimpan.')" :disabled="sibuk"
                                        class="rounded-md border border-red-200 px-2.5 py-1 text-red-700 transition hover:bg-red-50 disabled:opacity-60">Hapus foto</button>
                                <button type="button" x-show="p.deskripsi" @click="aksi(p, 'keterangan', 'Hapus keterangan produk ini?')" :disabled="sibuk"
                                        class="rounded-md border border-red-200 px-2.5 py-1 text-red-700 transition hover:bg-red-50 disabled:opacity-60">Hapus keterangan</button>
                                <button type="button" @click="aksi(p, '', 'Hapus produk ini beserta fotonya? Tindakan ini tidak dapat dibatalkan.')" :disabled="sibuk"
                                        class="rounded-md border border-slate-300 px-2.5 py-1 text-slate-600 transition hover:bg-slate-50 disabled:opacity-60">Hapus produk</button>
                            </div>
                        </div>
                    </div>

                    {{-- Form ubah data --}}
                    <form x-show="edit && edit.id === p.id" x-cloak @submit.prevent="simpanUbah()"
                          class="mt-3 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <div>
                            <label :for="'e-nama-' + p.id" class="mb-1 block text-xs font-medium text-slate-700">Nama produk *</label>
                            <input :id="'e-nama-' + p.id" type="text" :value="edit?.nama_produk ?? ''" @input="edit.nama_produk = $event.target.value" required maxlength="255"
                                   class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                        </div>
                        <div>
                            <label :for="'e-harga-' + p.id" class="mb-1 block text-xs font-medium text-slate-700">Harga (Rp)</label>
                            <input :id="'e-harga-' + p.id" type="number" min="0" step="1" inputmode="numeric" :value="edit?.harga ?? ''" @input="edit.harga = $event.target.value"
                                   class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                        </div>
                        <div>
                            <label :for="'e-badge-' + p.id" class="mb-1 block text-xs font-medium text-slate-700">Badge produk</label>
                            <select :id="'e-badge-' + p.id" @change="edit.badge = $event.target.value"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                <option value="" :selected="!edit?.badge">Tanpa badge</option>
                                <template x-for="(label, kode) in badges" :key="kode">
                                    <option :value="kode" :selected="edit?.badge === kode" x-text="label + (kode === 'unggulan' ? ' (foto utama)' : '')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label :for="'e-foto-' + p.id" class="mb-1 block text-xs font-medium text-slate-700">Ganti foto (opsional)</label>
                            <input :id="'e-foto-' + p.id" type="file" accept="image/jpeg,image/png,image/webp"
                                   @change="edit.foto = $event.target.files[0] || null"
                                   class="block w-full text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-white file:px-2.5 file:py-1.5 file:text-xs file:font-medium file:text-slate-700 file:ring-1 file:ring-slate-300">
                        </div>
                        <div class="sm:col-span-2">
                            <label :for="'e-desk-' + p.id" class="mb-1 block text-xs font-medium text-slate-700">Keterangan produk</label>
                            <textarea :id="'e-desk-' + p.id" rows="3" maxlength="2000" :value="edit?.deskripsi ?? ''" @input="edit.deskripsi = $event.target.value"
                                      placeholder="Contoh: Kopi robusta sangrai sedang, kemasan 250 g."
                                      class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700"></textarea>
                        </div>
                        <p x-show="editError" x-text="editError" class="text-sm text-red-600 sm:col-span-2"></p>
                        <div class="flex gap-2 sm:col-span-2">
                            <button type="submit" :disabled="sibuk"
                                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-800 disabled:opacity-60"
                                    x-text="sibuk ? 'Menyimpan...' : 'Simpan perubahan'"></button>
                            <button type="button" @click="edit = null"
                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Batal</button>
                        </div>
                    </form>
                </li>
            </template>
        </ul>
    </section>
</div>
@endsection

@push('scripts')
<script>
function uploadFoto({ listUrl, produkUrl, maks, awal, badges, warna, badgeLama }) {
    const MAKS_UKURAN = 2 * 1024 * 1024;
    const TIPE = ['image/jpeg', 'image/png', 'image/webp'];
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    return {
        umkmId: awal ? String(awal) : '', produk: [], memuat: false,
        files: [], previews: [], drag: false, loading: false, clientError: '', maks,
        badges, warna, badgeSemua: '',
        edit: null, editError: '', sibuk: false, pesan: '', pesanGagal: false,

        init() {
            if (this.umkmId) this.muatProduk();
            // Kembalikan pilihan badge "semua" bila validasi server gagal
            const lama = Object.values(badgeLama || {}).filter(Boolean);
            if (lama.length) this.badgeSemua = lama[0];
        },

        sisa() { return Math.max(0, this.maks - (this.umkmId ? this.produk.length : 0)); },

        rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 }); },

        // Foto yang akan menjadi foto utama: berbadge unggulan, atau foto pertama bila UMKM belum punya foto utama
        utamaIndex() {
            const i = this.previews.findIndex(p => p.badge === 'unggulan');
            if (i !== -1) return i;
            return this.produk.some(p => p.is_unggulan) || this.previews[0]?.badge ? -1 : 0;
        },

        async muatProduk() {
            this.produk = []; this.edit = null; this.pesan = '';
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

        // ---------- Badge ----------
        setBadgeSemua(kode) {
            this.badgeSemua = kode;
            // Unggulan hanya untuk satu foto (foto pertama); lainnya tanpa badge
            this.previews.forEach((p, i) => { p.badge = kode === 'unggulan' ? (i === 0 ? kode : '') : kode; });
        },

        setBadge(i, kode) {
            if (kode === 'unggulan') this.previews.forEach(p => { if (p.badge === 'unggulan') p.badge = ''; });
            this.previews[i].badge = kode;
        },

        // ---------- Pilih foto ----------
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
            this.setBadgeSemua(this.badgeSemua);
        },

        drop(e) { this.drag = false; this.pilih(e.dataTransfer.files); },

        hapus(i) {
            const badge = this.previews.map(p => p.badge);
            badge.splice(i, 1);
            this.files.splice(i, 1);
            this.sinkron(badge);
        },

        sinkron(badge = []) {
            this.previews.forEach(p => URL.revokeObjectURL(p.url));
            this.previews = this.files.map((f, i) => ({ name: f.name, url: URL.createObjectURL(f), badge: badge[i] ?? '' }));
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

        // ---------- Kelola produk yang sudah ada ----------
        async kirim(url, method, body = null) {
            const r = await fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body,
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const pertama = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(pertama || data.message || 'Terjadi kesalahan. Coba lagi.');
            }
            return data;
        },

        info(teks, gagal = false) { this.pesan = teks; this.pesanGagal = gagal; },

        mulaiUbah(p) {
            this.editError = '';
            this.edit = { id: p.id, nama_produk: p.nama_produk, harga: p.harga ?? '', deskripsi: p.deskripsi ?? '', badge: p.badge ?? '', foto: null };
        },

        async simpanUbah() {
            if (!this.edit) return;
            if (this.edit.foto && (!TIPE.includes(this.edit.foto.type) || this.edit.foto.size > MAKS_UKURAN)) {
                this.editError = 'Foto harus JPG/PNG/WEBP dengan ukuran maks. 2 MB.';
                return;
            }
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('nama_produk', this.edit.nama_produk);
            fd.append('harga', this.edit.harga ?? '');
            fd.append('deskripsi', this.edit.deskripsi ?? '');
            fd.append('badge', this.edit.badge ?? '');
            if (this.edit.foto) fd.append('foto', this.edit.foto);

            this.sibuk = true; this.editError = '';
            try {
                const data = await this.kirim(produkUrl.replace('__ID__', this.edit.id), 'POST', fd);
                this.edit = null;
                await this.muatProduk();
                this.info(data.message);
            } catch (err) {
                this.editError = err.message;
            } finally {
                this.sibuk = false;
            }
        },

        // bagian: 'foto' | 'keterangan' | '' (hapus produk)
        async aksi(p, bagian, konfirmasi) {
            if (!confirm(konfirmasi)) return;
            const url = produkUrl.replace('__ID__', p.id) + (bagian ? '/' + bagian : '');
            this.sibuk = true;
            try {
                const data = await this.kirim(url, 'DELETE');
                await this.muatProduk();
                this.info(data.message);
            } catch (err) {
                this.info(err.message, true);
            } finally {
                this.sibuk = false;
            }
        },
    };
}
</script>
@endpush
