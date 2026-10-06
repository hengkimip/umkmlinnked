{{-- Tombol aktif/nonaktifkan & hapus akun ($u) --}}
<div class="flex items-center gap-1.5">
    <form method="POST" action="{{ route('superadmin.akses.pengguna.update', $u) }}"
          onsubmit="return confirm(@js($u->is_active ? "Nonaktifkan akun {$u->email}? Akun ini tidak dapat masuk lagi." : "Aktifkan kembali akun {$u->email}?"))">
        @csrf @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $u->is_active ? 0 : 1 }}">
        <button type="submit" @class(['rounded-md px-2.5 py-1 text-xs font-medium transition',
            'border border-red-200 text-red-700 hover:bg-red-50' => $u->is_active,
            'border border-green-300 text-green-800 hover:bg-green-50' => ! $u->is_active])>
            {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
    </form>
    <form method="POST" action="{{ route('superadmin.akses.pengguna.destroy', $u) }}"
          onsubmit="return confirm(@js("Hapus akun {$u->name} ({$u->email}) secara permanen? Akun tidak dapat dipulihkan. Data UMKM yang pernah diunggah tetap ada."))">
        @csrf @method('DELETE')
        <button type="submit" class="rounded-md bg-red-600 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-red-700">
            Hapus
        </button>
    </form>
</div>
