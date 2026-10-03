<section>
    <header>
        <h2 class="font-semibold text-slate-900">Ganti kata sandi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Gunakan kata sandi panjang yang tidak dipakai di layanan lain.
            @production
                Minimal 10 karakter, memuat huruf besar, huruf kecil, dan angka.
            @else
                Minimal 8 karakter.
            @endproduction
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Kata sandi saat ini" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1.5 block w-full" required autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Kata sandi baru" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-navy-700 focus-visible:ring-offset-2">
                Ganti kata sandi
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm font-medium text-green-700">Kata sandi diperbarui.</p>
            @endif
        </div>
    </form>
</section>
