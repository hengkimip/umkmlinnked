<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Models\Umkm;
use App\Models\UmkmDuplikat;
use App\Models\User;
use App\Support\CacheData;
use App\Notifications\AkunAdminDibuat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Kelola Akses (khusus Super Admin — Bank Indonesia Wilayah Kalimantan Barat):
 * - memberi / mencabut akses OPD yang memasukkan data ke UMKMLinked,
 * - menentukan boleh tidaknya admin suatu OPD digandakan (batas jumlah admin per OPD),
 * - mengelola akun Super Admin dengan kuota 1–3 orang sesuai permintaan BI.
 */
class AksesController extends Controller
{
    public const MAKS_ADMIN_OPD = 10;

    public function index()
    {
        $opd = Opd::query()
            ->withCount('umkm')
            ->with(['users' => fn ($q) => $q->role(User::ROLE_ADMIN_OPD)->orderByDesc('is_active')->orderBy('name')])
            ->orderBy('nama_opd')
            ->get();

        return view('admin.akses.index', [
            'superAdmin'      => User::role(User::ROLE_SUPER_ADMIN)->orderByDesc('is_active')->orderBy('name')->get(),
            'superAktif'      => User::jumlahSuperAdminAktif(),
            'maksSuper'       => User::maksSuperAdmin(),
            'opd'             => $opd,
            'wilayahProvinsi' => Opd::WILAYAH_PROVINSI,
            'kabupaten'       => Umkm::KABUPATEN_LENGKAP,
            'maksAdminOpd'    => self::MAKS_ADMIN_OPD,
        ]);
    }

    /** Beri akses OPD baru. */
    public function tambahOpd(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('opd', [
            'nama_opd'   => ['required', 'string', 'max:255'],
            'kode_opd'   => ['required', 'alpha_dash', 'max:30', 'unique:opd,kode_opd'],
            'kabupaten'  => ['required', Rule::in(array_keys(Opd::WILAYAH))],
            'maks_admin' => ['required', 'integer', 'min:1', 'max:' . self::MAKS_ADMIN_OPD],
        ], [], ['nama_opd' => 'nama OPD', 'kode_opd' => 'kode OPD', 'maks_admin' => 'batas admin']);

        $opd = Opd::create($data + ['is_active' => true]);

        return back()->with('success', "Akses OPD \"{$opd->nama_opd}\" ditambahkan. Tambahkan akun admin untuk OPD ini.");
    }

    /** Aktif/nonaktifkan akses OPD, atau ubah batas jumlah admin (penggandaan admin). */
    public function ubahOpd(Request $request, Opd $opd): RedirectResponse
    {
        $data = $request->validateWithBag("opd{$opd->id}", [
            'is_active'  => ['sometimes', 'boolean'],
            'maks_admin' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAKS_ADMIN_OPD],
            // Nama OPD = "Instansi" admin-nya & label "Binaan …" pada UMKM binaannya
            'nama_opd'   => ['sometimes', 'required', 'string', 'max:255', Rule::unique('opd', 'nama_opd')->ignore($opd->id)],
            'kabupaten'  => ['sometimes', 'required', Rule::in(array_keys(Opd::WILAYAH))],
        ], [], ['nama_opd' => 'nama OPD']);
        $namaLama = $opd->nama_opd;

        if (isset($data['maks_admin']) && $data['maks_admin'] < $opd->jumlahAdminAktif()) {
            return back()->with('error', "{$opd->nama_opd} masih memiliki {$opd->jumlahAdminAktif()} admin aktif. "
                . 'Nonaktifkan sebagian admin terlebih dahulu sebelum menurunkan batas.');
        }

        $opd->update($data);

        $pesan = match (true) {
            isset($data['nama_opd']) => $namaLama === $opd->nama_opd
                ? "Data {$opd->nama_opd} disimpan."
                : "Nama OPD \"{$namaLama}\" diubah menjadi \"{$opd->nama_opd}\". Instansi admin-nya dan label pembina "
                  . "(Binaan {$opd->nama_opd}) pada {$opd->umkm()->count()} UMKM ikut berubah.",
            isset($data['is_active']) => $data['is_active']
                ? "Akses {$opd->nama_opd} diaktifkan kembali."
                : "Akses {$opd->nama_opd} dinonaktifkan. Admin OPD ini tidak dapat masuk sampai diaktifkan kembali.",
            default => "Batas admin {$opd->nama_opd} menjadi {$opd->maks_admin} orang"
                . ($opd->maks_admin > 1 ? ' (admin boleh digandakan).' : ' (admin tidak digandakan).'),
        };

