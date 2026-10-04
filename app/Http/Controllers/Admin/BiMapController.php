<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Services\ProfilUmkmService;
use App\Support\CacheData;
use App\Services\RekomendasiProgramService;
use Illuminate\Http\JsonResponse;

class BiMapController extends Controller
{
    /**
     * Tampilkan halaman peta interaktif.
     */
    public function index()
    {
        return view('admin.bi-map.index');
    }

    /**
     * Endpoint JSON — data UMKM asli dari database, dipanggil via fetch() oleh bi-map.js.
     * `jumlah` = jumlah UMKM aktif per kabupaten/kota (agregat MySQL) untuk label peta.
     */
    public function dataJson(): JsonResponse
    {
        // Data sama untuk semua Super Admin → di-cache (dibatalkan otomatis saat data UMKM berubah).
        // URL relatif agar benar di domain mana pun situs diakses.
        return response()->json(CacheData::ingat('peta:data', 600, fn () => $this->susunData()));
    }

    private function susunData(): array
    {
        $umkm = Umkm::aktif()
            ->select('id', 'nama_usaha', 'sektor', 'kabupaten', 'kecamatan', 'alamat_usaha',
                'whatsapp', 'email', 'website', 'jumlah_tenaga_kerja', 'tahun_berdiri', 'skor_total', 'klasifikasi')
            ->orderByDesc('skor_total')
            ->orderBy('nama_usaha')
            ->get()
            ->map(fn (Umkm $u) => [
                'id'            => $u->id,
                'nama'          => $u->nama_usaha,
                'sektor'        => $u->sektor_label,
                'sektor_kode'   => $u->sektor,
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
                'url'           => route('superadmin.peta-interaktif.umkm', ['umkm' => $u->id], absolute: false),
            ])
            ->all();

        // Agregat MySQL: jumlah UMKM aktif per kabupaten × kategori (sektor)
        $agregat = Umkm::aktif()->toBase()
            ->selectRaw('kabupaten, sektor, count(*) as jumlah')
            ->groupBy('kabupaten', 'sektor')
            ->get();

        $perKabupaten = $agregat->groupBy('kabupaten')->map->sum('jumlah');

        $jumlah = collect(Umkm::KABUPATEN_LENGKAP)
            ->mapWithKeys(fn ($lengkap, $singkat) => [$lengkap => (int) ($perKabupaten[$singkat] ?? 0)]);

        // Per kategori: { sektor: { "Kota Pontianak": n, ... } } — untuk label saat filter kategori aktif
        $jumlahSektor = $agregat
            ->filter(fn ($r) => isset(Umkm::KABUPATEN_LENGKAP[$r->kabupaten]))
            ->groupBy('sektor')
            ->map(fn ($baris) => $baris->mapWithKeys(fn ($r) => [Umkm::KABUPATEN_LENGKAP[$r->kabupaten] => (int) $r->jumlah]));

        return [
            'umkm'            => $umkm,
            'jumlah'          => $jumlah->all(),
            'jumlah_sektor'   => $jumlahSektor->map->all()->all(),
            // Kategori UMKM yang benar-benar ada di database (untuk dropdown filter)
            'sektor'          => collect(Umkm::sektorTersedia())
                ->map(fn ($s, $kode) => ['kode' => $kode, 'label' => $s['label'], 'jumlah' => $s['count']])
                ->values()
                ->all(),
            'tidak_diketahui' => (int) ($perKabupaten[Umkm::KABUPATEN_KOSONG] ?? 0),
        ];
    }

    /**
     * Halaman detail UMKM dari cabang peta, beserta rekomendasi program KPw BI.
     */
    public function show(Umkm $umkm, ProfilUmkmService $profil, RekomendasiProgramService $rekomendasi)
    {
        $umkm->load(['pemilik', 'opd', 'legalitas', 'pemasaran', 'keuanganTerakhir', 'profil']);

        return view('admin.bi-map.show', [
            'umkm'              => $umkm,
            'nilai'             => $profil->nilai($umkm),
            'programDitetapkan' => $umkm->profil?->rekomendasi_program ?: [],
            'rekomendasi'       => $rekomendasi->untuk($umkm),
        ]);
    }
}
