<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Legalitas;
use App\Models\Opd;
use App\Models\Pemasaran;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function umkm(array $attr = []): Umkm
    {
        $opd = Opd::firstOrCreate(['kode_opd' => 'A'], ['nama_opd' => 'Dinas A', 'kabupaten' => 'Pontianak']);

        $pemilik = PemilikUsaha::create([
            'nama_lengkap' => 'Pemilik Rahasia', 'nik' => 'NIK-' . uniqid(), 'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Pribadi 9', 'kabupaten' => 'Pontianak', 'kecamatan' => '-',
            'telepon' => '6281299990000', 'email' => 'pemilik.rahasia@example.com',
        ]);

        $umkm = Umkm::create(array_merge([
            'pemilik_usaha_id' => $pemilik->id, 'opd_id' => $opd->id,
            'nama_usaha' => 'Kopi Kapuas', 'slug' => 'kopi-kapuas-' . uniqid(),
            'sektor' => 'kuliner', 'kabupaten' => 'Pontianak', 'kecamatan' => '-',
            'alamat_usaha' => '-', 'status' => 'aktif', 'whatsapp' => '6281200001111',
            'instagram' => 'https://instagram.com/kopikapuas', 'klasifikasi' => 'dasar',
        ], $attr));

        Pemasaran::create(['umkm_id' => $umkm->id, 'platform_online' => ['instagram'], 'jangkauan_pasar' => 'regional']);
        Legalitas::create(['umkm_id' => $umkm->id, 'nomor_halal' => 'ADA']);
        Keuangan::create(['umkm_id' => $umkm->id, 'tahun' => 2026, 'omzet_tahunan' => 987654321]);

        return $umkm;
    }

    public function test_all_public_pages_render(): void
    {
        $this->umkm();

        foreach (['/', '/semua-brand', '/go-digital', '/go-global', '/berita', '/kemitraan', '/tentang-kami'] as $url) {
            $this->get($url)->assertOk()->assertSee('UMKMLinked', false);
        }
    }

    public function test_listing_pages_do_not_leak_private_data(): void
    {
        $this->umkm();

        foreach (['/', '/semua-brand', '/go-digital', '/go-global'] as $url) {
            $this->get($url)
                ->assertSee('Kopi Kapuas')
                ->assertDontSee('pemilik.rahasia@example.com')   // email pemilik (NFR-02)
                ->assertDontSee('6281299990000')                 // telepon pribadi pemilik
                ->assertDontSee('Pemilik Rahasia')
                ->assertDontSee('987654321')                     // data keuangan
                ->assertDontSee('987.654.321');
        }
    }

    public function test_sector_filter_only_lists_existing_sectors(): void
    {
        $this->umkm();
        $this->umkm(['nama_usaha' => 'Tenun Sambas', 'sektor' => 'kerajinan', 'kabupaten' => 'Sambas']);

        $this->get('/semua-brand')
            ->assertSee('data-filter-value="kuliner"', false)
            ->assertSee('data-filter-value="kerajinan"', false)
            ->assertDontSee('data-filter-value="camilan"', false)   // dulu selalu 0 hasil
            ->assertDontSee('data-filter-value="minuman"', false);

        // Trending tidak terfilter, jadi cek jumlah hasil grid
        $this->get('/semua-brand?sektor=kerajinan')
            ->assertSee('Tenun Sambas')
            ->assertSee('Ditemukan <strong>1</strong> brand', false);
    }

    public function test_unknown_kabupaten_is_hidden_on_cards(): void
    {
        $this->umkm(['nama_usaha' => 'Usaha Tanpa Wilayah', 'kabupaten' => Umkm::KABUPATEN_KOSONG]);

        $this->get('/semua-brand')
            ->assertSee('Usaha Tanpa Wilayah')
            ->assertSee('Wilayah belum terdata')                  // opsi filter
            ->assertDontSee('Kuliner · Tidak Diketahui');
    }

    public function test_search_keeps_known_filters_only(): void
    {
        $this->umkm();

        $this->get('/semua-brand?sektor=kuliner&evil=%3Cscript%3E')
            ->assertSee('name="sektor" value="kuliner"', false)
            ->assertDontSee('name="evil"', false)
            ->assertDontSee('<script>', false);
    }

    public function test_go_global_certification_filter_is_grouped_correctly(): void
    {
        // UMKM lokal tanpa sertifikat tidak boleh lolos hanya karena UMKM lain punya BPOM
        $lokal = $this->umkm(['nama_usaha' => 'Warung Lokal']);
        $lokal->pemasaran()->update(['jangkauan_pasar' => 'lokal']);
        $lokal->legalitas()->update(['nomor_halal' => null]);

        $bpom = $this->umkm(['nama_usaha' => 'Amplang BPOM']);
        $bpom->pemasaran()->update(['jangkauan_pasar' => 'lokal']);
        $bpom->legalitas()->update(['nomor_halal' => null, 'nomor_bpom' => 'ADA']);

        $this->get('/go-global')
            ->assertSee('Amplang BPOM')
            ->assertDontSee('Warung Lokal');
    }

    public function test_detail_page_is_safe_and_transaction_ready(): void
    {
        $umkm = $this->umkm([
            'website'   => 'javascript:alert(1)',
            'tokopedia' => 'Blibli: https://www.blibli.com/merchant/x Tokopedia:https://tk.tokopedia.com/abc/',
            'instagram' => 'Eduscale.id',
        ]);

        $this->get(route('direktori.show', $umkm->slug))
            ->assertOk()
            ->assertSee('Pesan via WhatsApp')
            ->assertSee('https://wa.me/6281200001111?text=', false)       // pesan pembuka terisi
            ->assertSee('href="https://tk.tokopedia.com/abc/"', false)   // URL tokopedia diambil dari teks campuran
            ->assertSee('href="https://www.instagram.com/Eduscale.id/"', false)
            ->assertDontSee('javascript:alert', false)                    // skema berbahaya ditolak
            ->assertDontSee('blibli.com', false)                          // tidak salah label "Tokopedia"
            ->assertSee('rel="noopener noreferrer nofollow"', false)
            ->assertDontSee('pemilik.rahasia@example.com')
            ->assertDontSee('6281299990000')
            ->assertDontSee('987654321')
            ->assertSee('og:title', false);
    }

    public function test_inactive_umkm_detail_is_not_public(): void
    {
        $umkm = $this->umkm(['status' => 'draft']);

        $this->get(route('direktori.show', $umkm->slug))->assertNotFound();
    }

    public function test_about_page_shows_live_stats(): void
    {
        $this->umkm();
        $this->umkm(['nama_usaha' => 'Tenun Sambas', 'sektor' => 'kerajinan', 'kabupaten' => 'Sambas']);

        $this->get('/tentang-kami')
            ->assertOk()
            ->assertSee('UMKM terdata')
            ->assertSee('Visi')
            ->assertSee('Fitur platform');
    }

    public function test_long_search_query_is_handled(): void
    {
        $this->get('/semua-brand?q=' . str_repeat('a', 5000))->assertOk();
    }

    public function test_home_shows_three_product_rows_and_logo_links_home(): void
    {
        $this->umkm(); // Instagram + Halal + regional → muncul di ketiga baris

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Semua Brand', 'Kopi Kapuas', 'Go Digital', 'Kopi Kapuas', 'Go Global', 'Kopi Kapuas'])
            ->assertSee('logo-umkmlinked.webp')
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee(route('direktori.index'));
    }

    public function test_home_shows_four_products_in_every_row(): void
    {
        foreach (range(1, 6) as $i) {
            $this->umkm(['nama_usaha' => "Brand {$i}", 'skor_total' => 100 - $i]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        // Tiga baris kategori, masing-masing tepat 4 kartu produk
        preg_match_all('#<ul class="ib-rail__grid".*?</ul>#s', $html, $grid);
        $this->assertCount(3, $grid[0]);
        foreach ($grid[0] as $baris) {
            $this->assertSame(4, substr_count($baris, 'class="ib-produk"'));
        }
    }

    public function test_home_rows_have_random_backup_cards_for_rotation(): void
    {
        foreach (range(1, 20) as $i) {
            $this->umkm(['nama_usaha' => "Brand {$i}"]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        // Setiap baris: 4 kartu tampil + kartu cadangan dalam <template> (gambarnya belum diunduh)
        preg_match_all('#<template data-rotasi-cadangan>(.*?)</template>#s', $html, $cadangan);
        $this->assertCount(3, $cadangan[1]);
        foreach ($cadangan[1] as $isi) {
            $this->assertSame(12, substr_count($isi, 'class="ib-produk"'));
        }
        $this->assertSame(3, substr_count($html, 'data-rotasi-jeda '));

        // Pemilihan acak: dari beberapa kunjungan, 4 kartu awal tidak selalu sama
        $awal = collect(range(1, 6))->map(function () {
            preg_match('#<ul class="ib-rail__grid".*?</ul>#s', $this->get('/')->getContent(), $m);
            return $m[0];
        });
        $this->assertGreaterThan(1, $awal->unique()->count());
    }

    public function test_cached_pages_show_changes_immediately(): void
    {
        $umkm = $this->umkm();

        $this->get('/')->assertSee('Kopi Kapuas');
        $this->get('/semua-brand')->assertSee('Kopi Kapuas');

        // Perubahan data membatalkan cache: nama baru langsung tampil, nama lama hilang
        $umkm->update(['nama_usaha' => 'Kopi Kapuas Baru']);

        $this->get('/')->assertSee('Kopi Kapuas Baru');
        $this->get('/semua-brand')->assertSee('Kopi Kapuas Baru');

        $umkm->update(['status' => 'draft']);
        $this->get('/')->assertDontSee('Kopi Kapuas Baru');
    }

    public function test_old_direktori_urls_redirect_permanently(): void
    {
        $umkm = $this->umkm();

        $this->get('/direktori')->assertStatus(301)->assertRedirect('/semua-brand');
        $this->get('/direktori?sektor=kuliner')->assertStatus(301)->assertRedirect('/semua-brand?sektor=kuliner');
        $this->get("/direktori/{$umkm->slug}")->assertStatus(301)->assertRedirect("/semua-brand/{$umkm->slug}");
        $this->get("/semua-brand/{$umkm->slug}")->assertOk()->assertSee('Kopi Kapuas');
    }

    public function test_invalid_or_array_filters_are_ignored_not_errors(): void
    {
        $this->umkm();

        // Nilai di luar whitelist dan parameter berbentuk array → diabaikan, bukan 500
        foreach ([
            '/semua-brand?sektor[]=kuliner&kabupaten[]=x&q[]=a&harga_min[]=1',
            '/semua-brand?sektor=%27%20OR%201%3D1--&klasifikasi=admin&kabupaten=Jakarta&harga_min=abc',
            '/go-digital?platform[]=x&platform=%25&sektor=kuliner%27',
            '/go-global?sertifikasi=nomor_nib&jangkauan[]=ekspor&sertifikasi[]=halal',
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('Kopi Kapuas');
        }
    }

    public function test_search_wildcards_are_matched_literally(): void
    {
        $this->umkm();

        // Bagian "Trending" selalu tampil, jadi yang diperiksa adalah jumlah hasil pencarian
        $this->get('/semua-brand?q=%25')->assertOk()->assertSee('Ditemukan <strong>0</strong>', false); // "%" bukan wildcard
        $this->get('/semua-brand?q=_')->assertOk()->assertSee('Ditemukan <strong>0</strong>', false);   // "_" bukan wildcard
        $this->get('/semua-brand?q=kapuas')->assertOk()->assertSee('Ditemukan <strong>1</strong>', false);
    }
}
