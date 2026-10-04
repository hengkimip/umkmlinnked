<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilUmkm;
use App\Models\Umkm;
use App\Services\ProfilUmkmService;
use App\Services\RekomendasiProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProfilUmkmController extends Controller
{
    public function __construct(private ProfilUmkmService $profil) {}

    public function index(Request $request, RekomendasiProgramService $rekomendasi)
    {
        $daftarUmkm = Umkm::milikPengguna($request->user())
            ->orderBy('nama_usaha')
            ->get(['id', 'nama_usaha', 'kabupaten']);

        $umkm = null;
        if ($request->filled('umkm')) {
            $umkm = Umkm::with('pemilik')->findOrFail($request->integer('umkm'));
            // Admin OPD hanya UMKM binaannya (FR-02)
            Gate::authorize('update', $umkm);
        }

        return view('admin.profil-umkm.index', [
            'daftarUmkm' => $daftarUmkm,
            'umkm'       => $umkm,
            'nilai'      => $umkm ? $this->profil->nilai($umkm) : [],
            // UMKM lain yang memakai data pemilik yang sama (ikut berubah)
            'pemilikBersama' => $umkm ? $umkm->pemilik?->umkm()->whereKeyNot($umkm->id)->count() : 0,
            // Pilihan cepat program: yang pernah ditetapkan untuk UMKM lain + usulan otomatis sistem
            'usulanProgram'  => $umkm ? ProfilUmkm::usulanProgram($umkm->id) : [],
            'programOtomatis' => $umkm ? array_column($rekomendasi->untuk($umkm), 'program') : [],
        ]);
    }

    /**
     * Ubah atau hapus (nilai kosong) satu kolom profil.
     */
    public function update(Request $request, Umkm $umkm): JsonResponse
    {
        Gate::authorize('update', $umkm);

        $request->validate([
            'kolom' => ['required', 'string', Rule::in(array_keys(ProfilUmkmService::kolom()))],
            'nilai' => ['nullable'],
        ]);

        $kolom = $request->string('kolom')->toString();
        $this->profil->simpan($umkm, $kolom, $request->input('nilai'));

        $umkm->refresh();

        return response()->json([
            'message'     => $request->filled('nilai')
                ? ProfilUmkmService::kolom()[$kolom]['label'] . ' berhasil disimpan.'
                : ProfilUmkmService::kolom()[$kolom]['label'] . ' berhasil dihapus.',
            'nilai'       => $this->profil->nilai($umkm),
            'skor_total'  => $umkm->skor_total,
            'klasifikasi' => $umkm->klasifikasi,
            'kabupaten'   => $umkm->kabupaten,
        ]);
    }
}
