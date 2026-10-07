<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Berita;

/**
 * Berita official dari Super Admin (dikelola di /superadmin/berita).
 * Hanya berita berstatus terbit yang tanggal terbitnya sudah lewat yang tampil.
 */
class BeritaController extends Controller
{
    public function index()
    {
        $berita = Berita::terbit()->orderByDesc('terbit_pada')->orderByDesc('id')->paginate(9);

        return view('public.berita.index', compact('berita'));
    }

    public function show(string $slug)
    {
        $berita = Berita::terbit()->with('penulis:id,name')->where('slug', $slug)->firstOrFail();

        $lainnya = Berita::terbit()->whereKeyNot($berita->id)
            ->orderByDesc('terbit_pada')->limit(3)->get();

        return view('public.berita.show', compact('berita', 'lainnya'));
    }
}
