<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;

class GoGlobalController extends Controller
{
    public function index(Request $request)
    {
        // Kriteria Go Global: jangkauan ekspor/nasional ATAU punya sertifikasi internasional
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk', 'pemasaran'])
            ->aktif()
            ->where(function ($q) {
                $q->whereHas('pemasaran', fn($p) =>
                    $p->whereIn('jangkauan_pasar', ['ekspor', 'nasional', 'regional'])
                )
                ->orWhereHas('legalitas', fn($l) =>
                    $l->whereNotNull('nomor_halal')
                      ->orWhereNotNull('nomor_bpom')
                      ->orWhereNotNull('nomor_sni')
                );
            });

        // Filter: Search
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

        // Filter: Sektor
        if ($request->filled('sektor')) {
            $query->where('sektor', $request->sektor);
        }

        // Filter: Kabupaten
        if ($request->filled('kabupaten')) {
            $query->where('kabupaten', $request->kabupaten);
        }

        // Filter: Jangkauan pasar
        if ($request->filled('jangkauan')) {
            $query->whereHas('pemasaran', fn($p) =>
                $p->where('jangkauan_pasar', $request->jangkauan)
            );
        }

        // Filter: Sertifikasi
        if ($request->filled('sertifikasi')) {
            $sert = $request->sertifikasi;
            $query->whereHas('legalitas', function ($l) use ($sert) {
                match($sert) {
                    'halal' => $l->whereNotNull('nomor_halal'),
                    'bpom'  => $l->whereNotNull('nomor_bpom'),
                    'pirt'  => $l->whereNotNull('nomor_pirt'),
                    'sni'   => $l->whereNotNull('nomor_sni'),
                    default => $l->whereNotNull('nomor_halal'),
                };
            });
        }

        // Filter: Klasifikasi
        if ($request->filled('klasifikasi')) {
            $query->where('klasifikasi', $request->klasifikasi);
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Trending Go Global
        $trending = Umkm::with(['produkUnggulan', 'produk', 'legalitas'])
            ->aktif()
            ->whereHas('pemasaran', fn($p) =>
                $p->whereIn('jangkauan_pasar', ['ekspor', 'nasional'])
            )
            ->orderByDesc('skor_total')
            ->limit(8)
            ->get();

        // Jika tidak ada yang ekspor/nasional, ambil yang punya sertifikasi
        if ($trending->isEmpty()) {
            $trending = Umkm::with(['produkUnggulan', 'legalitas'])
                ->aktif()
                ->whereHas('legalitas', fn($l) =>
                    $l->whereNotNull('nomor_halal')
                      ->orWhereNotNull('nomor_bpom')
                )
                ->orderByDesc('skor_total')
                ->limit(8)
                ->get();
        }

        // Data sidebar
        $kabupatenList = Umkm::aktif()
            ->distinct()
            ->orderBy('kabupaten')
            ->pluck('kabupaten')
            ->filter()
            ->values();

        // Kategori Go Global
        $kategoriList = [
            'kuliner'    => 'Pangan Olahan dalam Kemasan',
            'kerajinan'  => 'Kerajinan dan Furnitur',
            'fashion'    => 'Fesyen dan Aksesoris',
            'pertanian'  => 'Komoditas dan Agro',
            'kecantikan' => 'Kecantikan dan Perawatan Tubuh',
            'perikanan'  => 'Produk Kelautan & Perikanan',
            'minuman'    => 'Minuman dan Herbal',
        ];

        // Jangkauan pasar
        $jangkauanList = [
            'ekspor'   => '🌍 Ekspor (Internasional)',
            'nasional' => '🇮🇩 Nasional',
            'regional' => '🗺️ Regional (Antar Provinsi)',
        ];

        // Sertifikasi
        $sertifikasiList = [
            'halal' => '🌙 Halal MUI',
            'bpom'  => '🏥 BPOM',
            'pirt'  => '📋 PIRT',
            'sni'   => '🏆 SNI',
        ];

        // Statistik Go Global
        $stats = [
            'total'    => $query->count(),
            'ekspor'   => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar','ekspor'))->count(),
            'nasional' => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar','nasional'))->count(),
            'halal'    => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_halal'))->count(),
            'bpom'     => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_bpom'))->count(),
        ];

        return view('public.go-global', compact(
            'umkm', 'trending', 'kabupatenList',
            'kategoriList', 'jangkauanList', 'sertifikasiList', 'stats'
        ));
    }
}