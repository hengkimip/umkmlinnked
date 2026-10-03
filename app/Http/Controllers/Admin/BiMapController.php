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
        $umkm = Umkm::aktif()
            ->select('id', 'nama_usaha', 'sektor', 'kabupaten', 'skor_total', 'klasifikasi')
            ->get()
            ->map(fn ($u) => [
                'id'       => $u->id,
                'nama'     => $u->nama_usaha,
                'sektor'   => ucfirst($u->sektor),
                'kab'      => $this->namaKabupatenLengkap($u->kabupaten),
                'skor'     => $u->skor_total,
                'status'   => ucfirst($u->klasifikasi),
                'analisis' => $this->analisisSingkat($u->klasifikasi),
            ]);

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