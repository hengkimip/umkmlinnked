<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NFR-09: otorisasi diuji otomatis.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opdA;
    private Opd $opdB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);

        $this->opdA = Opd::create(['nama_opd' => 'Dinas A', 'kode_opd' => 'A', 'kabupaten' => 'Pontianak']);
        $this->opdB = Opd::create(['nama_opd' => 'Dinas B', 'kode_opd' => 'B', 'kabupaten' => 'Sambas']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
    }

    private function adminOpd(?Opd $opd = null): User
    {
        return User::factory()->create(['opd_id' => ($opd ?? $this->opdA)->id])->assignRole(User::ROLE_ADMIN_OPD);
    }

    private function umkm(Opd $opd): Umkm
    {
        $pemilik = PemilikUsaha::create([
            'nama_lengkap' => 'Pemilik', 'nik' => 'NIK-' . uniqid(), 'jenis_kelamin' => 'L',
            'alamat' => '-', 'kabupaten' => $opd->kabupaten, 'kecamatan' => '-', 'telepon' => uniqid(),
        ]);

        return Umkm::create([
            'pemilik_usaha_id' => $pemilik->id, 'opd_id' => $opd->id,
            'nama_usaha' => 'UMKM ' . $opd->kode_opd, 'slug' => 'umkm-' . uniqid(),
            'sektor' => 'kuliner', 'kabupaten' => $opd->kabupaten, 'kecamatan' => '-',
            'alamat_usaha' => '-', 'status' => 'aktif',
        ]);
    }

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        foreach (['/admin/dashboard', '/admin/import', '/admin/produk/upload-foto', '/superadmin/peta-interaktif', '/superadmin/peta-interaktif/data'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_upload_foto_routes_are_not_public(): void
    {
        $this->get('/produk/upload-foto')->assertNotFound();
        $this->post('/admin/produk/upload-foto')->assertRedirect('/login');
    }

    public function test_peta_interaktif_is_super_admin_only(): void
    {
        $this->actingAs($this->adminOpd())->get('/superadmin/peta-interaktif')->assertForbidden();
        $this->actingAs($this->adminOpd())->get('/superadmin/peta-interaktif/data')->assertForbidden();

        $this->actingAs($this->superAdmin())->get('/superadmin/peta-interaktif')->assertOk();
        $this->actingAs($this->superAdmin())->getJson('/superadmin/peta-interaktif/data')->assertOk();
    }

    public function test_user_without_role_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_pages_render_for_both_roles(): void
    {
        foreach ([$this->superAdmin(), $this->adminOpd()] as $user) {
            $this->actingAs($user)->get('/admin/dashboard')->assertOk();
            $this->actingAs($user)->get('/admin/import')->assertOk()->assertSee('Unggah file');
            $this->actingAs($user)->get('/admin/produk/upload-foto')->assertOk();
        }
    }

    public function test_dashboard_only_shows_own_opd_for_admin_opd(): void
    {
        $this->umkm($this->opdA);
        $this->umkm($this->opdB);

        $this->actingAs($this->adminOpd($this->opdA))->get('/admin/dashboard')
            ->assertSee('UMKM A')
            ->assertDontSee('UMKM B');

        $this->actingAs($this->superAdmin())->get('/admin/dashboard')
            ->assertSee('UMKM A')
            ->assertSee('UMKM B');
    }

    public function test_admin_opd_cannot_upload_photo_outside_region(): void
    {
        Storage::fake('public');
        $umkmLain = $this->umkm($this->opdB);

        $this->actingAs($this->adminOpd($this->opdA))
            ->post('/admin/produk/upload-foto', [
                'umkm_id' => $umkmLain->id,
                'nama_produk' => 'Produk',
                'foto' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertForbidden();

        $this->actingAs($this->adminOpd($this->opdA))
            ->getJson("/admin/produk/list/{$umkmLain->id}")
            ->assertForbidden();
    }

    public function test_admin_opd_can_upload_photo_in_region(): void
    {
        Storage::fake('public');
        $umkm = $this->umkm($this->opdA);

        $this->actingAs($this->adminOpd($this->opdA))
            ->post('/admin/produk/upload-foto', [
                'umkm_id' => $umkm->id,
                'nama_produk' => 'Kopi',
                'foto' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(2, $umkm->produk()->count());
    }

    public function test_uploaded_photo_gets_light_webp_thumbnail(): void
    {
        Storage::fake('public');
        $umkm = $this->umkm($this->opdA);

        $this->actingAs($this->adminOpd($this->opdA))->post('/admin/produk/upload-foto', [
            'umkm_id' => $umkm->id, 'nama_produk' => 'Kopi',
            'foto'    => [UploadedFile::fake()->image('besar.jpg', 2000, 2500)],
        ])->assertSessionHas('success');

        $produk = $umkm->produk()->first();
        $thumb  = \App\Support\Thumbnail::path($produk->foto);

        Storage::disk('public')->assertExists($thumb);
        $this->assertSame(\App\Support\Thumbnail::LEBAR, getimagesize(Storage::disk('public')->path($thumb))[0]);
        $this->assertStringEndsWith('.webp', $produk->foto_kecil);
        $this->assertStringEndsWith('.jpg', $produk->foto_final); // foto asli tetap untuk tampilan besar

        // Hapus foto → thumbnail ikut terhapus
        $this->actingAs($this->adminOpd($this->opdA))->deleteJson("/admin/produk/{$produk->id}/foto")->assertOk();
        Storage::disk('public')->assertMissing($thumb);
    }

    public function test_badge_unggulan_becomes_the_only_main_photo(): void
    {
        Storage::fake('public');
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd($this->opdA);

        $this->actingAs($admin)->post('/admin/produk/upload-foto', [
            'umkm_id' => $umkm->id, 'nama_produk' => 'Kopi',
            'foto'  => [UploadedFile::fake()->image('a.jpg')],
        ])->assertSessionHas('success');

        // Foto pertama UMKM tanpa badge otomatis jadi unggulan
        $this->assertSame('unggulan', $umkm->produk()->first()->badge);

        $this->actingAs($admin)->post('/admin/produk/upload-foto', [
            'umkm_id' => $umkm->id, 'nama_produk' => 'Teh',
            'foto'  => [UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
            'badge' => ['promo', 'unggulan'],
        ])->assertSessionHas('success');

        $utama = $umkm->produk()->where('is_unggulan', true)->get();
        $this->assertCount(1, $utama);
        $this->assertSame('Teh (foto 2)', $utama->first()->nama_produk);
        $this->assertSame(1, $umkm->produk()->where('badge', 'unggulan')->count());
        $this->assertSame(1, $umkm->produk()->where('badge', 'promo')->count());
    }

    public function test_upload_rejects_more_than_one_unggulan_or_unknown_badge(): void
    {
        Storage::fake('public');
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd($this->opdA);
        $foto  = fn () => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')];

        $this->actingAs($admin)->post('/admin/produk/upload-foto', [
            'umkm_id' => $umkm->id, 'nama_produk' => 'Kopi', 'foto' => $foto(), 'badge' => ['unggulan', 'unggulan'],
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post('/admin/produk/upload-foto', [
            'umkm_id' => $umkm->id, 'nama_produk' => 'Kopi', 'foto' => $foto(), 'badge' => ['diskon', ''],
        ])->assertSessionHasErrors('badge.0');

        $this->assertSame(0, $umkm->produk()->count());
    }

    public function test_admin_can_update_and_clear_product_photo_and_description(): void
    {
        Storage::fake('public');
        $umkm   = $this->umkm($this->opdA);
        $admin  = $this->adminOpd($this->opdA);
        $path   = UploadedFile::fake()->image('a.jpg')->store('produk/foto', 'public');
        $produk = $umkm->produk()->create(['nama_produk' => 'Kopi', 'foto' => $path, 'deskripsi' => 'Lama', 'urutan' => 1]);

        $this->actingAs($admin)->patchJson("/admin/produk/{$produk->id}", [
            'nama_produk' => 'Kopi Robusta', 'harga' => 50000, 'deskripsi' => 'Kemasan 250 g', 'badge' => 'terlaris',
        ])->assertOk()->assertJsonPath('produk.badge', 'terlaris');

        $produk->refresh();
        $this->assertSame('Kopi Robusta', $produk->nama_produk);
        $this->assertSame('Kemasan 250 g', $produk->deskripsi);

        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}/keterangan")->assertOk();
        $this->assertNull($produk->fresh()->deskripsi);

        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}/foto")->assertOk();
        $this->assertNull($produk->fresh()->foto);
        Storage::disk('public')->assertMissing($path);

        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}")->assertOk();
        $this->assertModelMissing($produk);
    }

    public function test_admin_opd_cannot_manage_products_outside_region(): void
    {
        $produk = $this->umkm($this->opdB)->produk()->create(['nama_produk' => 'Kopi', 'deskripsi' => 'X', 'urutan' => 1]);
        $admin  = $this->adminOpd($this->opdA);

        $this->actingAs($admin)->patchJson("/admin/produk/{$produk->id}", ['nama_produk' => 'Ubah'])->assertForbidden();
        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}/keterangan")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}/foto")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/admin/produk/{$produk->id}")->assertForbidden();

        $this->assertSame('X', $produk->fresh()->deskripsi);
    }

    public function test_product_update_returns_json_validation_errors(): void
    {
        $produk = $this->umkm($this->opdA)->produk()->create(['nama_produk' => 'Kopi', 'urutan' => 1]);

        $this->actingAs($this->adminOpd($this->opdA))
            ->patchJson("/admin/produk/{$produk->id}", ['nama_produk' => '', 'badge' => 'diskon'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama_produk', 'badge']);
    }

    public function test_import_rejects_non_spreadsheet_files(): void
    {
        $this->actingAs($this->adminOpd())
            ->post('/admin/import', ['file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')])
            ->assertSessionHasErrors('file');
    }

    public function test_super_admin_must_choose_target_opd_for_import(): void
    {
        $csv = UploadedFile::fake()->createWithContent('data.csv', "nama_umkmusaha\nTes\n");

        $this->actingAs($this->superAdmin())
            ->post('/admin/import', ['file' => $csv])
            ->assertSessionHasErrors('opd_id');

        $this->actingAs($this->superAdmin())->get('/admin/import')
            ->assertSee('OPD tujuan')
            ->assertSee('Dinas B');
    }

    public function test_admin_opd_cannot_import_into_other_opd(): void
    {
        $csv = UploadedFile::fake()->createWithContent('data.csv', "nama_umkmusaha\nTes\n");

        $this->actingAs($this->adminOpd($this->opdA))
            ->post('/admin/import', ['file' => $csv, 'opd_id' => $this->opdB->id])
            ->assertSessionHasErrors('opd_id');

        $this->actingAs($this->adminOpd($this->opdA))->get('/admin/import')
            ->assertDontSee('OPD tujuan');
    }

    public function test_admin_opd_without_opd_cannot_import(): void
    {
        $user = User::factory()->create()->assignRole(User::ROLE_ADMIN_OPD);
        $csv  = UploadedFile::fake()->createWithContent('data.csv', "nama_umkmusaha\nTes\n");

        $this->actingAs($user)->post('/admin/import', ['file' => $csv])
            ->assertSessionHas('error');
    }
}
