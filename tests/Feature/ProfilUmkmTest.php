<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfilUmkmTest extends TestCase
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

    private function adminOpd(?Opd $opd = null): User
    {
        return User::factory()->create(['opd_id' => ($opd ?? $this->opdA)->id])->assignRole(User::ROLE_ADMIN_OPD);
    }

    private function umkm(Opd $opd): Umkm
    {
        $pemilik = PemilikUsaha::create([
            'nama_lengkap' => 'Budi', 'nik' => 'NIK-' . uniqid(), 'jenis_kelamin' => 'L',
            'alamat' => '-', 'kabupaten' => $opd->kabupaten, 'kecamatan' => '-', 'telepon' => '6281234567890',
        ]);

        return Umkm::create([
            'pemilik_usaha_id' => $pemilik->id, 'opd_id' => $opd->id,
            'nama_usaha' => 'Kopi ' . $opd->kode_opd, 'slug' => 'kopi-' . uniqid(),
            'sektor' => 'kuliner', 'kabupaten' => $opd->kabupaten, 'kecamatan' => '-',
            'alamat_usaha' => '-', 'status' => 'aktif',
        ]);
    }

    private function ubah(User $user, Umkm $umkm, string $kolom, $nilai)
    {
        return $this->actingAs($user)->patchJson("/admin/profil-umkm/{$umkm->id}", ['kolom' => $kolom, 'nilai' => $nilai]);
    }

    public function test_page_lists_umkm_and_shows_profile_values(): void
    {
        $umkm = $this->umkm($this->opdA);

        $this->actingAs($this->adminOpd())->get('/admin/profil-umkm')
            ->assertOk()->assertSee('— Pilih UMKM —')->assertSee('Kopi A');

        $this->actingAs($this->adminOpd())->get("/admin/profil-umkm?umkm={$umkm->id}")
            ->assertOk()
            ->assertSee('Nama Pemilik Usaha')
            ->assertSee('Berapa rata-rata omzet usaha Anda perbulan :')
            ->assertSee('foto_url');
    }

    public function test_admin_opd_cannot_view_or_edit_other_region(): void
    {
        $lain = $this->umkm($this->opdB);

        $this->actingAs($this->adminOpd())->get("/admin/profil-umkm?umkm={$lain->id}")->assertForbidden();
        $this->ubah($this->adminOpd(), $lain, 'nama_usaha', 'Diambil alih')->assertForbidden();

        $this->assertSame('Kopi B', $lain->fresh()->nama_usaha);
    }

    public function test_fields_are_saved_to_their_tables(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->ubah($admin, $umkm, 'pemilik_nama', 'Budi Santoso')->assertOk();
        $this->ubah($admin, $umkm, 'pemilik_whatsapp', '0812-3456-7890')->assertOk();
        $this->ubah($admin, $umkm, 'alamat_usaha', 'Jl. Raya No. 1, Sambas')->assertOk()->assertJsonPath('kabupaten', 'Sambas');
        $this->ubah($admin, $umkm, 'program_bi', 'Wirausaha Muda BI')->assertOk();
        $this->ubah($admin, $umkm, 'omzet_bulanan', '5000000')->assertOk()->assertJsonPath('nilai.omzet_bulanan', 5000000);
        $this->ubah($admin, $umkm, 'marketplace', 'Toko kami https://www.tokopedia.com/kopi dan shopee')->assertOk();
        $this->ubah($admin, $umkm, 'sertifikasi_produk', 'Halal, P-IRT')->assertOk();

        $umkm->refresh();
        $this->assertSame('Budi Santoso', $umkm->pemilik->nama_lengkap);
        $this->assertSame('6281234567890', $umkm->pemilik->telepon);
        $this->assertSame('Wirausaha Muda BI', $umkm->profil->program_bi);
        $this->assertEquals(60000000, $umkm->keuanganTerakhir->omzet_tahunan);
        $this->assertSame('https://www.tokopedia.com/kopi', $umkm->tokopedia);
        $this->assertNull($umkm->shopee);
        $this->assertSame('ADA', $umkm->legalitas->nomor_halal);
        $this->assertSame('ADA', $umkm->legalitas->nomor_pirt);
        $this->assertNull($umkm->legalitas->nomor_bpom);
        $this->assertGreaterThan(0, $umkm->skor_sertifikasi);
    }

    public function test_product_fields_need_unggulan_product_first(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->ubah($admin, $umkm, 'kapasitas_produksi', '100')->assertStatus(422)->assertJsonValidationErrors('nilai');

        $this->ubah($admin, $umkm, 'produk_unggulan', 'Kopi Robusta')->assertOk();
        $this->ubah($admin, $umkm, 'kapasitas_produksi', '100')->assertOk();
        $this->ubah($admin, $umkm, 'foto_url', 'https://drive.google.com/file/d/abc123/view')->assertOk();

        $produk = $umkm->produkUtama();
        $this->assertSame('Kopi Robusta', $produk->nama_produk);
        $this->assertTrue($produk->is_unggulan);
        $this->assertSame(100, (int) $produk->kapasitas_produksi);
        $this->assertSame('https://drive.google.com/file/d/abc123/view', $produk->foto_url);
    }

    public function test_hapus_clears_optional_fields_but_not_required_ones(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->ubah($admin, $umkm, 'website', 'https://kopi.id')->assertOk();
        $this->ubah($admin, $umkm, 'website', null)->assertOk()->assertJsonPath('nilai.website', null);
        $this->assertFalse($umkm->fresh()->pemasaran->memiliki_website);

        $this->ubah($admin, $umkm, 'nama_usaha', null)->assertStatus(422)->assertJsonValidationErrors('nilai');
        $this->ubah($admin, $umkm, 'tahun_berdiri', '1800')->assertStatus(422);
        $this->ubah($admin, $umkm, 'sektor', 'tidak-ada')->assertStatus(422);
        $this->ubah($admin, $umkm, 'skor_total', '100')->assertStatus(422)->assertJsonValidationErrors('kolom');

        $this->assertSame('Kopi A', $umkm->fresh()->nama_usaha);
    }

    public function test_rekomendasi_program_saves_a_cleaned_list_and_shows_on_map_detail(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->ubah($admin, $umkm, 'rekomendasi_program', [
            '  Pendampingan  Sertifikasi Halal ', 'QRIS', 'qris', '', 'Business Matching Ekspor',
        ])->assertOk()->assertJsonPath('nilai.rekomendasi_program', [
            'Pendampingan Sertifikasi Halal', 'QRIS', 'Business Matching Ekspor',
        ]);

        $super = User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super)->get("/superadmin/peta-interaktif/umkm/{$umkm->id}")
            ->assertOk()
            ->assertSeeInOrder(['Ditetapkan KPw BI', 'Pendampingan Sertifikasi Halal', 'QRIS', 'Business Matching Ekspor', 'Usulan otomatis sistem']);

        // Hapus = daftar kosong
        $this->ubah($admin, $umkm, 'rekomendasi_program', null)->assertOk()->assertJsonPath('nilai.rekomendasi_program', []);
        $this->actingAs($super)->get("/superadmin/peta-interaktif/umkm/{$umkm->id}")->assertSee('Belum ada program yang ditetapkan.');
    }

    public function test_rekomendasi_program_suggests_programs_from_other_umkm(): void
    {
        $admin = $this->adminOpd();
        $lain1 = $this->umkm($this->opdA);
        $lain2 = $this->umkm($this->opdA);
        $umkm  = $this->umkm($this->opdA);

        $this->ubah($admin, $lain1, 'rekomendasi_program', ['QRIS', 'Klaster Pangan'])->assertOk();
        $this->ubah($admin, $lain2, 'rekomendasi_program', ['QRIS'])->assertOk();
        $this->ubah($admin, $umkm, 'rekomendasi_program', ['Hanya Milik UMKM Ini'])->assertOk();

        // Terbanyak dipakai dulu; program milik UMKM itu sendiri tidak dihitung sebagai usulan
        $this->assertSame(['QRIS' => 2, 'Klaster Pangan' => 1], \App\Models\ProfilUmkm::usulanProgram($umkm->id));

        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")
            ->assertOk()
            ->assertSee('Pernah diusulkan untuk UMKM lain')
            ->assertSeeInOrder(['QRIS', 'Klaster Pangan'])
            ->assertSee('Usulan otomatis sistem untuk UMKM ini');
    }

    public function test_rekomendasi_program_validation(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->ubah($admin, $umkm, 'rekomendasi_program', 'QRIS')->assertStatus(422);
        $this->ubah($admin, $umkm, 'rekomendasi_program', [str_repeat('a', 151)])->assertStatus(422);
        $this->ubah($admin, $umkm, 'rekomendasi_program', array_map(fn ($i) => "Program {$i}", range(1, 21)))
            ->assertStatus(422)->assertJsonPath('errors.nilai.0', 'Maksimal 20 program.');
        $this->ubah($admin, $umkm, 'pemilik_email', ['bukan@teks.id'])->assertStatus(422);
    }

    public function test_import_keeps_questionnaire_answers(): void
    {
        $header = [
            'Nama Pemilik Usaha', 'No Whatsapp', 'Program yang pernah diikuti dari Bank Indonesia', 'Nama UMKM/Usaha',
            'Alamat Usaha', 'Sertifikasi produk yang dimiliki :',
            'Apakah pada tahun 2026 sudah mendapatkan pembiayaan dari lembaga keuangan perbankan dan atau non perbankan ?',
            'Jika ada rencana akses pembiayaan sebutkan nama lembaga keuangan dan jumlah plafond yang akan diajukan ',
            'Produk / Jasa unggulan', 'foto_url',
        ];
        $baris = ['Siti', '081299998888', 'Klaster Pangan', 'Madu Hutan', 'Jl. A, Pontianak', 'Halal',
                  'Belum', 'BRI KUR Rp50 juta', 'Madu', 'https://drive.google.com/file/d/xyz/view'];

        $csv = UploadedFile::fake()->createWithContent('data.csv',
            implode("\n", array_map(fn ($r) => implode(',', array_map(fn ($v) => '"' . $v . '"', $r)), [$header, $baris])));

        $this->actingAs($this->adminOpd())->post('/admin/import', ['file' => $csv])->assertSessionHas('success');

        $umkm = Umkm::where('nama_usaha', 'Madu Hutan')->firstOrFail();
        $this->assertSame('Klaster Pangan', $umkm->profil->program_bi);
        $this->assertSame('Belum', $umkm->profil->pembiayaan_2026);
        $this->assertSame('BRI KUR Rp50 juta', $umkm->profil->rencana_pembiayaan);
        $this->assertSame('Halal', $umkm->profil->sertifikasi_produk);
        $this->assertSame('https://drive.google.com/file/d/xyz/view', $umkm->produkUtama()->foto_url);
    }
}
