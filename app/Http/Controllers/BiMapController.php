<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
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
     * Endpoint JSON — data UMKM asli dari database,
     * dipanggil via fetch() oleh bi-map.js.
     */
   public function dataJson(): JsonResponse
{
    $centroidKabupaten = [
        'Kota Pontianak'   => [-0.0263, 109.3425],
        'Kota Singkawang'  => [0.9100, 108.9800],
        'Kab. Sambas'      => [1.3622, 109.3031],
        'Kab. Mempawah'    => [0.3600, 108.9500],
        'Kab. Kubu Raya'   => [-0.15, 109.43],
        'Kab. Bengkayang'  => [0.8251, 109.4895],
        'Kab. Landak'      => [0.4200, 109.7500],
        'Kab. Sanggau'     => [0.1259, 110.5894],
        'Kab. Sekadau'     => [0.0300, 110.9500],
        'Kab. Sintang'     => [0.0700, 111.5000],
        'Kab. Melawi'      => [-0.3300, 111.7000],
        'Kab. Kapuas Hulu' => [0.8200, 112.9300],
        'Kab. Kayong Utara'=> [-1.1078, 109.9628],
        'Kab. Ketapang'    => [-1.8400, 109.9700],
    ];

       $umkm = Umkm::aktif()
        ->select('id', 'nama_usaha', 'sektor', 'kabupaten', 'alamat_usaha', 'skor_total', 'klasifikasi', 'latitude', 'longitude')
        ->get()
        ->map(function ($u) use ($centroidKabupaten) {
            $kabLengkap = $this->namaKabupatenLengkap($u->kabupaten);
            $centroid   = $centroidKabupaten[$kabLengkap] ?? [-0.1, 111.0];

            return [
                'id'       => $u->id,
                'nama'     => $u->nama_usaha,
                'sektor'   => $u->sektor_label,
                'kab'      => $kabLengkap,
                'alamat'   => $u->alamat_usaha ?: '-', // ← BARU
                'skor'     => $u->skor_total,
                'status'   => ucfirst($u->klasifikasi),
                'analisis' => $this->analisisSingkat($u->klasifikasi),
                'lat'      => $u->latitude  ?? $centroid[0],
                'lng'      => $u->longitude ?? $centroid[1],
                'geocoded' => $u->latitude !== null,
            ];
        });

    return response()->json($umkm);
}

    /**
     * Kolom `kabupaten` di tabel umkm tersimpan singkat (mis. "Pontianak"),
     * sedangkan peta butuh nama lengkap ("Kota Pontianak") untuk mencocokkan
     * marker. Mapping ini konsisten dengan extractKabupaten() di UmkmImport.
     */
    private function namaKabupatenLengkap(string $kab): string
    {
        $map = [
            'Pontianak'    => 'Kota Pontianak',
            'Singkawang'   => 'Kota Singkawang',
            'Sambas'       => 'Kab. Sambas',
            'Mempawah'     => 'Kab. Mempawah',
            'Kubu Raya'    => 'Kab. Kubu Raya',
            'Bengkayang'   => 'Kab. Bengkayang',
            'Landak'       => 'Kab. Landak',
            'Sanggau'      => 'Kab. Sanggau',
            'Sekadau'      => 'Kab. Sekadau',
            'Sintang'      => 'Kab. Sintang',
            'Melawi'       => 'Kab. Melawi',
            'Kapuas Hulu'  => 'Kab. Kapuas Hulu',
            'Kayong Utara' => 'Kab. Kayong Utara',
            'Ketapang'     => 'Kab. Ketapang',
        ];

        return $map[$kab] ?? $kab;
    }

    private function analisisSingkat(string $klasifikasi): string
    {
        return match ($klasifikasi) {
            'unggulan'   => 'Potensi Ekspor Luas',
            'berkembang' => 'Manajemen Perlu Digitalisasi',
            default      => 'Legalitas Belum Lengkap',
        };
    }
}