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
                ->orWhereHas('legalitas', fn($l) => $l->where(fn ($s) =>
                    $s->whereNotNull('nomor_halal')
                      ->orWhereNotNull('nomor_bpom')
                      ->orWhereNotNull('nomor_sni')
                ));
            });

        // Basis sebelum filter, untuk daftar sektor yang tersedia
        $basis = clone $query;

        // Filter: Search
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
                ->whereHas('legalitas', fn($l) => $l->where(fn ($s) =>
                    $s->whereNotNull('nomor_halal')
                      ->orWhereNotNull('nomor_bpom')
                ))
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
        // Hanya sektor yang benar-benar ada di cakupan Go Global
        $kategoriList = Umkm::sektorTersedia($basis);

        // Jangkauan pasar
        $jangkauanList = [
            'ekspor'   => 'Ekspor (internasional)',
            'nasional' => 'Nasional',
            'regional' => 'Regional (antarprovinsi)',
        ];

        // Sertifikasi
        $sertifikasiList = [
            'halal' => 'Halal',
            'bpom'  => 'BPOM',
            'pirt'  => 'PIRT',
            'sni'   => 'SNI',
        ];

        // Statistik Go Global
        $stats = [
            ['value' => $umkm->total(), 'label' => 'UMKM Go Global', 'highlight' => true],
            ['value' => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar', 'ekspor'))->count(),   'label' => 'Ekspor'],
            ['value' => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar', 'nasional'))->count(), 'label' => 'Nasional'],
            ['value' => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_halal'))->count(),          'label' => 'Bersertifikat Halal'],
            ['value' => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_bpom'))->count(),           'label' => 'Terdaftar BPOM'],
        ];

        return view('public.go-global', compact(
            'umkm', 'trending', 'kabupatenList',
            'kategoriList', 'jangkauanList', 'sertifikasiList', 'stats'
        ));
    }
}