<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProdukFotoController extends Controller
{
    private const MAKS_PRODUK = 10;

    public function index(Request $request)
    {
        $daftarUmkm = Umkm::milikPengguna($request->user())
            ->orderBy('nama_usaha')
            ->get(['id', 'nama_usaha', 'kabupaten']);

        return view('admin.produk.upload-foto', [
            'daftarUmkm' => $daftarUmkm,
            'maksProduk' => self::MAKS_PRODUK,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'umkm_id'     => ['required', 'integer', 'exists:umkm,id'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'foto'        => ['required', 'array', 'min:1', 'max:' . self::MAKS_PRODUK],
            'foto.*'      => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'harga'       => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ], [
            'umkm_id.required'     => 'Pilih UMKM terlebih dahulu.',
            'umkm_id.exists'       => 'UMKM tidak ditemukan.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'foto.required'        => 'Minimal 1 foto wajib diupload.',
            'foto.max'             => 'Maksimal ' . self::MAKS_PRODUK . ' foto per unggahan.',
            'foto.*.image'         => 'File harus berupa gambar.',
            'foto.*.mimes'         => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.*.max'           => 'Ukuran foto maksimal 2 MB.',
            'harga.numeric'        => 'Harga harus berupa angka.',
        ]);

        $umkm = Umkm::findOrFail($request->integer('umkm_id'));

        // Admin OPD hanya boleh mengubah UMKM binaannya (FR-02)
        Gate::authorize('update', $umkm);

        $fotos        = $request->file('foto');
        $jumlahProduk = Produk::where('umkm_id', $umkm->id)->count();
        $sisaSlot     = self::MAKS_PRODUK - $jumlahProduk;

        if ($sisaSlot <= 0) {
            return back()->withInput()->with('error', 'UMKM ini sudah memiliki ' . self::MAKS_PRODUK . ' produk (batas maksimum).');
        }

        if (count($fotos) > $sisaSlot) {
            return back()->withInput()->with('error', "UMKM ini hanya bisa menambah {$sisaSlot} produk lagi. Kurangi jumlah foto yang dipilih.");
        }

        $urutan = $jumlahProduk + 1;

        foreach ($fotos as $index => $foto) {
            $path = $foto->store('produk/foto', 'public');

            Produk::create([
                'umkm_id'      => $umkm->id,
                'nama_produk'  => $request->nama_produk . ($index > 0 ? " (foto " . ($index + 1) . ")" : ""),
                'foto'         => $path,
                'harga'        => $request->harga,
                'urutan'       => $urutan++,
                'is_unggulan'  => $jumlahProduk === 0 && $index === 0, // foto pertama UMKM = unggulan
                'is_active'    => true,
            ]);
        }

        return back()->with('success', count($fotos) . " foto produk berhasil diupload untuk {$umkm->nama_usaha}.");
    }

    public function listProduk(Request $request, int $umkmId)
    {
        $umkm = Umkm::findOrFail($umkmId);

        Gate::authorize('view', $umkm);

        $produk = Produk::where('umkm_id', $umkm->id)
            ->orderBy('urutan')
            ->get(['id', 'nama_produk', 'foto', 'urutan', 'is_unggulan']);

        return response()->json($produk->map(fn($p) => [
            'id'          => $p->id,
            'nama_produk' => $p->nama_produk,
            'foto_url'    => $p->foto ? Storage::url($p->foto) : null,
            'urutan'      => $p->urutan,
            'is_unggulan' => $p->is_unggulan,
        ]));
    }
}
