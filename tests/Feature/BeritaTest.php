<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Berita official: ditulis Super Admin di /superadmin/berita, tampil di halaman publik /berita.
 */
class BeritaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
    }

    private function berita(array $attr = []): Berita
    {
        return Berita::create($attr + [
            'judul' => 'Pelatihan Ekspor UMKM', 'slug' => 'pelatihan-ekspor-' . uniqid(),
            'isi' => 'Isi berita.', 'status' => 'terbit', 'terbit_pada' => now()->subHour(),
        ]);
    }

    public function test_super_admin_dashboard_links_to_news_management(): void
    {
        $this->actingAs($this->superAdmin())->get('/superadmin/dashboard')->assertOk()
            ->assertSee(route('superadmin.berita.create'))
            ->assertSee('Tulis Berita Official')
            ->assertSee(route('superadmin.berita.index'));
    }

    public function test_only_super_admin_can_manage_news(): void
    {
        $opd   = Opd::create(['nama_opd' => 'Dinas A', 'kode_opd' => 'A', 'kabupaten' => 'Pontianak']);
        $admin = User::factory()->create(['opd_id' => $opd->id])->assignRole(User::ROLE_ADMIN_OPD);

        $this->actingAs($admin)->get('/superadmin/berita')->assertForbidden();
        $this->actingAs($admin)->post('/superadmin/berita', ['judul' => 'X', 'isi' => 'Y', 'status' => 'terbit'])->assertForbidden();
        $this->actingAs($admin)->get('/admin/dashboard')->assertDontSee('Tulis Berita Official');
        $this->assertSame(0, Berita::count());
    }

    public function test_super_admin_publishes_news_that_appears_on_public_page(): void
    {
        Storage::fake('public');
        $super = $this->superAdmin();

        $this->actingAs($super)->get('/superadmin/berita/tulis')->assertOk()->assertSee('Isi berita');

        $this->actingAs($super)->post('/superadmin/berita', [
            'judul'     => 'Pelatihan Ekspor UMKM Kalbar 2026',
            'ringkasan' => 'Seratus UMKM ikut pelatihan ekspor.',
            'isi'       => "Paragraf pertama.\n\nParagraf kedua <script>alert(1)</script>",
            'gambar'    => UploadedFile::fake()->image('sampul.jpg', 1200, 675),
            'status'    => 'terbit',
        ])->assertRedirect(route('superadmin.berita.index'))->assertSessionHas('success');

        $b = Berita::sole();
        $this->assertSame('pelatihan-ekspor-umkm-kalbar-2026', $b->slug);
        $this->assertSame($super->id, $b->penulis_id);
        $this->assertNotNull($b->terbit_pada);   // tanpa tanggal → terbit sekarang
        Storage::disk('public')->assertExists($b->gambar);

        $this->get('/berita')->assertOk()
            ->assertSee('Pelatihan Ekspor UMKM Kalbar 2026')
            ->assertSee('Seratus UMKM ikut pelatihan ekspor.')
            ->assertSee(route('berita.show', $b->slug))
            ->assertDontSee('Berita segera hadir');

        $this->get('/berita/' . $b->slug)->assertOk()
            ->assertSee('<p>Paragraf pertama.</p>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)   // isi di-escape (aman dari XSS)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_drafts_and_scheduled_news_are_hidden_from_public(): void
    {
        $draf      = $this->berita(['judul' => 'Draf Rahasia', 'status' => 'draft', 'terbit_pada' => null]);
        $terjadwal = $this->berita(['judul' => 'Berita Besok', 'terbit_pada' => now()->addDay()]);
        $this->berita(['judul' => 'Berita Hari Ini']);

        $this->get('/berita')->assertOk()
            ->assertSee('Berita Hari Ini')
            ->assertDontSee('Draf Rahasia')
            ->assertDontSee('Berita Besok');
        $this->get('/berita/' . $draf->slug)->assertNotFound();
        $this->get('/berita/' . $terjadwal->slug)->assertNotFound();

        // Di admin semuanya terlihat dengan statusnya
        $this->actingAs($this->superAdmin())->get('/superadmin/berita')->assertOk()
            ->assertSee('Draf Rahasia')->assertSee('Terjadwal')->assertSee('Berita Hari Ini');
    }

    public function test_empty_news_page_still_shows_coming_soon_state(): void
    {
        $this->get('/berita')->assertOk()->assertSee('Berita segera hadir');
    }

    public function test_super_admin_edits_and_deletes_news(): void
    {
        Storage::fake('public');
        $super = $this->superAdmin();
        $b = $this->berita(['gambar' => UploadedFile::fake()->image('lama.jpg')->store('berita', 'public')]);
        $slugLama = $b->slug;
        $gambarLama = $b->gambar;

        $this->actingAs($super)->get("/superadmin/berita/{$b->id}/ubah")->assertOk()->assertSee('Pelatihan Ekspor UMKM');

        // Sudah tampil → slug tetap walau judul diubah; gambar diganti → file lama dihapus
        $this->actingAs($super)->put("/superadmin/berita/{$b->id}", [
            'judul' => 'Judul Baru', 'isi' => 'Isi baru.', 'status' => 'terbit',
            'terbit_pada' => $b->terbit_pada->format('Y-m-d\TH:i'),
            'gambar' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertSessionHas('success');

        $b->refresh();
        $this->assertSame('Judul Baru', $b->judul);
        $this->assertSame($slugLama, $b->slug);
        Storage::disk('public')->assertMissing($gambarLama);
        Storage::disk('public')->assertExists($b->gambar);

        $this->actingAs($super)->delete("/superadmin/berita/{$b->id}")->assertSessionHas('success');
        $this->assertModelMissing($b);
        Storage::disk('public')->assertMissing($b->gambar);
        $this->get('/berita/' . $slugLama)->assertNotFound();
    }

    public function test_validation_rejects_invalid_input(): void
    {
        $this->actingAs($this->superAdmin())->post('/superadmin/berita', [
            'judul' => '', 'isi' => '', 'status' => 'rahasia',
            'gambar' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['judul', 'isi', 'status', 'gambar']);

        $this->assertSame(0, Berita::count());
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $super = $this->superAdmin();
        foreach (range(1, 2) as $_) {
            $this->actingAs($super)->post('/superadmin/berita', ['judul' => 'Kabar UMKM', 'isi' => 'Isi.', 'status' => 'terbit']);
        }

        $this->assertSame(['kabar-umkm', 'kabar-umkm-2'], Berita::orderBy('id')->pluck('slug')->all());
    }
}
