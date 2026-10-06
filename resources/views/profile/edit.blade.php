@extends('layouts.admin')

@section('title', 'Profil Saya')
@section('heading', 'Profil Saya')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Peran</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $user->roleLabel() }}</dd>
            </div>
            <div>
                {{-- Super Admin: Bank Indonesia Wilayah Kalimantan Barat · Admin OPD: OPD-nya --}}
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Instansi</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $user->instansi() ?? '—' }}</dd>
            </div>
            @if ($user->jabatan)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Jabatan</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $user->jabatan }}</dd>
                </div>
            @endif
        </dl>
        <p class="mt-4 text-xs text-slate-500">
            @if ($user->isSuperAdmin())
                Akun Super Admin dikelola {{ config('umkm.instansi_super_admin') }}
                (maksimal {{ \App\Models\User::maksSuperAdmin() }} orang) lewat menu
                <a href="{{ route('superadmin.akses.index') }}" class="font-medium text-navy-700 underline">Kelola Akses</a>.
            @else
                Peran, instansi, dan akses akun diatur oleh Super Admin ({{ config('umkm.instansi_super_admin') }}).
                @if ($user->opd)
                    Nama instansi ini tampil sebagai pembina — <strong>"Binaan {{ $user->opd->nama_opd }}"</strong> — pada UMKM yang Anda masukkan.
                @endif
            @endif
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-password-form')
    </div>
</div>
@endsection
