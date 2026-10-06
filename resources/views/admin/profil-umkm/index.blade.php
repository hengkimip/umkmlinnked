@extends('layouts.admin')

@section('title', 'Kelola Profil UMKM')
@section('heading', 'Kelola Profil UMKM')

@use('App\Services\ProfilUmkmService')

@php
    // Metadata kolom untuk tampilan (tanpa aturan validasi)
    // Label program mengikuti lembaga pengguna: Super Admin → Bank Indonesia/KPw BI, Admin OPD → nama OPD
    $lembaga = ProfilUmkmService::lembaga(auth()->user());
    $kolom = collect(ProfilUmkmService::kolomUntuk(auth()->user()))
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
    <form method="GET" action="{{ route('admin.profil-umkm.index') }}" class="relative z-20 mb-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"
          x-data="pilihUmkm({
              daftar: @js($daftarUmkm->map(fn ($u) => [
                  'id' => $u->id, 'nama' => $u->nama_usaha,
                  'kab' => \App\Models\Umkm::KABUPATEN_LENGKAP[$u->kabupaten] ?? $u->kabupaten,
                  'milik' => $opdSaya && (int) $u->opd_id === (int) $opdSaya, // binaan Admin OPD ini (Otoritas Edit)
              ])->values()),
              terpilih: @js($umkm?->id),
          })"
          @click.outside="tutup()" @submit.prevent>
        <label for="umkm-cari" class="mb-1.5 block text-sm font-medium text-slate-700">UMKM</label>

        {{-- Kotak cari + daftar bergulir (combobox) --}}
        <div class="relative" x-cloak x-show="siap">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input id="umkm-cari" type="text" x-model="cari" x-ref="cari" autocomplete="off"
                   role="combobox" aria-autocomplete="list" aria-controls="umkm-daftar" :aria-expanded="buka"
                   :aria-activedescendant="buka && hasil[aktif] ? 'umkm-opsi-' + hasil[aktif].id : null"
                   @focus="bukaDaftar(); $el.select()" @click="bukaDaftar()" @input="mengetik = true; aktif = 0; buka = true"
                   @keydown.arrow-down.prevent="gerak(1)" @keydown.arrow-up.prevent="gerak(-1)"
                   @keydown.enter.prevent="pilih(hasil[aktif])" @keydown.escape.prevent="tutup()"
                   placeholder="Cari nama UMKM atau kabupaten/kota…"
                   class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-10 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700">
            <button type="button" @click="buka ? tutup() : (bukaDaftar(), $refs.cari?.focus())" tabindex="-1" aria-label="Tampilkan daftar UMKM"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                <svg class="h-4 w-4 transition" :class="buka && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>

            <ul id="umkm-daftar" role="listbox" x-show="buka" x-transition.opacity x-ref="daftar"
                class="absolute left-0 right-0 top-full mt-1 max-h-80 overflow-y-auto overscroll-contain rounded-lg border border-slate-200 bg-white py-1 shadow-xl">
                <li class="sticky top-0 border-b border-slate-100 bg-white/95 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                    x-text="hasil.length + ' dari ' + daftar.length + ' UMKM'"></li>
                <template x-for="(u, i) in hasil" :key="u.id">
                    <li :id="'umkm-opsi-' + u.id" role="option" :aria-selected="u.id === terpilih"
                        @click="pilih(u)" @mouseenter="aktif = i"
                        :class="i === aktif ? 'bg-navy-900/5' : ''"
                        class="flex scroll-mt-8 cursor-pointer items-center justify-between gap-3 px-3 py-2 text-sm">
                        <span class="min-w-0 truncate" :class="u.id === terpilih ? 'font-semibold text-navy-800' : 'text-slate-800'" x-text="u.nama"></span>
                        <span class="flex flex-shrink-0 items-center gap-1.5 text-xs text-slate-400">
                            <span x-show="u.milik" class="rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-800">Binaan Anda</span>
                            <span x-text="u.kab"></span>
                            <svg x-show="u.id === terpilih" class="h-4 w-4 text-navy-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </span>
                    </li>
                </template>
                <li x-show="!hasil.length" class="px-3 py-6 text-center text-sm text-slate-400">Tidak ada UMKM yang cocok dengan "<span x-text="cari"></span>".</li>
            </ul>
        </div>

        {{-- Tanpa JavaScript: pilihan biasa --}}
        <noscript>
            <div class="flex gap-2">
                <select name="umkm" class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">— Pilih UMKM —</option>
                    @foreach ($daftarUmkm as $u)
                        <option value="{{ $u->id }}" @selected($umkm?->id === $u->id)>{{ $u->nama_usaha }} — {{ $u->kabupaten }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-navy-800 px-4 text-sm font-medium text-white">Tampilkan</button>
            </div>
        </noscript>
        @if ($daftarUmkm->isEmpty())
            <p class="mt-1.5 text-xs text-amber-700">Belum ada UMKM di sistem. Import data terlebih dahulu.</p>
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
                rincianSkor: @js($rincianSkor),
                rekomendasi: @js($rekomendasi),
             })">

            {{-- Ringkasan --}}
            <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 to-navy-950 px-5 py-6 text-white sm:px-7">
                <div class="kalbar-motif" aria-hidden="true"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        {{-- Binaan [OPD] - Otoritas Edit - Didaftar dd/mm/yyyy
                             (Binaan = OPD yang pertama memasukkan UMKM, tidak dapat dialihkan; rincian di tooltip) --}}
                        @php
                            $otoritas = 'Dapat diubah oleh: ' . ($umkm->opd?->nama_opd ? "{$umkm->opd->nama_opd} (OPD pembina) & " : '') . 'Super Admin';
                            $didaftar = 'Didaftarkan pertama'
                                . ($umkm->pendaftar ? " oleh {$umkm->pendaftar->name}" . ($umkm->pendaftar->instansi() ? " ({$umkm->pendaftar->instansi()})" : '') : '')
                                . ' — status binaan tetap, tidak dapat dialihkan oleh OPD lain';
                        @endphp
                        <p class="mb-2 inline-flex flex-wrap items-center gap-x-1.5 gap-y-0.5 rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-white ring-1 ring-white/20">
                            <svg class="h-3.5 w-3.5 text-gold-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>
                            <span>{{ $umkm->teksBinaan() }}</span>
                            <span class="text-white/40" aria-hidden="true">-</span>
                            <span class="cursor-help underline decoration-dotted decoration-white/40 underline-offset-2" title="{{ $otoritas }}">Otoritas Edit</span>
                            <span class="text-white/40" aria-hidden="true">-</span>
                            <span class="cursor-help font-medium text-slate-200" title="{{ $didaftar }}">Didaftar {{ $umkm->created_at?->format('d/m/Y') ?? '—' }}</span>
                            <span class="sr-only">. {{ $otoritas }}. {{ $didaftar }}.</span>
                        </p>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gold-400">Profil UMKM</p>
                        <h2 class="mt-1 truncate text-xl font-bold sm:text-2xl" x-text="nilai.nama_usaha">{{ $umkm->nama_usaha }}</h2>
                        <p class="mt-1 text-sm text-slate-300">
                            <span x-text="kabupaten">{{ $umkm->kabupaten }}</span> ·
                            Skor <strong class="text-white" x-text="skor">{{ $umkm->skor_total }}</strong>/100 ·
                            <span class="capitalize" x-text="klasifikasi">{{ $umkm->klasifikasi }}</span>
                        </p>
                    </div>
                    {{-- gap-8 (±2 baris): jarak lega antara Hapus UMKM & baris Foto produk agar tidak salah tekan --}}
                    <div class="flex flex-col items-start gap-8 text-sm sm:items-end">
                        {{-- Hapus UMKM: di atas tombol Foto produk --}}
                        @can('delete', $umkm)
                            <button type="button" @click="konfirmasiHapus = true"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 font-medium text-white transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                Hapus UMKM
                            </button>
                        @endcan
                        <div class="flex flex-wrap gap-2">
                            @if ($umkm->status === 'aktif')
                                <a href="{{ route('direktori.show', $umkm) }}" target="_blank" rel="noopener"
                                   class="rounded-lg border border-white/25 px-3 py-1.5 font-medium text-white transition hover:bg-white/10">Lihat di direktori</a>
                            @endif
                            @if ($bolehUbah)
                                <a href="{{ route('admin.produk.upload-foto', ['umkm' => $umkm->id]) }}"
                                   class="rounded-lg bg-white px-3 py-1.5 font-medium text-navy-900 transition hover:bg-slate-100">Foto produk</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notifikasi konfirmasi sebelum menghapus UMKM --}}
            @can('delete', $umkm)
                <template x-teleport="body">
                    <div x-show="konfirmasiHapus" x-cloak x-transition.opacity @keydown.escape.window="konfirmasiHapus = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
                         role="alertdialog" aria-modal="true" aria-labelledby="hapus-title" aria-describedby="hapus-pesan">
                        <form method="POST" action="{{ route('admin.profil-umkm.destroy', ['umkm' => $umkm->id]) }}"
                              @click.outside="konfirmasiHapus = false" x-data="{ kirim: false }" @submit="kirim = true"
                              x-effect="konfirmasiHapus && $nextTick(() => $refs.batalHapus?.focus())"
                              class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
                            @csrf
                            @method('DELETE')
                            <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600" aria-hidden="true">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            </span>
                            <h2 id="hapus-title" class="text-lg font-bold text-slate-900">Hapus UMKM</h2>
                            <p id="hapus-pesan" class="mt-2 text-sm text-slate-600">
                                Apakah Anda yakin ingin menghapus data UMKM ini? Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $umkm->nama_usaha }}</p>

                            <div class="mt-6 grid grid-cols-2 gap-2 text-sm font-medium">
                                <button type="button" x-ref="batalHapus" @click="konfirmasiHapus = false"
                                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-slate-700 transition hover:bg-slate-50">Batal</button>
                                <button type="submit" :disabled="kirim"
                                        class="rounded-lg bg-red-600 px-4 py-2.5 text-white transition hover:bg-red-700 disabled:cursor-wait disabled:opacity-60"
                                        x-text="kirim ? 'Menghapus…' : 'Hapus'">Hapus</button>
                            </div>
                        </form>
                    </div>
                </template>
            @endcan

            @unless ($bolehUbah)
                {{-- Mode lihat saja: pengguna tidak memegang Otoritas Edit UMKM ini --}}
                <div class="mb-5 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="note">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    <p>
                        <strong>Mode lihat saja.</strong>
                        UMKM ini {{ $umkm->opd?->nama_opd ? "binaan {$umkm->opd->nama_opd}" : 'belum memiliki OPD pembina' }}.
                        Otoritas Edit hanya dimiliki {{ $umkm->opd?->nama_opd ? "{$umkm->opd->nama_opd} (OPD pembina) dan " : '' }}Super Admin.
                    </p>
                </div>
            @endunless

            {{-- Rincian skor kesiapan (sama dengan Detail UMKM; ikut berubah setiap kolom disimpan) --}}
            <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="skor-title">
                <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="skor-title" class="font-semibold text-slate-900">Rincian skor kesiapan</h2>
                    <p class="text-sm text-slate-500">
                        Total <strong class="text-lg text-slate-900" x-text="skor">{{ $umkm->skor_total }}</strong>/100 ·
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold capitalize"
                              :class="@js($warnaKlasifikasi)[klasifikasi] ?? 'bg-slate-100 text-slate-600'"
                              x-text="klasifikasi">{{ $umkm->klasifikasi }}</span>
                    </p>
                </div>
                <div class="space-y-3">
                    <template x-for="s in rincianSkor" :key="s.label">
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-medium text-slate-700" x-text="s.label"></span>
                                <span class="text-slate-500" x-text="s.nilai + ' / ' + s.maks"></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="img" :aria-label="s.label + ' ' + s.nilai + ' dari ' + s.maks">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="persen(s) >= 70 ? 'bg-green-600' : (persen(s) >= 40 ? 'bg-blue-600' : 'bg-amber-500')"
                                     :style="`width: ${persen(s)}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <div x-show="pesan" x-cloak role="status" x-transition.opacity
                 :class="pesanGagal ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-800'"
                 class="sticky top-20 z-10 mb-4 rounded-xl border px-4 py-3 text-sm shadow-sm" x-text="pesan"></div>

            @foreach (ProfilUmkmService::bagianUntuk(auth()->user()) as $kodeBagian => $judulBagian)
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
                                        @if ($bolehUbah)
                                        <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                                            <button type="button" @click="mulai('{{ $kunci }}')" :disabled="sibuk"
                                                    class="rounded-md bg-navy-800 px-2.5 py-1 text-white transition hover:bg-navy-900 disabled:opacity-60">Ubah</button>
                                            @if (empty($k['wajib']))
                                                <button type="button" x-show="!kosong('{{ $kunci }}')" @click="hapus('{{ $kunci }}')" :disabled="sibuk"
                                                        class="rounded-md border border-red-200 px-2.5 py-1 text-red-700 transition hover:bg-red-50 disabled:opacity-60">Hapus</button>
                                            @endif
                                        </div>
                                        @endif
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
                                                    @if (empty($k['wajib'])) <option value="">— Kosong —</option>
                                                    @else <option value="" disabled>— Pilih —</option> @endif
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

                    @if ($kodeBagian === 'rekomendasi')
                        {{-- Usulan otomatis sistem (sama dengan Detail UMKM; ikut berubah setiap kolom disimpan) --}}
                        <div class="border-t border-slate-100 px-5 pb-1 pt-4 sm:px-6">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usulan otomatis sistem</p>
                            <p class="mt-0.5 text-xs text-slate-500">Disusun dari data profil & skor sebagai bahan pertimbangan tim {{ $lembaga['rekomendasi'] === 'KPw BI' ? 'KPw BI Kalimantan Barat' : $lembaga['rekomendasi'] }}.</p>
                        </div>
                        <ol class="divide-y divide-slate-100">
                            <template x-for="(r, i) in rekomendasi" :key="r.program">
                                <li class="flex gap-3 px-5 py-4 sm:px-6">
                                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-white" x-text="i + 1"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900" x-text="r.program"></p>
                                        <p class="mt-1 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                                            <span class="rounded px-1.5 py-0.5 ring-1"
                                                  :class="{
                                                      'bg-red-100 text-red-700 ring-red-200': r.prioritas === 'tinggi',
                                                      'bg-amber-100 text-amber-800 ring-amber-200': r.prioritas === 'sedang',
                                                      'bg-slate-100 text-slate-600 ring-slate-200': r.prioritas === 'rendah',
                                                  }"
                                                  x-text="'Prioritas ' + r.prioritas"></span>
                                            <span class="rounded bg-navy-900/5 px-1.5 py-0.5 text-navy-800" x-text="r.bidang"></span>
                                        </p>
                                        <p class="mt-1.5 text-xs leading-relaxed text-slate-600" x-text="r.alasan"></p>
                                    </div>
                                </li>
                            </template>
                            <li x-show="!rekomendasi.length" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada rekomendasi.</li>
                        </ol>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Kotak pilih UMKM: cari (nama / kabupaten), gulir, papan ketik (↑ ↓ Enter Esc)
