<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Umkm extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Label tampilan untuk nilai kolom `sektor` (hasil UmkmImport::mapSektor)
    public const SEKTOR_LABEL = [
        'kuliner'     => 'Kuliner',
        'fashion'     => 'Fesyen',
        'kerajinan'   => 'Kerajinan',
        'pertanian'   => 'Pertanian & Agro',
        'perikanan'   => 'Perikanan',
        'jasa'        => 'Jasa',
        'teknologi'   => 'Teknologi',
        'perdagangan' => 'Perdagangan',
        'lainnya'     => 'Lainnya',
    ];

    public const KABUPATEN_KOSONG = 'Tidak Diketahui';

    protected $table = 'umkm';
    
    protected $fillable = [
        'pemilik_usaha_id','opd_id','nama_usaha','slug','deskripsi',
        'sektor','sub_sektor','kabupaten','kecamatan','kelurahan',
        'alamat_usaha','latitude','longitude','telepon','whatsapp',
        'email','website','instagram','facebook','tokopedia','shopee',
        'jumlah_tenaga_kerja','tahun_berdiri','foto_usaha',
        'skor_legalitas','skor_sertifikasi','skor_produksi',
        'skor_pemasaran','skor_keuangan','skor_total',
        'klasifikasi','status','is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'latitude'    => 'float',
        'longitude'   => 'float',
        'skor_legalitas' => 'integer',
        'skor_sertifikasi' => 'integer',
        'skor_produksi' => 'integer',
        'skor_pemasaran' => 'integer',
        'skor_keuangan' => 'integer',
        'skor_total' => 'integer',
        'jumlah_tenaga_kerja' => 'integer',
        'tahun_berdiri' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    public function pemilik()
    {
        return $this->belongsTo(PemilikUsaha::class, 'pemilik_usaha_id');
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function produk()
    {
        return $this->hasMany(Produk::class);
    }

    public function produkUnggulan()
    {
        return $this->hasMany(Produk::class)->where('is_unggulan', true);
    }

    public function pemasaran()
    {
        return $this->hasOne(Pemasaran::class);
    }

    public function legalitas()
    {
        return $this->hasOne(Legalitas::class);
    }

    public function keuangan()
    {
        return $this->hasMany(Keuangan::class)->orderByDesc('tahun');
    }

    public function keuanganTerakhir()
    {
        return $this->hasOne(Keuangan::class)->latestOfMany('tahun');
    }

    public function pembiayaan()
    {
        return $this->hasMany(Pembiayaan::class);
    }

    // ==================== SCOPES ====================
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByKabupaten($query, $kabupaten)
    {
        return $query->when($kabupaten, function ($q) use ($kabupaten) {
            // Support both short name (Pontianak) dan full name (Kota Pontianak)
            $q->where('kabupaten', 'like', "%{$kabupaten}%");
        });
    }

    public function scopeBySektor($query, $sektor)
    {
        return $query->when($sektor, fn($q) => $q->where('sektor', $sektor));
    }

    public function scopeByKlasifikasi($query, $klasifikasi)
    {
        return $query->when($klasifikasi, fn($q) => $q->where('klasifikasi', $klasifikasi));
    }

    public function scopeByKecamatan($query, $kecamatan)
    {
        return $query->when($kecamatan, fn($q) => $q->where('kecamatan', $kecamatan));
    }

    /**
     * Batasi data sesuai wilayah pengguna (FR-02):
     * super-admin melihat semua, admin-opd hanya UMKM OPD-nya.
     */
    public function scopeMilikPengguna($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $user->opd_id
            ? $query->where('opd_id', $user->opd_id)
            : $query->whereRaw('1 = 0');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->when($keyword, function ($q) use ($keyword) {
            $q->where(function ($q2) use ($keyword) {
                $q2->where('nama_usaha', 'like', "%{$keyword}%")
                   ->orWhere('deskripsi', 'like', "%{$keyword}%")
                   ->orWhere('sektor', 'like', "%{$keyword}%")
                   ->orWhereHas('produk', fn($p) =>
                       $p->where('nama_produk', 'like', "%{$keyword}%")
                   );
            });
        });
    }

    // ==================== ACCESSORS & MUTATORS ====================
    
    /**
     * Get display status (convert 'unggulan' → 'Unggulan')
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->klasifikasi) {
            'unggulan' => 'Unggulan',
            'berkembang' => 'Berkembang',
            'dasar' => 'Dasar',
            default => ucfirst($this->klasifikasi),
        };
    }

    public function getSektorLabelAttribute(): string
    {
        return self::SEKTOR_LABEL[$this->sektor] ?? ucfirst((string) $this->sektor);
    }

    /**
     * Daftar sektor aktif beserta jumlahnya untuk filter publik:
     * hanya sektor yang benar-benar ada datanya, agar filter tidak kosong.
     *
     * @return array<string, array{label: string, count: int}>
     */
    public static function sektorTersedia($query = null): array
    {
        return ($query ?? static::query()->aktif())
            ->toBase()
            ->reorder()
            ->select('sektor', \Illuminate\Support\Facades\DB::raw('count(*) as jumlah'))
            ->whereNotNull('sektor')
            ->groupBy('sektor')
            ->orderByDesc('jumlah')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->sektor => [
                'label' => self::SEKTOR_LABEL[$r->sektor] ?? ucfirst($r->sektor),
                'count' => (int) $r->jumlah,
            ]])
            ->all();
    }

    /**
     * Get kabupaten lengkap untuk peta (Pontianak → Kota Pontianak)
     */
    public function getKabupatenLengkapAttribute(): string
    {
        return $this->namaKabupatenLengkap($this->kabupaten);
    }

    /**
     * Get WhatsApp link
     */
    public function getWaLinkAttribute(): ?string
    {
        if (!$this->whatsapp) return null;
        $n = preg_replace('/[^0-9]/', '', $this->whatsapp);
        $n = preg_replace('/^0/', '62', $n);
        return "https://wa.me/{$n}";
    }

    // ==================== TAUTAN PUBLIK AMAN ====================
    // Data sosmed/marketplace hasil import bentuknya beragam ("Eduscale.id", "-",
    // "Blibli: https://... Tokopedia:https://..."). Helper ini hanya mengembalikan
    // URL http(s) yang valid, sehingga nilai seperti "javascript:..." tidak pernah
    // sampai ke atribut href.

    public function instagramUrl(): ?string
    {
        $v = trim((string) $this->instagram);
        if ($url = self::cariUrl($v, 'instagram.com')) {
            return $url;
        }
        // Handle tanpa URL, mis. "@kopikapuas" atau "Eduscale.id"
        if (preg_match('/^@?([A-Za-z0-9._]{2,30})$/', $v, $m)) {
            return 'https://www.instagram.com/' . rtrim($m[1], '.') . '/';
        }
        return null;
    }

    // Kolom import "urllink_instagram_usaha_facebook" kadang berisi URL Facebook
    public function facebookUrl(): ?string
    {
        return self::cariUrl((string) $this->facebook, 'facebook.com')
            ?? self::cariUrl((string) $this->instagram, 'facebook.com')
            ?? self::cariUrl((string) $this->instagram, 'fb.com');
    }

    public function marketplaceUrl(string $platform): ?string
    {
        return self::cariUrl((string) $this->{$platform}, $platform);
    }

    public function websiteUrl(): ?string
    {
        $v = trim((string) $this->website);
        if ($url = self::cariUrl($v)) {
            return $url;
        }
        // Domain tanpa skema, mis. "eduscale.id"
        if (preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}(\/\S*)?$/i', $v)) {
            return self::validUrl('https://' . $v);
        }
        return null;
    }

    /**
     * Tautan WhatsApp dengan pesan pembuka (FR-09).
     */
    public function waPesanLink(?string $produk = null): ?string
    {
        if (! $this->wa_link) {
            return null;
        }
        $pesan = $produk
            ? "Halo {$this->nama_usaha}, saya tertarik dengan produk \"{$produk}\" yang saya lihat di UMKMLinked.ID. Apakah masih tersedia?"
            : "Halo {$this->nama_usaha}, saya melihat usaha Anda di UMKMLinked.ID dan ingin bertanya tentang produknya.";

        return $this->wa_link . '?text=' . rawurlencode($pesan);
    }

    private static function cariUrl(string $teks, ?string $domain = null): ?string
    {
        preg_match_all('#https?://[^\s"\'<>]+#i', $teks, $m);
        $urls = array_values(array_filter(array_map(fn ($u) => self::validUrl(rtrim($u, '.,;)')), $m[0])));
        if ($domain) {
            // Ketat: label "Tokopedia" hanya untuk URL ber-domain tokopedia, dst.
            foreach ($urls as $u) {
                if (str_contains(strtolower((string) parse_url($u, PHP_URL_HOST)), $domain)) {
                    return $u;
                }
            }
            return null;
        }
        return $urls[0] ?? null;
    }

    private static function validUrl(string $url): ?string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL)
            ? $url
            : null;
    }

    /**
     * Get analisis singkat untuk peta
     */
    public function getAnalisisAttribute(): string
    {
        return match($this->klasifikasi) {
            'unggulan'   => 'Potensi Ekspor Luas',
            'berkembang' => 'Manajemen Perlu Digitalisasi',
            default      => 'Legalitas Belum Lengkap',
        };
    }

    // ==================== METHODS ====================
    
    /**
     * Format data untuk API peta (bi-map.js)
     */
    public function formatForMap(): array
    {
        return [
            'id'            => $this->id,
            'nama'          => $this->nama_usaha,
            'kabupaten'     => $this->kabupaten,
            'kab'           => $this->kabupaten_lengkap,
            'kecamatan'     => $this->kecamatan,
            'kelurahan'     => $this->kelurahan,
            'sektor'        => ucfirst($this->sektor),
            'sub_sektor'    => $this->sub_sektor,
            'alamat'        => $this->alamat_usaha,
            'telepon'       => $this->telepon,
            'whatsapp'      => $this->whatsapp,
            'email'         => $this->email,
            'website'       => $this->website,
            'skor'          => $this->skor_total,
            'status'        => $this->status_label,
            'lat'           => $this->latitude,
            'lng'           => $this->longitude,
            'tenaga_kerja'  => $this->jumlah_tenaga_kerja,
            'tahun_berdiri' => $this->tahun_berdiri,
            'analisis'      => $this->analisis,
        ];
    }

    /**
     * Map short kabupaten name ke full name
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

    // ==================== LOGGING ====================
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $e) => "UMKM {$this->nama_usaha} di-{$e}");
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}