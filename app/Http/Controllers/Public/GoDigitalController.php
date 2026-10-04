<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Support\CacheData;
use App\Support\FilterUmkm;
use Illuminate\Http\Request;

class GoDigitalController extends Controller
{
    public function index(Request $request)
    {
        // Query dasar: UMKM yang siap Go Digital
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk', 'pemasaran'])
            ->aktif()
            ->goDigital();

        // Basis sebelum filter, untuk daftar sektor yang tersedia
        $basis = clone $query;

        // Hanya parameter yang lolos whitelist yang dipakai (lihat FilterUmkm)
        $f = FilterUmkm::dari($request);

        if (isset($f['q'])) {
            $pola = FilterUmkm::like($f['q']);
            $query->where(function ($sub) use ($pola) {
                $sub->where('nama_usaha', 'like', $pola)
                    ->orWhere('sektor', 'like', $pola)
                    ->orWhereHas('produk', fn($p) => $p->where('nama_produk', 'like', $pola));
            });
        }

        foreach (['sektor', 'kabupaten', 'klasifikasi'] as $kolom) {
            if (isset($f[$kolom])) {
                $query->where($kolom, $f[$kolom]);
            }
        }

        // Filter: Platform spesifik (nilai sudah dibatasi ke FilterUmkm::PLATFORM)
        if (isset($f['platform'])) {
            $platform = $f['platform'];
            $query->where(function ($q) use ($platform) {
                if (in_array($platform, ['tokopedia', 'shopee', 'instagram', 'whatsapp'], true)) {
                    $q->whereNotNull($platform);
                } else {
                    $q->whereHas('pemasaran', fn($p) => $p->where('platform_online', 'like', FilterUmkm::like($platform)));
                }
            });
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Trending, daftar filter & statistik kanal sama untuk semua pengunjung → di-cache
        ['trending' => $trendingId, 'kabupatenList' => $kabupatenList, 'kategoriList' => $kategoriList, 'agg' => $agg]
            = CacheData::ingat('go-digital:samping', 600, fn () => [
                // Trending Go Digital: skor tertinggi
                'trending' => Umkm::aktif()
                    ->where(fn ($q) => $q->whereNotNull('tokopedia')->orWhereNotNull('shopee')
                        ->orWhereNotNull('instagram')->orWhereNotNull('whatsapp'))
                    ->orderByDesc('skor_total')->limit(8)->pluck('id')->all(),
                'kabupatenList' => Umkm::aktif()->distinct()->orderBy('kabupaten')
                    ->pluck('kabupaten')->filter()->values()->all(),
                // Hanya sektor yang benar-benar ada di cakupan Go Digital
                'kategoriList' => Umkm::sektorTersedia(clone $basis),
                'agg' => (array) Umkm::aktif()->toBase()->selectRaw('
                    sum(case when tokopedia is not null then 1 else 0 end) as tokopedia,
                    sum(case when shopee is not null then 1 else 0 end) as shopee,
                    sum(case when instagram is not null then 1 else 0 end) as instagram,
                    sum(case when whatsapp is not null then 1 else 0 end) as whatsapp
                ')->first(),
            ]);

        $platformList = [
            'tokopedia' => 'Tokopedia',
            'shopee'    => 'Shopee',
            'instagram' => 'Instagram',
            'whatsapp'  => 'WhatsApp Bisnis',
            'tiktok'    => 'TikTok Shop',
        ];

        // Statistik Go Digital
        $stats = [
            ['value' => $umkm->total(),               'label' => 'UMKM Go Digital', 'highlight' => true],
            ['value' => (int) $agg['tokopedia'],      'label' => 'Tokopedia'],
            ['value' => (int) $agg['shopee'],         'label' => 'Shopee'],
            ['value' => (int) $agg['instagram'],      'label' => 'Instagram'],
            ['value' => (int) $agg['whatsapp'],       'label' => 'WhatsApp'],
        ];

        // Cache hanya berisi ID; model trending dimuat ulang dalam urutan yang sama
        $trending = Umkm::muatUrut($trendingId, ['produkUnggulan', 'produk']);

        return view('public.go-digital', compact(
            'umkm', 'trending', 'kabupatenList',
            'kategoriList', 'platformList', 'stats'
        ));
    }
}