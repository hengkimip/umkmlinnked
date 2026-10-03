<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UmkmImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    // Halaman form upload
    public function index()
    {
        return view('admin.import.index');
    }

    // Proses upload
    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:5120',
            ],
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes'    => 'Format file harus CSV atau Excel (.xlsx/.xls).',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        // PERBAIKAN 1: gunakan Auth facade agar Intelephense tidak komplain
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $opdId = ($user && $user->opd_id) ? (int) $user->opd_id : 1;

        $import = new UmkmImport($opdId);

        Excel::import($import, $request->file('file'));

        $jumlahBerhasil = $import->imported;
        $pesan          = "Berhasil mengimpor {$jumlahBerhasil} data UMKM.";
$import = new UmkmImport($opdId);

Excel::import($import, $request->file('file'));

$jumlahBerhasil = $import->imported;
$pesan          = "Berhasil mengimpor {$jumlahBerhasil} data UMKM.";

/** @var \App\Imports\UmkmImport $import */
$failures = $import->failures();

if ($failures->isNotEmpty()) {
    // ... sisa kode
}
        // PERBAIKAN 2: gunakan failures() dari trait, bukan akses $errors langsung
        $failures = $import->failures();

        if ($failures->isNotEmpty()) { 
            $pesanGagal = [];
            foreach ($failures as $failure) {
                $pesanGagal[] = "Baris {$failure->row()}: "
                              . implode(', ', $failure->errors())
                              . " (kolom: {$failure->attribute()})";
            }

            return redirect()->route('admin.import.index')
                ->with('success', $pesan . " {$failures->count()} baris gagal diimpor.")
                ->with('import_errors', $pesanGagal);
        }

        return redirect()->route('admin.import.index')
            ->with('success', $pesan);
    }

    // Download template CSV
    public function template()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_umkm.csv"',
        ];

        $columns = [
            'nama_pemilik_usaha',
            'alamat_lengkap',
            'no_whatsapp',
            'alamat_e_mail',
            'program_yang_pernah_diikuti_dari_bank_indonesia',
            'nama_umkmusaha',
            'alamat_usaha',
            'tahun_berdirinya_usaha',
            'sektor_usaha',
            'jumlah_karyawan',
            'kapasitas_produksi_per_bulan_pcskg',
            'apakah_memiliki_jenis_produk_lainnya_mohon_disebutkan_secara_spesifik',
            'berapa_kapasitas_produksi_produk_tersebut',
            'saluran_pemasaran_produk_selama_ini',
            'jangkauan_pasar_utama_saat_ini',
            'nomor_link_wa_bisnis',
            'urllink_instagram_usaha_facebook',
            'urllink_marketplace_usaha_yang_dimiliki',
            'url_link_website',
            'bentuk_legalitas_usaha_yang_dimiliki',
            'sertifikasi_produk_yang_dimiliki',
            'bagaimana_metode_pencatatan_keuangan_usaha_anda_saat_ini',
            'apakah_pada_tahun_2026_sudah_mendapatkan_pembiayaan',
            'jika_sudah_sebutkan_nama_lembaga_dan_jumlah_plafond',
            'jika_ada_rencana_sebutkan_nama_lembaga_dan_jumlah_plafond',
            'produk_jasa_unggulan',
            'berapa_rata_rata_omzet_usaha_anda_perbulan',
            'foto_produk',
        ];

        $contoh = [
            'Budi Santoso',
            'Jl. Merdeka No.1, Pontianak',
            '081234567890',
            'budi@email.com',
            'Wirausaha Muda BI',
            'Kopi Nusantara',
            'Jl. Sudirman No.5, Pontianak',
            '2019',
            'Kuliner',
            '3',
            '100',
            'Teh Herbal',
            '50',
            'Tokopedia, Instagram, WhatsApp',
            'Regional',
            '081234567890',
            'https://instagram.com/kopinusantara',
            'https://tokopedia.com/kopinusantara',
            '',
            'NIB, NPWP',
            'PIRT, Halal',
            'Aplikasi (BukuKas)',
            'Ya',
            'KUR BRI - Rp 50.000.000',
            '',
            'Kopi Arabika Kalbar',
            '15000000',
            'https://drive.google.com/file/d/1ABC123XYZ/view?usp=sharing',
        ];

        $callback = function () use ($columns, $contoh) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, $columns);
            fputcsv($file, $contoh);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}