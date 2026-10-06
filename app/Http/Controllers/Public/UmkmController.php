<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Support\CacheData;
use App\Support\FilterUmkm;
use App\Support\TagUmkm;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk'])
            ->aktif();

        // Hanya parameter yang lolos whitelist yang dipakai (lihat FilterUmkm)
        $f = FilterUmkm::dari($request);

        if (isset($f['q'])) {
            $pola = FilterUmkm::like($f['q']);
            $query->where(function ($sub) use ($pola) {
                $sub->where('nama_usaha', 'like', $pola)
                    ->orWhere('sektor', 'like', $pola)
                    ->orWhere('kabupaten', 'like', $pola)
                    ->orWhereHas('produk', fn($p) => $p->where('nama_produk', 'like', $pola));
            });
        }

        foreach (['kabupaten', 'klasifikasi'] as $kolom) {
            if (isset($f[$kolom])) {
                $query->where($kolom, $f[$kolom]);
            }
        }

        // Sektor Usaha, Platform Digital, Jangkauan Pasar, Sertifikasi Produk (banyak pilihan; sama dengan peta)
        FilterUmkm::terapkanTag($query, $f);

        if (isset($f['harga_min'])) {
            $query->whereHas('produk', fn($p) => $p->where('harga', '>=', $f['harga_min']));
        }

        if (isset($f['harga_max'])) {
            $query->whereHas('produk', fn($p) => $p->where('harga', '<=', $f['harga_max']));
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Daftar filter & statistik sama untuk semua pengunjung → di-cache
        // (dibatalkan otomatis saat data UMKM berubah, lihat CacheData)
        ['kabupatenList' => $kabupatenList, 'stats' => $stats]
            = CacheData::ingat('semua-brand:samping', 600, function () {
                $kosong = Umkm::KABUPATEN_KOSONG;
                $agg = Umkm::aktif()->toBase()->selectRaw("
                    count(*) as total,
                    sum(case when klasifikasi = 'unggulan' then 1 else 0 end) as unggulan,
                    sum(case when klasifikasi = 'berkembang' then 1 else 0 end) as berkembang,
                    sum(case when instagram is not null then 1 else 0 end) as digital,
                    count(distinct case when kabupaten <> ? then kabupaten end) as kabupaten
                ", [$kosong])->first();

                return [
                    'kabupatenList' => Umkm::aktif()->distinct()->orderBy('kabupaten')
                        ->pluck('kabupaten')->filter()->values()->all(),
                    'stats' => [
                        ['value' => (int) $agg->total,      'label' => 'Total UMKM', 'highlight' => true],
                        ['value' => (int) $agg->unggulan,   'label' => 'Unggulan'],
                        ['value' => (int) $agg->berkembang, 'label' => 'Berkembang'],
                        ['value' => (int) $agg->digital,    'label' => 'Go Digital'],
                        ['value' => (int) $agg->kabupaten,  'label' => 'Kabupaten/Kota'],
                    ],
                ];
            });

        // Kotak centang filter (pilihan & jumlah sama dengan Peta Interaktif)
        $filterTag = TagUmkm::FILTER;
        $jumlahTag = TagUmkm::jumlah();

        return view('public.direktori', compact(
            'umkm', 'kabupatenList', 'stats', 'filterTag', 'jumlahTag'
        ));
    }

    public function show(Umkm $umkm)
    {
        abort_if($umkm->status !== 'aktif', 404);

        // Hanya relasi yang ditampilkan publik. Pemilik, keuangan, dan
        // pembiayaan sengaja tidak dimuat (NFR-02, minimisasi data).
        $umkm->load([
            'produk' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_unggulan')->orderBy('urutan'),
            'pemasaran', 'legalitas',
        ]);

        $related = Umkm::with(['produkUnggulan', 'produk'])
            ->aktif()
            ->where('sektor', $umkm->sektor)
            ->where('id', '!=', $umkm->id)
            ->orderByDesc('skor_total')
            ->limit(4)
            ->get();

        return view('public.detail', compact('umkm', 'related'));
    }
}