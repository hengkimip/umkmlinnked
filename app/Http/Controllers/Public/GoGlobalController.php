<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Support\CacheData;
use App\Support\FilterUmkm;
use Illuminate\Http\Request;

class GoGlobalController extends Controller
{
    public function index(Request $request)
    {
        // Kriteria Go Global: jangkauan ekspor/nasional ATAU punya sertifikasi internasional
        $query = Umkm::with(['legalitas', 'produkUnggulan', 'produk', 'pemasaran'])
            ->aktif()
            ->goGlobal();

        // Basis sebelum filter, untuk daftar sektor yang tersedia
        $basis = clone $query;

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

        foreach (['sektor', 'kabupaten', 'klasifikasi'] as $kolom) {
            if (isset($f[$kolom])) {
                $query->where($kolom, $f[$kolom]);
            }
        }

        if (isset($f['jangkauan'])) {
            $query->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar', $f['jangkauan']));
        }

        // Kolom dipetakan dari nilai whitelist, bukan dari input mentah
        if (isset($f['sertifikasi'])) {
            $kolomSert = 'nomor_' . $f['sertifikasi'];
            $query->whereHas('legalitas', fn ($l) => $l->whereNotNull($kolomSert));
        }

        $umkm = $query->orderByDesc('skor_total')
                      ->paginate(12)
                      ->withQueryString();

        // Trending, daftar filter & statistik sama untuk semua pengunjung → di-cache
        // Cache hanya berisi array/angka; model trending dimuat ulang dari ID-nya
        ['trending' => $trendingId, 'kabupatenList' => $kabupatenList, 'kategoriList' => $kategoriList, 'jumlah' => $jumlah]
            = CacheData::ingat('go-global:samping', 600, function () use ($basis) {
                // Trending Go Global; bila tidak ada yang ekspor/nasional, ambil yang bersertifikat
                $trending = Umkm::aktif()
                    ->whereHas('pemasaran', fn($p) => $p->whereIn('jangkauan_pasar', ['ekspor', 'nasional']))
                    ->orderByDesc('skor_total')->limit(8)->pluck('id')->all();

                if (! $trending) {
                    $trending = Umkm::aktif()
                        ->whereHas('legalitas', fn($l) => $l->where(fn ($s) => $s->whereNotNull('nomor_halal')->orWhereNotNull('nomor_bpom')))
                        ->orderByDesc('skor_total')->limit(8)->pluck('id')->all();
                }

                return [
                    'trending' => $trending,
                    'kabupatenList' => Umkm::aktif()->distinct()->orderBy('kabupaten')
                        ->pluck('kabupaten')->filter()->values()->all(),
                    // Hanya sektor yang benar-benar ada di cakupan Go Global
                    'kategoriList' => Umkm::sektorTersedia(clone $basis),
                    'jumlah' => [
                        'ekspor'   => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar', 'ekspor'))->count(),
                        'nasional' => Umkm::aktif()->whereHas('pemasaran', fn($p) => $p->where('jangkauan_pasar', 'nasional'))->count(),
                        'halal'    => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_halal'))->count(),
                        'bpom'     => Umkm::aktif()->whereHas('legalitas', fn($l) => $l->whereNotNull('nomor_bpom'))->count(),
                    ],
                ];
            });

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
            ['value' => $jumlah['ekspor'],   'label' => 'Ekspor'],
            ['value' => $jumlah['nasional'], 'label' => 'Nasional'],
            ['value' => $jumlah['halal'],    'label' => 'Bersertifikat Halal'],
            ['value' => $jumlah['bpom'],     'label' => 'Terdaftar BPOM'],
        ];

        $trending = Umkm::muatUrut($trendingId, ['produkUnggulan', 'produk', 'legalitas']);

        return view('public.go-global', compact(
            'umkm', 'trending', 'kabupatenList',
            'kategoriList', 'jangkauanList', 'sertifikasiList', 'stats'
        ));
    }
}