        return back()->with('success', $pesan);
    }

    /** Buat akun Super Admin (kuota BI) atau Admin OPD (batas per OPD). */
    public function tambahPengguna(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('pengguna', [
            'peran'    => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_OPD])],
            'opd_id'   => ['exclude_unless:peran,' . User::ROLE_ADMIN_OPD, 'required', 'integer', 'exists:opd,id'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'jabatan'  => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['opd_id' => 'OPD', 'name' => 'nama', 'password' => 'kata sandi awal']);

        if ($pesan = $this->kuotaPenuh($data['peran'], isset($data['opd_id']) ? Opd::find($data['opd_id']) : null)) {
            return back()->withInput($request->except('password', 'password_confirmation'))->with('error', $pesan);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'jabatan'   => $data['jabatan'] ?? null,
            'password'  => $data['password'],
            'opd_id'    => $data['peran'] === User::ROLE_ADMIN_OPD ? $data['opd_id'] : null,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($data['peran']);

        return back()->with('success', "Akun {$user->roleLabel()} {$user->email} dibuat. Sampaikan kata sandi awal secara langsung dan minta segera diganti."
            . $this->beritahuSuperAdmin($user, $request->user()));
    }

    /**
     * E-mail pemberitahuan akun baru ke setiap Super Admin aktif (selain akun baru itu sendiri).
     * Gagal kirim tidak membatalkan pembuatan akun; hasilnya disebut di pesan.
     */
    private function beritahuSuperAdmin(User $akun, User $pembuat): string
    {
        $penerima = User::role(User::ROLE_SUPER_ADMIN)->where('is_active', true)->whereKeyNot($akun->id)->get();
        if ($penerima->isEmpty()) {
            return '';
        }

        try {
            Notification::send($penerima, new AkunAdminDibuat($akun, $pembuat));

            return " Notifikasi e-mail dikirim ke {$penerima->count()} Super Admin.";
        } catch (\Throwable $e) {
            Log::error('Notifikasi akun baru gagal dikirim: ' . $e->getMessage(), ['akun' => $akun->id]);

            return ' Namun notifikasi e-mail ke Super Admin gagal dikirim — periksa pengaturan e-mail (MAIL_*).';
        }
    }

    /** Aktif/nonaktifkan akun (mencabut / memberi kembali akses). */
    public function ubahPengguna(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdminAny(), 404);
        $aktif = $request->validate(['is_active' => ['required', 'boolean']])['is_active'];

        if (! $aktif) {
            if ($user->is($request->user())) {
                return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            }
            if ($user->isSuperAdmin() && $user->is_active && User::jumlahSuperAdminAktif() <= 1) {
                return back()->with('error', 'Minimal harus ada satu Super Admin aktif.');
            }
        } elseif (! $user->is_active && ($pesan = $this->kuotaPenuh($user->isSuperAdmin() ? User::ROLE_SUPER_ADMIN : User::ROLE_ADMIN_OPD, $user->opd))) {
            return back()->with('error', $pesan);
        }

        $user->update(['is_active' => (bool) $aktif]);

        return back()->with('success', "Akun {$user->email} " . ($aktif ? 'diaktifkan.' : 'dinonaktifkan dan tidak dapat masuk lagi.'));
    }

    /**
     * Hapus OPD. UMKM binaannya (dan entri antrean duplikatnya) dipindahkan ke OPD lain atau dibiarkan
     * tanpa pembina; akun Admin OPD-nya ikut dihapus. Konfirmasi dengan mengetik kode OPD.
     */
    public function hapusOpd(Request $request, Opd $opd): RedirectResponse
    {
        // Pilihan "Tanpa OPD pembina" dikirim sebagai 0
        $request->merge(['pindah_ke' => $request->input('pindah_ke') ?: null]);

        $data = $request->validateWithBag("hapus{$opd->id}", [
            'konfirmasi' => ['required', Rule::in([$opd->kode_opd])],
            'pindah_ke'  => ['nullable', 'integer', Rule::exists('opd', 'id')->whereNot('id', $opd->id)],
        ], ['konfirmasi.in' => "Ketik kode OPD \"{$opd->kode_opd}\" persis untuk mengonfirmasi."], ['konfirmasi' => 'konfirmasi']);

        $tujuan     = isset($data['pindah_ke']) ? Opd::find($data['pindah_ke']) : null;
        $jumlahUmkm = $opd->umkm()->withTrashed()->count();
        $admin      = $opd->users()->get();
        $nama       = $opd->nama_opd;

        DB::transaction(function () use ($opd, $tujuan, $admin, $request, $jumlahUmkm, $nama) {
            // UMKM binaan (termasuk yang sudah dihapus/diarsipkan) → OPD tujuan atau tanpa pembina
            Umkm::withTrashed()->where('opd_id', $opd->id)->update(['opd_id' => $tujuan?->id]);
            // Antrean duplikat: ikut OPD tujuan; tanpa tujuan → entri dihapus (OPD tujuannya tidak ada lagi)
            $tujuan
                ? UmkmDuplikat::where('opd_id', $opd->id)->update(['opd_id' => $tujuan->id])
                : UmkmDuplikat::where('opd_id', $opd->id)->delete();

            foreach ($admin as $u) {
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $u->id)->delete();
                }
                $u->delete();
            }

            activity()->causedBy($request->user())
                ->withProperties([
                    'kode_opd' => $opd->kode_opd, 'umkm' => $jumlahUmkm, 'umkm_dipindah_ke' => $tujuan?->nama_opd,
                    'akun_dihapus' => $admin->pluck('email')->all(),
                ])
                ->log("OPD {$nama} dihapus");

            $opd->delete();
        });
        CacheData::segarkan(); // update massal tidak memicu event model

        return redirect()->route('superadmin.akses.index')->with('success', "OPD \"{$nama}\" dihapus."
            . ($jumlahUmkm ? ' ' . number_format($jumlahUmkm, 0, ',', '.') . ' UMKM binaannya '
                . ($tujuan ? "dipindahkan ke {$tujuan->nama_opd}." : 'kini tanpa OPD pembina.') : '')
            . ($admin->isNotEmpty() ? " {$admin->count()} akun admin OPD ikut dihapus." : ''));
    }

    /**
     * Atur instansi Admin OPD: pindahkan akun ke OPD lain (batas admin OPD tujuan tetap berlaku).
     * UMKM yang sudah diunggah tetap binaan OPD lamanya.
     */
    public function pindahOpd(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        $tujuan = Opd::findOrFail($request->validate(['opd_id' => ['required', 'integer', 'exists:opd,id']])['opd_id']);

        if ((int) $user->opd_id === $tujuan->id) {
            return back();
        }
        if ($user->is_active && ($pesan = $this->kuotaPenuh(User::ROLE_ADMIN_OPD, $tujuan))) {
            return back()->with('error', $pesan);
        }

        $lama = $user->instansi() ?? '—';
        $user->update(['opd_id' => $tujuan->id]);

        return back()->with('success', "Instansi {$user->name} diubah dari \"{$lama}\" menjadi \"{$tujuan->nama_opd}\". "
            . 'UMKM yang diunggah selanjutnya tercatat sebagai binaan OPD ini.');
    }

    /**
     * Hapus akun Super Admin / Admin OPD secara permanen. Data UMKM yang pernah diunggah tetap ada;
     * jejak di antrean duplikat dikosongkan (nullOnDelete) dan penghapusan dicatat di log aktivitas.
     */
    public function hapusPengguna(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdminAny(), 404);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }
        if ($user->isSuperAdmin() && User::jumlahSuperAdminAktif() - ($user->is_active ? 1 : 0) < 1) {
            return back()->with('error', 'Minimal harus ada satu Super Admin aktif.');
        }

        $keterangan = "{$user->roleLabel()} {$user->name} ({$user->email})" . ($user->instansi() ? " — {$user->instansi()}" : '');

        DB::transaction(function () use ($user, $request, $keterangan) {
            activity()->causedBy($request->user())
                ->withProperties(['email' => $user->email, 'peran' => $user->roleLabel(), 'instansi' => $user->instansi()])
                ->log("Akun {$keterangan} dihapus");

            // Sesi yang masih login ikut diakhiri
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            $user->delete();
        });

        return back()->with('success', "Akun {$keterangan} dihapus permanen.");
    }

    /** Pesan galat bila kuota Super Admin / batas admin OPD sudah penuh (null = masih boleh). */
    private function kuotaPenuh(string $peran, ?Opd $opd): ?string
    {
        if ($peran === User::ROLE_SUPER_ADMIN) {
            $maks = User::maksSuperAdmin();

            return User::jumlahSuperAdminAktif() >= $maks
                ? "Kuota Super Admin penuh ({$maks} orang sesuai ketentuan Bank Indonesia Wilayah Kalimantan Barat). Nonaktifkan salah satu akun terlebih dahulu."
                : null;
        }

        if ($opd && $opd->jumlahAdminAktif() >= $opd->maks_admin) {
            return "{$opd->nama_opd} sudah memiliki {$opd->maks_admin} admin aktif (batas maksimum). "
                . 'Naikkan batas admin OPD ini bila admin memang boleh digandakan.';
        }

        return null;
    }
}
