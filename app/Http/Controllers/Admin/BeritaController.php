<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Kelola berita official (khusus Super Admin) yang tampil di halaman publik /berita.
 */
class BeritaController extends Controller
{
    public function index()
    {
        return view('admin.berita.index', [
            'berita' => Berita::with('penulis:id,name')
                ->orderByRaw("status = 'draft' desc")
                ->orderByDesc('terbit_pada')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.berita.form', ['berita' => new Berita(['status' => 'terbit'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $berita = new Berita($data);
        $berita->slug       = Berita::slugUnik($data['judul']);
        $berita->penulis_id = $request->user()->id;
        $berita->gambar     = $request->file('gambar')?->store('berita', 'public');
        $berita->save();

        return redirect()->route('superadmin.berita.index')->with('success', $this->pesan($berita, 'disimpan'));
    }

    public function edit(Berita $berita)
    {
        return view('admin.berita.form', compact('berita'));
    }

    public function update(Request $request, Berita $berita): RedirectResponse
    {
        $data = $this->validasi($request);

        // Alamat (slug) tetap sejak pertama tampil agar tautan yang sudah dibagikan tidak putus
        if (! $berita->sudahTampil() && $data['judul'] !== $berita->judul) {
            $berita->slug = Berita::slugUnik($data['judul'], $berita->id);
        }

        if ($request->hasFile('gambar') || $request->boolean('hapus_gambar')) {
            $this->hapusGambar($berita);
            $berita->gambar = $request->file('gambar')?->store('berita', 'public');
        }

        $berita->fill($data)->save();

        return redirect()->route('superadmin.berita.index')->with('success', $this->pesan($berita, 'diperbarui'));
    }

    public function destroy(Berita $berita): RedirectResponse
    {
        $this->hapusGambar($berita);
        $berita->delete();

        return redirect()->route('superadmin.berita.index')->with('success', "Berita \"{$berita->judul}\" dihapus.");
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'judul'       => ['required', 'string', 'max:255'],
            'ringkasan'   => ['nullable', 'string', 'max:300'],
            'isi'         => ['required', 'string', 'max:50000'],
            'gambar'      => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', ProdukFotoController::DIMENSI],
            'status'      => ['required', Rule::in(array_keys(Berita::STATUS))],
            'terbit_pada' => ['nullable', 'date'],
        ], [
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'gambar.max'   => 'Ukuran gambar maksimal 2 MB.',
            'gambar.dimensions' => 'Resolusi gambar maksimal 8000 × 8000 piksel.',
        ], [
            'judul' => 'judul', 'ringkasan' => 'ringkasan', 'isi' => 'isi berita', 'terbit_pada' => 'tanggal terbit',
        ]);

        // Terbit tanpa tanggal → terbit sekarang; tanggal di masa depan → terjadwal
        if ($data['status'] === 'terbit' && empty($data['terbit_pada'])) {
            $data['terbit_pada'] = now();
        }
        unset($data['gambar']);

        return $data;
    }

    private function hapusGambar(Berita $berita): void
    {
        if ($berita->gambar) {
            Storage::disk('public')->delete($berita->gambar);
        }
    }

    private function pesan(Berita $berita, string $aksi): string
    {
        return match (true) {
            $berita->sudahTampil()       => "Berita \"{$berita->judul}\" {$aksi} dan sudah tampil di halaman Berita.",
            $berita->status === 'terbit' => "Berita \"{$berita->judul}\" {$aksi}; dijadwalkan tampil {$berita->terbit_pada->translatedFormat('d F Y H:i')}.",
            default                      => "Draf berita \"{$berita->judul}\" {$aksi}. Belum tampil untuk publik.",
        };
    }
}
