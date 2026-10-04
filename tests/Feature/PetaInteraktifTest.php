<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PetaInteraktifTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $this->opd = Opd::create(['nama_opd' => 'Dinas A', 'kode_opd' => 'A', 'kabupaten' => 'Pontianak']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
    }

    private function umkm(string $kabupaten, string $status = 'aktif', array $attr = []): Umkm
    {
        $pemilik = PemilikUsaha::create([
            'nama_lengkap' => 'Pemilik', 'nik' => 'NIK-' . uniqid(), 'jenis_kelamin' => 'L',
            'alamat' => '-', 'kabupaten' => $kabupaten, 'kecamatan' => '-', 'telepon' => uniqid(),
        ]);

        return Umkm::create($attr + [
            'pemilik_usaha_id' => $pemilik->id, 'opd_id' => $this->opd->id,
            'nama_usaha' => 'UMKM ' . uniqid(), 'slug' => 'umkm-' . uniqid(),
            'sektor' => 'kuliner', 'kabupaten' => $kabupaten, 'kecamatan' => '-',
            'alamat_usaha' => '-', 'status' => $status,
        ]);
    }

    public function test_data_counts_active_umkm_per_kabupaten_from_database(): void
    {
        $this->umkm('Pontianak');
        $this->umkm('Pontianak');
        $this->umkm('Pontianak', 'draft');
        $this->umkm('Sambas');
        $this->umkm(Umkm::KABUPATEN_KOSONG);

        $res = $this->actingAs($this->superAdmin())->getJson('/superadmin/peta-interaktif/data')
            ->assertOk()
            ->assertJsonPath('tidak_diketahui', 1)
            ->assertJsonCount(4, 'umkm')
            ->assertJsonStructure(['umkm' => [['id', 'nama', 'kab', 'status', 'skor', 'url']]]);

        // Kunci berisi titik ("Kab. Sambas") → baca langsung, bukan lewat dot-path
        $jumlah = $res->json('jumlah');
        $this->assertCount(14, $jumlah);
        $this->assertSame(2, $jumlah['Kota Pontianak']);
        $this->assertSame(1, $jumlah['Kab. Sambas']);
        $this->assertSame(0, $jumlah['Kab. Ketapang']);
    }

    public function test_data_includes_categories_and_counts_per_category_from_database(): void
    {
        $this->umkm('Pontianak', 'aktif', ['sektor' => 'kuliner']);
        $this->umkm('Pontianak', 'aktif', ['sektor' => 'kuliner']);
        $this->umkm('Pontianak', 'aktif', ['sektor' => 'fashion']);
        $this->umkm('Sambas', 'aktif', ['sektor' => 'fashion']);
        $this->umkm('Sambas', 'draft', ['sektor' => 'kerajinan']); // tidak aktif → tidak dihitung

        $res = $this->actingAs($this->superAdmin())->getJson('/superadmin/peta-interaktif/data')->assertOk();

        $this->assertSame(
            [['kode' => 'kuliner', 'label' => 'Kuliner', 'jumlah' => 2], ['kode' => 'fashion', 'label' => 'Fesyen', 'jumlah' => 2]],
            collect($res->json('sektor'))->sortBy('kode', SORT_STRING, true)->values()->all(),
        );
        $this->assertSame(['Kota Pontianak' => 2], $res->json('jumlah_sektor')['kuliner']);
        $this->assertEquals(['Kota Pontianak' => 1, 'Kab. Sambas' => 1], $res->json('jumlah_sektor')['fashion']);
        $this->assertSame('kuliner', collect($res->json('umkm'))->firstWhere('sektor', 'Kuliner')['sektor_kode']);
    }

    public function test_old_admin_map_urls_redirect_to_superadmin(): void
    {
        $this->get('/admin/peta-interaktif')->assertStatus(301)->assertRedirect('/superadmin/peta-interaktif');
        $this->get('/admin/peta-interaktif/umkm/7')->assertStatus(301)->assertRedirect('/superadmin/peta-interaktif/umkm/7');
    }

    public function test_kalbar_boundary_geojson_is_valid(): void
    {
        $geo = json_decode(file_get_contents(public_path('geo/kalbar.geojson')), true);

        $this->assertSame('FeatureCollection', $geo['type']);
        $this->assertSame('Kalimantan Barat', $geo['features'][0]['properties']['nama']);
        $this->assertContains($geo['features'][0]['geometry']['type'], ['Polygon', 'MultiPolygon']);

        $this->actingAs($this->superAdmin())->get('/superadmin/peta-interaktif')
            ->assertOk()
            ->assertSee('geo/kalbar.geojson')
            ->assertSee('Semua Kategori')
            ->assertDontSee('Semua Tier');
    }

    public function test_umkm_detail_shows_profile_and_bi_program_recommendations(): void
    {
        $umkm = $this->umkm('Pontianak', 'aktif', ['nama_usaha' => 'Keripik <b>Pisang</b>']);

        $this->actingAs($this->superAdmin())->get("/superadmin/peta-interaktif/umkm/{$umkm->id}")
            ->assertOk()
            ->assertSee('Keripik &lt;b&gt;Pisang&lt;/b&gt;', false)
            ->assertSee('Rekomendasi Program KPw BI')
            ->assertSee('Fasilitasi Legalitas Usaha (NIB melalui OSS)')
            ->assertSee('Pendampingan Sertifikasi Halal')
            ->assertSee('Pelatihan Pencatatan Keuangan Digital (SI APIK)');
    }

    public function test_umkm_detail_is_super_admin_only(): void
    {
        $umkm  = $this->umkm('Pontianak');
        $admin = User::factory()->create(['opd_id' => $this->opd->id])->assignRole(User::ROLE_ADMIN_OPD);

        $this->get("/superadmin/peta-interaktif/umkm/{$umkm->id}")->assertRedirect('/login');
        $this->actingAs($admin)->get("/superadmin/peta-interaktif/umkm/{$umkm->id}")->assertForbidden();
    }
}
