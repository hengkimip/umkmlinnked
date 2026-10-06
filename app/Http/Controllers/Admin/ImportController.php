<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UmkmImport;
use App\Models\Opd;
use App\Models\ProfilUmkm;
use App\Models\Umkm;
use App\Services\ProfilUmkmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    // Halaman form upload
    public function index(Request $request)
    {
        Gate::authorize('create', Umkm::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        return view('admin.import.index', [
            'opd'       => $user->opd,
            // Pilihan cepat kolom "Rekomendasi Program KPw BI" pada formulir manual
            'usulanProgram' => array_keys(ProfilUmkm::usulanProgram()),
            // Super Admin memilih OPD tujuan; Admin OPD terkunci ke OPD-nya
            'daftarOpd' => $user->isSuperAdmin()
                ? Opd::query()->orderBy('nama_opd')->get(['id', 'nama_opd', 'kabupaten'])
                : collect(),
        ]);
    }

    // Proses upload
    public function store(Request $request)
    {
        Gate::authorize('create', Umkm::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'opd_id' => $user->isSuperAdmin()
                ? ['required', 'integer', 'exists:opd,id']
                : ['prohibited'],
            'file' => [
                'required',
                'file',
                'extensions:csv,xlsx,xls',
                'mimes:csv,txt,xlsx,xls',
                'max:5120',
            ],
        ], [
            'opd_id.required'   => 'Pilih OPD tujuan data.',
            'opd_id.exists'     => 'OPD tidak ditemukan.',
            'opd_id.prohibited' => 'Admin OPD tidak dapat memilih OPD lain.',
            'file.required'   => 'File wajib dipilih.',
            'file.extensions' => 'Format file harus CSV atau Excel (.xlsx/.xls).',
            'file.mimes'      => 'Isi file tidak dikenali sebagai CSV atau Excel.',
            'file.max'        => 'Ukuran file maksimal 5 MB.',
        ]);

        // Super Admin memilih OPD tujuan secara eksplisit; Admin OPD wajib
        // terikat ke OPD-nya dan data impor otomatis masuk wilayahnya (FR-02, FR-15).
        $opdId = $user->isSuperAdmin() ? $request->integer('opd_id') : $user->opd_id;

        if (! $opdId) {
            return back()->with('error', 'Akun Anda belum terhubung ke OPD. Hubungi Super Admin.');
        }

        $import = new UmkmImport((int) $opdId, $user->id);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Import UMKM gagal: ' . $e->getMessage(), ['user_id' => $user->id]);

            return back()->with('error', 'File tidak dapat dibaca. Pastikan memakai template terbaru dan format CSV/Excel yang valid.');
        }

        $pesan    = "Berhasil mengimpor {$import->imported} data UMKM."
            // Skor kemiripan >= ambang: belum disimpan, menunggu keputusan admin
            . ($import->antre ? " {$import->antre} baris mirip UMKM yang sudah terdaftar dan masuk antrean review duplikat." : '');
        $failures = $import->errors();

        if ($failures->isNotEmpty()) {
            return redirect()->route('admin.import.index')
                ->with('success', $pesan . " {$failures->count()} baris gagal diimpor.")
                ->with('import_errors', $failures->all());
        }

        return redirect()->route('admin.import.index')
            ->with('success', $pesan);
    }

    /**
     * Tambah satu UMKM secara manual dengan seluruh kolom "Kelola Profil UMKM".
     * Wilayah & hak akses sama dengan import file (Admin OPD terkunci ke OPD-nya).
     */
    public function manual(Request $request, ProfilUmkmService $profil)
    {
        Gate::authorize('create', Umkm::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validateWithBag('manual', [
            'opd_id' => $user->isSuperAdmin() ? ['required', 'integer', 'exists:opd,id'] : ['prohibited'],
        ], [
            'opd_id.required'   => 'Pilih OPD pembina.',
            'opd_id.exists'     => 'OPD tidak ditemukan.',
            'opd_id.prohibited' => 'Admin OPD tidak dapat memilih OPD lain.',
        ]);

        $opdId = $user->isSuperAdmin() ? $request->integer('opd_id') : $user->opd_id;
        if (! $opdId) {
            return back()->withInput()->with('error', 'Akun Anda belum terhubung ke OPD. Hubungi Super Admin.');
        }

        $input = $request->only(array_keys(ProfilUmkmService::kolom()));
        // Formulir: satu program per baris
        $input['rekomendasi_program'] = preg_split('/\R/u', (string) $request->input('rekomendasi_program', ''));

        try {
            ['umkm' => $umkm, 'pemilikTerdaftar' => $terdaftar, 'antrean' => $antrean] = $profil->buat($input, (int) $opdId, $user);
        } catch (ValidationException $e) {
            throw $e->errorBag('manual');
        }

        // Kemungkinan duplikat → belum disimpan, menunggu review di antrean
        if ($antrean) {
            return redirect()->route('admin.duplikat.index')->with('success',
                "Data \"{$antrean->data['nama_usaha']}\" belum disimpan: mirip UMKM \"{$antrean->umkm->nama_usaha}\" yang sudah terdaftar "
                . "({$antrean->umkm->teksBinaan()}, skor kemiripan {$antrean->skor}/100). Silakan putuskan di antrean review duplikat.");
        }

        $pesan = "UMKM \"{$umkm->nama_usaha}\" berhasil ditambahkan (skor {$umkm->skor_total}/100).";
        if ($terdaftar) {
            $pesan .= ' Nomor WhatsApp pemilik sudah terdaftar, jadi UMKM ini dihubungkan ke data pemilik tersebut.';
        }

        return redirect()->route('admin.profil-umkm.index', ['umkm' => $umkm->id])->with('success', $pesan);
    }

    // Download template CSV
    public function template()
    {
        Gate::authorize('create', Umkm::class);

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
            'kota_kabupaten',
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
            'Kota Pontianak',
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
