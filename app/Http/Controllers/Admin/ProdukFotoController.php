<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Umkm;
use App\Support\Thumbnail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProdukFotoController extends Controller
{
    private const MAKS_PRODUK = 10;
    // Batas resolusi: file 2 MB bisa berisi gambar berpiksel raksasa yang menghabiskan memori server
    public const DIMENSI = 'dimensions:max_width=8000,max_height=8000';

    public function index(Request $request)
    {
        $daftarUmkm = Umkm::milikPengguna($request->user())
            ->orderBy('nama_usaha')
            ->get(['id', 'nama_usaha', 'kabupaten']);

        // ?umkm={id} dari tombol "Kelola foto" di halaman direktori
        $umkmAwal = old('umkm_id') ?? $daftarUmkm->firstWhere('id', $request->integer('umkm'))?->id;

        return view('admin.produk.upload-foto', [
            'daftarUmkm' => $daftarUmkm,
            'umkmAwal'   => $umkmAwal,
            'maksProduk' => self::MAKS_PRODUK,
            'badges'     => Produk::BADGE,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'umkm_id'     => ['required', 'integer', 'exists:umkm,id'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'foto'        => ['required', 'array', 'min:1', 'max:' . self::MAKS_PRODUK],
            'foto.*'      => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', self::DIMENSI],
            'badge'       => ['nullable', 'array'],
            'badge.*'     => ['nullable', Rule::in(array_keys(Produk::BADGE))],
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
            'foto.*.dimensions'    => 'Resolusi foto maksimal 8000 × 8000 piksel.',
            'badge.*.in'           => 'Badge produk tidak valid.',
            'harga.numeric'        => 'Harga harus berupa angka.',
        ]);

        $umkm = Umkm::findOrFail($request->integer('umkm_id'));

        // Admin OPD hanya boleh mengubah UMKM binaannya (FR-02)
        Gate::authorize('update', $umkm);

        $fotos  = $request->file('foto');
        $badges = $request->input('badge', []);

        if (count(array_keys($badges, 'unggulan', true)) > 1) {
            return back()->withInput()->with('error', 'Badge "Unggulan" hanya boleh dipilih untuk satu foto (foto utama).');
        }

        $jumlahProduk = Produk::where('umkm_id', $umkm->id)->count();
        $sisaSlot     = self::MAKS_PRODUK - $jumlahProduk;

        if ($sisaSlot <= 0) {
            return back()->withInput()->with('error', 'UMKM ini sudah memiliki ' . self::MAKS_PRODUK . ' produk (batas maksimum).');
        }

        if (count($fotos) > $sisaSlot) {
            return back()->withInput()->with('error', "UMKM ini hanya bisa menambah {$sisaSlot} produk lagi. Kurangi jumlah foto yang dipilih.");
        }

        $urutan       = (int) Produk::where('umkm_id', $umkm->id)->max('urutan') + 1;
        $adaUnggulan  = Produk::where('umkm_id', $umkm->id)->where('is_unggulan', true)->exists();
        $utama        = null;

        DB::transaction(function () use ($fotos, $badges, $umkm, $request, &$urutan, $adaUnggulan, &$utama) {
            foreach ($fotos as $index => $foto) {
                $badge = ($badges[$index] ?? null) ?: null;

                // UMKM tanpa foto utama: foto pertama tanpa badge otomatis jadi unggulan
                if (! $adaUnggulan && $index === 0 && $badge === null && ! in_array('unggulan', $badges, true)) {
                    $badge = 'unggulan';
                }

                $produk = Produk::create([
                    'umkm_id'     => $umkm->id,
                    'nama_produk' => $request->nama_produk . ($index > 0 ? ' (foto ' . ($index + 1) . ')' : ''),
                    'foto'        => tap($foto->store('produk/foto', 'public'), fn ($path) => Thumbnail::buat($path)),
                    'harga'       => $request->harga,
                    'urutan'      => $urutan++,
                    'badge'       => $badge,
                    'is_unggulan' => false,
                    'is_active'   => true,
                ]);

                if ($badge === 'unggulan') {
                    $utama = $produk;
                }
            }

            // Badge unggulan = foto utama (hanya satu per UMKM)
            $utama?->jadikanUtama();
        });

        return back()->with('success', count($fotos) . " foto produk berhasil diupload untuk {$umkm->nama_usaha}."
            . ($utama ? ' Foto berbadge Unggulan dijadikan foto utama.' : ''));
    }

    public function listProduk(Request $request, int $umkmId)
    {
        $umkm = Umkm::findOrFail($umkmId);

        Gate::authorize('view', $umkm);

        $produk = Produk::where('umkm_id', $umkm->id)
            ->orderByDesc('is_unggulan')
            ->orderBy('urutan')
            ->get();

        return response()->json($produk->map(fn (Produk $p) => $this->json($p)));
    }

    /**
     * Ubah data produk (nama, harga, keterangan, badge, ganti foto) — tampil di halaman direktori.
     */
    public function update(Request $request, Produk $produk): JsonResponse
    {
        Gate::authorize('update', $produk->umkm);

        $data = $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'harga'       => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'deskripsi'   => ['nullable', 'string', 'max:2000'],
            'badge'       => ['nullable', Rule::in(array_keys(Produk::BADGE))],
            'foto'        => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', self::DIMENSI],
        ], [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'deskripsi.max'        => 'Keterangan maksimal 2000 karakter.',
            'badge.in'             => 'Badge produk tidak valid.',
            'foto.image'           => 'File harus berupa gambar.',
            'foto.mimes'           => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max'             => 'Ukuran foto maksimal 2 MB.',
            'foto.dimensions'      => 'Resolusi foto maksimal 8000 × 8000 piksel.',
        ]);

        DB::transaction(function () use ($request, $produk, $data) {
            if ($request->hasFile('foto')) {
                $produk->hapusFileFoto();
                $produk->foto     = $request->file('foto')->store('produk/foto', 'public');
                Thumbnail::buat($produk->foto);
                $produk->foto_url = null;
            }

            $badge = $data['badge'] ?? null;

            $produk->fill([
                'nama_produk' => $data['nama_produk'],
                'harga'       => $data['harga'] ?? null,
                'deskripsi'   => filled($data['deskripsi'] ?? null) ? trim($data['deskripsi']) : null,
                'badge'       => $badge,
                'is_unggulan' => $badge === 'unggulan',
            ])->save();

            if ($badge === 'unggulan') {
                $produk->jadikanUtama();
            }
        });

        return response()->json([
            'message' => 'Data produk berhasil diperbarui.',
            'produk'  => $this->json($produk->fresh()),
        ]);
    }

    /**
     * Hapus foto saja; data produk tetap ada.
     */
    public function destroyFoto(Produk $produk): JsonResponse
    {
        Gate::authorize('update', $produk->umkm);

        $produk->hapusFileFoto();
        $produk->forceFill(['foto' => null, 'foto_url' => null])->save();

        return response()->json([
            'message' => 'Foto produk berhasil dihapus.',
            'produk'  => $this->json($produk),
        ]);
    }

    /**
     * Hapus keterangan (deskripsi) produk.
     */
    public function destroyKeterangan(Produk $produk): JsonResponse
    {
        Gate::authorize('update', $produk->umkm);

        $produk->forceFill(['deskripsi' => null])->save();

        return response()->json([
            'message' => 'Keterangan produk berhasil dihapus.',
            'produk'  => $this->json($produk),
        ]);
    }

    /**
     * Hapus produk beserta fotonya.
     */
    public function destroy(Produk $produk): JsonResponse
    {
        Gate::authorize('update', $produk->umkm);

        $produk->hapusFileFoto();
        $produk->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    private function json(Produk $p): array
    {
        return [
            'id'          => $p->id,
            'nama_produk' => $p->nama_produk,
            'harga'       => $p->harga,
            'deskripsi'   => $p->deskripsi,
            'foto_url'    => $p->foto_kecil,
            'urutan'      => $p->urutan,
            'badge'       => $p->badge,
            'is_unggulan' => $p->is_unggulan,
        ];
    }
}
