<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\UmkmDuplikat;
use App\Models\User;
use App\Services\DeteksiDuplikatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Deteksi duplikasi dengan skor kemiripan gabungan + antrean review manual.
 */
class DeteksiDuplikatTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opdA;
    private Opd $opdB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $this->opdA = Opd::create(['nama_opd' => 'Bank Indonesia Kalbar', 'kode_opd' => 'BI', 'kabupaten' => 'Pontianak']);
        $this->opdB = Opd::create(['nama_opd' => 'Dinas Koperasi', 'kode_opd' => 'DK', 'kabupaten' => 'Pontianak']);
    }

    private function admin(Opd $opd): User
    {
        return User::factory()->create(['opd_id' => $opd->id])->assignRole(User::ROLE_ADMIN_OPD);
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
    }

    /** UMKM tersimpan: Keripik Siti milik Siti Aminah, Pontianak. */
    private function tersimpan(array $umkm = [], array $pemilik = []): Umkm
    {
        $p = PemilikUsaha::create($pemilik + [
            'nama_lengkap' => 'Siti Aminah', 'nik' => 'NIK-' . uniqid(), 'jenis_kelamin' => 'P',
            'alamat' => 'Jl. Merdeka 1', 'kabupaten' => 'Pontianak', 'kecamatan' => '-',
            'telepon' => '6281211112222', 'email' => 'siti@contoh.id',
        ]);

        return Umkm::create($umkm + [
            'pemilik_usaha_id' => $p->id, 'opd_id' => $this->opdA->id,
            'nama_usaha' => 'Keripik Siti', 'slug' => 'keripik-siti-' . uniqid(), 'sektor' => 'kuliner',
            'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'alamat_usaha' => 'Jl. Gajah Mada No. 10, Pontianak',
            'tahun_berdiri' => 2019, 'status' => 'aktif',
        ]);
    }

    /** Data baru dalam format kolom (sama dengan formulir Tambah Manual). */
    private function data(array $ubah = []): array
    {
        return $ubah + [
            'pemilik_nama' => 'Siti Aminah', 'pemilik_alamat' => 'Jl. Merdeka 1', 'pemilik_whatsapp' => '0812-1111-2222',
            'pemilik_email' => 'SITI@contoh.id', 'nama_usaha' => 'Keripik Siti', 'kabupaten' => 'Pontianak',
            'alamat_usaha' => 'Jl. Gajah Mada No. 10, Pontianak', 'sektor' => 'kuliner', 'produk_unggulan' => 'Keripik Pisang',
        ];
    }

    private function skor(array $data, Umkm $umkm): array
    {
        return app(DeteksiDuplikatService::class)->skor($data, $umkm);
    }

    // ==================== Skor & normalisasi ====================

    public function test_normalisation_of_whatsapp_and_text(): void
    {
        $this->assertSame('6281211112222', DeteksiDuplikatService::normalWa('0812-1111 2222'));
        $this->assertSame('6281211112222', DeteksiDuplikatService::normalWa('+62 812 1111 2222'));
        $this->assertSame('cv kopi kapuas', DeteksiDuplikatService::normalTeks('  CV. Kopi,  KAPUAS!! '));
    }

    public function test_every_signal_contributes_its_weight(): void
    {
        $umkm = $this->tersimpan();

        $hasil = $this->skor($this->data(), $umkm);
        $this->assertSame(100, $hasil['skor']);
        $this->assertSame(['pemilik' => 25, 'usaha' => 25, 'alamat' => 20, 'wa' => 20, 'email' => 10],
            array_map(fn ($r) => $r['dapat'], $hasil['rincian']));
    }

    public function test_fuzzy_matching_tolerates_spelling_but_not_different_names(): void
    {
        $umkm = $this->tersimpan();

        // Salah ketik ringan (≥80% mirip) tetap dihitung
        $r = $this->skor($this->data(['pemilik_nama' => 'Siti Aminnah', 'nama_usaha' => 'Kripik Siti']), $umkm)['rincian'];
        $this->assertSame(25, $r['pemilik']['dapat']);
        $this->assertSame(25, $r['usaha']['dapat']);

        // Nama berbeda tidak dihitung
        $r = $this->skor($this->data(['pemilik_nama' => 'Budi Santoso', 'nama_usaha' => 'Kopi Kapuas']), $umkm)['rincian'];
        $this->assertSame(0, $r['pemilik']['dapat']);
        $this->assertSame(0, $r['usaha']['dapat']);
    }

    public function test_address_only_counts_within_the_same_kabupaten(): void
    {
        $umkm = $this->tersimpan();

        $this->assertSame(20, $this->skor($this->data(), $umkm)['rincian']['alamat']['dapat']);
        $this->assertSame(0, $this->skor($this->data(['kabupaten' => 'Sambas']), $umkm)['rincian']['alamat']['dapat']);
    }

    public function test_changed_phone_number_is_still_detected(): void
    {
        $this->tersimpan();

        // Nomor & e-mail baru, tetapi pemilik + nama usaha + alamat sama → 70 ≥ 60
        $hasil = app(DeteksiDuplikatService::class)->periksa($this->data([
            'pemilik_whatsapp' => '0899-0000-1111', 'pemilik_email' => 'baru@contoh.id',
        ]));
        $this->assertSame(70, $hasil['skor']);
    }

    public function test_below_threshold_is_treated_as_new_data(): void
    {
        $this->tersimpan();

        // Nama usaha sama + WA sama, pemilik & alamat beda → 45 < 60
        $this->assertNull(app(DeteksiDuplikatService::class)->periksa($this->data([
            'pemilik_nama' => 'Orang Lain', 'alamat_usaha' => 'Jl. Tanjungpura 99', 'pemilik_email' => null,
        ])));

        // UMKM di kabupaten lain tidak menjadi kandidat
        $this->assertNull(app(DeteksiDuplikatService::class)->periksa($this->data(['kabupaten' => 'Sambas'])));
    }

    public function test_threshold_is_configurable(): void
    {
        $this->tersimpan();
        $data = $this->data(['pemilik_nama' => 'Orang Lain', 'alamat_usaha' => 'Jl. Tanjungpura 99', 'pemilik_email' => null]);

        config(['umkm.ambang_duplikat' => 40]);
        $this->assertSame(45, app(DeteksiDuplikatService::class)->periksa($data)['skor']);
    }

    // ==================== Antrean & keputusan ====================

    public function test_import_row_goes_to_queue_and_can_be_saved_as_a_different_business(): void
    {
        $this->tersimpan();
        $csv = UploadedFile::fake()->createWithContent('data.csv', implode("\n", [
            'nama_pemilik_usaha,no_whatsapp,alamat_e_mail,nama_umkmusaha,alamat_usaha,kota_kabupaten,sektor_usaha,produk_jasa_unggulan',
            'Siti Aminah,081211112222,siti@contoh.id,Keripik Siti Cabang 2,"Jl. Gajah Mada No. 10, Pontianak",Kota Pontianak,Kuliner,Keripik',
        ]));

        $adminB = $this->admin($this->opdB);
        $this->actingAs($adminB)->post('/admin/import', ['file' => $csv])->assertSessionHas('success');
        $this->assertSame(1, Umkm::count());

        $antre = UmkmDuplikat::sole();
        $this->assertSame('impor', $antre->sumber);
        $this->assertSame('Keripik Siti Cabang 2', $antre->data['nama_usaha']);
        $this->assertSame('Pontianak', $antre->data['kabupaten']);

        // Admin OPD pengunggah melihat antrean & memutuskan "Usaha berbeda"
        $this->actingAs($adminB)->get('/admin/duplikat')->assertOk()
            ->assertSee('Keripik Siti Cabang 2')
            ->assertSee('Binaan Bank Indonesia Kalbar')
            ->assertSee('Skor kemiripan ' . $antre->skor . '/100')
            ->assertSee('Usaha berbeda — simpan sebagai UMKM baru')
            ->assertDontSee('Usaha yang sama — perbarui data lama'); // UMKM lama binaan OPD lain

        $this->actingAs($adminB)->post("/admin/duplikat/{$antre->id}/berbeda")->assertRedirect();

        $baru = Umkm::firstWhere('nama_usaha', 'Keripik Siti Cabang 2');
        $this->assertSame($this->opdB->id, $baru->opd_id);
        $this->assertSame(UmkmDuplikat::DIBUAT_BARU, $antre->fresh()->status);
        $this->assertSame($baru->id, $antre->fresh()->umkm_hasil_id);
        $this->assertSame(2, Umkm::count());
    }

    public function test_same_business_updates_existing_data_with_new_contact(): void
    {
        $lama = $this->tersimpan();
        $adminA = $this->admin($this->opdA);

        // Manual: nomor WA baru (kontak berganti) → skor 70 → antrean
        $this->actingAs($adminA)->post('/admin/import/manual', $this->data([
            'pemilik_whatsapp' => '0899-0000-1111', 'pemilik_email' => null, 'tahun_berdiri' => '2018',
        ]))->assertRedirect('/admin/duplikat');
        $antre = UmkmDuplikat::sole();

        $this->actingAs($adminA)->post("/admin/duplikat/{$antre->id}/sama")->assertRedirect()->assertSessionHas('success');

        $lama->refresh();
        $this->assertSame('6289900001111', $lama->pemilik->telepon); // kontak baru menggantikan kontak lama
        $this->assertSame('siti@contoh.id', $lama->pemilik->email);  // isian kosong tidak menghapus data lama
        $this->assertSame(2018, $lama->tahun_berdiri);
        $this->assertSame(1, Umkm::count());
        $this->assertSame(UmkmDuplikat::DIGABUNG, $antre->fresh()->status);

        // Sudah diputuskan → tidak bisa diputuskan lagi
        $this->actingAs($adminA)->post("/admin/duplikat/{$antre->id}/berbeda")->assertStatus(409);
    }

    public function test_proposed_data_can_be_discarded(): void
    {
        $lama  = $this->tersimpan(); // binaan OPD A
        $adminB = $this->admin($this->opdB);
        $this->actingAs($adminB)->post('/admin/import/manual', $this->data(['tahun_berdiri' => '2010']))->assertRedirect('/admin/duplikat');
        $antre = UmkmDuplikat::sole();

        $this->actingAs($adminB)->get('/admin/duplikat')->assertSeeInOrder([
            'Usaha berbeda — simpan sebagai UMKM baru', 'Hapus data usulan baru',
        ]);

        // OPD yang tidak terkait tidak boleh menghapus
        $opdC = Opd::create(['nama_opd' => 'Dinas C', 'kode_opd' => 'C', 'kabupaten' => 'Sambas']);
        $this->actingAs($this->admin($opdC))->delete("/admin/duplikat/{$antre->id}")->assertForbidden();

        $this->actingAs($adminB)->delete("/admin/duplikat/{$antre->id}")
            ->assertRedirect()->assertSessionHas('success', 'Data usulan baru "Keripik Siti" dihapus dan tidak disimpan.');

        // Tidak ada UMKM baru, data lama tidak berubah, entri tercatat di riwayat
        $this->assertSame(1, Umkm::count());
        $this->assertSame(2019, $lama->fresh()->tahun_berdiri);
        $this->assertSame(UmkmDuplikat::DIHAPUS, $antre->fresh()->status);
        $this->assertNull($antre->fresh()->umkm_hasil_id);
        $this->actingAs($adminB)->get('/admin/duplikat?status=selesai')->assertSee('Data usulan baru dihapus (tidak disimpan)');
        $this->actingAs($adminB)->get('/admin/duplikat')->assertSee('Tidak ada data yang menunggu review.');

        // Sudah diputuskan → tidak bisa dihapus/diputuskan lagi
        $this->actingAs($adminB)->delete("/admin/duplikat/{$antre->id}")->assertStatus(409);
    }

    public function test_binaan_belongs_to_the_first_opd_that_registered_the_umkm(): void
    {
        $adminA = $this->admin($this->opdA);
        $adminB = $this->admin($this->opdB);

        // OPD A memasukkan pertama → binaan A, pendaftar tercatat
        $this->actingAs($adminA)->post('/admin/import/manual', $this->data())->assertSessionHasNoErrors();
        $umkm = Umkm::sole();
        $this->assertSame($this->opdA->id, $umkm->opd_id);
        $this->assertSame($adminA->id, $umkm->didaftarkan_oleh);
        $this->actingAs($adminA)->get("/admin/profil-umkm?umkm={$umkm->id}")
            // Satu baris: Binaan [OPD] - Otoritas Edit - Didaftar dd/mm/yyyy (rincian pendaftar di tooltip)
            ->assertSeeInOrder(['Binaan Bank Indonesia Kalbar', '-', 'Otoritas Edit', '-', 'Didaftar ' . $umkm->created_at->format('d/m/Y')])
            ->assertSee("Didaftarkan pertama oleh {$adminA->name} (Bank Indonesia Kalbar)", false);

        // OPD B memasukkan UMKM yang sama kemudian → tidak menjadi binaan B
        $this->actingAs($adminB)->post('/admin/import/manual', $this->data(['tahun_berdiri' => '2015']))->assertRedirect('/admin/duplikat');
        $antre = UmkmDuplikat::sole();
        $this->actingAs($adminA)->post("/admin/duplikat/{$antre->id}/sama")->assertSessionHas('success');

        $umkm->refresh();
        $this->assertSame(2015, $umkm->tahun_berdiri);          // data diperbarui
        $this->assertSame($this->opdA->id, $umkm->opd_id);       // binaan tetap OPD pertama
        $this->assertSame($adminA->id, $umkm->didaftarkan_oleh); // pendaftar tetap
        $this->assertSame(1, Umkm::count());

        // Mengalihkan binaan lewat model ditolak
        $this->expectException(\LogicException::class);
        $umkm->update(['opd_id' => $this->opdB->id]);
    }

    public function test_different_business_from_queue_is_registered_by_the_uploader(): void
    {
        $this->tersimpan();
        $adminB = $this->admin($this->opdB);
        $this->actingAs($adminB)->post('/admin/import/manual', $this->data(['nama_usaha' => 'Keripik Siti Dua']))->assertRedirect('/admin/duplikat');

        // Super Admin yang memutuskan, tetapi pendaftar & binaan = OPD B (pengunggah)
        $this->actingAs($this->superAdmin())->post('/admin/duplikat/' . UmkmDuplikat::sole()->id . '/berbeda')->assertSessionHas('success');
        $baru = Umkm::firstWhere('nama_usaha', 'Keripik Siti Dua');
        $this->assertSame($this->opdB->id, $baru->opd_id);
        $this->assertSame($adminB->id, $baru->didaftarkan_oleh);
    }

    public function test_review_permissions_follow_opd_scope(): void
    {
        $this->tersimpan(); // binaan OPD A
        $adminB = $this->admin($this->opdB);
        $this->actingAs($adminB)->post('/admin/import/manual', $this->data())->assertRedirect('/admin/duplikat');
        $antre = UmkmDuplikat::sole();

        // OPD B (pengunggah) tidak boleh memperbarui UMKM binaan OPD A
        $this->actingAs($adminB)->post("/admin/duplikat/{$antre->id}/sama")->assertForbidden();

        // OPD A (pembina UMKM lama) melihat entri; boleh "sama", tidak boleh menyimpan ke OPD B
        $adminA = $this->admin($this->opdA);
        $this->actingAs($adminA)->get('/admin/duplikat')->assertSee('Usaha yang sama — perbarui data lama');
        $this->actingAs($adminA)->post("/admin/duplikat/{$antre->id}/berbeda")->assertForbidden();

        // OPD lain yang tidak terkait tidak melihat entri sama sekali
        $opdC = Opd::create(['nama_opd' => 'Dinas C', 'kode_opd' => 'C', 'kabupaten' => 'Sambas']);
        $this->actingAs($this->admin($opdC))->get('/admin/duplikat')->assertDontSee('Keripik Siti');
        $this->actingAs($this->admin($opdC))->post("/admin/duplikat/{$antre->id}/berbeda")->assertForbidden();

        // Super Admin: semua & menu menampilkan jumlah antrean
        $this->actingAs($this->superAdmin())->get('/superadmin/dashboard')->assertSee('Antrean Duplikat')->assertSee('1 menunggu keputusan');
        auth()->logout();
        $this->get('/admin/duplikat')->assertRedirect('/login');
    }
}
