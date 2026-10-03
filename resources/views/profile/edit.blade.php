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
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">OPD</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $user->opd?->nama_opd ?? '—' }}</dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-slate-500">Peran dan OPD diatur oleh Super Admin.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-password-form')
    </div>
</div>
@endsection
