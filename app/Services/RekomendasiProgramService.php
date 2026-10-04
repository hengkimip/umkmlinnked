<?php
namespace App\Services;

use App\Models\Umkm;

/**
 * Rekomendasi program pengembangan UMKM dari KPw Bank Indonesia Kalimantan Barat,
 * diturunkan otomatis dari data profil (aturan sederhana & dapat ditelusuri).
 * Keputusan akhir tetap pada tim KPw BI.
 */
class RekomendasiProgramService
{
    public const PRIORITAS = ['tinggi' => 1, 'sedang' => 2, 'rendah' => 3];

    /**
     * @return array<int, array{program: string, bidang: string, prioritas: string, alasan: string}>
     */
    public function untuk(Umkm $umkm): array
    {
        $umkm->loadMissing(['legalitas', 'pemasaran', 'keuanganTerakhir', 'profil', 'produk']);

        $leg       = $umkm->legalitas;
        $pemasaran = $umkm->pemasaran;
        $keuangan  = $umkm->keuanganTerakhir;
        $profil    = $umkm->profil;
        $pangan    = in_array($umkm->sektor, ['kuliner', 'pertanian', 'perikanan'], true);
        $platform  = count($pemasaran?->platform_online ?? []);
        $jangkauan = $pemasaran?->jangkauan_pasar;

        $r = [];
        $tambah = function (string $program, string $bidang, string $prioritas, string $alasan) use (&$r) {
            $r[] = compact('program', 'bidang', 'prioritas', 'alasan');
        };

        // ---------- Kelembagaan & legalitas ----------
        if (! $leg?->nomor_nib) {
            $tambah('Fasilitasi Legalitas Usaha (NIB melalui OSS)', 'Legalitas', 'tinggi',
                'Belum tercatat memiliki NIB — syarat dasar untuk akses pembiayaan, sertifikasi, dan kemitraan.');
        } elseif (! $leg->nomor_npwp) {
            $tambah('Pendampingan Administrasi Usaha (NPWP)', 'Legalitas', 'rendah',
                'NIB sudah ada, NPWP belum tercatat.');
        }

        // ---------- Sertifikasi produk ----------
        if ($pangan && ! $leg?->nomor_halal) {
            $tambah('Pendampingan Sertifikasi Halal', 'Sertifikasi', 'tinggi',
                'Usaha sektor ' . mb_strtolower($umkm->sektor_label) . ' belum tercatat bersertifikat Halal.');
        }
        if ($umkm->sektor === 'kuliner' && ! $leg?->nomor_pirt && ! $leg?->nomor_bpom) {
            $tambah('Pendampingan Izin Edar (PIRT / BPOM)', 'Sertifikasi', 'sedang',
                'Produk pangan olahan belum tercatat memiliki PIRT maupun BPOM.');
        }

        // ---------- Keuangan & pembayaran digital ----------
        if (! $keuangan?->memiliki_pencatatan) {
            $tambah('Pelatihan Pencatatan Keuangan Digital (SI APIK)', 'Keuangan', 'tinggi',
                'Belum tercatat menggunakan pencatatan keuangan digital/aplikasi.');
        }
        $tambah('Perluasan Akseptasi Pembayaran Digital (QRIS)', 'Sistem Pembayaran', $platform === 0 ? 'sedang' : 'rendah',
            'Mendukung transaksi nontunai; status penggunaan QRIS belum tercatat di profil.');

        // ---------- Pembiayaan ----------
        $rencana = filled($profil?->rencana_pembiayaan);
        $belum   = $profil?->pembiayaan_2026 && preg_match('/belum|tidak/i', $profil->pembiayaan_2026);
        if ($rencana || $belum) {
            $tambah('Fasilitasi Akses Pembiayaan (KUR & Business Matching Perbankan)', 'Pembiayaan', $rencana ? 'tinggi' : 'sedang',
                $rencana ? 'Memiliki rencana pengajuan pembiayaan: ' . \Illuminate\Support\Str::limit($profil->rencana_pembiayaan, 80)
                         : 'Belum mendapatkan pembiayaan pada tahun berjalan.');
        }

        // ---------- Pemasaran ----------
        if ($platform < 2 || (! $umkm->tokopedia && ! $umkm->shopee)) {
            $tambah('Onboarding Digital & Pemasaran E-commerce', 'Pemasaran', $platform === 0 ? 'tinggi' : 'sedang',
                $platform === 0 ? 'Belum tercatat memakai kanal pemasaran digital.' : "Baru memakai {$platform} kanal digital dan belum hadir di marketplace.");
        }

        // ---------- Pengembangan berdasarkan tingkat kematangan ----------
        match ($umkm->klasifikasi) {
            'unggulan' => $tambah('Kurasi Produk & Business Matching Ekspor (Go Global)', 'Pengembangan Pasar',
                in_array($jangkauan, ['nasional', 'ekspor'], true) ? 'tinggi' : 'sedang',
                "Skor kesiapan {$umkm->skor_total}/100 (unggulan)" . ($jangkauan ? ", jangkauan pasar {$jangkauan}." : '.')),
            'berkembang' => $tambah('Pendampingan Wirausaha Bank Indonesia (WUBI)', 'Pengembangan Usaha', 'sedang',
                "Skor kesiapan {$umkm->skor_total}/100 (berkembang) — siap naik kelas dengan pendampingan terstruktur."),
            default => $tambah('Pelatihan Dasar Manajemen Usaha & Kewirausahaan', 'Pengembangan Usaha', 'tinggi',
                "Skor kesiapan {$umkm->skor_total}/100 (dasar) — perlu penguatan fondasi usaha."),
        };

        if ($umkm->klasifikasi !== 'dasar' && in_array($umkm->sektor, ['kerajinan', 'fashion', 'kuliner'], true)) {
            $tambah('Kurasi Pameran Karya Kreatif Indonesia (KKI)', 'Pengembangan Pasar', 'rendah',
                'Produk ' . mb_strtolower($umkm->sektor_label) . ' berpotensi dikurasi untuk etalase nasional.');
        }

        if (in_array($umkm->sektor, ['pertanian', 'perikanan'], true)) {
            $tambah('Program Klaster Ketahanan Pangan', 'Klaster', 'sedang',
                'Sektor ' . mb_strtolower($umkm->sektor_label) . ' sejalan dengan program pengendalian inflasi pangan.');
        }

        if ($umkm->produk->isNotEmpty() && ! $umkm->produk->sum('kapasitas_produksi')) {
            $tambah('Pendampingan Peningkatan Kapasitas Produksi', 'Produksi', 'rendah',
                'Kapasitas produksi belum tercatat; diperlukan untuk perencanaan pasokan.');
        }

        usort($r, fn ($a, $b) => self::PRIORITAS[$a['prioritas']] <=> self::PRIORITAS[$b['prioritas']]);

        return $r;
    }
}
