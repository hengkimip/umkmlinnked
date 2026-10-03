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
        foreach (['/admin/dashboard', '/admin/import', '/admin/produk/upload-foto', '/admin/peta-interaktif', '/admin/peta-interaktif/data'] as $url) {
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
        $this->actingAs($this->adminOpd())->get('/admin/peta-interaktif')->assertForbidden();
        $this->actingAs($this->adminOpd())->get('/admin/peta-interaktif/data')->assertForbidden();

        $this->actingAs($this->superAdmin())->get('/admin/peta-interaktif')->assertOk();
        $this->actingAs($this->superAdmin())->getJson('/admin/peta-interaktif/data')->assertOk();
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
