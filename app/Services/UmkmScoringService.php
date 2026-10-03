<?php
// app/Services/UmkmScoringService.php
namespace App\Services;

use App\Models\Umkm;

class UmkmScoringService
{
    // Total maksimum = 100 poin
    // Legalitas: 25 | Sertifikasi: 20 | Produksi: 20 | Pemasaran: 20 | Keuangan: 15

    public function hitung(Umkm $umkm): array
    {
        $umkm->load(['legalitas', 'produk', 'pemasaran', 'keuanganTerakhir']);

        $skorLegalitas   = $this->hitungLegalitas($umkm);
        $skorSertifikasi = $this->hitungSertifikasi($umkm);
        $skorProduksi    = $this->hitungProduksi($umkm);
        $skorPemasaran   = $this->hitungPemasaran($umkm);
        $skorKeuangan    = $this->hitungKeuangan($umkm);

        $total = $skorLegalitas + $skorSertifikasi + $skorProduksi
               + $skorPemasaran + $skorKeuangan;

        $klasifikasi = match(true) {
            $total >= 75 => 'unggulan',
            $total >= 45 => 'berkembang',
            default      => 'dasar',
        };

        return compact(
            'skorLegalitas', 'skorSertifikasi', 'skorProduksi',
            'skorPemasaran', 'skorKeuangan', 'total', 'klasifikasi'
        );
    }

    public function simpan(Umkm $umkm): Umkm
    {
        $skor = $this->hitung($umkm);

        $umkm->update([
            'skor_legalitas'   => $skor['skorLegalitas'],
            'skor_sertifikasi' => $skor['skorSertifikasi'],
            'skor_produksi'    => $skor['skorProduksi'],
            'skor_pemasaran'   => $skor['skorPemasaran'],
            'skor_keuangan'    => $skor['skorKeuangan'],
            'skor_total'       => $skor['total'],
            'klasifikasi'      => $skor['klasifikasi'],
        ]);

        return $umkm->fresh();
    }

    private function hitungLegalitas(Umkm $umkm): int
    {
        $l = $umkm->legalitas;
        if (!$l) return 0;

        $skor = 0;
        if ($l->nomor_nib)  $skor += 10;
        if ($l->nomor_siup) $skor += 5;
        if ($l->nomor_npwp) $skor += 5;
        if ($l->nomor_akte) $skor += 5;

        return min($skor, 25);
    }

    private function hitungSertifikasi(Umkm $umkm): int
    {
        $l = $umkm->legalitas;
        if (!$l) return 0;

        $skor = 0;
        if ($l->nomor_halal) $skor += 8;
        if ($l->nomor_bpom)  $skor += 7;
        if ($l->nomor_pirt)  $skor += 5;

        return min($skor, 20);
    }

    private function hitungProduksi(Umkm $umkm): int
    {
        $jumlahProduk = $umkm->produk->count();
        $tenagaKerja  = $umkm->jumlah_tenaga_kerja;

        $skor  = min($jumlahProduk * 3, 10); // maks 10 dari produk
        $skor += match(true) {
            $tenagaKerja >= 20 => 10,
            $tenagaKerja >= 5  => 7,
            $tenagaKerja >= 2  => 4,
            default            => 0,
        };

        return min($skor, 20);
    }

    private function hitungPemasaran(Umkm $umkm): int
    {
        $p = $umkm->pemasaran;
        if (!$p) return 0;

        $skor = 0;
        $platformCount = count($p->platform_online ?? []);
        $skor += min($platformCount * 3, 10);

        $skor += match($p->jangkauan_pasar) {
            'ekspor'    => 10,
            'nasional'  => 7,
            'regional'  => 4,
            'lokal'     => 2,
            default     => 0,
        };

        return min($skor, 20);
    }

    private function hitungKeuangan(Umkm $umkm): int
    {
        $k = $umkm->keuanganTerakhir;
        if (!$k) return 0;

        $skor = 0;
        if ($k->memiliki_pencatatan) $skor += 5;
        if ($k->sudah_audit)         $skor += 5;

        $skor += match(true) {
            ($k->omzet_tahunan ?? 0) >= 2_500_000_000 => 5, // >= 2.5M
            ($k->omzet_tahunan ?? 0) >= 300_000_000   => 3,
            ($k->omzet_tahunan ?? 0) >= 50_000_000    => 1,
            default                                     => 0,
        };

        return min($skor, 15);
    }
}