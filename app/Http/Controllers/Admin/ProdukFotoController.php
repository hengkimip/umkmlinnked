<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdukFotoController extends Controller
{
    public function index()
    {
        return view('admin.produk.upload-foto');
    }

    public function store(Request $request)
    {
        $request->validate([
            'umkm_id'     => ['required', 'exists:umkm,id'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'foto'        => ['required', 'array', 'min:1', 'max:10'],
            'foto.*'      => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
            'harga'       => ['nullable', 'numeric', 'min:0'],
        ], [
            'foto.required'  => 'Minimal 1 foto wajib diupload.',
            'foto.max'       => 'Maksimal 10 foto per produk.',
            'foto.*.mimes'   => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.*.max'     => 'Ukuran foto maksimal 2MB.',
        ]);

        // Cek batas 10 produk per UMKM
        $jumlahProduk = Produk::where('umkm_id', $request->umkm_id)->count();
        if ($jumlahProduk >= 10) {
            return back()->with('error', 'UMKM ini sudah memiliki 10 produk (batas maksimum).');
        }

        $fotos = $request->file('foto');
        $urutan = 1;

        foreach ($fotos as $index => $foto) {
            $path = $foto->store('produk/foto', 'public');

            Produk::create([
                'umkm_id'      => $request->umkm_id,
                'nama_produk'  => $request->nama_produk . ($index > 0 ? " (foto " . ($index + 1) . ")" : ""),
                'foto'         => $path,
                'harga'        => $request->harga,
                'urutan'       => $urutan++,
                'is_unggulan'  => $index === 0, // foto pertama = unggulan
                'is_active'    => true,
            ]);
        }

        return back()->with('success', count($fotos) . " foto produk berhasil diupload untuk UMKM.");
    }

    public function listProduk($umkmId)
    {
        $produk = Produk::where('umkm_id', $umkmId)
            ->orderBy('urutan')
            ->get(['id', 'nama_produk', 'foto', 'urutan', 'is_unggulan']);

        return response()->json($produk->map(fn($p) => [
            'id'          => $p->id,
            'nama_produk' => $p->nama_produk,
            'foto'        => $p->foto,
            'foto_url'    => $p->foto ? Storage::url($p->foto) : null,
            'urutan'      => $p->urutan,
            'is_unggulan' => $p->is_unggulan,
        ]));
    }
}