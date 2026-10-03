<?php
// app/Http/Controllers/Public/UmkmController.php
namespace App\Http\Controllers\Public;
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $umkm = Umkm::with(['pemilik', 'produkUnggulan', 'legalitas'])
            ->aktif()
            ->search($request->q)
            ->byKabupaten($request->kabupaten)
            ->bySektor($request->sektor)
            ->byKlasifikasi($request->klasifikasi)
            ->orderByDesc('skor_total')
            ->paginate(12)
            ->withQueryString();

        // Cache data filter (kabupaten, sektor)
        $kabupatenList = Cache::remember('kabupaten_list', 3600, fn() =>
            Umkm::aktif()->distinct()->pluck('kabupaten')->sort()->values()
        );
        $sektorList = Cache::remember('sektor_list', 3600, fn() =>
            Umkm::aktif()->distinct()->pluck('sektor')->sort()->values()
        );

        return view('public.direktori', compact('umkm', 'kabupatenList', 'sektorList'));
    }

    public function show(Umkm $umkm)
    {
        // Hanya tampilkan UMKM aktif untuk publik
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