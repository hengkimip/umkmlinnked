<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk'])
            ->aktif();

        if ($request->filled('q')) {
            $q = $request->q;
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

        $sektorList = Umkm::aktif()
            ->distinct()
            ->orderBy('sektor')
            ->pluck('sektor')
            ->filter()
            ->values();

        // Kategori sidebar
        $kategoriGoDigital = [
            'kuliner'    => 'Makanan Berat dan Bumbu',
            'minuman'    => 'Minuman dan Madu',
            'camilan'    => 'Camilan dan Kue',
            'fashion'    => 'Pakaian',
            'aksesoris'  => 'Aksesoris',
            'kerajinan'  => 'Kerajinan',
        ];

        $kategoriGoGlobal = [
            'pangan'     => 'Pangan Olahan dalam Kemasan',
            'furnitur'   => 'Kerajinan dan Furnitur',
            'fesyen'     => 'Fesyen dan Aksesoris',
            'komoditas'  => 'Komoditas dan Agro',
            'kecantikan' => 'Kecantikan dan Perawatan Tubuh',
        ];

        // Program list dari sektor aktual di database
        $programList = DB::table('umkm')
            ->whereNotNull('sektor')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('sektor')
            ->pluck('sektor')
            ->filter()
            ->values();

        return view('public.direktori', compact(
            'umkm', 'trending', 'kabupatenList', 'sektorList',
            'kategoriGoDigital', 'kategoriGoGlobal', 'programList'
        ));
    }

    public function show(Umkm $umkm)
    {
        abort_if($umkm->status !== 'aktif', 404);

        $umkm->load([
            'pemilik', 'opd', 'produk', 'pemasaran',
            'legalitas', 'keuanganTerakhir', 'pembiayaan'
        ]);

        $related = Umkm::with('produkUnggulan')
            ->aktif()
            ->where('sektor', $umkm->sektor)
            ->where('id', '!=', $umkm->id)
            ->limit(4)
            ->get();

        return view('public.detail', compact('umkm', 'related'));
    }
}