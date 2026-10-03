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