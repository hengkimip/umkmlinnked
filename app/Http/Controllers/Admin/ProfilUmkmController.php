<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilUmkm;
use App\Models\Umkm;
use App\Services\ProfilUmkmService;
use App\Services\RekomendasiProgramService;
use App\Services\UmkmScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProfilUmkmController extends Controller
{
    public function __construct(private ProfilUmkmService $profil) {}

    public function index(Request $request, RekomendasiProgramService $rekomendasi)
    {
        // Semua admin dapat MELIHAT seluruh UMKM; hak UBAH hanya pemegang "Otoritas Edit"
        // (OPD pembina & Super Admin — UmkmPolicy::update), dicek lagi di setiap aksi ubah/hapus.
        Gate::authorize('viewAny', Umkm::class);
        $user = $request->user();

        $daftarUmkm = Umkm::query()
            ->orderBy('nama_usaha')
            ->get(['id', 'nama_usaha', 'kabupaten', 'opd_id']);

        $umkm = null;
        if ($request->filled('umkm')) {
            $umkm = Umkm::with(['pemilik', 'opd:id,nama_opd', 'pendaftar:id,name,opd_id', 'pendaftar.opd:id,nama_opd'])
                ->findOrFail($request->integer('umkm'));
        }

        return view('admin.profil-umkm.index', [
            'daftarUmkm' => $daftarUmkm,
            'umkm'       => $umkm,
            // Otoritas Edit pengguna atas UMKM ini (false = mode lihat saja)
            'bolehUbah'  => $umkm && $user->can('update', $umkm),
            // Untuk menandai "binaan Anda" di daftar pilihan
            'opdSaya'    => $user->isSuperAdmin() ? null : $user->opd_id,
            'nilai'      => $umkm ? $this->profil->nilai($umkm) : [],
            // UMKM lain yang memakai data pemilik yang sama (ikut berubah)
            'pemilikBersama' => $umkm ? $umkm->pemilik?->umkm()->whereKeyNot($umkm->id)->count() : 0,
            // Pilihan cepat program: yang pernah ditetapkan untuk UMKM lain + usulan otomatis sistem
            'usulanProgram'  => $umkm ? ProfilUmkm::usulanProgram($umkm->id) : [],
            'programOtomatis' => $umkm ? array_column($rekomendasi->untuk($umkm), 'program') : [],
            // Sama dengan halaman Detail UMKM (peta interaktif)
            'rincianSkor'     => $umkm ? UmkmScoringService::rincian($umkm) : [],
            'rekomendasi'     => $umkm ? $rekomendasi->untuk($umkm) : [],
        ]);
    }

    /**
     * Hapus UMKM (mis. usaha sudah tutup). Soft delete: hilang dari peta, direktori & daftar admin,
     * tetapi baris & riwayatnya tetap tersimpan sehingga bisa dipulihkan dari database.
     */
    public function destroy(Umkm $umkm): RedirectResponse
    {
        Gate::authorize('delete', $umkm);

        // Log aktivitas (siapa & kapan) dicatat otomatis oleh LogsActivity pada event "deleted"
        $umkm->delete();

        return redirect()->route('admin.profil-umkm.index')
            ->with('success', "UMKM \"{$umkm->nama_usaha}\" telah dihapus.");
    }

    /**
     * Ubah atau hapus (nilai kosong) satu kolom profil.
     */
    public function update(Request $request, Umkm $umkm, RekomendasiProgramService $rekomendasi): JsonResponse
    {
        Gate::authorize('update', $umkm);

        $request->validate([
            'kolom' => ['required', 'string', Rule::in(array_keys(ProfilUmkmService::kolom()))],
            'nilai' => ['nullable'],
        ]);

        $kolom = $request->string('kolom')->toString();
        $label = ProfilUmkmService::kolomUntuk($request->user())[$kolom]['label'];
        $this->profil->simpan($umkm, $kolom, $request->input('nilai'));

        $umkm->refresh();

        return response()->json([
            'message'     => $request->filled('nilai')
                ? $label . ' berhasil disimpan.'
                : $label . ' berhasil dihapus.',
            'nilai'       => $this->profil->nilai($umkm),
            'skor_total'  => $umkm->skor_total,
            'klasifikasi' => $umkm->klasifikasi,
            'kabupaten'   => $umkm->kabupaten,
            // Skor & usulan program berubah mengikuti isi profil
            'rincian_skor' => UmkmScoringService::rincian($umkm),
            'rekomendasi'  => $rekomendasi->untuk($umkm),
        ]);
    }
}
