@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@php
    /** @var \App\Models\User $user */
    $user  = auth()->user();
    $basis = fn () => \App\Models\Umkm::milikPengguna($user);

    $stats = [
        ['label' => 'Total UMKM',     'value' => $basis()->count(),                                  'color' => 'text-navy-800'],
        ['label' => 'UMKM Aktif',     'value' => $basis()->where('status', 'aktif')->count(),        'color' => 'text-green-700'],
        ['label' => 'UMKM Unggulan',  'value' => $basis()->where('klasifikasi', 'unggulan')->count(), 'color' => 'text-amber-600'],
        ['label' => 'Total Produk',   'value' => \App\Models\Produk::whereIn('umkm_id', $basis()->select('id'))->count(), 'color' => 'text-violet-700'],
    ];

    $terbaru = $basis()->latest()->limit(10)->get(['id', 'nama_usaha', 'sektor', 'kabupaten', 'klasifikasi', 'status']);

    $badgeKlasifikasi = [
        'unggulan'   => 'bg-amber-100 text-amber-800',
        'berkembang' => 'bg-blue-100 text-blue-700',
    ];
    $badgeStatus = [
        'aktif' => 'bg-green-100 text-green-700',
        'draft' => 'bg-slate-100 text-slate-600',
    ];
@endphp

@section('content')
    {{-- Banner sambutan: motif & siluet Kalbar seperti panel biru halaman lain --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 to-navy-950 px-5 py-7 text-white sm:px-8 sm:py-9">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gold-500/15 blur-2xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true"></div>
        <div class="kalbar-motif" aria-hidden="true"></div>
        <div class="kalbar-skyline" aria-hidden="true"></div>

        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-gold-400/50 bg-gold-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-gold-400">
                <span class="h-1.5 w-1.5 rotate-45 bg-gold-400"></span>
                {{ $user->roleLabel() }}
            </span>
            <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ $user->name }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-300">
                @if ($user->isSuperAdmin())
                    Anda melihat data seluruh OPD.
                @elseif ($user->opd)
                    Data yang ditampilkan khusus UMKM binaan <span class="font-semibold text-white">{{ $user->opd->nama_opd }}</span>.
                @else
                    <span class="font-medium text-red-300">Akun Anda belum terhubung ke OPD — hubungi Super Admin.</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as $s)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-medium text-slate-500">{{ $s['label'] }}</p>
                <p class="mt-1 text-3xl font-bold {{ $s['color'] }}">{{ number_format($s['value'], 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Aksi cepat --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="mb-4 font-semibold text-slate-900">Aksi Cepat</h2>
        <div class="flex flex-wrap gap-3">
            @if ($user->isSuperAdmin())
                <a href="{{ route('superadmin.peta-interaktif') }}" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-900">
                    Buka Peta Interaktif
                </a>
            @endif
            <a href="{{ route('admin.profil-umkm.index') }}" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-900">
                Kelola Profil UMKM
            </a>
            <a href="{{ route('admin.import.index') }}" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-800">
                Import Data Excel/CSV
            </a>
            <a href="{{ route('admin.produk.upload-foto') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Upload Foto Produk
            </a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Lihat Website Publik
            </a>
        </div>
    </div>

    {{-- UMKM terbaru --}}
    <div class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-slate-900">UMKM Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold sm:px-6">Nama UMKM</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Sektor</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Kabupaten</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Klasifikasi</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($terbaru as $umkm)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-900 sm:px-6">{{ $umkm->nama_usaha }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ ucfirst($umkm->sektor) }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                @if ($umkm->kabupaten === 'Tidak Diketahui')
                                    <span class="text-amber-700" title="Perlu koreksi manual">{{ $umkm->kabupaten }}</span>
                                @else
                                    {{ $umkm->kabupaten }}
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeKlasifikasi[$umkm->klasifikasi] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($umkm->klasifikasi ?? 'dasar') }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeStatus[$umkm->status] ?? 'bg-red-100 text-red-600' }}">
                                    {{ ucfirst($umkm->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                Belum ada data.
                                <a href="{{ route('admin.import.index') }}" class="font-medium text-navy-700 hover:underline">Import data</a>
                                terlebih dahulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
