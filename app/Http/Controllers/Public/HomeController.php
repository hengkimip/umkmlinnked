<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Support\CacheData;

/**
 * Beranda: etalase produk UMKM dalam tiga baris — Semua Brand, Go Digital, Go Global.
 * Setiap baris menampilkan 4 produk acak dan berganti otomatis dari kartu cadangan.
 */
class HomeController extends Controller
{
    // Empat produk berjejer per baris kategori
    public const PER_BARIS = 4;

    // Kartu per baris (tampil + cadangan rotasi) dan jumlah kandidat yang diundi
    private const PER_KOLAM  = 16;
    private const KANDIDAT   = 60;
    private const CACHE_DETIK = 600;

    public function index()
    {
        $kategori = [
            ['kunci' => 'semua-brand', 'judul' => 'Semua Brand', 'tema' => 'navy', 'ikon' => 'sparkles',
             'sub' => 'Produk pilihan dari UMKM binaan KPw Bank Indonesia di 14 kabupaten/kota Kalimantan Barat.',
             'url' => route('direktori.index'), 'lencana' => 'klasifikasi'],
            ['kunci' => 'go-digital', 'judul' => 'Go Digital', 'tema' => 'teal', 'ikon' => 'globe',
             'sub' => 'Sudah hadir di marketplace & media sosial — pesan langsung dari kanal favorit Anda.',
             'url' => route('godigital.index'), 'lencana' => 'digital'],
            ['kunci' => 'go-global', 'judul' => 'Go Global', 'tema' => 'gold', 'ikon' => 'shield-check',
             'sub' => 'Bersertifikat & menjangkau pasar nasional hingga ekspor — siap bersaing lebih luas.',
             'url' => route('goglobal.index'), 'lencana' => 'global'],
        ];

        // Kandidat (ID) & total per kategori di-cache; dibatalkan otomatis saat data berubah
        $data = CacheData::ingat('beranda', self::CACHE_DETIK, fn () => $this->dataKategori());

        // Undi kolam acak per baris; 4 kartu awal tiap baris tidak sama dengan baris lain
        $sudahTampil = [];
        $kolam = [];
        foreach ($kategori as $k) {
            $kandidat = collect($data[$k['kunci']]['kandidat'])->shuffle();
            $awal     = $kandidat->diff($sudahTampil)->take(self::PER_BARIS);
            $awal     = $awal->concat($kandidat->diff($awal)->take(self::PER_BARIS - $awal->count()));
            array_push($sudahTampil, ...$awal);

            $kolam[$k['kunci']] = [
                'awal'     => $awal->values()->all(),
                'cadangan' => $kandidat->diff($awal)->take(self::PER_KOLAM - $awal->count())->values()->all(),
            ];
        }

        // Satu query untuk semua UMKM yang dipakai di ketiga baris
        $model = Umkm::with([
                'produk' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_unggulan')->orderBy('urutan'),
                'legalitas', 'pemasaran',
            ])
            ->findMany(collect($kolam)->flatMap(fn ($k) => [...$k['awal'], ...$k['cadangan']])->unique()->all())
            ->keyBy('id');

        $ambil = fn (array $id) => collect($id)->map(fn ($i) => $model->get($i))->filter()->values();

        $baris = array_map(fn ($k) => $k + [
            'total'    => $data[$k['kunci']]['total'],
            'umkm'     => $ambil($kolam[$k['kunci']]['awal']),
            'cadangan' => $ambil($kolam[$k['kunci']]['cadangan']),
        ], $kategori);

        $stats = [
            ['value' => $baris[0]['total'], 'label' => 'Brand UMKM', 'highlight' => true],
            ['value' => $baris[1]['total'], 'label' => 'Go Digital'],
            ['value' => $baris[2]['total'], 'label' => 'Go Global'],
            ['value' => $data['kabupaten'],  'label' => 'Kabupaten/Kota'],
        ];

        return view('public.home', compact('baris', 'stats'));
    }

    /**
     * Kandidat per kategori: UMKM berfoto dan berskor tertinggi didahulukan,
     * Go Digital mendahulukan yang hadir di lebih banyak kanal marketplace/media sosial.
     */
    private function dataKategori(): array
    {
        $kanalDigital = '(tokopedia is not null) + (shopee is not null) + (instagram is not null) + (facebook is not null)';

        // get() (bukan pluck) agar kolom urutan "punya_foto" dari fotoDulu() tetap ada di SELECT
        $kandidat = fn ($query) => $query->fotoDulu()->orderByDesc('skor_total')
            ->limit(self::KANDIDAT)->get()->modelKeys();

        return [
            'semua-brand' => [
                'total'    => Umkm::aktif()->count(),
                'kandidat' => $kandidat(Umkm::aktif()),
            ],
            'go-digital' => [
                'total'    => Umkm::aktif()->goDigital()->count(),
                'kandidat' => $kandidat(Umkm::aktif()->goDigital()->orderByRaw("{$kanalDigital} desc")),
            ],
            'go-global' => [
                'total'    => Umkm::aktif()->goGlobal()->count(),
                'kandidat' => $kandidat(Umkm::aktif()->goGlobal()),
            ],
            'kabupaten' => Umkm::aktif()
                ->where('kabupaten', '!=', Umkm::KABUPATEN_KOSONG)
                ->distinct()
                ->count('kabupaten'),
        ];
    }
}
