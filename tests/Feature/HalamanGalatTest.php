<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman galat bertema UMKMLinked: biru penuh layar, motif Kalbar, ilustrasi, pesan bahasa masyarakat.
 */
class HalamanGalatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]); // seperti production: halaman galat tampil, bukan debug
        Route::get('/_uji-galat/{kode}', fn (int $kode) => abort($kode))->whereNumber('kode');
        Route::get('/_uji-galat-sistem', fn () => throw new \RuntimeException('Rahasia teknis: kolom xyz'));
    }

    private function cekTema($res, int $kode, string $teknis, string $pesan): void
    {
        $res->assertStatus($kode)
            ->assertSee("{$kode} · {$teknis}")
            ->assertSee($pesan)
            ->assertSee('data:image/svg+xml;base64', false)   // motif & siluet Kalbar, tanpa berkas luar
            ->assertSee('class="ilustrasi"', false)
            ->assertSee('Ke beranda')
            ->assertSee('Bank Indonesia Wilayah Kalimantan Barat')
            ->assertDontSee('build/assets', false);            // tidak bergantung pada aset Vite
    }

    public function test_404_page(): void
    {
        $this->cekTema($this->get('/halaman-yang-tidak-ada'), 404, 'Not Found', 'Halaman yang Anda cari tidak ditemukan.');
    }

    public function test_403_page(): void
    {
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $opd   = Opd::create(['nama_opd' => 'Dinas A', 'kode_opd' => 'A', 'kabupaten' => 'Pontianak']);
        $admin = User::factory()->create(['opd_id' => $opd->id])->assignRole(User::ROLE_ADMIN_OPD);

        $this->cekTema($this->actingAs($admin)->get('/superadmin/akses'), 403, 'Forbidden',
            'Maaf, Anda tidak memiliki izin untuk membuka halaman ini.');
    }

    public function test_401_page_offers_login(): void
    {
        $res = $this->get('/_uji-galat/401');
        $this->cekTema($res, 401, 'Unauthorized', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        $res->assertSee('href="' . url('/login') . '"', false)->assertSee('>Masuk<', false);
    }

    public function test_500_page_hides_technical_details(): void
    {
        $res = $this->get('/_uji-galat-sistem');
        $this->cekTema($res, 500, 'Internal Server Error', 'Terjadi gangguan pada sistem. Silakan coba beberapa saat lagi.');
        $res->assertSee('Coba lagi')->assertDontSee('Rahasia teknis')->assertDontSee('RuntimeException');
    }

    public function test_other_codes_use_the_same_theme(): void
    {
        $this->cekTema($this->get('/_uji-galat/419'), 419, 'Page Expired', 'Sesi halaman sudah berakhir.');
        $this->cekTema($this->get('/_uji-galat/429'), 429, 'Too Many Requests', 'Terlalu banyak permintaan');
        $this->cekTema($this->get('/_uji-galat/503'), 503, 'Service Unavailable', 'Sistem sedang dalam pemeliharaan.');
    }
}
