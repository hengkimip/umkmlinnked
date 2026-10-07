@extends('layouts.admin')

@section('title', 'Kelola Akses')
@section('heading', 'Kelola Akses')

@use('App\Models\User')

@php
    $saya  = auth()->user();
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-navy-700 focus:ring-navy-700';
    $status = fn (bool $aktif) => $aktif
        ? '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Aktif</span>'
        : '<span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">Nonaktif</span>';
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <p class="text-sm text-slate-500">
        Super Admin dikelola oleh <strong class="text-slate-700">{{ config('umkm.instansi_super_admin') }}</strong>:
        memberi akses kepada OPD yang memasukkan data ke UMKMLinked, menentukan boleh tidaknya admin suatu OPD digandakan,
        dan mengelola akun Super Admin (maksimal {{ $maksSuper }} orang).
    </p>

    {{-- ==================== SUPER ADMIN ==================== --}}
    <section class="rounded-2xl border border-slate-200 bg-white" aria-labelledby="super-title">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 id="super-title" class="font-semibold text-slate-900">Super Admin — {{ config('umkm.instansi_super_admin') }}</h2>
                <p class="text-xs text-slate-500">Jumlah akun mengikuti permintaan Bank Indonesia (diatur di konfigurasi <code>UMKM_MAKS_SUPER_ADMIN</code>, 1–3 orang).</p>
            </div>
            <span @class(['rounded-full px-3 py-1 text-sm font-bold ring-1',
                          'bg-amber-100 text-amber-800 ring-amber-200' => $superAktif >= $maksSuper,
                          'bg-green-100 text-green-800 ring-green-200' => $superAktif < $maksSuper])>
                {{ $superAktif }} / {{ $maksSuper }} aktif
            </span>
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach ($superAdmin as $u)
                <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm sm:px-6">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">{{ $u->name }} @if ($u->is($saya)) <span class="text-xs font-normal text-slate-400">(Anda)</span> @endif</p>
                        <p class="text-xs text-slate-500">{{ $u->email }}@if ($u->jabatan) · {{ $u->jabatan }}@endif</p>
                    </div>
                    {!! $status($u->is_active) !!}
                    @unless ($u->is($saya))
                        @include('admin.akses._tombol-status', ['u' => $u])
                    @endunless
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ==================== AKSES OPD ==================== --}}
    <section aria-labelledby="opd-title">
        <h2 id="opd-title" class="mb-3 font-semibold text-slate-900">Akses OPD</h2>
        <div class="space-y-4">
            @forelse ($opd as $o)
                @php $adminAktif = $o->users->where('is_active', true)->count(); @endphp
                @php
                    $bagOpd   = $errors->getBag("opd{$o->id}");
                    $bagHapus = $errors->getBag("hapus{$o->id}");
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white" id="opd-{{ $o->id }}"
                     x-data="{ ubahNama: @js($bagOpd->any()), hapus: @js($bagHapus->any()) }">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ $o->nama_opd }} {!! $status($o->is_active) !!}
                                <button type="button" @click="ubahNama = !ubahNama" class="ml-1 text-xs font-medium text-navy-700 underline">Ubah nama</button>
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $o->kode_opd }} · {{ $o->wilayahLabel() }} · {{ number_format($o->umkm_count, 0, ',', '.') }} UMKM binaan
                            </p>

                            {{-- Nama OPD = Instansi admin-nya & label "Binaan …" di UMKM binaannya --}}
                            <form x-show="ubahNama" x-cloak method="POST" action="{{ route('superadmin.akses.opd.update', $o) }}"
                                  class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]">
                                @csrf @method('PATCH')
                                <div>
                                    <label for="nama-{{ $o->id }}" class="mb-1 block text-xs font-medium text-slate-600">Nama OPD (instansi &amp; pembina UMKM)</label>
                                    <input id="nama-{{ $o->id }}" name="nama_opd" value="{{ $bagOpd->any() ? old('nama_opd', $o->nama_opd) : $o->nama_opd }}"
                                           required maxlength="255" class="{{ $input }}">
                                </div>
                                <div>
                                    <label for="kab-{{ $o->id }}" class="mb-1 block text-xs font-medium text-slate-600">Provinsi/Kota/Kabupaten</label>
                                    <select id="kab-{{ $o->id }}" name="kabupaten" class="{{ $input }}">
                                        <optgroup label="Tingkat Provinsi">
                                            @foreach ($wilayahProvinsi as $kode => $nama)
                                                <option value="{{ $kode }}" @selected($o->kabupaten === $kode)>{{ $nama }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Tingkat Kota/Kabupaten">
                                            @foreach ($kabupaten as $kode => $nama)
                                                <option value="{{ $kode }}" @selected($o->kabupaten === $kode)>{{ $nama }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="rounded-lg bg-navy-800 px-3 py-2 text-xs font-medium text-white transition hover:bg-navy-900">Simpan</button>
                                </div>
                                <p class="text-xs text-slate-500 sm:col-span-3">
                                    Nama ini tampil sebagai <strong>Instansi</strong> di profil admin OPD dan sebagai
                                    <strong>"Binaan {{ $o->nama_opd }}"</strong> di atas {{ number_format($o->umkm_count, 0, ',', '.') }} UMKM binaannya.
                                </p>
                                @foreach ($bagOpd->all() as $e) <p class="text-xs text-red-600 sm:col-span-3">{{ $e }}</p> @endforeach
                            </form>
                        </div>
                        <form method="POST" action="{{ route('superadmin.akses.opd.update', $o) }}"
                              onsubmit="return confirm(@js($o->is_active ? 'Nonaktifkan akses OPD ini? Semua adminnya tidak dapat masuk sampai diaktifkan kembali.' : 'Aktifkan kembali akses OPD ini?'))">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $o->is_active ? 0 : 1 }}">
                            <button type="submit" @class(['rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                'border border-red-200 text-red-700 hover:bg-red-50' => $o->is_active,
                                'bg-green-700 text-white hover:bg-green-800' => ! $o->is_active])>
                                {{ $o->is_active ? 'Nonaktifkan akses' : 'Aktifkan akses' }}
                            </button>
                        </form>
                        <button type="button" @click="hapus = !hapus" :aria-expanded="hapus"
                                class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700">
                            Hapus OPD
                        </button>
                    </div>

                    {{-- Hapus OPD: tentukan nasib UMKM binaan, konfirmasi dengan kode OPD --}}
                    <form x-show="hapus" x-cloak method="POST" action="{{ route('superadmin.akses.opd.destroy', $o) }}"
                          class="space-y-3 border-b border-red-100 bg-red-50 px-5 py-4 text-sm sm:px-6">
                        @csrf @method('DELETE')
                        <p class="font-semibold text-red-800">Hapus OPD "{{ $o->nama_opd }}" secara permanen?</p>
                        <ul class="list-inside list-disc text-xs text-red-800">
                            <li>{{ number_format($o->umkm_count, 0, ',', '.') }} UMKM binaan — tentukan pembina barunya di bawah.</li>
                            <li>
                                {{ $o->users->count() }} akun admin OPD ikut <strong>dihapus</strong>@if ($o->users->isNotEmpty()):
                                    {{ $o->users->pluck('email')->implode(', ') }}@endif.
                            </li>
                            <li>Tindakan ini tidak dapat dibatalkan. Bila hanya ingin menghentikan sementara, gunakan "Nonaktifkan akses".</li>
                        </ul>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="pindah-{{ $o->id }}" class="mb-1 block text-xs font-medium text-red-900">UMKM binaan dipindahkan ke</label>
                                <select id="pindah-{{ $o->id }}" name="pindah_ke" required class="{{ $input }}">
                                    <option value="" disabled @selected(! $bagHapus->any())>— Pilih —</option>
                                    @foreach ($opd->where('id', '!=', $o->id) as $lain)
                                        <option value="{{ $lain->id }}" @selected($bagHapus->any() && old('pindah_ke') == $lain->id)>{{ $lain->nama_opd }}</option>
                                    @endforeach
                                    <option value="0" @selected($bagHapus->any() && old('pindah_ke') === '0')>Tanpa OPD pembina (hanya dikelola Super Admin)</option>
                                </select>
                            </div>
                            <div>
                                <label for="konfirmasi-{{ $o->id }}" class="mb-1 block text-xs font-medium text-red-900">
                                    Ketik kode OPD <code class="rounded bg-white px-1">{{ $o->kode_opd }}</code> untuk konfirmasi
                                </label>
                                <input id="konfirmasi-{{ $o->id }}" name="konfirmasi" autocomplete="off" required class="{{ $input }}">
                            </div>
                        </div>
                        @foreach ($bagHapus->all() as $e) <p class="text-xs font-medium text-red-700">{{ $e }}</p> @endforeach
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700">Hapus OPD permanen</button>
                            <button type="button" @click="hapus = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        </div>
                    </form>

                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] sm:px-6">
                        {{-- Batas admin = penggandaan admin --}}
                        <form method="POST" action="{{ route('superadmin.akses.opd.update', $o) }}" class="text-sm">
                            @csrf @method('PATCH')
                            <label for="maks-{{ $o->id }}" class="mb-1 block font-medium text-slate-700">Batas admin OPD</label>
                            <div class="flex gap-2">
                                <input id="maks-{{ $o->id }}" type="number" name="maks_admin" min="1" max="{{ $maksAdminOpd }}" value="{{ $o->maks_admin }}"
                                       class="{{ $input }} w-20">
                                <button type="submit" class="rounded-lg bg-navy-800 px-3 text-xs font-medium text-white transition hover:bg-navy-900">Simpan</button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $adminAktif }} / {{ $o->maks_admin }} admin aktif ·
                                {{ $o->maks_admin > 1 ? 'admin boleh digandakan' : 'admin tidak digandakan' }}
                            </p>
                        </form>

                        {{-- Akun admin OPD --}}
                        <div class="text-sm">
                            <p class="mb-1 font-medium text-slate-700">Akun admin</p>
                            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-100">
                                @forelse ($o->users as $u)
                                    <li class="flex flex-wrap items-center gap-2 px-3 py-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-slate-900">{{ $u->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $u->email }}@if ($u->jabatan) · {{ $u->jabatan }}@endif</p>
                                        </div>
                                        {!! $status($u->is_active) !!}
                                        @include('admin.akses._tombol-status', ['u' => $u])
                                        {{-- Instansi admin = OPD-nya; Super Admin dapat memindahkan ke OPD lain --}}
                                        <form method="POST" action="{{ route('superadmin.akses.pengguna.opd', $u) }}" class="flex w-full items-center gap-1.5 text-xs"
                                              onsubmit="return confirm(@js("Ubah instansi {$u->name}? UMKM yang diunggah selanjutnya tercatat sebagai binaan OPD yang dipilih."))">
                                            @csrf @method('PATCH')
                                            <label for="instansi-{{ $u->id }}" class="text-slate-500">Instansi:</label>
                                            <select id="instansi-{{ $u->id }}" name="opd_id" class="rounded-md border-slate-300 py-1 text-xs shadow-sm focus:border-navy-700 focus:ring-navy-700">
                                                @foreach ($opd as $pilihan)
                                                    <option value="{{ $pilihan->id }}" @selected($pilihan->id === $u->opd_id)>{{ $pilihan->nama_opd }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 font-medium text-slate-700 transition hover:bg-slate-50">Pindahkan</button>
                                        </form>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-xs italic text-slate-400">Belum ada akun admin.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Belum ada OPD.</p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- ==================== TAMBAH OPD ==================== --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="tambah-opd-title">
            <h2 id="tambah-opd-title" class="mb-4 font-semibold text-slate-900">Beri akses OPD baru</h2>
            <form method="POST" action="{{ route('superadmin.akses.opd.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label for="nama_opd" class="mb-1 block font-medium text-slate-700">Nama OPD</label>
                    <input id="nama_opd" name="nama_opd" value="{{ old('nama_opd') }}" class="{{ $input }}" required maxlength="255">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="kode_opd" class="mb-1 block font-medium text-slate-700">Kode OPD</label>
                        <input id="kode_opd" name="kode_opd" value="{{ old('kode_opd') }}" class="{{ $input }}" required maxlength="30" placeholder="mis. DISKOP-PTK">
                    </div>
                    <div>
                        <label for="maks_admin" class="mb-1 block font-medium text-slate-700">Batas admin</label>
                        <input id="maks_admin" type="number" name="maks_admin" min="1" max="{{ $maksAdminOpd }}" value="{{ old('maks_admin', 1) }}" class="{{ $input }}" required>
                    </div>
                </div>
                <div>
                    <label for="opd-kabupaten" class="mb-1 block font-medium text-slate-700">Provinsi/Kota/Kabupaten</label>
                    <select id="opd-kabupaten" name="kabupaten" class="{{ $input }}" required>
                        <option value="">— Pilih —</option>
                        <optgroup label="Tingkat Provinsi (OPD provinsi)">
                            @foreach ($wilayahProvinsi as $kode => $nama)
                                <option value="{{ $kode }}" @selected(old('kabupaten') === $kode)>{{ $nama }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Tingkat Kota/Kabupaten">
                            @foreach ($kabupaten as $kode => $nama)
                                <option value="{{ $kode }}" @selected(old('kabupaten') === $kode)>{{ $nama }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                @if ($errors->opd->any())
                    <ul class="list-inside list-disc text-sm text-red-600">
                        @foreach ($errors->opd->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                @endif
                <button type="submit" class="rounded-lg bg-green-700 px-4 py-2 font-medium text-white transition hover:bg-green-800">Tambah OPD</button>
            </form>
        </section>

        {{-- ==================== TAMBAH AKUN ==================== --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="akun-title"
                 x-data="{ peran: @js(old('peran', User::ROLE_ADMIN_OPD)) }">
            <h2 id="akun-title" class="font-semibold text-slate-900">Tambah akun</h2>
            <p class="mb-4 mt-1 text-xs text-slate-500">
                Setiap akun baru diberitahukan lewat e-mail ke semua Super Admin aktif
                ({{ $superAdmin->where('is_active', true)->pluck('email')->implode(', ') }}).
            </p>
            <form method="POST" action="{{ route('superadmin.akses.pengguna.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label for="peran" class="mb-1 block font-medium text-slate-700">Peran</label>
                    <select id="peran" name="peran" x-model="peran" class="{{ $input }}">
                        <option value="{{ User::ROLE_ADMIN_OPD }}">Admin OPD</option>
                        <option value="{{ User::ROLE_SUPER_ADMIN }}" @disabled($superAktif >= $maksSuper)>
                            Super Admin{{ $superAktif >= $maksSuper ? ' — kuota penuh' : '' }}
                        </option>
                    </select>
                </div>
                <div x-show="peran === @js(User::ROLE_ADMIN_OPD)">
                    <label for="opd_id" class="mb-1 block font-medium text-slate-700">OPD</label>
                    <select id="opd_id" name="opd_id" class="{{ $input }}">
                        <option value="">— Pilih OPD —</option>
                        @foreach ($opd as $o)
                            <option value="{{ $o->id }}" @selected(old('opd_id') == $o->id)>
                                {{ $o->nama_opd }} ({{ $o->users->where('is_active', true)->count() }}/{{ $o->maks_admin }} admin)
                            </option>
                        @endforeach
                    </select>
                </div>
                @foreach (['name' => ['Nama', 'text'], 'email' => ['E-mail', 'email'], 'jabatan' => ['Jabatan (opsional)', 'text']] as $k => [$label, $tipe])
                    <div>
                        <label for="akun-{{ $k }}" class="mb-1 block font-medium text-slate-700">{{ $label }}</label>
                        <input id="akun-{{ $k }}" type="{{ $tipe }}" name="{{ $k }}" value="{{ old($k) }}" class="{{ $input }}" @required($k !== 'jabatan')>
                    </div>
                @endforeach
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="akun-password" class="mb-1 block font-medium text-slate-700">Kata sandi awal</label>
                        <input id="akun-password" type="password" name="password" autocomplete="new-password" class="{{ $input }}" required>
                    </div>
                    <div>
                        <label for="akun-password2" class="mb-1 block font-medium text-slate-700">Ulangi kata sandi</label>
                        <input id="akun-password2" type="password" name="password_confirmation" autocomplete="new-password" class="{{ $input }}" required>
                    </div>
                </div>
                @if ($errors->pengguna->any())
                    <ul class="list-inside list-disc text-sm text-red-600">
                        @foreach ($errors->pengguna->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                @endif
                <button type="submit" class="rounded-lg bg-green-700 px-4 py-2 font-medium text-white transition hover:bg-green-800">Buat akun</button>
            </form>
        </section>
    </div>
</div>
@endsection
