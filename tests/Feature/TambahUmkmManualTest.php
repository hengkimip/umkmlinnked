<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tambah UMKM manual di halaman Import Data — kolom sama dengan "Kelola Profil UMKM".
 */
class TambahUmkmManualTest extends TestCase
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

    private function adminOpd(): User
    {
        return User::factory()->create(['opd_id' => $this->opdA->id])->assignRole(User::ROLE_ADMIN_OPD);
    }

    private function wajib(array $lain = []): array
    {
        return $lain + [
            'pemilik_nama'     => 'Siti Aminah',
            'pemilik_alamat'   => 'Jl. Merdeka 1, Pontianak',
            'pemilik_whatsapp' => '0812-1111-2222',
            'nama_usaha'       => 'Keripik Siti',
            'alamat_usaha'     => 'Jl. Gajah Mada 10, Pontianak',
            'kabupaten'        => 'Pontianak',
            'sektor'           => 'kuliner',
            'produk_unggulan'  => 'Keripik Pisang',
        ];
    }

    public function test_import_page_shows_manual_form_with_all_profile_fields(): void
    {
        $this->actingAs($this->adminOpd())->get('/admin/import')
            ->assertOk()
            ->assertSee(['Import File (CSV/Excel)', 'Tambah Manual', 'Simpan UMKM Baru'])
            ->assertSee('name="pemilik_nama"', false)
            ->assertSee('name="omzet_bulanan"', false)
            ->assertSee('name="rekomendasi_program"', false)
            ->assertSee('name="kabupaten"', false)
            ->assertSee('Rekomendasi Program Dinas A'); // Admin OPD: nama OPD-nya
    }

    public function test_manual_entry_saves_every_field_to_the_same_tables_as_profile_and_import(): void
    {
        $res = $this->actingAs($this->adminOpd())->post('/admin/import/manual', $this->wajib([
            'pemilik_email'       => 'siti@contoh.id',
            'program_bi'          => 'Wirausaha Muda BI',
            'tahun_berdiri'       => '2019',
            'jumlah_karyawan'     => '4',
            'kapasitas_produksi'  => '300',
            'foto_url'            => 'https://drive.google.com/file/d/abc/view',
            'produk_lainnya'      => 'Keripik singkong',
            'saluran_pemasaran'   => 'Shopee, Instagram',
            'jangkauan_pasar'     => 'nasional',
            'wa_bisnis'           => '0813 3333 4444',
            'instagram'           => '@keripiksiti',
            'marketplace'         => 'https://shopee.co.id/keripiksiti',
            'website'             => 'https://keripiksiti.id',
            'bentuk_legalitas'    => 'NIB, NPWP',
            'sertifikasi_produk'  => 'Halal, PIRT',
            'metode_pencatatan'   => 'Aplikasi digital',
            'omzet_bulanan'       => '5000000',
            'pembiayaan_2026'     => 'Sudah',
            'rekomendasi_program' => "Pendampingan Ekspor\n\npendampingan ekspor\nPelatihan QRIS",
        ]));

        $umkm = Umkm::firstWhere('nama_usaha', 'Keripik Siti');
        $res->assertRedirect("/admin/profil-umkm?umkm={$umkm->id}")->assertSessionHas('success');

        $this->assertSame($this->opdA->id, $umkm->opd_id); // Admin OPD terkunci ke OPD-nya
        $this->assertSame('Pontianak', $umkm->kabupaten);
        $this->assertSame('aktif', $umkm->status);
        $this->assertSame('6281333334444', $umkm->whatsapp);
        $this->assertSame('https://shopee.co.id/keripiksiti', $umkm->shopee);
        $this->assertSame(4, $umkm->jumlah_tenaga_kerja);

        $this->assertSame('Siti Aminah', $umkm->pemilik->nama_lengkap);
        $this->assertSame('6281211112222', $umkm->pemilik->telepon);
        $this->assertSame('siti@contoh.id', $umkm->pemilik->email);

        $produk = $umkm->produkUtama();
        $this->assertSame('Keripik Pisang', $produk->nama_produk);
        $this->assertEquals(300, $produk->kapasitas_produksi);
        $this->assertSame('https://drive.google.com/file/d/abc/view', $produk->foto_url);

        $this->assertSame('nasional', $umkm->pemasaran->jangkauan_pasar);
        $this->assertSame(['shopee', 'instagram'], $umkm->pemasaran->platform_online);
        $this->assertSame('ADA', $umkm->legalitas->nomor_nib);
        $this->assertSame('ADA', $umkm->legalitas->nomor_halal);
        $this->assertCount(1, $umkm->keuangan); // dua kolom keuangan → tetap satu baris
        $this->assertEquals(60000000, $umkm->keuanganTerakhir->omzet_tahunan);
        $this->assertTrue((bool) $umkm->keuanganTerakhir->memiliki_pencatatan);
        $this->assertSame('Wirausaha Muda BI', $umkm->profil->program_bi);
        $this->assertSame(['Pendampingan Ekspor', 'Pelatihan QRIS'], $umkm->profil->rekomendasi_program);

        $this->assertGreaterThan(0, $umkm->skor_total);
        $this->assertGreaterThan(0, $umkm->skor_sertifikasi);
    }

    public function test_kota_kabupaten_is_chosen_in_the_form_not_guessed_from_address(): void
    {
        // Alamat menyebut Pontianak, tetapi Kota/Kabupaten yang dipilih Sambas → Sambas
        $this->actingAs($this->adminOpd())->post('/admin/import/manual', $this->wajib(['kabupaten' => 'Sambas']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Sambas', Umkm::sole()->kabupaten);

        // Nilai di luar 14 kabupaten/kota Kalbar ditolak
        $this->actingAs($this->adminOpd())->post('/admin/import/manual', $this->wajib(['nama_usaha' => 'Lain', 'kabupaten' => 'Jakarta']))
            ->assertSessionHasErrorsIn('manual', ['kabupaten']);
    }

    public function test_required_fields_are_validated_and_nothing_is_saved(): void
    {
        $this->actingAs($this->adminOpd())
            ->from('/admin/import')
            ->post('/admin/import/manual', ['_form' => 'manual', 'nama_usaha' => 'Tanpa Pemilik', 'tahun_berdiri' => '1500'])
            ->assertRedirect('/admin/import')
            ->assertSessionHasErrorsIn('manual', ['pemilik_nama', 'pemilik_whatsapp', 'alamat_usaha', 'kabupaten', 'sektor', 'produk_unggulan', 'tahun_berdiri']);

        $this->assertSame(0, Umkm::count());
        $this->assertSame(0, PemilikUsaha::count());
    }

    public function test_existing_owner_is_reused_and_likely_duplicate_goes_to_review_queue(): void
    {
        $admin = $this->adminOpd();

        $this->actingAs($admin)->post('/admin/import/manual', $this->wajib())->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/import/manual', $this->wajib(['nama_usaha' => 'Kopi Siti', 'pemilik_nama' => 'Nama Lain']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PemilikUsaha::count());
        $this->assertSame('Siti Aminah', PemilikUsaha::first()->nama_lengkap); // data pemilik lama tidak ditimpa
        $this->assertSame(2, PemilikUsaha::first()->umkm()->count());

        // Skor ≥ 60 → tidak disimpan, masuk antrean review
        $this->actingAs($admin)->post('/admin/import/manual', $this->wajib(['nama_usaha' => 'keripik SITI']))
            ->assertRedirect('/admin/duplikat');
        $this->assertSame(2, Umkm::count());
        $this->assertSame(1, \App\Models\UmkmDuplikat::menunggu()->count());
    }

    public function test_umkm_registered_by_another_opd_goes_to_review_instead_of_being_saved_again(): void
    {
        $this->actingAs($this->adminOpd())->post('/admin/import/manual', $this->wajib())->assertSessionHasNoErrors();

        // OPD lain memasukkan UMKM yang sama (beda huruf besar/spasi/tanda baca, WA ditulis lain)
        $adminB = User::factory()->create(['opd_id' => $this->opdB->id])->assignRole(User::ROLE_ADMIN_OPD);
        $this->actingAs($adminB)->post('/admin/import/manual', $this->wajib([
            'nama_usaha' => '  KERIPIK   siti! ', 'pemilik_whatsapp' => '+62 812-1111-2222',
        ]))->assertRedirect('/admin/duplikat')->assertSessionHasNoErrors();

        $pesan = session('success');
        $this->assertStringContainsString('belum disimpan', $pesan);
        $this->assertStringContainsString('Binaan Dinas A', $pesan);
        $this->assertStringContainsString('skor kemiripan 90/100', $pesan); // tanpa e-mail: 25+25+20+20
        $this->assertSame(1, Umkm::count());

        $antre = \App\Models\UmkmDuplikat::sole();
        $this->assertSame($this->opdB->id, $antre->opd_id);
        $this->assertSame('manual', $antre->sumber);
        $this->assertSame('KERIPIK   siti!', $antre->data['nama_usaha']); // isian asli (hanya di-trim)
    }

    public function test_opd_rules_match_file_import(): void
    {
        // Admin OPD tidak boleh memilih OPD lain
        $this->actingAs($this->adminOpd())->post('/admin/import/manual', $this->wajib(['opd_id' => $this->opdB->id]))
            ->assertSessionHasErrorsIn('manual', ['opd_id']);

        // Super Admin wajib memilih OPD tujuan
        $super = User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super)->post('/admin/import/manual', $this->wajib())->assertSessionHasErrorsIn('manual', ['opd_id']);
        $this->actingAs($super)->post('/admin/import/manual', $this->wajib(['opd_id' => $this->opdB->id]))->assertSessionHasNoErrors();

        $this->assertSame($this->opdB->id, Umkm::sole()->opd_id);
        $this->post('/logout');
        $this->post('/admin/import/manual', $this->wajib())->assertRedirect('/login');
    }
}
