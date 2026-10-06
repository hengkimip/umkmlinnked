<?php
namespace App\Services;

use App\Imports\UmkmImport;
use App\Models\Umkm;
use App\Models\UmkmDuplikat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Deteksi duplikasi UMKM dengan skor kemiripan gabungan (composite scoring, 0–100).
 *
 * Exact match satu kolom (mis. hanya No. WhatsApp) gagal saat kontak berganti, sedangkan
 * pencocokan hanya nama usaha terlalu longgar. Karena itu beberapa sinyal digabung:
 *
 *   Sinyal                         Bobot  Pencocokan
 *   Nama pemilik usaha               25   fuzzy ≥ 80% mirip
 *   Nama usaha / brand               25   fuzzy ≥ 80% mirip
 *   Alamat usaha                     20   fuzzy ≥ 60% mirip, prasyarat kabupaten/kota sama
 *   Nomor WhatsApp                   20   sama persis setelah normalisasi (0… → 62…)
 *   Alamat e-mail                    10   sama persis (tanpa beda huruf besar)
 *
 * Data baru dibandingkan dengan UMKM tersimpan di kabupaten/kota yang sama. Skor ≥ ambang
 * (config umkm.ambang_duplikat, bawaan 60) → tidak langsung disimpan, masuk antrean review.
 * Tahun berdiri & program yang pernah diikuti hanya sinyal pendukung saat review (tidak dihitung).
 */
class DeteksiDuplikatService
{
    public const SINYAL = [
        'pemilik' => ['label' => 'Nama pemilik usaha', 'bobot' => 25, 'min' => 0.80],
        'usaha'   => ['label' => 'Nama usaha/brand',   'bobot' => 25, 'min' => 0.80],
        'alamat'  => ['label' => 'Alamat usaha',       'bobot' => 20, 'min' => 0.60],
        'wa'      => ['label' => 'Nomor WhatsApp',     'bobot' => 20, 'min' => 1.0],
        'email'   => ['label' => 'Alamat e-mail',      'bobot' => 10, 'min' => 1.0],
    ];

    public function __construct(private ProfilUmkmService $profil) {}

    public static function ambang(): int
    {
        return (int) config('umkm.ambang_duplikat', 60);
    }

    // ==================== Normalisasi ====================

    /** "0812-3456 7890" / "+62 812…" → "6281234567890". */
    public static function normalWa(?string $wa): string
    {
        $angka = preg_replace('/\D/', '', (string) $wa);

        return preg_replace('/^0/', '62', $angka);
    }

