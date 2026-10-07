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

    public function test_semua_brand_starts_with_results_without_trending_section(): void
    {
        $this->umkm();

        $html = $this->get('/semua-brand')->assertOk()
            ->assertDontSee('Pilihan teratas')
            ->assertSee('Ditemukan <strong>1</strong> brand', false)
            ->getContent();

        // Judul hasil adalah isi pertama kolom utama
        $this->assertMatchesRegularExpression('#<main class="ib-main">\s*(<!--.*?-->\s*)?<h2 class="ib-result-title">#s', $html);
    }

    public function test_semua_brand_has_the_four_filter_groups_of_the_map(): void
    {
        $this->umkm();

        $res = $this->get('/semua-brand')->assertOk();
        $res->assertSeeInOrder(['Sektor Usaha', 'Platform Digital', 'Jangkauan Pasar', 'Sertifikasi Produk', 'Rentang harga produk']);
        foreach (\App\Support\TagUmkm::FILTER as $grup => $def) {
            foreach ($def['opsi'] as $kode => $label) {
                $res->assertSee('data-filter-multi="' . $grup . '" data-filter-value="' . $kode . '"', false);
                $res->assertSee(e($label), false);
            }
        }
    }

    public function test_semua_brand_multi_filters_match_the_map(): void
    {
        // Kopi Kapuas: kuliner, Instagram + WhatsApp, regional, Halal
        $this->umkm();
        $tenun = $this->umkm([
            'nama_usaha' => 'Tenun Sambas', 'sektor' => 'kerajinan', 'kabupaten' => 'Sambas',
            'instagram' => null, 'shopee' => 'https://shopee.co.id/tenun',
        ]);
        $tenun->pemasaran()->update(['jangkauan_pasar' => 'ekspor', 'platform_online' => []]);
        $tenun->legalitas()->update(['nomor_halal' => null]);
        $tenun->profil()->create(['sertifikasi_produk' => 'Merek terdaftar (HAKI)']);

        $hasil = fn (string $q) => $this->get('/semua-brand?' . $q)->assertOk();

        // Satu grup: salah satu cocok (ATAU)
        $hasil('sektor=kuliner,kerajinan')->assertSee('Ditemukan <strong>2</strong> brand', false);
        $hasil('platform=shopee')->assertSee('Ditemukan <strong>1</strong> brand', false)->assertSee('Tenun Sambas');
        $hasil('jangkauan=ekspor')->assertSee('Ditemukan <strong>1</strong> brand', false)->assertSee('Tenun Sambas');
        $hasil('sertifikasi=hki')->assertSee('Ditemukan <strong>1</strong> brand', false)->assertSee('Tenun Sambas');
        // Antar-grup: semuanya harus cocok (DAN)
        $hasil('sektor=kuliner&jangkauan=ekspor')->assertSee('Ditemukan <strong>0</strong> brand', false);
        // Pilihan tercentang & dibawa saat mencari; nilai asing dibuang
        $hasil('platform=shopee,evil,tiktok')
            ->assertSee('name="platform" value="shopee,tiktok"', false)
            ->assertSee('data-filter-multi="platform" data-filter-value="shopee" checked', false);

        // Kode sertifikasi baru tidak membuat Go Global galat (dulu dipakai sebagai nama kolom)
        $this->get('/go-global?sertifikasi=hki,organik')->assertOk();
        $this->get('/go-digital?platform=website')->assertOk();
    }

    public function test_sector_filter_lists_known_sectors_only(): void
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
            ->assertSee('https://wa.me/6281200001111?text=', false)       // pesan pembuka terisi (galeri & bilah HP)
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
            ->assertSee('<dt class="ib-stats__label">Brand UMKM</dt>', false)
            ->assertSee('<dt class="ib-stats__label">Kerajinan</dt>', false)
            ->assertDontSee('UMKM terdata')
            ->assertSee('Visi')
            ->assertSee('Fitur platform');
    }

    public function test_long_search_query_is_handled(): void
    {
        $this->get('/semua-brand?q=' . str_repeat('a', 5000))->assertOk();
    }

    public function test_home_shows_only_semua_brand_without_categories_and_logo_links_home(): void
    {
        $this->umkm(); // Instagram + Halal + regional → dulu juga muncul di Go Digital & Go Global

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('id="semua-brand"', false)
            ->assertDontSee('id="go-digital"', false)->assertDontSee('id="go-global"', false)
            ->assertDontSee('Kategori 1')->assertDontSee('ib-home-chip', false)
            ->assertSee('logo-umkmlinked.webp')
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee(route('direktori.index'))
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Kopi Kapuas</p>'));   // tampil sekali saja
    }

    public function test_navbar_has_map_after_semua_brand_without_go_digital_and_go_global(): void
    {
        $nav = fn () => \Illuminate\Support\Str::betweenFirst($this->get('/')->assertOk()->getContent(), '<nav aria-label="Menu utama">', '</nav>');

        $html = $nav();
        $this->assertMatchesRegularExpression('#Semua Brand</a>\s*</li>\s*<li>\s*<a href="' . preg_quote(route('superadmin.peta-interaktif'), '#') . '"[^>]*>Peta Interaktif</a>#', $html);
        foreach (['Tentang Kami', 'Berita', 'Kemitraan', 'Login Admin'] as $menu) {
            $this->assertStringContainsString($menu, $html);
        }
        $this->assertStringNotContainsString('Go Digital', $html);
        $this->assertStringNotContainsString('Go Global', $html);

        // Peta terbuka: tamu langsung masuk tanpa login
        $this->get('/peta-interaktif')->assertOk();
    }

    public function test_detail_hides_score_and_workforce_and_links_address_to_google_maps(): void
    {
        $umkm = $this->umkm([
            'alamat_usaha' => 'Jl. Gajah Mada No. 10', 'jumlah_tenaga_kerja' => 7, 'skor_total' => 63,
        ]);

        $this->get("/semua-brand/{$umkm->slug}")
            ->assertOk()
            ->assertDontSee('Skor kesiapan usaha')
            ->assertDontSee('Tenaga kerja')
            ->assertDontSee('7 orang')
            ->assertSee('Jl. Gajah Mada No. 10')
            ->assertSee('Buka di Google Maps')
            ->assertSee(e('https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Jl. Gajah Mada No. 10, Kota Pontianak, Kalimantan Barat')), false);

        // Kartu "Pesan langsung ke penjual" tanpa tombol "Pesan via WhatsApp"
        $html = $this->get("/semua-brand/{$umkm->slug}")->getContent();
        $kartu = \Illuminate\Support\Str::betweenFirst($html, '<aside class="ib-order"', '</aside>');
        $this->assertStringContainsString('Pesan langsung ke penjual', $kartu);
        $this->assertStringNotContainsString('Pesan via WhatsApp', $kartu);

        // Tanpa alamat: tombol Google Maps tidak muncul
        $tanpaAlamat = $this->umkm(['alamat_usaha' => '-']);
        $this->get("/semua-brand/{$tanpaAlamat->slug}")->assertOk()->assertDontSee('Buka di Google Maps');
    }

    public function test_detail_gallery_has_prev_next_arrows_only_with_multiple_photos(): void
    {
        $umkm = $this->umkm();
        $foto = fn (string $nama, int $urutan) => $umkm->produk()->create([
            'nama_produk' => $nama, 'urutan' => $urutan, 'is_active' => true,
            'foto_url' => "https://example.com/{$urutan}.jpg",
        ]);

        $foto('Kopi Bubuk', 1);
        $this->get("/semua-brand/{$umkm->slug}")->assertOk()->assertDontSee('data-gallery-next', false);

        $foto('Kopi Sachet', 2);
        $foto('Kopi Literan', 3);
        $this->get("/semua-brand/{$umkm->slug}")
            ->assertOk()
            ->assertSeeInOrder(['aria-label="Foto sebelumnya"', 'aria-label="Foto berikutnya"', '1 / 3'], false);
    }

    public function test_home_shows_every_umkm_once_in_rows_of_four(): void
    {
        foreach (range(1, 10) as $i) {
            $this->umkm(['nama_usaha' => "Brand {$i}"]);
        }
        $this->umkm(['nama_usaha' => 'Brand Draft', 'status' => 'draft']);

        $html = $this->get('/')->assertOk()->assertDontSee('Brand Draft')->getContent();

        // Semua UMKM aktif dalam baris berisi 4 kartu (baris terakhir sisanya), tanpa kartu cadangan tersembunyi
        preg_match_all('#<ul class="ib-rail__grid".*?</ul>#s', $html, $grid);
        $this->assertSame([4, 4, 2], array_map(fn ($b) => substr_count($b, 'class="ib-produk"'), $grid[0]));
        $this->assertSame(3, substr_count($html, 'data-rotasi-baris'));
        $this->assertStringNotContainsString('data-rotasi-cadangan', $html);
        foreach (range(1, 10) as $i) {
            $this->assertSame(1, substr_count($html, "Brand {$i}</p>"), "Brand {$i} harus tampil tepat sekali");
        }

        // Efek acak: dari beberapa kunjungan, urutan baris pertama tidak selalu sama
        $awal = collect(range(1, 6))->map(function () {
            preg_match('#<ul class="ib-rail__grid".*?</ul>#s', $this->get('/')->getContent(), $m);
            return $m[0];
        });
        $this->assertGreaterThan(1, $awal->unique()->count());
        $this->assertSame(1, substr_count($html, 'data-rotasi-jeda '));
    }

    public function test_home_is_paginated_with_consistent_random_order_across_pages(): void
    {
        foreach (range(1, 30) as $i) {
            $this->umkm(['nama_usaha' => "Brand {$i}"]);
        }
        $nama = function (string $html) {
            preg_match_all('#<p class="ib-produk__brand">(.*?)</p>#', $html, $m);
            return $m[1];
        };

        // Halaman 1: 24 produk (6 baris × 4) + navigasi halaman yang kembali ke #semua-brand
        $h1 = $this->get('/')->assertOk()
            ->assertSee('Menampilkan <strong>1–24</strong>', false)
            ->assertSee(route('home', ['page' => 2]) . '#semua-brand', false)
            ->getContent();
        preg_match_all('#<ul class="ib-rail__grid".*?</ul>#s', $h1, $grid);
        $this->assertCount(6, $grid[0]);

        // Halaman 2 melanjutkan urutan acak yang sama: sisa 6 produk, tanpa dobel/terlewat
        $h2 = $this->get('/?page=2')->assertOk()->assertSee('Menampilkan <strong>25–30</strong>', false)->getContent();
        $this->assertCount(24, $nama($h1));
        $this->assertCount(6, $nama($h2));
        $this->assertEqualsCanonicalizing(
            collect(range(1, 30))->map(fn ($i) => "Brand {$i}")->all(),
            [...$nama($h1), ...$nama($h2)],
        );

        // Kembali ke halaman 1 lewat tautan halaman → urutan tetap; buka beranda baru → diacak ulang
        $this->assertSame($nama($h1), $nama($this->get('/?page=1')->getContent()));
        $baru = collect(range(1, 5))->map(fn () => implode('|', $nama($this->get('/')->getContent())));
        $this->assertGreaterThan(1, $baru->push(implode('|', $nama($h1)))->unique()->count());

        // Nomor halaman melebihi batas → halaman terakhir
        $this->get('/?page=99')->assertRedirect(route('home', ['page' => 2]) . '#semua-brand');
    }

    public function test_home_summary_row_shows_only_sectors_with_data(): void
    {
        $this->umkm();
        $this->umkm(['nama_usaha' => 'Kopi Sambas', 'kabupaten' => 'Sambas']);
        $this->umkm(['nama_usaha' => 'Tenun Sambas', 'sektor' => 'kerajinan', 'kabupaten' => 'Sambas']);
        $this->umkm(['nama_usaha' => 'Ikan Asin', 'sektor' => 'perikanan']);   // digabung ke Pertanian & Agroindustri

        $label = fn (string $l) => '<dt class="ib-stats__label">' . $l . '</dt>';

        // Satu baris: Brand UMKM, sektor yang ada datanya, Kabupaten/Kota — Go Digital/Go Global tidak lagi di ringkasan
        $res = $this->get('/')->assertOk()->assertSee('ib-stats__grid--baris', false)->assertSee('data-count="5"', false);
        $res->assertSeeInOrder([
            $label('Brand UMKM'), '4',
            $label('Kuliner'), '2',
            $label('Kerajinan'), '1',
            $label('Pertanian &amp; Agroindustri'), '1',
            $label('Kabupaten/Kota'), '2',
        ], false);

        foreach (['Go Digital', 'Go Global', 'Fesyen', 'Jasa', 'Manufaktur', 'Teknologi Digital', 'Kesehatan &amp; Kecantikan', 'Lainnya'] as $l) {
            $res->assertDontSee($label($l), false);
        }
    }

    public function test_semua_brand_summary_row_matches_home_and_about(): void
    {
        $this->umkm();
        $this->umkm(['nama_usaha' => 'Kopi Sambas', 'kabupaten' => 'Sambas', 'klasifikasi' => 'unggulan']);
        $this->umkm(['nama_usaha' => 'Tenun Sambas', 'sektor' => 'kerajinan', 'kabupaten' => 'Sambas']);

        $label = fn (string $l) => '<dt class="ib-stats__label">' . $l . '</dt>';
        $ringkasan = fn (string $html) => \Illuminate\Support\Str::betweenFirst($html, 'aria-label="Ringkasan data"', '</section>');

        $semua = $this->get('/semua-brand')->assertOk()->getContent();
        $this->assertSame($ringkasan($this->get('/')->getContent()), $ringkasan($semua));               // identik dengan Beranda
        $this->assertSame($ringkasan($this->get('/tentang-kami')->getContent()), $ringkasan($semua));   // & Tentang Kami

        $this->get('/semua-brand')
            ->assertSee('ib-stats__grid--baris', false)
            ->assertSeeInOrder([$label('Brand UMKM'), '3', $label('Kuliner'), '2', $label('Kerajinan'), '1', $label('Kabupaten/Kota'), '2'], false);
        foreach (['Unggulan', 'Berkembang', 'Go Digital', 'Total UMKM', 'Fesyen', 'Manufaktur'] as $l) {
            $this->assertStringNotContainsString($label($l), $ringkasan($semua));
        }
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
