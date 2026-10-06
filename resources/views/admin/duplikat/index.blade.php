@extends('layouts.admin')

@section('title', 'Antrean Duplikat')
@section('heading', 'Antrean Review Duplikat')

@use('App\Models\Umkm')
@use('App\Models\UmkmDuplikat')
@use('App\Services\DeteksiDuplikatService')

@php
    $kab   = fn (?string $k) => $k ? (Umkm::KABUPATEN_LENGKAP[$k] ?? $k) : null;
    $sektor = fn (?string $s) => $s ? (Umkm::SEKTOR_LABEL[$s] ?? ucfirst($s)) : null;
    $user  = auth()->user();
@endphp

@section('content')
<div class="mx-auto max-w-5xl">

    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600 sm:p-6">
        <p>
            Data UMKM baru (import file atau tambah manual) yang <strong>skor kemiripannya ≥ {{ $ambang }}</strong>
            dengan UMKM yang sudah tersimpan di kota/kabupaten yang sama <strong>tidak langsung disimpan</strong>.
            Bandingkan kedua data, lalu putuskan:
        </p>
        <ul class="mt-2 list-inside list-disc space-y-0.5">
            <li><strong>Usaha yang sama</strong> — data lama diperbarui dengan isian terbaru (kontak baru menggantikan kontak lama).
                <strong>Status binaan tetap</strong> pada OPD yang pertama memasukkannya; tidak berpindah ke OPD pengunggah baru.</li>
            <li><strong>Usaha berbeda</strong> — data baru tetap disimpan sebagai UMKM baru.</li>
            <li><strong>Hapus data usulan baru</strong> — data baru dibuang (tidak disimpan); data lama tidak berubah.</li>
        </ul>
        <p class="mt-2 text-xs text-slate-400">
            Skor: nama pemilik 25 · nama usaha 25 (mirip ≥80%) · alamat 20 (mirip ≥60%, kabupaten sama) · No. WhatsApp 20 · e-mail 10.
            Tahun berdiri &amp; program yang pernah diikuti hanya sinyal pendukung.
        </p>
    </div>

    {{-- Menunggu / Selesai --}}
    <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-white p-1 text-sm font-semibold">
        <a href="{{ route('admin.duplikat.index') }}"
           @class(['rounded-lg px-4 py-2 transition', 'bg-navy-800 text-white' => $status === 'menunggu', 'text-slate-600 hover:bg-slate-50' => $status !== 'menunggu'])>
            Menunggu <span class="ml-1 rounded-full bg-gold-500 px-1.5 text-[11px] text-navy-950">{{ $jumlahMenunggu }}</span>
        </a>
        <a href="{{ route('admin.duplikat.index', ['status' => 'selesai']) }}"
           @class(['rounded-lg px-4 py-2 transition', 'bg-navy-800 text-white' => $status === 'selesai', 'text-slate-600 hover:bg-slate-50' => $status !== 'selesai'])>
            Sudah diputuskan
        </a>
    </div>

    @forelse ($daftar as $d)
        @php
            $lama    = $d->umkm;
            $baru    = $d->data;
            $r       = $d->rincian;
            $bisaSama    = $lama && ! $lama->trashed() && $user->can('update', $lama);
            $bisaBerbeda = $user->isSuperAdmin() || (int) $d->opd_id === (int) $user->opd_id;
            // [label, nilai baru, nilai lama, kunci sinyal (null = pendukung)]
            $baris = [
                ['Nama usaha',     $baru['nama_usaha'] ?? null,       $lama?->nama_usaha,             'usaha'],
                ['Nama pemilik',   $baru['pemilik_nama'] ?? null,     $lama?->pemilik?->nama_lengkap, 'pemilik'],
                ['No. WhatsApp',   $baru['pemilik_whatsapp'] ?? null, $lama?->pemilik?->telepon,      'wa'],
                ['E-mail',         $baru['pemilik_email'] ?? null,    $lama?->pemilik?->email,        'email'],
                ['Alamat usaha',   $baru['alamat_usaha'] ?? null,     $lama?->alamat_usaha,           'alamat'],
                ['Kota/Kabupaten', $kab($baru['kabupaten'] ?? null),  $kab($lama?->kabupaten),        null],
                ['Sektor',         $sektor($baru['sektor'] ?? null),  $lama?->sektor_label,           null],
                ['Tahun berdiri',  $baru['tahun_berdiri'] ?? null,    $lama?->tahun_berdiri,          null],
                ['Program yang pernah diikuti', $baru['program_bi'] ?? null, $lama?->profil?->program_bi, null],
            ];
        @endphp

        <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="Kemungkinan duplikat {{ $baru['nama_usaha'] ?? '' }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">{{ $baru['nama_usaha'] ?? '—' }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $d->sumber === 'impor' ? 'Import file' : 'Tambah manual' }}
                        · untuk {{ $d->opd?->nama_opd ?? '—' }}
                        · oleh {{ $d->pengunggah?->name ?? '—' }}
                        · {{ $d->created_at->translatedFormat('d M Y H:i') }}
                    </p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-800 ring-1 ring-amber-200">
                    Skor kemiripan {{ $d->skor }}/100
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-2 font-semibold sm:px-6">Kolom</th>
                            <th class="px-3 py-2 font-semibold">Data baru</th>
                            <th class="px-3 py-2 font-semibold">Data tersimpan <span class="normal-case text-slate-400">({{ $lama?->teksBinaan() ?? 'sudah dihapus' }})</span></th>
                            <th class="px-5 py-2 text-right font-semibold sm:px-6">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($baris as [$label, $nilaiBaru, $nilaiLama, $sinyal])
                            @php $s = $sinyal ? ($r[$sinyal] ?? null) : null; @endphp
                            <tr @class(['bg-amber-50/60' => $s && $s['dapat'] > 0])>
                                <td class="px-5 py-2.5 text-slate-500 sm:px-6">
                                    {{ $label }}
                                    @unless ($sinyal) <span class="text-[11px] text-slate-400">(pendukung)</span> @endunless
                                </td>
                                <td class="break-words px-3 py-2.5 {{ filled($nilaiBaru) ? 'text-slate-900' : 'italic text-slate-400' }}">{{ filled($nilaiBaru) ? $nilaiBaru : '—' }}</td>
                                <td class="break-words px-3 py-2.5 {{ filled($nilaiLama) ? 'text-slate-900' : 'italic text-slate-400' }}">{{ filled($nilaiLama) ? $nilaiLama : '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-right sm:px-6">
                                    @if ($s)
                                        <span @class(['font-bold', 'text-amber-700' => $s['dapat'] > 0, 'text-slate-400' => ! $s['dapat']])>
                                            {{ $s['dapat'] }}/{{ $s['bobot'] }}
                                        </span>
                                        @if (in_array($sinyal, ['usaha', 'pemilik', 'alamat'], true))
                                            <span class="block text-[11px] text-slate-400">mirip {{ $s['nilai'] }}%</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                @if ($d->status === UmkmDuplikat::MENUNGGU)
                    @if ($bisaSama)
                        <form method="POST" action="{{ route('admin.duplikat.sama', $d) }}"
                              onsubmit="return confirm('Data lama akan diperbarui dengan isian data baru. Lanjutkan?')">
                            @csrf
                            <button type="submit" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-900">
                                Usaha yang sama — perbarui data lama
                            </button>
                        </form>
                    @else
                        <p class="mr-auto text-xs text-slate-500">"Usaha yang sama" hanya dapat diputuskan Super Admin atau OPD pembina UMKM tersimpan.</p>
                    @endif
                    @if ($bisaBerbeda)
                        <form method="POST" action="{{ route('admin.duplikat.berbeda', $d) }}"
                              onsubmit="return confirm('Data baru akan disimpan sebagai UMKM terpisah. Lanjutkan?')">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                Usaha berbeda — simpan sebagai UMKM baru
                            </button>
                        </form>
                    @endif
                    {{-- Buang data usulan (tidak disimpan); UMKM lama tidak berubah --}}
                    <form method="POST" action="{{ route('admin.duplikat.hapus', $d) }}"
                          onsubmit="return confirm('Data usulan baru akan dihapus dan tidak disimpan. Data UMKM yang sudah terdaftar tidak berubah. Lanjutkan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                            Hapus data usulan baru
                        </button>
                    </form>
                @else
                    <p class="mr-auto text-sm text-slate-600">
                        <strong>{{ [
                            UmkmDuplikat::DIGABUNG    => 'Usaha yang sama — data lama diperbarui',
                            UmkmDuplikat::DIBUAT_BARU => 'Usaha berbeda — disimpan sebagai UMKM baru',
                            UmkmDuplikat::DIHAPUS     => 'Data usulan baru dihapus (tidak disimpan)',
                        ][$d->status] ?? $d->status }}</strong>
                        oleh {{ $d->pemutus?->name ?? '—' }} · {{ $d->diputuskan_pada?->translatedFormat('d M Y H:i') }}
                    </p>
                    @if ($d->umkm_hasil_id)
                        <a href="{{ route('admin.profil-umkm.index', ['umkm' => $d->umkm_hasil_id]) }}"
                           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Buka profil</a>
                    @endif
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
            {{ $status === 'menunggu' ? 'Tidak ada data yang menunggu review.' : 'Belum ada keputusan.' }}
        </div>
    @endforelse

    {{ $daftar->links() }}
</div>
@endsection
