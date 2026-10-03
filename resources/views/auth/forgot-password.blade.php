<x-guest-layout title="Lupa Kata Sandi">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Lupa kata sandi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Masukkan email akun Anda. Jika terdaftar, kami kirimkan tautan untuk membuat kata sandi baru.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus maxlength="255" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-navy-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-navy-700 focus-visible:ring-offset-2">
            Kirim tautan reset
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="font-medium text-navy-700 hover:underline">Kembali ke halaman masuk</a>
    </p>
</x-guest-layout>
