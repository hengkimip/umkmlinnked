{{-- Tambah UMKM manual: kolom diambil dari ProfilUmkmService::kolom() (sama dengan "Kelola Profil UMKM") --}}
@use('App\Services\ProfilUmkmService')

@php
    // Label program mengikuti lembaga pengguna: Super Admin → Bank Indonesia/KPw BI, Admin OPD → nama OPD
    $kolomManual = collect(ProfilUmkmService::kolomUntuk(auth()->user()));
    $galatManual = $errors->manual;
    $kelasInput  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700';
@endphp

<div x-show="tab === 'manual'" x-cloak role="tabpanel">
    <form method="POST" action="{{ route('admin.import.manual') }}"
          x-data="{ kirim: false, program: @js(old('rekomendasi_program', '')) }" @submit="kirim = true">
        @csrf
        <input type="hidden" name="_form" value="manual">

        @if ($galatManual->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                <p class="font-semibold">Data belum tersimpan. Periksa {{ $galatManual->count() }} isian berikut:</p>
                <ul class="mt-2 list-inside list-disc space-y-0.5">
                    @foreach ($galatManual->all() as $pesan) <li>{{ $pesan }}</li> @endforeach
                </ul>
            </div>
        @endif

        <p class="mb-5 text-sm text-slate-500">
            Isian sama dengan <strong>Kelola Profil UMKM</strong>. Tanda <span class="text-red-500">*</span> wajib diisi.
            Setelah disimpan, UMKM langsung tercatat aktif, skornya dihitung, dan tampil di peta, direktori, serta Kelola Profil UMKM.
        </p>

        {{-- OPD pembina (Super Admin memilih; Admin OPD otomatis OPD-nya) --}}
        @if ($daftarOpd->isNotEmpty())
            <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <label for="m-opd_id" class="mb-1.5 block text-sm font-medium text-slate-700">OPD pembina <span class="text-red-500">*</span></label>
                <select id="m-opd_id" name="opd_id" required class="{{ $kelasInput }}">
                    <option value="">— Pilih OPD pembina —</option>
                    @foreach ($daftarOpd as $o)
                        <option value="{{ $o->id }}" @selected(old('opd_id', $daftarOpd->count() === 1 ? $o->id : null) == $o->id)>
                            {{ $o->nama_opd }}@if ($o->kabupaten) — {{ $o->kabupaten }}@endif
                        </option>
                    @endforeach
                </select>
                @if ($galatManual->has('opd_id')) <p class="mt-1.5 text-sm text-red-600">{{ $galatManual->first('opd_id') }}</p> @endif
            </section>
        @endif

        @foreach (ProfilUmkmService::bagianUntuk(auth()->user()) as $kodeBagian => $judulBagian)
            <section class="mb-5 rounded-2xl border border-slate-200 bg-white" aria-labelledby="m-bagian-{{ $kodeBagian }}">
                <h2 id="m-bagian-{{ $kodeBagian }}" class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900 sm:px-6">{{ $judulBagian }}</h2>

                <div class="divide-y divide-slate-100">
                    @foreach ($kolomManual->where('bagian', $kodeBagian) as $kunci => $k)
                        @php
                            $wajib = ! empty($k['wajib']);
                            $salah = $galatManual->has($kunci) || $galatManual->has("{$kunci}.*");
                            $kelas = $kelasInput . ($salah ? ' border-red-400' : '');
                        @endphp
                        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:gap-6 sm:px-6">
                            <div class="text-sm text-slate-600">
                                <label for="m-{{ $kunci }}">{{ $k['label'] }}</label>
                                @if ($wajib) <span class="text-red-500" title="Wajib">*</span> @endif
                                @if ($k['tipe'] === 'program')
                                    <p class="mt-0.5 text-xs text-slate-400">Satu program per baris. Klik pilihan cepat untuk menambahkan.</p>
                                @elseif (! empty($k['bantuan']))
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $k['bantuan'] }}</p>
                                @endif
                            </div>
                            <div class="min-w-0">
                                @switch($k['tipe'])
                                    @case('program')
                                        <textarea id="m-{{ $kunci }}" name="{{ $kunci }}" rows="4" x-model="program"
                                                  placeholder="mis. Pendampingan Sertifikasi Halal" class="{{ $kelas }}"></textarea>
                                        @if ($usulanProgram)
                                            <p class="mb-1.5 mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Pernah diusulkan untuk UMKM lain</p>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($usulanProgram as $p)
                                                    <button type="button"
                                                            @click="if (!program.split('\n').some((b) => b.trim().toLowerCase() === @js(mb_strtolower($p)))) program = (program.trim() ? program.trim() + '\n' : '') + @js($p)"
                                                            class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:border-navy-700 hover:bg-navy-900/5">
                                                        + {{ $p }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                        @break
                                    @case('textarea')
                                        <textarea id="m-{{ $kunci }}" name="{{ $kunci }}" rows="3" @required($wajib) class="{{ $kelas }}">{{ old($kunci) }}</textarea>
                                        @break
                                    @case('select')
                                        <select id="m-{{ $kunci }}" name="{{ $kunci }}" @required($wajib) class="{{ $kelas }}">
                                            <option value="">{{ $wajib ? '— Pilih —' : '— Kosong —' }}</option>
                                            @foreach ($k['opsi'] as $nilaiOpsi => $labelOpsi)
                                                <option value="{{ $nilaiOpsi }}" @selected(old($kunci) === (string) $nilaiOpsi)>{{ $labelOpsi }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('rupiah')
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                                            <input id="m-{{ $kunci }}" name="{{ $kunci }}" type="number" min="0" step="1" inputmode="numeric"
                                                   value="{{ old($kunci) }}" @required($wajib) class="{{ $kelas }} pl-10">
                                        </div>
                                        @break
                                    @default
                                        <input id="m-{{ $kunci }}" name="{{ $kunci }}" value="{{ old($kunci) }}" @required($wajib)
                                               type="{{ ['tel' => 'tel', 'email' => 'email', 'url' => 'url', 'number' => 'number'][$k['tipe']] ?? 'text' }}"
                                               @if ($k['tipe'] === 'number') min="0" step="1" inputmode="numeric" @endif
                                               @if (! empty($k['saran'])) list="m-saran-{{ $kunci }}" @endif
                                               class="{{ $kelas }}">
                                        @if (! empty($k['saran']))
                                            <datalist id="m-saran-{{ $kunci }}">
                                                @foreach ($k['saran'] as $s) <option value="{{ $s }}"> @endforeach
                                            </datalist>
                                        @endif
                                @endswitch

                                @foreach ([$kunci, "{$kunci}.*"] as $kunciGalat)
                                    @if ($galatManual->has($kunciGalat))
                                        <p class="mt-1.5 text-sm text-red-600">{{ $galatManual->first($kunciGalat) }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <button type="submit" :disabled="kirim"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 py-3 text-sm font-semibold text-white transition hover:bg-green-800 disabled:cursor-wait disabled:opacity-70">
            <span x-text="kirim ? 'Menyimpan...' : 'Simpan UMKM Baru'">Simpan UMKM Baru</span>
        </button>
        <p class="mt-3 text-center text-xs text-slate-500">
            Nomor WhatsApp pemilik yang sudah terdaftar tidak digandakan — UMKM baru dihubungkan ke pemilik tersebut.
            Data yang sangat mirip UMKM yang sudah terdaftar (skor kemiripan ≥ {{ config('umkm.ambang_duplikat') }}) tidak langsung disimpan,
            melainkan masuk Antrean Duplikat untuk diputuskan admin.
        </p>
    </form>
</div>
