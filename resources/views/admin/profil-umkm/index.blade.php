@extends('layouts.admin')

@section('title', 'Kelola Profil UMKM')
@section('heading', 'Kelola Profil UMKM')

@use('App\Services\ProfilUmkmService')

@php
    // Metadata kolom untuk tampilan (tanpa aturan validasi)
    $kolom = collect(ProfilUmkmService::kolom())
        ->map(fn ($k) => \Illuminate\Support\Arr::only($k, ['label', 'bagian', 'tipe', 'wajib', 'opsi', 'saran', 'bantuan', 'butuhProduk']));

    $warnaKlasifikasi = [
        'unggulan'   => 'bg-amber-100 text-amber-800',
        'berkembang' => 'bg-blue-100 text-blue-700',
        'dasar'      => 'bg-slate-100 text-slate-600',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-4xl">

    {{-- Pilih UMKM --}}
    <form method="GET" action="{{ route('admin.profil-umkm.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <label for="umkm-select" class="mb-1.5 block text-sm font-medium text-slate-700">UMKM</label>
        <div class="flex gap-2">
            <select name="umkm" id="umkm-select" onchange="this.form.submit()"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                <option value="">— Pilih UMKM —</option>
                @foreach ($daftarUmkm as $u)
                    <option value="{{ $u->id }}" @selected($umkm?->id === $u->id)>{{ $u->nama_usaha }} — {{ $u->kabupaten }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="rounded-lg bg-navy-800 px-4 text-sm font-medium text-white">Tampilkan</button></noscript>
        </div>
        @if ($daftarUmkm->isEmpty())
            <p class="mt-1.5 text-xs text-amber-700">Belum ada UMKM pada wilayah Anda. Import data terlebih dahulu.</p>
        @elseif (! $umkm)
            <p class="mt-1.5 text-xs text-slate-500">Pilih UMKM untuk menampilkan, mengubah, atau menghapus data profilnya.</p>
        @endif
    </form>

    @if ($umkm)
        <div x-data="profilUmkm({
                url: @js(route('admin.profil-umkm.update', ['umkm' => $umkm->id])),
                nilai: @js($nilai),
                kolom: @js($kolom),
                skor: {{ (int) $umkm->skor_total }},
                klasifikasi: @js($umkm->klasifikasi),
                kabupaten: @js($umkm->kabupaten),
             })">

            {{-- Ringkasan --}}
            <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 to-navy-950 px-5 py-6 text-white sm:px-7">
                <div class="kalbar-motif" aria-hidden="true"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gold-400">Profil UMKM</p>
                        <h2 class="mt-1 truncate text-xl font-bold sm:text-2xl" x-text="nilai.nama_usaha">{{ $umkm->nama_usaha }}</h2>
                        <p class="mt-1 text-sm text-slate-300">
                            <span x-text="kabupaten">{{ $umkm->kabupaten }}</span> ·
                            Skor <strong class="text-white" x-text="skor">{{ $umkm->skor_total }}</strong>/100 ·
                            <span class="capitalize" x-text="klasifikasi">{{ $umkm->klasifikasi }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        @if ($umkm->status === 'aktif')
                            <a href="{{ route('direktori.show', $umkm) }}" target="_blank" rel="noopener"
                               class="rounded-lg border border-white/25 px-3 py-1.5 font-medium text-white transition hover:bg-white/10">Lihat di direktori</a>
                        @endif
                        <a href="{{ route('admin.produk.upload-foto', ['umkm' => $umkm->id]) }}"
                           class="rounded-lg bg-white px-3 py-1.5 font-medium text-navy-900 transition hover:bg-slate-100">Foto produk</a>
                    </div>
                </div>
            </div>

            <div x-show="pesan" x-cloak role="status" x-transition.opacity
                 :class="pesanGagal ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-800'"
                 class="sticky top-20 z-10 mb-4 rounded-xl border px-4 py-3 text-sm shadow-sm" x-text="pesan"></div>

            @foreach (ProfilUmkmService::BAGIAN as $kodeBagian => $judulBagian)
                <section class="mb-5 rounded-2xl border border-slate-200 bg-white" aria-labelledby="bagian-{{ $kodeBagian }}">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                        <h2 id="bagian-{{ $kodeBagian }}" class="scroll-mt-24 font-semibold text-slate-900">{{ $judulBagian }}</h2>
                        @if ($kodeBagian === 'pemilik' && $pemilikBersama > 0)
                            <p class="mt-1 text-xs text-amber-700">
                                Data pemilik ini juga dipakai {{ $pemilikBersama }} UMKM lain — perubahan di bagian ini ikut berlaku di sana
                                (kecuali "Program yang pernah diikuti").
                            </p>
                        @endif
                    </div>

                    <dl class="divide-y divide-slate-100">
                        @foreach ($kolom->where('bagian', $kodeBagian) as $kunci => $k)
                            <div @class([
                                'grid gap-2 px-5 py-4 sm:gap-6 sm:px-6',
                                'sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]' => $k['tipe'] !== 'program',
                            ])>
                                <dt class="text-sm text-slate-600">
                                    <label for="input-{{ $kunci }}">{{ $k['label'] }}</label>
                                    @if (! empty($k['wajib'])) <span class="text-red-500" title="Wajib">*</span> @endif
                                    @if (! empty($k['bantuan']))
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $k['bantuan'] }}</p>
                                    @endif
                                </dt>
                                <dd class="min-w-0">
                                    {{-- Mode tampil --}}
                                    <div x-show="edit !== '{{ $kunci }}'">
                                        @if ($k['tipe'] === 'program')
                                            <p x-show="kosong('{{ $kunci }}')" class="text-sm italic text-slate-400">— belum ada program yang ditetapkan —</p>
                                            <ol x-show="!kosong('{{ $kunci }}')" class="flex flex-wrap gap-2">
                                                <template x-for="(p, i) in nilai['{{ $kunci }}']" :key="p">
                                                    <li class="inline-flex items-center gap-1.5 rounded-full bg-navy-900 py-1 pl-1 pr-3 text-sm font-semibold text-white">
                                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gold-400 text-[11px] font-bold text-navy-950" x-text="i + 1"></span>
                                                        <span x-text="p"></span>
                                                    </li>
                                                </template>
                                            </ol>
                                        @else
                                            <p class="whitespace-pre-line break-words text-sm"
                                               :class="kosong('{{ $kunci }}') ? 'italic text-slate-400' : 'text-slate-900'"
                                               x-text="tampil('{{ $kunci }}')"></p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                                            <button type="button" @click="mulai('{{ $kunci }}')" :disabled="sibuk"
                                                    class="rounded-md bg-navy-800 px-2.5 py-1 text-white transition hover:bg-navy-900 disabled:opacity-60">Ubah</button>
                                            @if (empty($k['wajib']))
                                                <button type="button" x-show="!kosong('{{ $kunci }}')" @click="hapus('{{ $kunci }}')" :disabled="sibuk"
                                                        class="rounded-md border border-red-200 px-2.5 py-1 text-red-700 transition hover:bg-red-50 disabled:opacity-60">Hapus</button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Mode ubah --}}
                                    <form x-show="edit === '{{ $kunci }}'" x-cloak @submit.prevent="simpan()" @keydown.escape="batal()">
                                        @switch($k['tipe'])
                                            @case('program')
                                                {{-- Program terpilih (urut sesuai pilihan) --}}
                                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Program terpilih (<span x-text="draftProgram.length"></span>/{{ ProfilUmkmService::MAKS_PROGRAM }})
                                                </p>
                                                <div class="mb-3 flex min-h-[2.75rem] flex-wrap gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-2">
                                                    <p x-show="!draftProgram.length" class="self-center px-1 text-sm italic text-slate-400">Klik program di bawah atau tulis program baru.</p>
                                                    <template x-for="(p, i) in draftProgram" :key="p">
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-navy-900 py-1 pl-3 pr-1 text-sm font-semibold text-white">
                                                            <span x-text="p"></span>
                                                            <button type="button" @click="draftProgram.splice(i, 1)" :aria-label="'Hapus ' + p"
                                                                    class="flex h-5 w-5 items-center justify-center rounded-full text-white/80 transition hover:bg-white/20 hover:text-white">
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </span>
                                                    </template>
                                                </div>

                                                {{-- Program baru --}}
                                                <div class="mb-4 flex gap-2">
                                                    <input id="input-{{ $kunci }}" type="text" x-model="programBaru" maxlength="150"
                                                           @keydown.enter.prevent="tambahProgram(programBaru)"
                                                           placeholder="Tulis program lain, lalu Enter"
                                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                                    <button type="button" @click="tambahProgram(programBaru)"
                                                            class="whitespace-nowrap rounded-lg border border-navy-800 px-3 text-sm font-medium text-navy-800 transition hover:bg-navy-900/5">+ Tambah</button>
                                                </div>

                                                {{-- Pilihan cepat: klik untuk memilih / membatalkan --}}
                                                @php
                                                    $kelompokProgram = array_filter([
                                                        'Pernah diusulkan untuk UMKM lain' => array_map(fn ($n, $j) => ['nama' => $n, 'jumlah' => $j], array_keys($usulanProgram), $usulanProgram),
                                                        'Usulan otomatis sistem untuk UMKM ini' => array_map(fn ($n) => ['nama' => $n, 'jumlah' => null], $programOtomatis),
                                                    ]);
                                                @endphp
                                                @forelse ($kelompokProgram as $judul => $daftar)
                                                    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $judul }}</p>
                                                    <div class="mb-3 flex flex-wrap gap-2">
                                                        @foreach ($daftar as $p)
                                                            <button type="button" @click="pilihProgram(@js($p['nama']))"
                                                                    :aria-pressed="dipilih(@js($p['nama']))"
                                                                    :class="dipilih(@js($p['nama']))
                                                                        ? 'border-navy-900 bg-navy-900 text-white'
                                                                        : 'border-slate-300 bg-white text-slate-700 hover:border-navy-700 hover:bg-navy-900/5'"
                                                                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium transition">
                                                                <span x-text="dipilih(@js($p['nama'])) ? '✓' : '+'" aria-hidden="true"></span>
                                                                {{ $p['nama'] }}
                                                                @if ($p['jumlah'])
                                                                    <span class="rounded-full bg-gold-400/30 px-1.5 text-[11px] font-bold" title="Dipakai {{ $p['jumlah'] }} UMKM lain">{{ $p['jumlah'] }}</span>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @empty
                                                    <p class="mb-3 text-xs text-slate-400">Belum ada program yang diusulkan untuk UMKM lain.</p>
                                                @endforelse
                                                @break
                                            @case('textarea')
                                                <textarea id="input-{{ $kunci }}" rows="3" x-model="draft"
                                                          class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700"></textarea>
                                                @break
                                            @case('select')
                                                <select id="input-{{ $kunci }}" x-model="draft"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                                    @if (empty($k['wajib'])) <option value="">— Kosong —</option> @endif
                                                    @foreach ($k['opsi'] as $nilaiOpsi => $labelOpsi)
                                                        <option value="{{ $nilaiOpsi }}">{{ $labelOpsi }}</option>
                                                    @endforeach
                                                </select>
                                                @break
                                            @case('rupiah')
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                                                    <input id="input-{{ $kunci }}" type="number" min="0" step="1" inputmode="numeric" x-model="draft"
                                                           class="w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                                </div>
                                                @break
                                            @default
                                                <input id="input-{{ $kunci }}" x-model="draft"
                                                       type="{{ ['tel' => 'tel', 'email' => 'email', 'url' => 'url', 'number' => 'number'][$k['tipe']] ?? 'text' }}"
                                                       @if ($k['tipe'] === 'number') min="0" step="1" inputmode="numeric" @endif
                                                       @if (! empty($k['saran'])) list="saran-{{ $kunci }}" @endif
                                                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                                @if (! empty($k['saran']))
                                                    <datalist id="saran-{{ $kunci }}">
                                                        @foreach ($k['saran'] as $s) <option value="{{ $s }}"> @endforeach
                                                    </datalist>
                                                @endif
                                        @endswitch

                                        <p x-show="galat" x-text="galat" class="mt-1.5 text-sm text-red-600"></p>

                                        <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                                            <button type="submit" :disabled="sibuk"
                                                    class="rounded-md bg-green-700 px-3 py-1.5 text-white transition hover:bg-green-800 disabled:opacity-60"
                                                    x-text="sibuk ? 'Menyimpan...' : 'Simpan'">Simpan</button>
                                            <button type="button" @click="batal()" :disabled="sibuk"
                                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 transition hover:bg-slate-50">Batal</button>
                                        </div>
                                    </form>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function profilUmkm({ url, nilai, kolom, skor, klasifikasi, kabupaten }) {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    return {
        nilai, kolom, skor, klasifikasi, kabupaten,
        edit: null, draft: '', draftProgram: [], galat: '', sibuk: false, pesan: '', pesanGagal: false, programBaru: '',

        kosong(k) {
            const v = this.nilai[k];
            if (Array.isArray(v)) return v.length === 0;
            return v === null || v === undefined || String(v).trim() === '';
        },

        // ---------- Kolom daftar program (klik untuk memilih) ----------
        dipilih(nama) { return this.draftProgram.some(p => p.toLowerCase() === nama.toLowerCase()); },

        pilihProgram(nama) {
            const i = this.draftProgram.findIndex(p => p.toLowerCase() === nama.toLowerCase());
            if (i !== -1) { this.draftProgram.splice(i, 1); return; }
            if (this.draftProgram.length >= {{ ProfilUmkmService::MAKS_PROGRAM }}) {
                this.galat = 'Maksimal {{ ProfilUmkmService::MAKS_PROGRAM }} program.';
                return;
            }
            this.galat = '';
            this.draftProgram.push(nama);
        },

        tambahProgram(teks) {
            const nama = (teks || '').replace(/\s+/g, ' ').trim();
            if (!nama) return;
            if (!this.dipilih(nama)) this.pilihProgram(nama);
            this.programBaru = '';
        },

        tampil(k) {
            if (this.kosong(k)) return '— belum diisi —';
            const v = this.nilai[k], def = this.kolom[k];
            if (def.tipe === 'select') return (def.opsi || {})[v] ?? v;
            if (def.tipe === 'rupiah') return 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            if (def.tipe === 'number') return Number(v).toLocaleString('id-ID');
            return String(v);
        },

        mulai(k) {
            this.edit = k;
            this.galat = '';
            this.programBaru = '';
            if (this.kolom[k].tipe === 'program') {
                this.draftProgram = [...(this.nilai[k] || [])];
            } else {
                this.draft = this.kosong(k) ? '' : String(this.nilai[k]);
            }
            this.$nextTick(() => document.getElementById('input-' + k)?.focus());
        },

        batal() { this.edit = null; this.galat = ''; },

        async kirim(k, isi) {
            this.sibuk = true; this.galat = '';
            try {
                const r = await fetch(url, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ kolom: k, nilai: isi }),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const pertama = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(pertama || data.message || 'Terjadi kesalahan. Coba lagi.');
                }
                this.nilai = data.nilai;
                this.skor = data.skor_total;
                this.klasifikasi = data.klasifikasi;
                this.kabupaten = data.kabupaten;
                this.pesan = data.message; this.pesanGagal = false;
                return true;
            } catch (err) {
                return err.message;
            } finally {
                this.sibuk = false;
            }
        },

        async simpan() {
            // Program baru yang ditulis tapi belum di-"Tambah" ikut disimpan
            if (this.kolom[this.edit].tipe === 'program') this.tambahProgram(this.programBaru);
            const isi = this.kolom[this.edit].tipe === 'program' ? [...this.draftProgram] : this.draft;
            const hasil = await this.kirim(this.edit, isi);
            if (hasil === true) this.edit = null;
            else this.galat = hasil;
        },

        async hapus(k) {
            if (!confirm(`Hapus isi kolom "${this.kolom[k].label}"?`)) return;
            if (this.edit) this.batal();
            const hasil = await this.kirim(k, null);
            if (hasil !== true) { this.pesan = hasil; this.pesanGagal = true; }
        },
    };
}
</script>
@endpush
