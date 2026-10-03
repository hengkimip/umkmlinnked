<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoDigitalController extends Controller
{
    public function index(Request $request)
    {
        // Query dasar: UMKM yang siap Go Digital
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk', 'pemasaran'])
            ->aktif()
            ->where(function ($q) {
                $q->whereNotNull('tokopedia')
                  ->orWhereNotNull('shopee')
                  ->orWhereNotNull('instagram')
                  ->orWhereNotNull('whatsapp')
                  ->orWhereHas('pemasaran', fn($p) =>
                      $p->whereNotNull('platform_online')
                        ->where('platform_online', '!=', '[]')
                        ->where('platform_online', '!=', 'null')
                  );
            });

        // Filter: Search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_usaha', 'like', "%{$q}%")
                    ->orWhere('sektor', 'like', "%{$q}%")
                    ->orWhereHas('produk', fn($p) =>
                        $p->where('nama_produk', 'like', "%{$q}%")
                    );
            });
        }

        // Filter: Kategori sektor
        if ($request->filled('sektor')) {
            $query->where('sektor', $request->sektor);
        }

        // Filter: Kabupaten
        if ($request->filled('kabupaten')) {
            $query->where('kabupaten', $request->kabupaten);
        }

        // Filter: Platform spesifik
        if ($request->filled('platform')) {
            $platform = $request->platform;
            $query->where(function ($q) use ($platform) {
                if ($platform === 'tokopedia') {
                    $q->whereNotNull('tokopedia');
                } elseif ($platform === 'shopee') {
                    $q->whereNotNull('shopee');
                } elseif ($platform === 'instagram') {
                    $q->whereNotNull('instagram');
                } elseif ($platform === 'whatsapp') {
                    $q->whereNotNull('whatsapp');
                } else {
                    $q->whereHas('pemasaran', fn($p) =>
                        $p->where('platform_online', 'like', "%{$platform}%")
                    );
                }
            });
        }

        // Filter: Klasifikasi
        if ($request->filled('klasifikasi')) {
            $query->where('klasifikasi', $request->klasifikasi);
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Trending Go Digital: skor tertinggi
        $trending = Umkm::with(['produkUnggulan', 'produk'])
            ->aktif()
            ->where(function ($q) {
                $q->whereNotNull('tokopedia')
                  ->orWhereNotNull('shopee')
                  ->orWhereNotNull('instagram')
                  ->orWhereNotNull('whatsapp');
            })
            ->orderByDesc('skor_total')
            ->limit(8)
            ->get();

        // Data sidebar
        $kabupatenList = Umkm::aktif()
            ->distinct()
            ->orderBy('kabupaten')
            ->pluck('kabupaten')
            ->filter()
            ->values();

        $kategoriList = [
            'kuliner'    => 'Makanan Berat dan Bumbu',
            'minuman'    => 'Minuman dan Madu',
            'camilan'    => 'Camilan dan Kue',
            'fashion'    => 'Pakaian',
            'aksesoris'  => 'Aksesoris',
            'kerajinan'  => 'Kerajinan',
            'pertanian'  => 'Pertanian & Agro',
            'jasa'       => 'Jasa & Layanan',
        ];

        $platformList = [
            'tokopedia' => 'Tokopedia',
            'shopee'    => 'Shopee',
            'instagram' => 'Instagram',
            'whatsapp'  => 'WhatsApp Bisnis',
            'tiktok'    => 'TikTok Shop',
        ];

        // Statistik Go Digital
        $stats = [
            'total'      => $query->count(),
            'tokopedia'  => Umkm::aktif()->whereNotNull('tokopedia')->count(),
            'shopee'     => Umkm::aktif()->whereNotNull('shopee')->count(),
            'instagram'  => Umkm::aktif()->whereNotNull('instagram')->count(),
            'whatsapp'   => Umkm::aktif()->whereNotNull('whatsapp')->count(),
        ];

        return view('public.go-digital', compact(
            'umkm', 'trending', 'kabupatenList',
            'kategoriList', 'platformList', 'stats'
        ));
    }
}