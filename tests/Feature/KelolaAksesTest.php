<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Super Admin (Bank Indonesia Wilayah Kalimantan Barat): akses OPD, batas admin per OPD, kuota Super Admin.
 */
class KelolaAksesTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $this->opd = Opd::create(['nama_opd' => 'Dinas Koperasi Pontianak', 'kode_opd' => 'DK', 'kabupaten' => 'Pontianak', 'maks_admin' => 1]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_SUPER_ADMIN);
    }

    private function adminOpd(?Opd $opd = null): User
    {
        return User::factory()->create(['opd_id' => ($opd ?? $this->opd)->id])->assignRole(User::ROLE_ADMIN_OPD);
    }

    private function akunBaru(array $ubah = []): array
    {
        return $ubah + [
            'peran' => User::ROLE_ADMIN_OPD, 'opd_id' => $this->opd->id, 'name' => 'Admin Baru',
            'email' => 'baru' . uniqid() . '@contoh.id', 'jabatan' => 'Staf',
            'password' => 'Rahasia-12345', 'password_confirmation' => 'Rahasia-12345',
        ];
    }

    public function test_profile_shows_institution(): void
    {
        $this->actingAs($this->superAdmin())->get('/profile')->assertOk()
            ->assertSeeInOrder(['Peran', 'Super Admin', 'Instansi', 'Bank Indonesia Wilayah Kalimantan Barat']);

        $this->actingAs($this->adminOpd())->get('/profile')->assertOk()
            ->assertSeeInOrder(['Peran', 'Admin OPD', 'Instansi', 'Dinas Koperasi Pontianak']);
    }

    public function test_kelola_akses_is_super_admin_only(): void
    {
        $this->get('/superadmin/akses')->assertRedirect('/login');
        $this->actingAs($this->adminOpd())->get('/superadmin/akses')->assertForbidden();
        $this->actingAs($this->adminOpd())->post('/superadmin/akses/pengguna', $this->akunBaru())->assertForbidden();
        $this->actingAs($this->adminOpd())->patch("/superadmin/akses/opd/{$this->opd->id}", ['is_active' => 0])->assertForbidden();

        $this->actingAs($this->superAdmin())->get('/superadmin/akses')->assertOk()
            ->assertSee('Super Admin — Bank Indonesia Wilayah Kalimantan Barat')
            ->assertSee('Dinas Koperasi Pontianak')
            ->assertSee('Kelola Akses');
    }

    public function test_super_admin_grants_and_revokes_opd_access(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super)->post('/superadmin/akses/opd', [
            'nama_opd' => 'Dinas Perdagangan Sambas', 'kode_opd' => 'DAG-SBS', 'kabupaten' => 'Sambas', 'maks_admin' => 2,
        ])->assertSessionHas('success');
        $baru = Opd::firstWhere('kode_opd', 'DAG-SBS');
        $this->assertTrue($baru->is_active);
        $this->assertSame(2, $baru->maks_admin);

        // Nonaktifkan akses OPD → adminnya tidak bisa masuk & yang sedang login dikeluarkan
        $admin = $this->adminOpd();
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['is_active' => 0])->assertSessionHas('success');

        $this->actingAs($admin->fresh())->get('/admin/dashboard')
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Akses OPD Anda sedang dinonaktifkan oleh Super Admin.']);
        $this->assertGuest();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Aktifkan kembali → bisa masuk
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['is_active' => 1]);
        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/dashboard');
    }

    public function test_super_admin_can_grant_access_to_provincial_opd(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->get('/superadmin/akses')->assertOk()
            ->assertSeeInOrder(['id="tambah-opd-title"', 'Beri akses OPD baru', 'id="akun-title"', 'Tambah akun'], false)   // OPD dulu, baru akun
            ->assertSee('Provinsi/Kota/Kabupaten')
            ->assertSee('<option value="Provinsi" >Provinsi Kalimantan Barat</option>', false);

        $this->actingAs($super)->post('/superadmin/akses/opd', [
            'nama_opd' => 'Dinas Koperasi dan UKM Provinsi Kalimantan Barat', 'kode_opd' => 'DISKOP-PROV',
            'kabupaten' => Opd::PROVINSI, 'maks_admin' => 1,
        ])->assertSessionHas('success');

        $prov = Opd::firstWhere('kode_opd', 'DISKOP-PROV');
        $this->assertSame(Opd::PROVINSI, $prov->kabupaten);
        $this->assertSame('Provinsi Kalimantan Barat', $prov->wilayahLabel());
        $this->actingAs($super)->get('/superadmin/akses')->assertSee('DISKOP-PROV · Provinsi Kalimantan Barat');

        // OPD kota/kabupaten bisa dipindah ke tingkat provinsi; wilayah lain tetap ditolak
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['kabupaten' => Opd::PROVINSI])->assertSessionHas('success');
        $this->assertSame(Opd::PROVINSI, $this->opd->fresh()->kabupaten);

        $this->actingAs($super)->post('/superadmin/akses/opd', [
            'nama_opd' => 'Dinas Luar', 'kode_opd' => 'LUAR', 'kabupaten' => 'Jakarta', 'maks_admin' => 1,
        ])->assertSessionHasErrors('kabupaten', null, 'opd');
    }

    public function test_super_admin_decides_whether_opd_admins_may_be_duplicated(): void
    {
        $super = $this->superAdmin();
        $this->adminOpd(); // batas 1 → sudah penuh

        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru())
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'sudah memiliki 1 admin aktif'));
        $this->assertSame(1, $this->opd->jumlahAdminAktif());

        // Izinkan admin digandakan (batas 2) → akun kedua boleh dibuat
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['maks_admin' => 2])
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'admin boleh digandakan'));
        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['email' => 'kedua@contoh.id']))->assertSessionHas('success');
        $kedua = User::firstWhere('email', 'kedua@contoh.id');
        $this->assertTrue($kedua->isAdmin());
        $this->assertNotNull($kedua->email_verified_at);
        $this->assertSame(2, $this->opd->jumlahAdminAktif());

        // Batas tidak bisa diturunkan di bawah jumlah admin aktif
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['maks_admin' => 1])->assertSessionHas('error');
        $this->assertSame(2, $this->opd->fresh()->maks_admin);

        // Nonaktifkan akun → bisa diturunkan; mengaktifkan kembali melebihi batas ditolak
        $this->actingAs($super)->patch("/superadmin/akses/pengguna/{$kedua->id}", ['is_active' => 0])->assertSessionHas('success');
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['maks_admin' => 1])->assertSessionHas('success');
        $this->actingAs($super)->patch("/superadmin/akses/pengguna/{$kedua->id}", ['is_active' => 1])->assertSessionHas('error');
        $this->assertFalse($kedua->fresh()->is_active);
    }

    public function test_super_admin_quota_follows_bank_indonesia_setting(): void
    {
        config(['umkm.maks_super_admin' => 1]);
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['peran' => User::ROLE_SUPER_ADMIN, 'opd_id' => null]))
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'Kuota Super Admin penuh (1 orang'));
        $this->assertSame(1, User::jumlahSuperAdminAktif());

        // Kuota 3 → dua Super Admin lagi boleh dibuat, yang keempat ditolak
        config(['umkm.maks_super_admin' => 3]);
        foreach (['dua', 'tiga'] as $n) {
            $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru([
                'peran' => User::ROLE_SUPER_ADMIN, 'opd_id' => null, 'email' => "{$n}@bi.go.id",
            ]))->assertSessionHas('success');
        }
        $this->assertNull(User::firstWhere('email', 'dua@bi.go.id')->opd_id);
        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['peran' => User::ROLE_SUPER_ADMIN, 'opd_id' => null]))
            ->assertSessionHas('error');
        $this->assertSame(3, User::jumlahSuperAdminAktif());

        // Tidak bisa menonaktifkan akun sendiri
        $this->actingAs($super)->patch("/superadmin/akses/pengguna/{$super->id}", ['is_active' => 0])
            ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
    }

    public function test_new_account_is_emailed_to_every_active_super_admin(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $super  = $this->superAdmin();
        $super2 = $this->superAdmin();
        $nonaktif = User::factory()->create(['is_active' => false])->assignRole(User::ROLE_SUPER_ADMIN);
        $adminLain = $this->adminOpd();
        $this->opd->update(['maks_admin' => 2]);

        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['email' => 'opd.baru@contoh.id']))
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'Notifikasi e-mail dikirim ke 2 Super Admin.'));

        $baru = User::firstWhere('email', 'opd.baru@contoh.id');
        foreach ([$super, $super2] as $penerima) {
            \Illuminate\Support\Facades\Notification::assertSentTo($penerima, \App\Notifications\AkunAdminDibuat::class,
                fn ($n) => $n->akun->is($baru) && $n->pembuat->is($super));
        }
        \Illuminate\Support\Facades\Notification::assertNotSentTo([$nonaktif, $adminLain, $baru], \App\Notifications\AkunAdminDibuat::class);

        // Super Admin baru juga diberitahukan ke Super Admin lain (bukan ke dirinya sendiri)
        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru([
            'peran' => User::ROLE_SUPER_ADMIN, 'opd_id' => null, 'email' => 'super3@bi.go.id',
        ]))->assertSessionHas('success');
        $super3 = User::firstWhere('email', 'super3@bi.go.id');
        \Illuminate\Support\Facades\Notification::assertSentToTimes($super2, \App\Notifications\AkunAdminDibuat::class, 2);
        \Illuminate\Support\Facades\Notification::assertNotSentTo($super3, \App\Notifications\AkunAdminDibuat::class);
    }

    public function test_notification_email_content_and_failure_does_not_block_account(): void
    {
        $super = $this->superAdmin();
        $akun  = $this->adminOpd();
        $akun->update(['jabatan' => 'Kepala Bidang']);

        $mail = (new \App\Notifications\AkunAdminDibuat($akun, $super))->toMail($super);
        $this->assertSame("[UMKMLinked] Akun Admin OPD baru: {$akun->name}", $mail->subject);
        $isi = implode("\n", $mail->introLines);
        $this->assertStringContainsString($akun->email, $isi);
        $this->assertStringContainsString('Dinas Koperasi Pontianak', $isi);
        $this->assertStringContainsString('Kepala Bidang', $isi);
        $this->assertStringContainsString($super->email, $isi);
        $this->assertStringNotContainsString('password', strtolower($isi));
        $this->assertSame(route('superadmin.akses.index'), $mail->actionUrl);

        // Server e-mail galat → akun tetap dibuat, admin diberi tahu
        $this->superAdmin();
        $this->opd->update(['maks_admin' => 5]);
        \Illuminate\Support\Facades\Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP mati'));
        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['email' => 'tetap@contoh.id']))
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'notifikasi e-mail ke Super Admin gagal dikirim'));
        $this->assertNotNull(User::firstWhere('email', 'tetap@contoh.id'));
    }

    public function test_super_admin_can_delete_admin_and_super_admin_accounts(): void
    {
        $super  = $this->superAdmin();
        $super2 = $this->superAdmin();
        $admin  = $this->adminOpd();
        $antre  = \App\Models\UmkmDuplikat::forceCreate([
            'umkm_id' => \App\Models\Umkm::create([
                'pemilik_usaha_id' => \App\Models\PemilikUsaha::create([
                    'nama_lengkap' => 'P', 'nik' => 'N1', 'jenis_kelamin' => 'L', 'alamat' => '-',
                    'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'telepon' => '1',
                ])->id,
                'opd_id' => $this->opd->id, 'nama_usaha' => 'U', 'slug' => 'u-1', 'sektor' => 'kuliner',
                'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'alamat_usaha' => '-', 'status' => 'aktif',
            ])->id,
            'opd_id' => $this->opd->id, 'user_id' => $admin->id, 'sumber' => 'manual',
            'data' => [], 'skor' => 70, 'rincian' => [],
        ]);

        $this->actingAs($super)->get('/superadmin/akses')->assertOk()
            ->assertSee(route('superadmin.akses.pengguna.destroy', $admin), false)
            ->assertSee(route('superadmin.akses.pengguna.destroy', $super2), false)
            ->assertDontSee(route('superadmin.akses.pengguna.destroy', $super), false); // akun sendiri

        // Hapus Admin OPD
        $this->actingAs($super)->delete("/superadmin/akses/pengguna/{$admin->id}")
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'dihapus permanen') && str_contains($p, 'Dinas Koperasi Pontianak'));
        $this->assertModelMissing($admin);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $admin->id]);
        $this->assertNull($antre->fresh()->user_id);               // jejak antrean dikosongkan
        $this->assertDatabaseHas('activity_log', ['causer_id' => $super->id, 'description' => "Akun Admin OPD {$admin->name} ({$admin->email}) — Dinas Koperasi Pontianak dihapus"]);

        // Akun yang dihapus tidak bisa masuk lagi; e-mailnya bisa dipakai untuk akun baru
        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($super)->post('/superadmin/akses/pengguna', $this->akunBaru(['email' => $admin->email]))->assertSessionHas('success');

        // Hapus Super Admin lain boleh; akun sendiri tidak
        $this->actingAs($super)->delete("/superadmin/akses/pengguna/{$super2->id}")->assertSessionHas('success');
        $this->assertModelMissing($super2);
        $this->actingAs($super)->delete("/superadmin/akses/pengguna/{$super->id}")
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun sendiri.');
        $this->assertModelExists($super);

        // Admin OPD tidak boleh menghapus akun
        $this->actingAs($this->adminOpd())->delete("/superadmin/akses/pengguna/{$super->id}")->assertForbidden();
    }

    public function test_super_admin_sets_opd_name_used_as_instansi_and_binaan_label(): void
    {
        $super = $this->superAdmin();
        $admin = $this->adminOpd();
        $umkm  = \App\Models\Umkm::create([
            'pemilik_usaha_id' => \App\Models\PemilikUsaha::create([
                'nama_lengkap' => 'P', 'nik' => 'N2', 'jenis_kelamin' => 'L', 'alamat' => '-',
                'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'telepon' => '2',
            ])->id,
            'opd_id' => $this->opd->id, 'nama_usaha' => 'Kopi Uji', 'slug' => 'kopi-uji', 'sektor' => 'kuliner',
            'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'alamat_usaha' => '-', 'status' => 'aktif',
        ]);

        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", [
            'nama_opd' => 'Dinas Koperasi, UKM dan Perdagangan Kota Pontianak', 'kabupaten' => 'Pontianak',
        ])->assertSessionHas('success', fn ($p) => str_contains($p, 'pada 1 UMKM ikut berubah'));

        // Instansi di profil admin & label pembina di atas UMKM mengikuti nama baru
        $this->actingAs($admin->fresh())->get('/profile')
            ->assertSee('Dinas Koperasi, UKM dan Perdagangan Kota Pontianak')
            ->assertSee('Binaan Dinas Koperasi, UKM dan Perdagangan Kota Pontianak');
        $this->actingAs($admin->fresh())->get("/admin/profil-umkm?umkm={$umkm->id}")
            ->assertSeeInOrder(['Binaan Dinas Koperasi, UKM dan Perdagangan Kota Pontianak', 'Profil UMKM']);

        // Nama OPD wajib & tidak boleh sama dengan OPD lain
        $lain = Opd::create(['nama_opd' => 'Dinas Lain', 'kode_opd' => 'DL', 'kabupaten' => 'Sambas']);
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['nama_opd' => 'Dinas Lain'])
            ->assertSessionHasErrorsIn("opd{$this->opd->id}", ['nama_opd']);
        $this->actingAs($super)->patch("/superadmin/akses/opd/{$this->opd->id}", ['nama_opd' => ''])
            ->assertSessionHasErrorsIn("opd{$this->opd->id}", ['nama_opd']);

        // Pindahkan admin ke OPD lain → instansinya berubah; UMKM lama tetap binaan OPD lama
        $this->actingAs($super)->patch("/superadmin/akses/pengguna/{$admin->id}/opd", ['opd_id' => $lain->id])
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'menjadi "Dinas Lain"'));
        $this->assertSame('Dinas Lain', $admin->fresh()->instansi());
        $this->assertSame($this->opd->id, $umkm->fresh()->opd_id);

        // Batas admin OPD tujuan tetap berlaku
        $adminLain = $this->adminOpd(); // OPD awal (batas 1) kosong lagi → boleh
        $this->actingAs($super)->patch("/superadmin/akses/pengguna/{$adminLain->id}/opd", ['opd_id' => $lain->id])
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'Dinas Lain sudah memiliki 1 admin aktif'));

        // Admin OPD tidak bisa mengubah nama OPD / instansi
        $this->actingAs($admin->fresh())->patch("/superadmin/akses/opd/{$lain->id}", ['nama_opd' => 'Ganti'])->assertForbidden();
        $this->actingAs($admin->fresh())->patch("/superadmin/akses/pengguna/{$admin->id}/opd", ['opd_id' => $this->opd->id])->assertForbidden();
    }

    private function umkmBinaan(Opd $opd, string $nama): \App\Models\Umkm
    {
        return \App\Models\Umkm::create([
            'pemilik_usaha_id' => \App\Models\PemilikUsaha::create([
                'nama_lengkap' => 'P', 'nik' => 'N-' . uniqid(), 'jenis_kelamin' => 'L', 'alamat' => '-',
                'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'telepon' => uniqid(),
            ])->id,
            'opd_id' => $opd->id, 'nama_usaha' => $nama, 'slug' => \Illuminate\Support\Str::slug($nama) . '-' . uniqid(),
            'sektor' => 'kuliner', 'kabupaten' => 'Pontianak', 'kecamatan' => '-', 'alamat_usaha' => '-', 'status' => 'aktif',
        ]);
    }

    public function test_super_admin_can_delete_opd_and_choose_where_its_umkm_go(): void
    {
        $super  = $this->superAdmin();
        $admin  = $this->adminOpd();
        $umkm   = $this->umkmBinaan($this->opd, 'Kopi Binaan');
        $arsip  = $this->umkmBinaan($this->opd, 'Usaha Tutup');
        $arsip->delete(); // UMKM terhapus (arsip) ikut dipindahkan
        $tujuan = Opd::create(['nama_opd' => 'Bank Indonesia Wilayah Kalimantan Barat', 'kode_opd' => 'BI', 'kabupaten' => 'Pontianak']);
        $antre  = \App\Models\UmkmDuplikat::forceCreate([
            'umkm_id' => $umkm->id, 'opd_id' => $this->opd->id, 'user_id' => $admin->id,
            'sumber' => 'manual', 'data' => [], 'skor' => 70, 'rincian' => [],
        ]);

        $this->actingAs($super)->get('/superadmin/akses')->assertOk()
            ->assertSee('Hapus OPD')
            ->assertSee(route('superadmin.akses.opd.destroy', $this->opd), false);

        // Kode konfirmasi salah → ditolak, tidak ada yang berubah
        $this->actingAs($super)->delete("/superadmin/akses/opd/{$this->opd->id}", ['konfirmasi' => 'SALAH', 'pindah_ke' => $tujuan->id])
            ->assertSessionHasErrorsIn("hapus{$this->opd->id}", ['konfirmasi']);
        $this->assertModelExists($this->opd);

        $this->actingAs($super)->delete("/superadmin/akses/opd/{$this->opd->id}", ['konfirmasi' => 'DK', 'pindah_ke' => $tujuan->id])
            ->assertRedirect('/superadmin/akses')
            ->assertSessionHas('success', fn ($p) => str_contains($p, '2 UMKM binaannya dipindahkan ke Bank Indonesia Wilayah Kalimantan Barat')
                && str_contains($p, '1 akun admin OPD ikut dihapus'));

        $this->assertModelMissing($this->opd);
        $this->assertModelMissing($admin);
        $this->assertSame($tujuan->id, $umkm->fresh()->opd_id);
        $this->assertSame($tujuan->id, $arsip->fresh()->opd_id);
        $this->assertSame('Binaan Bank Indonesia Wilayah Kalimantan Barat', $umkm->fresh()->teksBinaan());
        $this->assertSame($tujuan->id, $antre->fresh()->opd_id);
        $this->assertDatabaseHas('activity_log', ['causer_id' => $super->id, 'description' => 'OPD Dinas Koperasi Pontianak dihapus']);
    }

    public function test_deleted_opd_umkm_can_be_left_without_pembina(): void
    {
        $super = $this->superAdmin();
        $umkm  = $this->umkmBinaan($this->opd, 'Kopi Sendiri');
        $antre = \App\Models\UmkmDuplikat::forceCreate([
            'umkm_id' => $umkm->id, 'opd_id' => $this->opd->id, 'sumber' => 'manual', 'data' => [], 'skor' => 70, 'rincian' => [],
        ]);

        $this->actingAs($super)->delete("/superadmin/akses/opd/{$this->opd->id}", ['konfirmasi' => 'DK', 'pindah_ke' => '0'])
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'kini tanpa OPD pembina'));

        $this->assertNull($umkm->fresh()->opd_id);
        $this->assertSame('Belum ada OPD pembina', $umkm->fresh()->teksBinaan());
        $this->assertModelMissing($antre); // OPD tujuan antrean sudah tidak ada

        // OPD tidak bisa "dipindahkan ke dirinya sendiri"; Admin OPD tidak bisa menghapus OPD
        $lain = Opd::create(['nama_opd' => 'Dinas X', 'kode_opd' => 'X', 'kabupaten' => 'Sambas']);
        $this->actingAs($super)->delete("/superadmin/akses/opd/{$lain->id}", ['konfirmasi' => 'X', 'pindah_ke' => $lain->id])
            ->assertSessionHasErrorsIn("hapus{$lain->id}", ['pindah_ke']);
        $this->actingAs($this->adminOpd($lain))->delete("/superadmin/akses/opd/{$lain->id}", ['konfirmasi' => 'X'])->assertForbidden();
        $this->assertModelExists($lain);
    }

    public function test_deactivated_account_is_signed_out_immediately(): void
    {
        $admin = $this->adminOpd();
        $this->actingAs($this->superAdmin())->patch("/superadmin/akses/pengguna/{$admin->id}", ['is_active' => 0]);

        $this->actingAs($admin->fresh())->get('/admin/profil-umkm')
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi Super Admin.']);
        $this->assertGuest();
    }
}
