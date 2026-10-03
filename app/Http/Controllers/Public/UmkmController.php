<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk'])
            ->aktif();

        if ($request->filled('q')) {
            $q = mb_substr(trim((string) $request->q), 0, 100);
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_usaha', 'like', "%{$q}%")
                    ->orWhere('sektor', 'like', "%{$q}%")
                    ->orWhere('kabupaten', 'like', "%{$q}%")
                    ->orWhereHas('produk', fn($p) =>
                        $p->where('nama_produk', 'like', "%{$q}%")
                    );
            });
        }

        if ($request->filled('sektor')) {
            $query->where('sektor', $request->sektor);
        }

        if ($request->filled('kabupaten')) {
            $query->where('kabupaten', $request->kabupaten);
        }

        if ($request->filled('klasifikasi')) {
            $query->where('klasifikasi', $request->klasifikasi);
        }

        if ($request->filled('harga_min')) {
            $query->whereHas('produk', fn($p) =>
                $p->where('harga', '>=', (int)$request->harga_min)
            );
        }

        if ($request->filled('harga_max')) {
            $query->whereHas('produk', fn($p) =>
                $p->where('harga', '<=', (int)$request->harga_max)
            );
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Trending — query langsung tanpa cache
        $trending = Umkm::with(['produkUnggulan', 'produk'])
            ->aktif()
            ->orderByDesc('skor_total')
            ->limit(8)
            ->get();

        // Data filter sidebar — query langsung
        $kabupatenList = Umkm::aktif()
            ->distinct()
            ->orderBy('kabupaten')
            ->pluck('kabupaten')
            ->filter()
            ->values();

        // Kategori sidebar: hanya sektor yang benar-benar ada datanya
        $sektorList = Umkm::sektorTersedia();

        // Statistik dalam satu query agregat
        $kosong = Umkm::KABUPATEN_KOSONG;
        $agg = Umkm::aktif()->toBase()->selectRaw("
            count(*) as total,
            sum(case when klasifikasi = 'unggulan' then 1 else 0 end) as unggulan,
            sum(case when klasifikasi = 'berkembang' then 1 else 0 end) as berkembang,
            sum(case when instagram is not null then 1 else 0 end) as digital,
            count(distinct case when kabupaten <> ? then kabupaten end) as kabupaten
        ", [$kosong])->first();

        $stats = [
            ['value' => (int) $agg->total,      'label' => 'Total UMKM', 'highlight' => true],
            ['value' => (int) $agg->unggulan,   'label' => 'Unggulan'],
            ['value' => (int) $agg->berkembang, 'label' => 'Berkembang'],
            ['value' => (int) $agg->digital,    'label' => 'Go Digital'],
            ['value' => (int) $agg->kabupaten,  'label' => 'Kabupaten/Kota'],
        ];

        return view('public.direktori', compact(
            'umkm', 'trending', 'kabupatenList', 'sektorList', 'stats'
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