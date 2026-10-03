<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;

class TentangController extends Controller
{
    public function index()
    {
        $agg = Umkm::aktif()->toBase()->selectRaw("
            count(*) as total,
            count(distinct case when kabupaten <> ? then kabupaten end) as kabupaten,
            count(distinct sektor) as sektor,
            sum(case when instagram is not null or tokopedia is not null or shopee is not null then 1 else 0 end) as digital
        ", [Umkm::KABUPATEN_KOSONG])->first();

        $stats = [
            ['value' => (int) $agg->total,     'label' => 'UMKM terdata', 'highlight' => true],
            ['value' => (int) $agg->kabupaten, 'label' => 'Kabupaten/Kota'],
            ['value' => (int) $agg->sektor,    'label' => 'Sektor usaha'],
            ['value' => (int) $agg->digital,   'label' => 'Sudah go digital'],
        ];

        return view('public.tentang', compact('stats'));
    }
}
