@extends('layouts.admin')

@section('title', $umkm->nama_usaha)
@section('heading', 'Detail UMKM')

@php
    $kabupaten = \App\Models\Umkm::KABUPATEN_LENGKAP[$umkm->kabupaten] ?? $umkm->kabupaten;

    $skor = [
        ['label' => 'Legalitas',   'nilai' => $umkm->skor_legalitas,   'maks' => 25],
        ['label' => 'Sertifikasi', 'nilai' => $umkm->skor_sertifikasi, 'maks' => 20],
        ['label' => 'Produksi',    'nilai' => $umkm->skor_produksi,    'maks' => 20],
        ['label' => 'Pemasaran',   'nilai' => $umkm->skor_pemasaran,   'maks' => 20],
        ['label' => 'Keuangan',    'nilai' => $umkm->skor_keuangan,    'maks' => 15],
    ];

    $warnaKlasifikasi = [
        'unggulan'   => 'bg-green-100 text-green-800',
        'berkembang' => 'bg-blue-100 text-blue-800',
        'dasar'      => 'bg-slate-200 text-slate-700',
    ][$umkm->klasifikasi] ?? 'bg-slate-200 text-slate-700';

    $warnaPrioritas = [
        'tinggi' => 'bg-red-100 text-red-700 ring-red-200',
        'sedang' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'rendah' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];

    $rp = fn ($n) => $n ? 'Rp ' . number_format($n, 0, ',', '.') : null;

    $fakta = [
        'Pemilik'            => $nilai['pemilik_nama'],
        'Sektor'             => $umkm->sektor_label,
        'Kabupaten/Kota'     => $kabupaten,
        'Alamat usaha'       => $nilai['alamat_usaha'] !== '-' ? $nilai['alamat_usaha'] : null,
        'Tahun berdiri'      => $nilai['tahun_berdiri'],
        'Jumlah karyawan'    => $nilai['jumlah_karyawan'] ? $nilai['jumlah_karyawan'] . ' orang' : null,
        'Produk unggulan'    => $nilai['produk_unggulan'],
        'Kapasitas / bulan'  => $nilai['kapasitas_produksi'] ? number_format($nilai['kapasitas_produksi'], 0, ',', '.') . ' pcs/kg' : null,
        'Omzet rata-rata / bulan' => $rp($nilai['omzet_bulanan']),
        'Jangkauan pasar'    => \App\Services\ProfilUmkmService::JANGKAUAN[$nilai['jangkauan_pasar']] ?? null,
        'Saluran pemasaran'  => $nilai['saluran_pemasaran'],
        'Legalitas'          => $nilai['bentuk_legalitas'],
        'Sertifikasi produk' => $nilai['sertifikasi_produk'],
        'Pencatatan keuangan'=> $nilai['metode_pencatatan'],
        'Pembiayaan 2026'    => $nilai['pembiayaan_2026'],
        'Program BI yang pernah diikuti' => $nilai['program_bi'],
        'OPD pembina'        => $umkm->opd?->nama_opd,
    ];
@endphp

