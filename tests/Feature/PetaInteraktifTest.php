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

        $res = $this->actingAs($this->superAdmin())->getJson('/peta-interaktif/data')
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

    public function test_data_tags_each_umkm_for_filter_buttons(): void
    {
        $lengkap = $this->umkm('Pontianak', 'aktif', [
            'sektor' => 'perikanan', 'whatsapp' => '08123456789', 'shopee' => 'https://shopee.co.id/toko',
            'instagram' => '@tokoku', 'website' => 'https://tokoku.id',
        ]);
        $lengkap->pemasaran()->create(['platform_online' => ['tiktok'], 'jangkauan_pasar' => 'ekspor']);
        $lengkap->legalitas()->create(['nomor_halal' => 'ID123', 'nomor_nib' => '9120']);
        $lengkap->profil()->create(['sertifikasi_produk' => 'Halal, HAKI merek, organik']);

        $polos = $this->umkm('Sambas', 'aktif', ['sektor' => 'perdagangan', 'website' => 'https://instagram.com/x']);
        $polos->profil()->create(['sertifikasi_produk' => 'Sertifikat CPPOB']);

        $umkm = collect($this->actingAs($this->superAdmin())->getJson('/peta-interaktif/data')
            ->assertOk()->json('umkm'))->keyBy('id');

        $this->assertSame([
            'sektor'      => ['pertanian'],
            'platform'    => ['shopee', 'tiktok', 'instagram', 'whatsapp', 'website'],
            'jangkauan'   => ['ekspor'],
            'sertifikasi' => ['halal', 'hki', 'nib', 'organik'],
        ], $umkm[$lengkap->id]['f']);

        $this->assertSame([
            'sektor'      => ['lainnya'],
            'platform'    => ['instagram'],
            'jangkauan'   => [],
            'sertifikasi' => ['lainnya'],
        ], $umkm[$polos->id]['f']);
    }

    public function test_map_is_open_to_guests_with_public_data_only(): void
    {
        $umkm = $this->umkm('Pontianak', 'aktif', [
            'email' => 'rahasia@usaha.id', 'jumlah_tenaga_kerja' => 9, 'skor_total' => 61, 'klasifikasi' => 'berkembang',
            'whatsapp' => '6281200001111', 'alamat_usaha' => 'Jl. Gajah Mada 1',
        ]);
        $umkm->profil()->create(['program_bi' => 'Wirausaha Muda BI']);
        $this->umkm(Umkm::KABUPATEN_KOSONG);

        // Tamu: halaman terbuka tanpa login, tanpa bagian admin
        $this->get('/peta-interaktif')
            ->assertOk()
            ->assertSee('href="' . route('login') . '"', false) // tombol Login Admin
            ->assertSee('Sektor Usaha')
            ->assertDontSee('Program Rekomendasi')
            ->assertDontSee(route('logout'))
            ->assertDontSee(route('admin.profil-umkm.index'));

        $tamu = $this->getJson('/peta-interaktif/data')->assertOk();
        $data = collect($tamu->json('umkm'))->firstWhere('id', $umkm->id);

        // Yang juga tampil di Semua Brand tetap dikirim
        $this->assertSame('Jl. Gajah Mada 1', $data['alamat']);
        $this->assertSame('6281200001111', $data['whatsapp']);
        $this->assertSame('Berkembang', $data['status']);
        $this->assertSame("/semua-brand/{$umkm->slug}", $data['url_publik']);
        $this->assertArrayHasKey('f', $data);
        // Data internal tidak dikirim ke pengunjung umum
        foreach (['email', 'tenaga_kerja', 'skor', 'analisis', 'program', 'url'] as $kolom) {
            $this->assertArrayNotHasKey($kolom, $data, "Kolom {$kolom} bocor ke publik");
        }
        $this->assertNull($tamu->json('tidak_diketahui'));
        $this->assertStringNotContainsString('rahasia@usaha.id', $tamu->getContent());
        $this->assertStringNotContainsString('Wirausaha Muda BI', $tamu->getContent());

        // Super Admin tetap menerima data & tampilan lengkap
        $admin = $this->actingAs($this->superAdmin());
        $admin->get('/peta-interaktif')->assertOk()->assertSee('Program Rekomendasi')->assertDontSee('href="' . route('login') . '"', false);
        $lengkap = collect($admin->getJson('/peta-interaktif/data')->json('umkm'))->firstWhere('id', $umkm->id);
        $this->assertSame(61, $lengkap['skor']);
        $this->assertSame('rahasia@usaha.id', $lengkap['email']);
        $this->assertSame(['Wirausaha Muda BI'], $lengkap['program']);
    }

    public function test_dashboard_button_links_to_dashboard_by_role(): void
    {
        $tombol = fn (string $url) => '<a href="' . $url . '" title="Buka dashboard admin"';

        // Pengunjung: tidak ada tombol Dashboard; tab Database menampilkan teks sambutan hingga ada pilihan
        $this->get('/peta-interaktif')->assertOk()
            ->assertDontSee('title="Buka dashboard admin"', false)
            ->assertSeeInOrder(['<template x-if="tampilSambutan">', 'Platform Strategis UMKM Kalimantan Barat',
                'untuk membuka halaman UMKM tersebut.', 'Lihat daftar semua UMKM', 'Daftar UMKM'], false);

        // Super Admin → /superadmin/dashboard
        $this->actingAs($this->superAdmin())->get('/peta-interaktif')->assertOk()
            ->assertSee($tombol('/superadmin/dashboard'), false)
            ->assertSee('untuk detail &amp; rekomendasi program KPw BI.', false)
            ->assertSee('<template x-if="tampilSambutan">', false);

        // Admin OPD → /admin/dashboard
        $adminOpd = User::factory()->create(['opd_id' => $this->opd->id])->assignRole(User::ROLE_ADMIN_OPD);
        $this->actingAs($adminOpd)->get('/peta-interaktif')->assertOk()
            ->assertSee($tombol('/admin/dashboard'), false);
    }

    public function test_map_page_is_responsive(): void
    {
        $html = $this->get('/peta-interaktif')->assertOk()->getContent();

        $this->assertStringContainsString('name="viewport" content="width=device-width, initial-scale=1.0"', $html);
        // HP/tablet: tombol Filter membuka baris filter; desktop: baris filter selalu tampil
        $this->assertStringContainsString('aria-controls="filter-bar"', $html);
        $this->assertStringContainsString(":class=\"filterBuka ? 'flex' : 'hidden lg:flex'\"", $html);
        // Panel samping melayang di atas peta pada layar kecil, di samping peta mulai lg
        $this->assertStringContainsString('w-[min(20rem,85vw)] lg:w-80', $html);
        $this->assertStringContainsString('lg:relative', $html);
        // Tinggi mengikuti viewport dinamis HP
        $this->assertStringContainsString('supports-[height:100dvh]:h-dvh', $html);
    }

    public function test_map_label_links_to_the_umkm_public_page_in_semua_brand(): void
    {
        $umkm = $this->umkm('Pontianak', 'aktif', ['nama_usaha' => 'Kopi Kapuas', 'slug' => 'kopi-kapuas-x1']);

        $data = collect($this->actingAs($this->superAdmin())->getJson('/peta-interaktif/data')
            ->assertOk()->json('umkm'))->firstWhere('id', $umkm->id);

        $this->assertSame('/semua-brand/kopi-kapuas-x1', $data['url_publik']);
        $this->get($data['url_publik'])->assertOk()->assertSee('Kopi Kapuas');
    }

    public function test_data_lists_bi_programs_written_by_umkm(): void
    {
        $a = $this->umkm('Pontianak');
        $a->profil()->create(['program_bi' => "1. Pelatihan Digital Marketing\n2. Pameran KKI; pelatihan digital marketing"]);
        $b = $this->umkm('Sambas');
        $b->profil()->create(['program_bi' => 'Tidak ada']);

        $umkm = collect($this->actingAs($this->superAdmin())->getJson('/peta-interaktif/data')
            ->assertOk()->json('umkm'))->keyBy('id');

        $this->assertSame(['Pelatihan Digital Marketing', 'Pameran KKI'], $umkm[$a->id]['program']);
        $this->assertSame([], $umkm[$b->id]['program']);

        $this->get('/peta-interaktif')->assertSee('Program Rekomendasi')->assertDontSee('flagcdn.com');
    }

    public function test_old_map_urls_redirect_to_new_url(): void
    {
        foreach (['admin', 'superadmin'] as $lama) {
            $this->get("/{$lama}/peta-interaktif")->assertStatus(301)->assertRedirect('/peta-interaktif');
            $this->get("/{$lama}/peta-interaktif/umkm/7")->assertStatus(301)->assertRedirect('/peta-interaktif/umkm/7');
        }
        $this->assertSame(url('/peta-interaktif'), route('superadmin.peta-interaktif'));
    }

    public function test_kalbar_boundary_geojson_is_valid(): void
    {
        $geo = json_decode(file_get_contents(public_path('geo/kalbar.geojson')), true);

        $this->assertSame('FeatureCollection', $geo['type']);
        $this->assertSame('Kalimantan Barat', $geo['features'][0]['properties']['nama']);
        $this->assertContains($geo['features'][0]['geometry']['type'], ['Polygon', 'MultiPolygon']);

        $this->actingAs($this->superAdmin())->get('/peta-interaktif')
            ->assertOk()
            ->assertSee('geo/kalbar.geojson')
            ->assertSeeInOrder(['Wilayah', 'Sektor Usaha', 'Platform Digital', 'Jangkauan Pasar', 'Sertifikasi Produk', 'Reset', 'Beranda'])
            ->assertSee(['Fesyen/Wastra', 'Pertanian &amp; Agroindustri', 'TikTok Shop', 'Ekspor (Internasional)', 'HKI/Merek Terdaftar'], false)
            // Sektor usaha hanya 6: Kuliner, Fesyen/Wastra, Kerajinan, Pertanian & Agroindustri, Jasa, Lainnya
            ->assertDontSee(['Manufaktur', 'Teknologi Digital', 'Kesehatan &amp; Kecantikan', 'Perikanan', 'Perdagangan'], false)
            // Tombol "Beranda" (dulu "Semua Brand") kembali ke halaman depan
            ->assertSee('href="' . route('home') . '"', false)
            ->assertDontSee('Semua Brand')
            ->assertDontSee('Panel Admin')
            ->assertDontSee('Semua Tier');
    }

    public function test_umkm_detail_shows_profile_and_bi_program_recommendations(): void
    {
        $umkm = $this->umkm('Pontianak', 'aktif', ['nama_usaha' => 'Keripik <b>Pisang</b>']);

        $this->actingAs($this->superAdmin())->get("/peta-interaktif/umkm/{$umkm->id}")
            ->assertOk()
            ->assertSee('Keripik &lt;b&gt;Pisang&lt;/b&gt;', false)
            ->assertSee('Rekomendasi Program KPw BI')
            ->assertSee('Fasilitasi Legalitas Usaha (NIB melalui OSS)')
            ->assertSee('Pendampingan Sertifikasi Halal')
            ->assertSee('Pelatihan Pencatatan Keuangan Digital (SI APIK)');
    }

    public function test_umkm_detail_for_super_admin_and_every_admin_opd(): void
    {
        $milik  = $this->umkm('Pontianak');
        $opdB   = Opd::create(['nama_opd' => 'Dinas B', 'kode_opd' => 'B', 'kabupaten' => 'Sambas']);
        $lain   = $this->umkm('Sambas', 'aktif', ['opd_id' => $opdB->id]);
        $admin  = User::factory()->create(['opd_id' => $this->opd->id])->assignRole(User::ROLE_ADMIN_OPD);
        $fotoMilik = route('admin.produk.upload-foto', ['umkm' => $milik->id]);
        $fotoLain  = route('admin.produk.upload-foto', ['umkm' => $lain->id]);

        $this->get("/peta-interaktif/umkm/{$milik->id}")->assertRedirect('/login');

        // Admin OPD: UMKM binaannya → detail + tautan ubah (Otoritas Edit)
        $this->actingAs($admin)->get("/peta-interaktif/umkm/{$milik->id}")->assertOk()
            ->assertSee('Rekomendasi Program Dinas A')->assertDontSee('KPw BI')
            ->assertSee('Otoritas Edit')->assertSee('Kelola profil')->assertSee($fotoMilik, false);

        // Admin OPD: UMKM OPD lain → detail tetap terbuka, tanpa tautan ubah (mode lihat saja)
        $this->actingAs($admin)->get("/peta-interaktif/umkm/{$lain->id}")->assertOk()
            ->assertSee('Binaan Dinas B')->assertSee('Mode lihat saja')->assertSee('Lihat profil')
            ->assertDontSee($fotoLain, false)
            ->assertDontSee('#bagian-rekomendasi', false);

        // Super Admin: semua UMKM dengan Otoritas Edit
        $super = $this->superAdmin();
        $this->actingAs($super)->get("/peta-interaktif/umkm/{$lain->id}")->assertOk()
            ->assertSee('Rekomendasi Program KPw BI')->assertSee('Otoritas Edit')->assertSee($fotoLain, false);
    }

    public function test_map_label_links_follow_role(): void
    {
        $milik = $this->umkm('Pontianak');
        $opdB  = Opd::create(['nama_opd' => 'Dinas B', 'kode_opd' => 'B', 'kabupaten' => 'Sambas']);
        $lain  = $this->umkm('Sambas', 'aktif', ['opd_id' => $opdB->id]);
        $data  = fn ($res) => collect($res->assertOk()->json('umkm'))->keyBy('id');

        // Super Admin: semua label → /peta-interaktif/umkm/{no}
        $s = $data($this->actingAs($this->superAdmin())->getJson('/peta-interaktif/data'));
        $this->assertSame("/peta-interaktif/umkm/{$milik->id}", $s[$milik->id]['url']);
        $this->assertSame("/peta-interaktif/umkm/{$lain->id}", $s[$lain->id]['url']);

        // Admin OPD: semua label (binaannya maupun OPD lain) → /peta-interaktif/umkm/{no}
        $admin = User::factory()->create(['opd_id' => $this->opd->id])->assignRole(User::ROLE_ADMIN_OPD);
        $a = $data($this->actingAs($admin)->getJson('/peta-interaktif/data'));
        $this->assertSame("/peta-interaktif/umkm/{$milik->id}", $a[$milik->id]['url']);
        $this->assertSame("/peta-interaktif/umkm/{$lain->id}", $a[$lain->id]['url']);
        $this->assertArrayNotHasKey('opd_id', $a[$milik->id]);
        $this->assertArrayNotHasKey('skor', $a[$milik->id]);

        // Pengunjung: tidak ada tautan detail sama sekali
        auth()->logout();
        $g = $data($this->getJson('/peta-interaktif/data'));
        $this->assertArrayNotHasKey('url', $g[$milik->id]);
        $this->assertArrayNotHasKey('opd_id', $g[$milik->id]);
    }
}