    /** Huruf kecil, tanda baca umum dihapus, spasi berlebih dirapikan. */
    public static function normalTeks(?string $teks): string
    {
        $teks = Str::ascii(mb_strtolower((string) $teks));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $teks)));
    }

    /** Kemiripan 0–1 (1 − jarak Levenshtein / panjang teks terpanjang). */
    public static function mirip(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        return max(0.0, 1 - levenshtein($a, $b) / max(strlen($a), strlen($b)));
    }

    // ==================== Skor ====================

    /**
     * Rincian skor data baru (format kolom Kelola Profil) terhadap satu UMKM tersimpan.
     *
     * @return array{skor: int, rincian: array<string, array{bobot: int, dapat: int, nilai: int}>}
     */
    public function skor(array $data, Umkm $umkm): array
    {
        $pemilik = $umkm->pemilik;
        $kabSama = ($data['kabupaten'] ?? null) && $data['kabupaten'] !== Umkm::KABUPATEN_KOSONG
            && $data['kabupaten'] === $umkm->kabupaten;

        $waBaru   = self::normalWa($data['pemilik_whatsapp'] ?? null);
        $waLama   = array_filter([self::normalWa($pemilik?->telepon), self::normalWa($umkm->whatsapp)]);
        $mailBaru = mb_strtolower(trim((string) ($data['pemilik_email'] ?? '')));
        $mailLama = array_filter([mb_strtolower(trim((string) $pemilik?->email)), mb_strtolower(trim((string) $umkm->email))]);

        $nilai = [
            'pemilik' => self::mirip(self::normalTeks($data['pemilik_nama'] ?? null), self::normalTeks($pemilik?->nama_lengkap)),
            'usaha'   => self::mirip(self::normalTeks($data['nama_usaha'] ?? null), self::normalTeks($umkm->nama_usaha)),
            'alamat'  => $kabSama ? self::mirip(self::normalTeks($data['alamat_usaha'] ?? null), self::normalTeks($umkm->alamat_usaha)) : 0.0,
            'wa'      => strlen($waBaru) >= 8 && in_array($waBaru, $waLama, true) ? 1.0 : 0.0,
            'email'   => $mailBaru !== '' && in_array($mailBaru, $mailLama, true) ? 1.0 : 0.0,
        ];

        $rincian = [];
        foreach (self::SINYAL as $kunci => $s) {
            $rincian[$kunci] = [
                'bobot' => $s['bobot'],
                'dapat' => $nilai[$kunci] >= $s['min'] ? $s['bobot'] : 0,
                'nilai' => (int) round($nilai[$kunci] * 100),
            ];
        }

        return ['skor' => array_sum(array_column($rincian, 'dapat')), 'rincian' => $rincian];
    }

    /**
     * UMKM tersimpan yang paling mirip dengan data baru, bila skornya ≥ ambang.
     *
     * @return array{umkm: Umkm, skor: int, rincian: array}|null
     */
    public function periksa(array $data): ?array
    {
        $kab = $data['kabupaten'] ?? null;

        $kandidat = Umkm::query()
            ->with('pemilik:id,nama_lengkap,telepon,email')
            // Kandidat: kabupaten/kota yang sama (bila wilayah belum diketahui, bandingkan dengan semua)
            ->when($kab && $kab !== Umkm::KABUPATEN_KOSONG, fn ($q) => $q->where('kabupaten', $kab))
            ->get(['id', 'pemilik_usaha_id', 'opd_id', 'nama_usaha', 'alamat_usaha', 'kabupaten', 'whatsapp', 'email']);

        $terbaik = null;
        foreach ($kandidat as $umkm) {
            $hasil = $this->skor($data, $umkm);
            if (! $terbaik || $hasil['skor'] > $terbaik['skor']) {
                $terbaik = ['umkm' => $umkm] + $hasil;
            }
        }

        return $terbaik && $terbaik['skor'] >= self::ambang() ? $terbaik : null;
    }

    /** Masukkan data baru ke antrean review manual (data belum disimpan sebagai UMKM). */
    public function antrekan(array $data, array $hasil, int $opdId, ?User $user, string $sumber, ?array $baris = null): UmkmDuplikat
    {
        return UmkmDuplikat::create([
            'umkm_id' => $hasil['umkm']->id,
            'opd_id'  => $opdId,
            'user_id' => $user?->id,
            'sumber'  => $sumber,
            'data'    => $data,
            'baris'   => $baris,
            'skor'    => $hasil['skor'],
            'rincian' => $hasil['rincian'],
        ]);
    }

    // ==================== Keputusan admin ====================

    /**
     * "Usaha yang sama": UMKM lama diperbarui dengan isian terbaru (kontak baru menggantikan
     * kontak lama). Kolom yang isiannya tidak valid dilewati dan dilaporkan.
     *
     * @return list<string> label kolom yang dilewati
     */
    public function gabungkan(UmkmDuplikat $d, User $user): array
    {
        $umkm = $d->umkm;
        abort_if(! $umkm || $umkm->trashed(), 409, 'UMKM pembanding sudah dihapus. Pilih "Usaha berbeda".');

        $dilewati = [];
        DB::transaction(function () use ($d, $user, $umkm, &$dilewati) {
            foreach (ProfilUmkmService::kolom() as $kolom => $def) {
                $nilai = $d->data[$kolom] ?? null;
                if ($nilai === null || $nilai === '' || $nilai === []) {
                    continue; // kosong = tidak mengubah data lama
                }
                try {
                    $this->profil->simpan($umkm->fresh(), $kolom, $nilai);
                } catch (ValidationException) {
                    $dilewati[] = $def['label'];
                }
            }

            $this->tandai($d, UmkmDuplikat::DIGABUNG, $user, $umkm->id);
        });

        return $dilewati;
    }

    /** "Usaha berbeda": data baru tetap disimpan sebagai UMKM baru (tanpa cek duplikat ulang). */
    public function simpanBaru(UmkmDuplikat $d, User $user): Umkm
    {
        return DB::transaction(function () use ($d, $user) {
            $umkm = $d->sumber === 'impor' && $d->baris
                ? (new UmkmImport($d->opd_id, $d->user_id))->simpanBaris($d->baris, cekDuplikat: false)
                : $this->profil->buat($d->data, $d->opd_id, cekDuplikat: false)['umkm'];

            // Pendaftar pertama = pengunggah data, bukan admin yang memutuskan di antrean
            $umkm->forceFill(['didaftarkan_oleh' => $d->user_id ?? $user->id])->saveQuietly();

            $this->tandai($d, UmkmDuplikat::DIBUAT_BARU, $user, $umkm->id);

            return $umkm;
        });
    }

    /** "Hapus data usulan baru": data baru dibuang (tidak disimpan); entri tetap tercatat di riwayat. */
    public function hapusUsulan(UmkmDuplikat $d, User $user): void
    {
        $this->tandai($d, UmkmDuplikat::DIHAPUS, $user, null);
    }

    private function tandai(UmkmDuplikat $d, string $status, User $user, ?int $umkmId): void
    {
        $d->update([
            'status'          => $status,
            'diputuskan_oleh' => $user->id,
            'diputuskan_pada' => now(),
            'umkm_hasil_id'   => $umkmId,
        ]);
    }
}
