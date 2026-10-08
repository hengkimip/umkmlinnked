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

    public function test_page_shows_score_breakdown_and_auto_recommendations_like_map_detail(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")
            ->assertOk()
            ->assertSeeInOrder(['Rincian skor kesiapan', 'Data pemilik usaha', 'Rekomendasi Program Dinas A', 'Usulan otomatis sistem'])
            ->assertSee('Pendampingan Sertifikasi Halal') // usulan otomatis: UMKM kuliner tanpa Halal
            ->assertViewHas('rincianSkor', fn ($r) => end($r) === ['label' => 'Keuangan', 'nilai' => 0, 'maks' => 15]);

        // Setelah kolom disimpan, skor & usulan dikirim ulang agar tampilan ikut berubah
        $res = $this->ubah($admin, $umkm, 'sertifikasi_produk', 'Halal')->assertOk();

        $this->assertSame(['Legalitas', 'Sertifikasi', 'Produksi', 'Pemasaran', 'Keuangan'], array_column($res->json('rincian_skor'), 'label'));
        $this->assertGreaterThan(0, collect($res->json('rincian_skor'))->firstWhere('label', 'Sertifikasi')['nilai']);
        $this->assertNotContains('Pendampingan Sertifikasi Halal', array_column($res->json('rekomendasi'), 'program'));
    }

    public function test_page_has_searchable_umkm_picker_and_delete_button(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->actingAs($admin)->get('/admin/profil-umkm')
            ->assertOk()
            ->assertSee('Cari nama UMKM atau kabupaten/kota')
            ->assertSee('Kopi A')
            ->assertDontSee('Hapus UMKM');

        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")
            ->assertOk()
            // Tombol Hapus UMKM di atas Foto produk; notifikasi konfirmasi dengan tombol Batal & Hapus
            ->assertSeeInOrder(['Hapus UMKM', 'Foto produk'])
            ->assertSeeInOrder([
                'Apakah Anda yakin ingin menghapus data UMKM ini? Tindakan ini tidak dapat dibatalkan.',
                '>Batal<', '>Hapus<',
            ], false);
    }

    public function test_delete_umkm_archives_it_and_logs_who_deleted_it(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->actingAs($admin)
            ->delete("/admin/profil-umkm/{$umkm->id}")
            ->assertRedirect('/admin/profil-umkm')
            ->assertSessionHas('success', 'UMKM "Kopi A" telah dihapus.');

        $this->assertSoftDeleted($umkm);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Umkm::class,
            'subject_id'   => $umkm->id,
            'event'        => 'deleted',
            'causer_id'    => $admin->id,
        ]);

        // Hilang dari daftar admin, direktori publik, dan peta
        $this->actingAs($admin)->get('/admin/profil-umkm')
            ->assertSee('telah dihapus')
            ->assertViewHas('daftarUmkm', fn ($daftar) => $daftar->isEmpty());
        $this->get("/semua-brand/{$umkm->slug}")->assertNotFound();
        $super = User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super)->getJson('/peta-interaktif/data')->assertJsonCount(0, 'umkm');
    }

    public function test_admin_opd_cannot_delete_umkm_of_other_region(): void
    {
        $lain = $this->umkm($this->opdB);

        $this->actingAs($this->adminOpd())->delete("/admin/profil-umkm/{$lain->id}")->assertForbidden();
        $this->assertNotSoftDeleted($lain);
    }

    public function test_only_opd_pembina_and_super_admin_can_edit_profile(): void
    {
        $milik     = $this->umkm($this->opdA);
        $lain      = $this->umkm($this->opdB);
        $tanpaOpd  = $this->umkm($this->opdA);
        \Illuminate\Support\Facades\DB::table('umkm')->where('id', $tanpaOpd->id)->update(['opd_id' => null]);
        $admin     = $this->adminOpd();
        $super     = User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);

        // Admin OPD melihat SEMUA UMKM di daftar pilihan; binaannya ditandai
        $this->actingAs($admin)->get('/admin/profil-umkm')
            ->assertViewHas('daftarUmkm', fn ($d) => $d->count() === 3);

        // Admin OPD pembina (Otoritas Edit): lihat, ubah, hapus UMKM binaannya
        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$milik->id}")->assertOk()
            ->assertViewHas('bolehUbah', true)
            ->assertSeeInOrder(['Binaan Dinas A', 'Otoritas Edit', 'Didaftar ' . $milik->created_at->format('d/m/Y')])
            ->assertSee('title="Dapat diubah oleh: Dinas A (OPD pembina) &amp; Super Admin"', false) // rincian di tooltip
            ->assertDontSee('status binaan tetap</p>', false)
            ->assertDontSee('Mode lihat saja')
            ->assertSee('Hapus UMKM')
            ->assertSee("@click=\"mulai('tahun_berdiri')\"", false);
        $this->ubah($admin, $milik, 'tahun_berdiri', 2020)->assertOk();

        // Admin OPD bukan pembina: boleh MELIHAT (mode lihat saja), tidak bisa mengubah atau menghapus
        foreach ([$lain, $tanpaOpd->fresh()] as $u) {
            $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$u->id}")->assertOk()
                ->assertViewHas('bolehUbah', false)
                ->assertSee('Mode lihat saja')
                ->assertSee('Kopi ' . ($u->is($lain) ? 'B' : 'A'))
                ->assertDontSee("@click=\"mulai('tahun_berdiri')\"", false)  // tombol Ubah per kolom
                ->assertDontSee('Hapus UMKM')
                ->assertDontSee(route('admin.produk.upload-foto', ['umkm' => $u->id]), false);
            $this->ubah($admin, $u, 'tahun_berdiri', 2001)->assertForbidden();
            $this->actingAs($admin)->delete("/admin/profil-umkm/{$u->id}")->assertForbidden();
            $this->assertNull($u->fresh()->tahun_berdiri);
            $this->assertNotSoftDeleted($u);
        }

        // Super Admin: semua UMKM, termasuk binaan OPD lain & tanpa OPD pembina
        $this->actingAs($super)->get('/admin/profil-umkm')
            ->assertViewHas('daftarUmkm', fn ($d) => $d->count() === 3);
        foreach ([$milik, $lain, $tanpaOpd] as $u) {
            $this->actingAs($super)->get("/admin/profil-umkm?umkm={$u->id}")->assertOk();
            $this->ubah($super, $u, 'tahun_berdiri', 2015)->assertOk();
            $this->assertSame(2015, $u->fresh()->tahun_berdiri);
        }
        $this->actingAs($super)->get("/admin/profil-umkm?umkm={$tanpaOpd->id}")
            ->assertSee('Dapat diubah oleh: Super Admin');
    }

    public function test_admin_opd_can_view_but_not_edit_other_region(): void
    {
        $lain = $this->umkm($this->opdB);

        $this->actingAs($this->adminOpd())->get("/admin/profil-umkm?umkm={$lain->id}")->assertOk()->assertSee('Mode lihat saja');
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
        $this->actingAs($super)->get("/peta-interaktif/umkm/{$umkm->id}")
            ->assertOk()
            ->assertSeeInOrder(['Ditetapkan KPw BI', 'Pendampingan Sertifikasi Halal', 'QRIS', 'Business Matching Ekspor', 'Usulan otomatis sistem']);

        // Hapus = daftar kosong
        $this->ubah($admin, $umkm, 'rekomendasi_program', null)->assertOk()->assertJsonPath('nilai.rekomendasi_program', []);
        $this->actingAs($super)->get("/peta-interaktif/umkm/{$umkm->id}")->assertSee('Belum ada program yang ditetapkan.');
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

    public function test_program_labels_follow_the_users_institution(): void
    {
        $umkm = $this->umkm($this->opdA);

        // Super Admin: Bank Indonesia / KPw BI
        $super = User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super)->get("/admin/profil-umkm?umkm={$umkm->id}")->assertOk()
            ->assertSee('Program yang pernah diikuti dari Bank Indonesia')
            ->assertSee('Rekomendasi Program KPw BI')
            ->assertSee('tim KPw BI Kalimantan Barat');
        $this->actingAs($super)->get('/admin/import')->assertOk()
            ->assertSee('Program yang pernah diikuti dari Bank Indonesia')->assertSee('Rekomendasi Program KPw BI');

        // Admin OPD: nama OPD-nya
        $admin = $this->adminOpd();
        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")->assertOk()
            ->assertSee('Program yang pernah diikuti dari Dinas A')
            ->assertSee('Rekomendasi Program Dinas A')
            ->assertDontSee('Bank Indonesia')
            ->assertDontSee('KPw BI');
        $this->actingAs($admin)->get('/admin/import')->assertOk()
            ->assertSee('Program yang pernah diikuti dari Dinas A')->assertSee('Rekomendasi Program Dinas A');
        $this->ubah($admin, $umkm, 'rekomendasi_program', ['Pelatihan Ekspor'])
            ->assertJsonPath('message', 'Rekomendasi Program Dinas A berhasil disimpan.');
    }

    public function test_kota_kabupaten_field_is_editable_in_kelola_profil(): void
    {
        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();

        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")->assertOk()
            ->assertSeeInOrder(['Data usaha', 'Alamat Usaha', 'Kota/Kabupaten', 'Tahun Berdirinya Usaha']);

        $this->ubah($admin, $umkm, 'kabupaten', 'Kubu Raya')->assertOk()->assertJsonPath('kabupaten', 'Kubu Raya');
        $this->assertSame('Kubu Raya', $umkm->fresh()->kabupaten);

        $this->ubah($admin, $umkm, 'kabupaten', 'Jakarta')->assertUnprocessable();
        $this->ubah($admin, $umkm, 'kabupaten', null)->assertUnprocessable(); // wajib, tidak bisa dihapus
    }

    public function test_profile_header_shows_binaan_opd(): void
    {
        $umkm = $this->umkm($this->opdA);

        $this->actingAs($this->adminOpd())->get("/admin/profil-umkm?umkm={$umkm->id}")->assertOk()
            ->assertSeeInOrder(['Binaan Dinas A', 'Profil UMKM', 'Kopi A']);
    }

    public function test_import_queues_umkm_already_registered_by_any_opd(): void
    {
        $file = fn () => UploadedFile::fake()->createWithContent('data.csv', implode("
", [
            'nama_pemilik_usaha,no_whatsapp,nama_umkmusaha,alamat_usaha,sektor_usaha',
            'Ani,081211110001,Amplang Ani,"Jl. A, Pontianak",Kuliner',
            'Ani,081211110001,AMPLANG  ani,"Jl. A, Pontianak",Kuliner',   // ganda di file yang sama
        ]));

        $this->actingAs($this->adminOpd())->post('/admin/import', ['file' => $file()])
            ->assertSessionHas('success', 'Berhasil mengimpor 1 data UMKM. 1 baris mirip UMKM yang sudah terdaftar dan masuk antrean review duplikat.')
            ->assertSessionMissing('import_errors');

        // OPD lain mengunggah file yang sama → kedua baris masuk antrean, tidak disimpan
        $this->actingAs($this->adminOpd($this->opdB))->post('/admin/import', ['file' => $file()])
            ->assertSessionHas('success', 'Berhasil mengimpor 0 data UMKM. 2 baris mirip UMKM yang sudah terdaftar dan masuk antrean review duplikat.');

        $this->assertSame(1, Umkm::where('nama_usaha', 'like', 'amplang%')->count());
        $this->assertSame(3, \App\Models\UmkmDuplikat::menunggu()->where('sumber', 'impor')->count());
    }

    public function test_csv_template_and_import_use_kota_kabupaten_column(): void
    {
        $template = $this->actingAs($this->adminOpd())->get('/admin/import/template')->assertOk()->streamedContent();
        $this->assertStringContainsString('alamat_usaha,kota_kabupaten,tahun_berdirinya_usaha', $template);
        $this->assertStringContainsString('"Kota Pontianak"', $template);

        // Kolom kota_kabupaten diutamakan; bila kosong, ditebak dari alamat
        $csv = UploadedFile::fake()->createWithContent('data.csv', implode("
", [
            'nama_pemilik_usaha,no_whatsapp,nama_umkmusaha,alamat_usaha,kota_kabupaten,sektor_usaha',
            'Ani,081211110001,Amplang Ani,"Jl. A, Pontianak",Kab. Sambas,Kuliner',
            'Budi,081211110002,Kopi Budi,"Jl. B, Singkawang",,Kuliner',
        ]));
        $this->actingAs($this->adminOpd())->post('/admin/import', ['file' => $csv])->assertSessionHas('success');

        $this->assertSame('Sambas', Umkm::firstWhere('nama_usaha', 'Amplang Ani')->kabupaten);
        $this->assertSame('Singkawang', Umkm::firstWhere('nama_usaha', 'Kopi Budi')->kabupaten);
    }

    public function test_sektor_usaha_only_has_six_options_in_import_and_profile(): void
    {
        $csv = UploadedFile::fake()->createWithContent('data.csv', implode("\n", [
            'nama_pemilik_usaha,no_whatsapp,nama_umkmusaha,alamat_usaha,sektor_usaha',
            'A,081211110001,Usaha Satu,"Jl. A, Pontianak",Tenun / Wastra',
            'B,081211110002,Usaha Dua,"Jl. B, Pontianak",Perikanan',
            'C,081211110003,Usaha Tiga,"Jl. C, Pontianak",Teknologi Digital',
            'D,081211110004,Usaha Empat,"Jl. D, Pontianak",Manufaktur',
        ]));
        $this->actingAs($this->adminOpd())->post('/admin/import', ['file' => $csv])->assertSessionHas('success');

        $this->assertSame(
            ['Usaha Satu' => 'fashion', 'Usaha Dua' => 'pertanian', 'Usaha Tiga' => 'jasa', 'Usaha Empat' => 'lainnya'],
            Umkm::whereLike('nama_usaha', 'Usaha %')->pluck('sektor', 'nama_usaha')->all(),
        );

        $umkm  = $this->umkm($this->opdA);
        $admin = $this->adminOpd();
        $this->actingAs($admin)->get("/admin/profil-umkm?umkm={$umkm->id}")->assertOk()
            ->assertSeeInOrder(['Kuliner', 'Fesyen/Wastra', 'Kerajinan', 'Pertanian &amp; Agroindustri', 'Jasa', 'Lainnya'], false)
            ->assertDontSee('Manufaktur');
        $this->ubah($admin, $umkm, 'sektor', 'manufaktur')->assertUnprocessable();
        $this->ubah($admin, $umkm, 'sektor', 'fashion')->assertOk();
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
