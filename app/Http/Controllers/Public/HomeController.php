<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Support\CacheData;
use App\Support\RingkasanData;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Beranda: satu kelompok "Semua Brand" — seluruh UMKM aktif, diacak setiap kunjungan,
 * 4 produk per baris dan dibagi per halaman. Baris yang tampil di layar berganti acak dengan
 * kartu dari baris lain di halaman yang sama (lihat initRotasiProduk), jadi setiap UMKM tetap tampil tepat sekali.
 */
class HomeController extends Controller
{
    // Empat produk berjejer per baris
    public const PER_BARIS = 4;

    // 6 baris × 4 produk per halaman
    public const PER_HALAMAN = 24;

    private const CACHE_DETIK = 600;
    private const KUNCI_ACAK  = 'beranda.acak';

    public function index(Request $request)
    {
        // ID UMKM (berfoto / tanpa foto) & jumlah kabupaten di-cache; dibatalkan otomatis saat data berubah
        $data = CacheData::ingat('beranda:urutan', self::CACHE_DETIK, fn () => $this->dataBeranda());

        // Urutan acak baru setiap beranda dibuka tanpa nomor halaman; kunci acak disimpan di sesi
        // agar halaman 2, 3, … melanjutkan urutan yang sama (tidak ada UMKM dobel/terlewat antarhalaman)
        $kunci = $request->session()->get(self::KUNCI_ACAK);
        if (! $request->has('page') || ! is_string($kunci)) {
            $kunci = bin2hex(random_bytes(8));
            $request->session()->put(self::KUNCI_ACAK, $kunci);
        }
        $acak = fn (array $id) => collect($id)->sortBy(fn ($i) => hash('xxh3', "{$kunci}:{$i}"))->values();

        // UMKM berfoto didahulukan agar etalase atas tetap menarik
        $urutan = $acak($data['berfoto'])->concat($acak($data['tanpaFoto']));

        $halaman   = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $urutan->forPage($halaman, self::PER_HALAMAN)->values(),
            $urutan->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => route('home'), 'pageName' => 'page'],
        );
        $paginator->fragment('semua-brand');

        // Nomor halaman melebihi jumlah halaman → kembali ke halaman terakhir
        if ($halaman > 1 && $paginator->isEmpty() && $urutan->isNotEmpty()) {
            return redirect($paginator->url($paginator->lastPage()));
        }

        $model = Umkm::with([
                'produk' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_unggulan')->orderBy('urutan'),
                'legalitas', 'pemasaran',
            ])
            ->findMany($paginator->all())
            ->keyBy('id');

        $semuaBrand = [
            'judul' => 'Semua Brand',
            'sub'   => 'Produk pilihan dari UMKM Binaan Kantor Perwakilan Bank Indonesia Provinsi Kalimantan Barat di 14 kabupaten/kota.',
            'url'   => route('direktori.index'),
            'total' => $urutan->count(),
            'baris' => collect($paginator->items())->map(fn ($id) => $model->get($id))->filter()->chunk(self::PER_BARIS)->values(),
        ];

        $stats = RingkasanData::publik();

        return view('public.home', compact('semuaBrand', 'stats', 'paginator'));
    }

    private function dataBeranda(): array
    {
        $umkm = Umkm::aktif()->select('umkm.id')->fotoDulu()->toBase()->get()
            ->groupBy(fn ($u) => $u->punya_foto ? 'berfoto' : 'tanpaFoto');

        return [
            'berfoto'   => $umkm->get('berfoto', collect())->pluck('id')->all(),
            'tanpaFoto' => $umkm->get('tanpaFoto', collect())->pluck('id')->all(),
        ];
    }
}
