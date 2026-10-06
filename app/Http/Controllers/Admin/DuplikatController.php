<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UmkmDuplikat;
use App\Services\DeteksiDuplikatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Antrean review "kemungkinan duplikat": admin membandingkan data baru dengan UMKM tersimpan,
 * lalu memutuskan "Usaha yang sama" (perbarui data lama) atau "Usaha berbeda" (simpan sebagai baru).
 */
class DuplikatController extends Controller
{
    public function __construct(private DeteksiDuplikatService $deteksi) {}

    public function index(Request $request)
    {
        $user   = $request->user();
        $status = $request->query('status') === 'selesai' ? 'selesai' : 'menunggu';

        $daftar = UmkmDuplikat::terlihatOleh($user)
            ->with([
                'umkm.pemilik:id,nama_lengkap,telepon,email',
                'umkm.opd:id,nama_opd',
                'umkm.profil:id,umkm_id,program_bi',
                'opd:id,nama_opd', 'pengunggah:id,name', 'pemutus:id,name',
            ])
            ->when($status === 'menunggu',
                fn ($q) => $q->menunggu()->oldest(),
                fn ($q) => $q->where('status', '!=', UmkmDuplikat::MENUNGGU)->latest('diputuskan_pada'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.duplikat.index', [
            'daftar'         => $daftar,
            'status'         => $status,
            'jumlahMenunggu' => UmkmDuplikat::terlihatOleh($user)->menunggu()->count(),
            'ambang'         => DeteksiDuplikatService::ambang(),
        ]);
    }

    /** "Usaha yang sama" → UMKM lama diperbarui dengan isian terbaru. */
    public function sama(Request $request, UmkmDuplikat $duplikat): RedirectResponse
    {
        $this->bolehMemutuskan($request, $duplikat);
        // Memperbarui UMKM lama hanya oleh Super Admin / OPD pembinanya (FR-02)
        Gate::authorize('update', $duplikat->umkm);

        $dilewati = $this->deteksi->gabungkan($duplikat, $request->user());

        return back()->with('success', "Data \"{$duplikat->umkm->nama_usaha}\" diperbarui dengan isian terbaru."
            . ($dilewati ? ' Kolom tidak valid dilewati: ' . implode(', ', $dilewati) . '.' : ''));
    }

    /** "Usaha berbeda" → data baru disimpan sebagai UMKM baru. */
    public function berbeda(Request $request, UmkmDuplikat $duplikat): RedirectResponse
    {
        $this->bolehMemutuskan($request, $duplikat);
        $user = $request->user();
        // Menyimpan UMKM baru ke OPD tujuan hanya oleh Super Admin / OPD tersebut
        abort_unless($user->isSuperAdmin() || (int) $duplikat->opd_id === (int) $user->opd_id, 403);

        $umkm = $this->deteksi->simpanBaru($duplikat, $user);

        return back()->with('success', "\"{$umkm->nama_usaha}\" disimpan sebagai UMKM baru.");
    }

    /** "Hapus data usulan baru" → data baru tidak disimpan; UMKM lama tidak berubah. */
    public function hapus(Request $request, UmkmDuplikat $duplikat): RedirectResponse
    {
        $this->bolehMemutuskan($request, $duplikat);

        $this->deteksi->hapusUsulan($duplikat, $request->user());

        return back()->with('success', 'Data usulan baru "' . ($duplikat->data['nama_usaha'] ?? '-') . '" dihapus dan tidak disimpan.');
    }

    private function bolehMemutuskan(Request $request, UmkmDuplikat $duplikat): void
    {
        abort_unless(UmkmDuplikat::terlihatOleh($request->user())->whereKey($duplikat->id)->exists(), 403);
        abort_if($duplikat->status !== UmkmDuplikat::MENUNGGU, 409, 'Entri ini sudah diputuskan.');
    }
}
