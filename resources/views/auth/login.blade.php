<x-guest-layout title="Masuk">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Masuk ke panel admin</h2>
        <p class="mt-1 text-sm text-slate-500">Khusus Super Admin dan Admin OPD. Publik tidak perlu login.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3" :status="session('status')" />

    @if ($errors->any())
        <div role="alert" class="mb-5 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5"
          x-data="{ show: false, loading: false }" @submit="loading = true">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username"
                          placeholder="nama@instansi.go.id" maxlength="255" />
        </div>

        <!-- Kata sandi -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata sandi" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-navy-700 hover:text-navy-900 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-navy-700 rounded"
                       href="{{ route('password.request') }}">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <div class="relative mt-1.5">
                <x-text-input id="password" class="block w-full pr-11"
                              x-bind:type="show ? 'text' : 'password'" type="password"
                              name="password" required autocomplete="current-password" />
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:text-navy-700"
                        :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3-9.96-7.18Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 12c1.29 4.34 5.31 7.5 9.96 7.5.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.76 0 8.77 3.16 10.06 7.5a10.52 10.52 0 0 1-4.29 5.77M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Ingat saya -->
        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded border-slate-300 text-navy-800 shadow-sm focus:ring-navy-700">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" :disabled="loading"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-navy-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-navy-700 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4z"/>
            </svg>
            <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
        </button>
    </form>

    <p class="mt-6 border-t border-slate-100 pt-4 text-center text-xs text-slate-400">
        Akun dibuat oleh Super Admin. Tidak tersedia pendaftaran mandiri.
    </p>
</x-guest-layout>