function pilihUmkm({ daftar, terpilih }) {
    const URL_HALAMAN = @js(route('admin.profil-umkm.index'));
    const namaTerpilih = daftar.find(u => u.id === terpilih)?.nama ?? '';
    const normal = (s) => String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    return {
        daftar, terpilih, siap: true,
        cari: namaTerpilih, mengetik: false, buka: false, aktif: 0,

        get hasil() {
            const kata = this.mengetik ? normal(this.cari).split(/\s+/).filter(Boolean) : [];
            if (!kata.length) return this.daftar;
            return this.daftar.filter(u => {
                const teks = normal(u.nama + ' ' + u.kab);
                return kata.every(k => teks.includes(k));
            });
        },

        bukaDaftar() {
            if (this.buka) return;
            this.buka = true;
            this.mengetik = false;
            // Mulai dari UMKM yang sedang dibuka
            this.aktif = Math.max(0, this.hasil.findIndex(u => u.id === this.terpilih));
            this.$nextTick(() => this.gulirKeAktif());
        },

        tutup() {
            this.buka = false;
            this.mengetik = false;
            this.cari = namaTerpilih;
        },

        gerak(arah) {
            if (!this.buka) return this.bukaDaftar();
            if (!this.hasil.length) return;
            this.aktif = (this.aktif + arah + this.hasil.length) % this.hasil.length;
            this.$nextTick(() => this.gulirKeAktif());
        },

        gulirKeAktif() {
            const u = this.hasil[this.aktif];
            if (u) document.getElementById('umkm-opsi-' + u.id)?.scrollIntoView({ block: 'nearest' });
        },

        pilih(u) {
            if (!u) return;
            this.buka = false;
            this.cari = u.nama;
            if (u.id !== this.terpilih) window.location.href = URL_HALAMAN + '?umkm=' + u.id;
        },
    };
}

function profilUmkm({ url, nilai, kolom, skor, klasifikasi, kabupaten, rincianSkor, rekomendasi }) {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    return {
        nilai, kolom, skor, klasifikasi, kabupaten, rincianSkor, rekomendasi,
        konfirmasiHapus: false,
        edit: null, draft: '', draftProgram: [], galat: '', sibuk: false, pesan: '', pesanGagal: false, programBaru: '',

        persen(s) { return s.maks ? Math.round(s.nilai / s.maks * 100) : 0; },

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
                // Nilai lama di luar pilihan (mis. kabupaten "Tidak Diketahui") → pilih ulang
                if (this.kolom[k].tipe === 'select' && !(this.draft in (this.kolom[k].opsi || {}))) this.draft = '';
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
                this.rincianSkor = data.rincian_skor;
                this.rekomendasi = data.rekomendasi;
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