@section('content')
<div class="mx-auto max-w-5xl">
    <a href="{{ route('superadmin.peta-interaktif') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-navy-700 hover:underline">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Kembali ke peta interaktif
    </a>

    {{-- Ringkasan --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 to-navy-950 px-5 py-7 text-white sm:px-8">
        <div class="kalbar-motif" aria-hidden="true"></div>
        <div class="kalbar-skyline" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-5">
            <div class="min-w-0">
                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $warnaKlasifikasi }}">{{ ucfirst($umkm->klasifikasi) }}</span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">{{ $umkm->nama_usaha }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ $umkm->sektor_label }} · {{ $kabupaten }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gold-400">Skor kesiapan</p>
                <p class="text-4xl font-bold">{{ $umkm->skor_total }}<span class="text-lg text-slate-400">/100</span></p>
            </div>
        </div>
        <div class="relative mt-5 flex flex-wrap gap-2 text-sm">
            <a href="{{ route('admin.profil-umkm.index', ['umkm' => $umkm->id]) }}"
               class="rounded-lg bg-white px-3 py-1.5 font-medium text-navy-900 transition hover:bg-slate-100">Kelola profil</a>
            <a href="{{ route('admin.produk.upload-foto', ['umkm' => $umkm->id]) }}"
               class="rounded-lg border border-white/25 px-3 py-1.5 font-medium text-white transition hover:bg-white/10">Foto produk</a>
            @if ($umkm->status === 'aktif')
                <a href="{{ route('direktori.show', $umkm) }}" target="_blank" rel="noopener"
                   class="rounded-lg border border-white/25 px-3 py-1.5 font-medium text-white transition hover:bg-white/10">Halaman direktori publik</a>
            @endif
            @if ($umkm->wa_link)
                <a href="{{ $umkm->wa_link }}" target="_blank" rel="noopener noreferrer"
                   class="rounded-lg border border-white/25 px-3 py-1.5 font-medium text-white transition hover:bg-white/10">WhatsApp</a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        {{-- Rekomendasi program --}}
        <section class="self-start rounded-2xl border border-slate-200 bg-white lg:order-2" aria-labelledby="rekomendasi-title">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <h2 id="rekomendasi-title" class="font-semibold text-slate-900">Rekomendasi Program KPw BI</h2>
                <a href="{{ route('admin.profil-umkm.index', ['umkm' => $umkm->id]) }}#bagian-rekomendasi"
                   class="flex-shrink-0 rounded-md bg-navy-800 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-navy-900">Ubah</a>
            </div>

            {{-- Program yang ditetapkan KPw BI (diatur di Kelola Profil UMKM) --}}
            <div class="border-b border-slate-100 bg-navy-900/[0.03] px-5 py-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-navy-800">Ditetapkan KPw BI</p>
                @if ($programDitetapkan)
                    <ol class="space-y-2">
                        @foreach ($programDitetapkan as $i => $program)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-gold-400 text-xs font-bold text-navy-950">{{ $i + 1 }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $program }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm italic text-slate-400">Belum ada program yang ditetapkan.
                        <a href="{{ route('admin.profil-umkm.index', ['umkm' => $umkm->id]) }}#bagian-rekomendasi" class="font-medium not-italic text-navy-700 hover:underline">Tetapkan program</a>.
                    </p>
                @endif
            </div>

            <div class="px-5 pb-1 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usulan otomatis sistem</p>
                <p class="mt-0.5 text-xs text-slate-500">Disusun dari data profil & skor sebagai bahan pertimbangan tim KPw BI Kalimantan Barat.</p>
            </div>
            <ol class="divide-y divide-slate-100">
                @forelse ($rekomendasi as $i => $r)
                    <li class="flex gap-3 px-5 py-4">
                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-white">{{ $i + 1 }}</span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $r['program'] }}</p>
                            <p class="mt-1 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                                <span class="rounded px-1.5 py-0.5 ring-1 {{ $warnaPrioritas[$r['prioritas']] }}">Prioritas {{ $r['prioritas'] }}</span>
                                <span class="rounded bg-navy-900/5 px-1.5 py-0.5 text-navy-800">{{ $r['bidang'] }}</span>
                            </p>
                            <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $r['alasan'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-400">Belum ada rekomendasi.</li>
                @endforelse
            </ol>
        </section>

        <div class="space-y-6 lg:order-1">
            {{-- Rincian skor --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="skor-title">
                <h2 id="skor-title" class="mb-4 font-semibold text-slate-900">Rincian skor kesiapan</h2>
                <div class="space-y-3">
                    @foreach ($skor as $s)
                        @php $persen = $s['maks'] ? round($s['nilai'] / $s['maks'] * 100) : 0; @endphp
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-medium text-slate-700">{{ $s['label'] }}</span>
                                <span class="text-slate-500">{{ $s['nilai'] }} / {{ $s['maks'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ $s['label'] }} {{ $s['nilai'] }} dari {{ $s['maks'] }}">
                                <div class="h-full rounded-full {{ $persen >= 70 ? 'bg-green-600' : ($persen >= 40 ? 'bg-blue-600' : 'bg-amber-500') }}" style="width: {{ $persen }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Profil --}}
            <section class="rounded-2xl border border-slate-200 bg-white" aria-labelledby="profil-title">
                <h2 id="profil-title" class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Profil usaha</h2>
                <dl class="divide-y divide-slate-100">
                    @foreach ($fakta as $label => $isi)
                        <div class="grid gap-1 px-5 py-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:gap-4">
                            <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                            <dd class="whitespace-pre-line break-words text-sm {{ filled($isi) ? 'text-slate-900' : 'italic text-slate-400' }}">{{ filled($isi) ? $isi : 'Belum diisi' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    </div>
</div>
@endsection
