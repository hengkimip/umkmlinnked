<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilUmkm;
use App\Models\Umkm;
use App\Services\ProfilUmkmService;
use App\Support\CacheData;
use App\Support\TagUmkm;
use App\Services\RekomendasiProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BiMapController extends Controller
{
    // Kolom data peta yang TIDAK dikirim ke pengunjung umum (tidak tampil di halaman Semua Brand)
    private const KOLOM_ADMIN = [
        'email' => true, 'tenaga_kerja' => true, 'skor' => true, 'analisis' => true,
        'program' => true, 'url' => true,
    ];

    /**
     * Tampilkan halaman peta interaktif.
     */
    public function index(Request $request)
    {
        return view('admin.bi-map.index', [
            'filterPeta' => TagUmkm::FILTER,
            'lengkap'    => self::aksesLengkap($request),
            // Tombol "Dashboard" di panel samping (hanya Super Admin & Admin OPD yang login):
            // Super Admin → /superadmin/dashboard, Admin OPD → /admin/dashboard
            'dashboardUrl' => $request->user()?->isAdminAny() ? $request->user()->dashboardUrl() : null,
        ]);
    }

    /**
     * Endpoint JSON — data UMKM asli dari database, dipanggil via fetch() oleh bi-map.js.
     * `jumlah` = jumlah UMKM aktif per kabupaten/kota (agregat MySQL) untuk label peta.
     * Peta terbuka untuk umum: selain Super Admin hanya menerima kolom yang juga tampil di Semua Brand.
     */
    public function dataJson(Request $request): JsonResponse
    {
        // Data sama untuk semua pengunjung → di-cache (dibatalkan otomatis saat data UMKM berubah).
        // URL relatif agar benar di domain mana pun situs diakses.
        $data = CacheData::ingat('peta:data', 600, fn () => $this->susunData());

        if (! self::aksesLengkap($request)) {
            // Admin OPD: semua label UMKM membuka halaman detail (/peta-interaktif/umkm/{id});
            // pengunjung umum tetap ke halaman publik Semua Brand.
            $adminOpd = (bool) $request->user()?->isAdmin();

            $data['umkm'] = array_map(function (array $u) use ($adminOpd) {
                $publik = array_diff_key($u, self::KOLOM_ADMIN + ['opd_id' => true]);
                if ($adminOpd) {
                    $publik['url'] = $u['url'];
                }

                return $publik;
            }, $data['umkm']);
            unset($data['tidak_diketahui']);
        }

        return response()->json($data);
    }

    /** Super Admin melihat data lengkap (skor, kontak e-mail, program BI, tautan detail admin). */
    private static function aksesLengkap(Request $request): bool
    {
        return (bool) $request->user()?->isSuperAdmin();
    }

    private function susunData(): array
    {
        $umkm = Umkm::aktif()
            ->select('id', 'opd_id', 'slug', 'nama_usaha', 'sektor', 'kabupaten', 'kecamatan', 'alamat_usaha',
                'whatsapp', 'email', 'website', 'instagram', 'facebook', 'tokopedia', 'shopee',
                'jumlah_tenaga_kerja', 'tahun_berdiri', 'skor_total', 'klasifikasi')
            ->with(TagUmkm::RELASI)
            ->orderByDesc('skor_total')
            ->orderBy('nama_usaha')
            ->get()
            ->map(fn (Umkm $u) => [
                'id'            => $u->id,
                'opd_id'        => $u->opd_id, // internal: penentu tautan detail Admin OPD (tidak dikirim ke publik)
                'nama'          => $u->nama_usaha,
                'sektor'        => $u->sektor_label,
                'kab'           => Umkm::KABUPATEN_LENGKAP[$u->kabupaten] ?? $u->kabupaten,
                'kecamatan'     => $u->kecamatan !== '-' ? $u->kecamatan : null,
                'alamat'        => $u->alamat_usaha !== '-' ? $u->alamat_usaha : null,
                'whatsapp'      => $u->whatsapp,
                'email'         => $u->email,
                'website'       => $u->websiteUrl(),
                'tenaga_kerja'  => $u->jumlah_tenaga_kerja,
                'tahun_berdiri' => $u->tahun_berdiri,
                'skor'          => $u->skor_total,
                'status'        => ucfirst($u->klasifikasi),
                'analisis'      => $u->analisis,
                'f'             => TagUmkm::untuk($u), // sama dengan filter Semua Brand
                // Program BI yang pernah dituliskan UMKM (referensi di tab Program)
                'program'       => ProfilUmkm::daftarProgramBi($u->profil?->program_bi),
                'url'           => route('superadmin.peta-interaktif.umkm', ['umkm' => $u->id], absolute: false),
                // Halaman publik UMKM di Semua Brand (dibuka dari label cabang di peta)
                'url_publik'    => route('direktori.show', $u, absolute: false),
            ])
            ->all();

        // Agregat MySQL: jumlah UMKM aktif per kabupaten (label peta saat tidak ada filter)
        $perKabupaten = Umkm::aktif()->toBase()
            ->selectRaw('kabupaten, count(*) as jumlah')
            ->groupBy('kabupaten')
            ->pluck('jumlah', 'kabupaten');

        $jumlah = collect(Umkm::KABUPATEN_LENGKAP)
            ->mapWithKeys(fn ($lengkap, $singkat) => [$lengkap => (int) ($perKabupaten[$singkat] ?? 0)]);

        return [
            'umkm'            => $umkm,
            'jumlah'          => $jumlah->all(),
            'tidak_diketahui' => (int) ($perKabupaten[Umkm::KABUPATEN_KOSONG] ?? 0),
        ];
    }

    /**
     * Halaman detail UMKM dari cabang peta, beserta rekomendasi program KPw BI.
     */
    public function show(Umkm $umkm, ProfilUmkmService $profil, RekomendasiProgramService $rekomendasi)
    {
        // Super Admin & Admin OPD dapat MELIHAT detail semua UMKM; tautan ubah hanya bagi pemegang
        // Otoritas Edit (OPD pembina & Super Admin) — sama dengan Kelola Profil UMKM.
        Gate::authorize('viewAny', Umkm::class);

        $umkm->load(['pemilik', 'opd', 'legalitas', 'pemasaran', 'keuanganTerakhir', 'profil']);

        return view('admin.bi-map.show', [
            'umkm'              => $umkm,
            'nilai'             => $profil->nilai($umkm),
            'programDitetapkan' => $umkm->profil?->rekomendasi_program ?: [],
            'rekomendasi'       => $rekomendasi->untuk($umkm),
            'bolehUbah'         => Gate::allows('update', $umkm), // Otoritas Edit
        ]);
    }
}
