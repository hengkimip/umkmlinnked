@extends('layouts.admin')

@section('title', 'Import Data UMKM')
@section('heading', 'Import Data UMKM')

@section('content')
<div class="mx-auto max-w-3xl" x-data="{ tab: @js($errors->manual->any() || old('_form') === 'manual' ? 'manual' : 'file') }">

    <p class="mb-4 text-sm text-slate-500">
        Impor banyak UMKM sekaligus dari file CSV/Excel, atau tambahkan satu UMKM secara manual.
        @if ($opd)
            Data akan tercatat sebagai binaan <span class="font-semibold text-slate-700">{{ $opd->nama_opd }}</span>.
        @endif
    </p>

    {{-- Pilihan cara menambah data --}}
    <div class="mb-6 grid grid-cols-2 gap-1 rounded-xl border border-slate-200 bg-white p-1" role="tablist">
        <button type="button" role="tab" @click="tab = 'file'" :aria-selected="tab === 'file'"
                :class="tab === 'file' ? 'bg-navy-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="rounded-lg px-3 py-2.5 text-sm font-semibold transition">Import File (CSV/Excel)</button>
        <button type="button" role="tab" @click="tab = 'manual'" :aria-selected="tab === 'manual'"
                :class="tab === 'manual' ? 'bg-navy-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="rounded-lg px-3 py-2.5 text-sm font-semibold transition">Tambah Manual</button>
    </div>

    @include('admin.import._manual')

    <div x-show="tab === 'file'" role="tabpanel">

    {{-- Laporan baris gagal (FR-15) --}}
    @if (session('import_errors'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm" x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left font-semibold text-amber-800">
                <span>{{ count(session('import_errors')) }} baris gagal diimpor</span>
                <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
            <ul x-show="open" class="mt-3 max-h-48 list-inside list-disc space-y-1 overflow-y-auto text-amber-900">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-amber-700">Perbaiki baris tersebut di file Anda, lalu unggah ulang hanya baris yang gagal.</p>
        </div>
    @endif

    {{-- Langkah 1 --}}
    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-navy-800 text-sm font-bold text-white">1</span>
            <div class="flex-1">
                <h2 class="font-semibold text-slate-900">Unduh template</h2>
                <p class="mb-4 mt-1 text-sm text-slate-500">
                    Isi template di Excel atau Google Sheets, lalu simpan sebagai <strong>CSV</strong> atau <strong>.xlsx</strong>.
                </p>
                <a href="{{ route('admin.import.template') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Unduh Template CSV
                </a>
            </div>
        </div>
    </section>

    {{-- Langkah 2 --}}
    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-navy-800 text-sm font-bold text-white">2</span>
            <div class="flex-1">
                <h2 class="mb-3 font-semibold text-slate-900">Isi sesuai panduan</h2>
                <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-600 marker:font-semibold marker:text-navy-700">
                    <li>Jangan ubah atau hapus baris pertama (nama kolom).</li>
                    <li>Isi data mulai baris kedua. Satu baris = satu UMKM.</li>
                    <li>No. WhatsApp: angka saja tanpa spasi/strip, contoh <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">081234567890</code>.</li>
                    <li>Omzet per bulan: angka saja tanpa "Rp" atau titik, contoh <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">15000000</code>.</li>
                    <li>Legalitas: pisahkan dengan koma, contoh <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">NIB, NPWP, SIUP</code>.</li>
                    <li>
                        Kolom <strong>kota_kabupaten</strong>: nama kota/kabupaten di Kalimantan Barat, contoh
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">Kota Pontianak</code> atau
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">Kab. Sambas</code>.
                        Jika kosong, wilayah dibaca dari alamat usaha (bila tidak terbaca, tercatat <em>"Tidak Diketahui"</em> dan perlu koreksi manual).
                    </li>
                    <li>
                        Kolom <strong>foto_produk</strong>: tautan Google Drive yang dibagikan sebagai
                        <em>Anyone with the link</em>, contoh
                        <code class="break-all rounded bg-slate-100 px-1.5 py-0.5 text-xs">https://drive.google.com/file/d/1ABC.../view</code>.
                    </li>
                    <li>Simpan dalam format <strong>CSV</strong> atau <strong>Excel (.xlsx)</strong>.</li>
                </ol>
            </div>
        </div>
    </section>

    {{-- Langkah 3 --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"
             x-data="importForm()">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-navy-800 text-sm font-bold text-white">3</span>
            <div class="min-w-0 flex-1">
                <h2 class="mb-4 font-semibold text-slate-900">Unggah file</h2>

                <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data"
                      @submit="submit($event)">
                    @csrf

                    @if ($daftarOpd->isNotEmpty())
                        <div class="mb-4">
                            <label for="opd_id" class="mb-1.5 block text-sm font-medium text-slate-700">
                                OPD tujuan <span class="text-red-500">*</span>
                            </label>
                            <select id="opd_id" name="opd_id" required
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                <option value="">— Pilih OPD pembina —</option>
                                @foreach ($daftarOpd as $o)
                                    <option value="{{ $o->id }}" @selected(old('opd_id', $daftarOpd->count() === 1 ? $o->id : null) == $o->id)>
                                        {{ $o->nama_opd }}@if ($o->kabupaten) — {{ $o->wilayahLabel() }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-slate-500">Seluruh baris pada file akan tercatat sebagai binaan OPD ini.</p>
                            @error('opd_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @elseif (auth()->user()->isSuperAdmin())
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Belum ada data OPD. Tambahkan OPD terlebih dahulu sebelum mengimpor.
                        </div>
                    @endif

                    <label for="file-input"
                           @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drop($event)"
                           :class="drag ? 'border-navy-700 bg-navy-900/5' : (file ? 'border-green-500 bg-green-50/50' : 'border-slate-300 bg-slate-50 hover:border-navy-700')"
                           class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-10 text-center transition">
                        <svg class="mb-3 h-10 w-10 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.63a3.38 3.38 0 0 0-3.38-3.37h-1.5A1.13 1.13 0 0 1 13.5 7.13v-1.5a3.38 3.38 0 0 0-3.38-3.38H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.63c-.62 0-1.13.5-1.13 1.13v17.25c0 .62.5 1.12 1.13 1.12h12.75c.62 0 1.12-.5 1.12-1.12V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <template x-if="!file">
                            <div>
                                <p class="text-sm font-medium text-slate-700">Klik atau seret file ke sini</p>
                                <p class="mt-1 text-xs text-slate-400">CSV, .xlsx, .xls · maksimal 5 MB</p>
                            </div>
                        </template>
                        <template x-if="file">
                            <div>
                                <p class="break-all text-sm font-semibold text-green-700" x-text="file.name"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="ukuran(file.size) + ' · klik untuk mengganti'"></p>
                            </div>
                        </template>
                    </label>

                    <input type="file" id="file-input" name="file" x-ref="input"
                           accept=".csv,.xlsx,.xls" class="sr-only" @change="pilih($event.target.files[0])">

                    <p x-show="clientError" x-text="clientError" x-cloak class="mt-2 text-sm text-red-600"></p>
                    @error('file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" :disabled="loading"
                            class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 py-3 text-sm font-semibold text-white transition hover:bg-green-800 disabled:cursor-wait disabled:opacity-70">
                        <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4z"/>
                        </svg>
                        <span x-text="loading ? 'Memproses data...' : 'Unggah & Import Data'">Unggah &amp; Import Data</span>
                    </button>

                    <p x-show="loading" x-cloak class="mt-3 text-center text-xs text-slate-500">
                        Mohon tunggu, jangan tutup atau muat ulang halaman ini.
                    </p>
                </form>
            </div>
        </div>
    </section>

    {{-- Informasi --}}
    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-500">
        <p class="mb-1 font-semibold text-slate-600">Informasi</p>
        <ul class="list-inside list-disc space-y-1">
            <li>Baris yang gagal tidak memengaruhi baris yang berhasil diimpor.</li>
            <li>Pemilik usaha dengan nomor WhatsApp yang sama tidak digandakan.</li>
            <li>
                Setiap baris dibandingkan dengan UMKM yang sudah terdaftar di kota/kabupaten yang sama (oleh OPD mana pun, termasuk Bank Indonesia):
                nama pemilik, nama usaha, alamat, No. WhatsApp, dan e-mail. Baris dengan skor kemiripan ≥ {{ config('umkm.ambang_duplikat') }}
                <strong>tidak langsung disimpan</strong>, melainkan masuk <a href="{{ route('admin.duplikat.index') }}" class="font-semibold text-navy-700 underline">Antrean Duplikat</a> untuk diputuskan admin.
            </li>
            <li>Skor dan klasifikasi dihitung otomatis setelah import.</li>
            <li>File besar (&gt;500 baris) dapat memakan waktu 2–5 menit.</li>
        </ul>
    </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function importForm() {
    const MAKS = 5 * 1024 * 1024;
    const EKSTENSI = ['csv', 'xlsx', 'xls'];

    return {
        file: null, drag: false, loading: false, clientError: '',

        pilih(f) {
            this.clientError = '';
            if (!f) { this.file = null; return; }
            const ext = f.name.split('.').pop().toLowerCase();
            if (!EKSTENSI.includes(ext)) {
                this.clientError = 'Format file harus CSV atau Excel (.xlsx/.xls).';
                this.reset(); return;
            }
            if (f.size > MAKS) {
                this.clientError = 'Ukuran file maksimal 5 MB.';
                this.reset(); return;
            }
            this.file = f;
        },
        drop(e) {
            this.drag = false;
            const f = e.dataTransfer.files[0];
            if (!f) return;
            const dt = new DataTransfer();
            dt.items.add(f);
            this.$refs.input.files = dt.files;
            this.pilih(f);
        },
        reset() {
            this.file = null;
            this.$refs.input.value = '';
        },
        ukuran(b) {
            return b < 1024 * 1024 ? (b / 1024).toFixed(0) + ' KB' : (b / 1024 / 1024).toFixed(2) + ' MB';
        },
        submit(e) {
            if (!this.file) {
                e.preventDefault();
                this.clientError = 'Pilih file CSV atau Excel terlebih dahulu.';
                return;
            }
            this.loading = true;
        },
    };
}
</script>
@endpush
