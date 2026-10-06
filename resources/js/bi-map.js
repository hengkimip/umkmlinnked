// resources/js/bi-map.js

import Alpine from "alpinejs";

// Geometri dalam piksel layar. Semua hiasan (garis, label, cabang) digambar sebagai HTML
// di dalam SATU penanda per kota yang berjangkar di koordinat kota, sehingga ikut animasi
// zoom Leaflet dengan mulus dan tidak pernah dihitung ulang (tidak "mantul").
const LABEL_JARAK = 100; // panjang garis label kota/kabupaten
const CABANG_MIN = 50; // panjang cabang UMKM: selalu < LABEL_JARAK
const CABANG_MAKS = 80;
const CABANG_JARAK_LABEL = 15; // jarak busur minimal antar-label cabang (px)
const CABANG_MAKS_LABEL = 36; // lebih dari ini → label "+N lainnya"
const CELAH_LABEL_KOTA = (50 * Math.PI) / 180; // sudut kosong di arah label kota

// Tingkat zoom
const ZOOM_MIN = 5; // cukup untuk melihat seluruh Indonesia
const ZOOM_MAKS = 18; // cukup untuk melihat jalan di dalam kota
const ZOOM_PROVINSI = 6; // ≤ ini: satu label "Kalimantan Barat"
const ZOOM_LABEL_LENGKAP = 7; // ≥ ini: label nama kota lengkap (bila tidak bertabrakan); di bawahnya gelembung angka

// Batas Provinsi Kalimantan Barat (cadangan bila GeoJSON gagal dimuat)
const BATAS_KALBAR = [
    [-3.04, 108.82],
    [2.0, 114.2],
];

// Objek Leaflet disimpan di luar state Alpine agar tidak dibungkus proxy
const kotaMarker = new Map(); // nama kota → { city, marker, el }
let radialMarker = null;
let provinsiMarker = null;
let batasLayer = null;
let jalanMarker = null; // penanda hasil "cari jalan" dari kartu UMKM
const jalanCache = new Map(); // kueri → [lat, lng] | null (hemat permintaan ke Nominatim)

const NOMINATIM = "https://nominatim.openstreetmap.org/search";

// Kata yang menandai akhir nama jalan pada alamat bebas ("Jl. Purnama 2 GG. Indah no. 44")
const AKHIR_JALAN =
    /(?<=\S)\s*(?:[,;()]|\b(?:jl|jln|jalan|gg|gang|no|nomor|rt|rw|komp|kompleks|komplek|perum|perumahan|blok|samping|depan|belakang|blkg|sebelah|dekat|kel|kelurahan|kec|kecamatan|desa|kab|kabupaten|kota)\b\.?).*$/i;

const esc = (s) =>
    String(s ?? "").replace(
        /[&<>"']/g,
        (c) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;",
            })[c],
    );

window.umkmApp = () => {
    return {
        // ==================== STATE ====================
        // Tab awal selalu Database (tombol "Dashboard" admin kini membuka halaman dashboard)
        activeTab: "database",
        lihatDaftar: false, // pengunjung menekan "Lihat daftar semua UMKM"
        // HP/tablet (< lg 1024px): panel samping mulai tertutup agar peta terlihat
        sidebarCollapsed: window.matchMedia("(max-width: 1023px)").matches,
        filterBuka: false, // baris filter di header (HP/tablet)
        selectedCity: null,
        selectedKabupaten: "",
        // Tombol filter (kotak centang): satu grup = salah satu cocok; antar-grup = semua harus cocok.
        // Kode pilihan sama dengan umkm.f dari server (App\Support\TagUmkm::FILTER).
        filter: { sektor: [], platform: [], jangkauan: [], sertifikasi: [] },
        hitungOpsi: {}, // { grup: { kode: jumlah UMKM } }
        databaseUMKM: [],
        jumlahKab: {}, // jumlah UMKM per kabupaten/kota (agregat MySQL)
        tidakDiketahui: 0,
        loadError: "",
        radialCity: null,
        searchQuery: "",
        cariJalanId: null, // id UMKM yang jalannya sedang dicari di peta
        pesanPeta: "", // pesan singkat di atas peta (mis. jalan tidak ditemukan)
        isLoading: true,
        currentStats: { dasar: 0, berkembang: 0, unggulan: 0 },
        map: null,
        hasInitialized: false,
        // ==================== KABUPATEN & TIER DATA ====================
        kabupatens: [
            "Kota Pontianak",
            "Kota Singkawang",
            "Kab. Sambas",
            "Kab. Mempawah",
            "Kab. Kubu Raya",
            "Kab. Bengkayang",
            "Kab. Landak",
            "Kab. Sanggau",
            "Kab. Sekadau",
            "Kab. Sintang",
            "Kab. Melawi",
            "Kab. Kapuas Hulu",
            "Kab. Kayong Utara",
            "Kab. Ketapang",
        ],

        // ==================== CITIES DATA - 14 KOTA/KAB KALBAR ====================
        // coords = pusat kota / ibu kota kabupaten, sehingga titik jatuh tepat pada nama kota
        // di peta dasar OSM. direction = arah label (0° timur, 90° utara), dipilih agar
        // ke-14 label tidak saling tumpang tindih pada zoom awal.
        cities: [
            {
                name: "Kota Pontianak",
                ibukota: "Pontianak",
                coords: [-0.0263, 109.3425],
                direction: 180,
            },
            {
                name: "Kab. Kubu Raya",
                ibukota: "Sungai Raya",
                coords: [-0.1, 109.383],
                direction: 225,
            },
            {
                name: "Kab. Mempawah",
                ibukota: "Mempawah",
                coords: [0.3614, 108.9592],
                direction: 165,
            },
            {
                name: "Kota Singkawang",
                ibukota: "Singkawang",
                coords: [0.9061, 108.9872],
                direction: 120,
            },
            {
                name: "Kab. Sambas",
                ibukota: "Sambas",
                coords: [1.3631, 109.2963],
                direction: 90,
            },
            {
                name: "Kab. Bengkayang",
                ibukota: "Bengkayang",
                coords: [0.8167, 109.4833],
                direction: 60,
            },
            {
                name: "Kab. Landak",
                ibukota: "Ngabang",
                coords: [0.3833, 109.95],
                direction: 50,
            },
            {
                name: "Kab. Sanggau",
                ibukota: "Sanggau",
                coords: [0.1333, 110.5833],
                direction: 30,
            },
            {
                name: "Kab. Sekadau",
                ibukota: "Sekadau",
                coords: [0.0333, 110.95],
                direction: 300,
            },
            {
                name: "Kab. Sintang",
                ibukota: "Sintang",
                coords: [0.0667, 111.5],
                direction: 5,
            },
            {
                name: "Kab. Kapuas Hulu",
                ibukota: "Putussibau",
                coords: [0.8333, 112.9333],
                direction: 90,
            },
            {
                name: "Kab. Melawi",
                ibukota: "Nanga Pinoh",
                coords: [-0.3333, 111.7333],
                direction: 340,
            },
            {
                name: "Kab. Kayong Utara",
                ibukota: "Sukadana",
                coords: [-1.25, 109.95],
                direction: 200,
            },
            {
                name: "Kab. Ketapang",
                ibukota: "Ketapang",
                coords: [-1.85, 109.9833],
                direction: 0,
            },
        ],

        // ==================== COMPUTED PROPERTIES ====================
        get filteredDatabase() {
            let data = this.databaseUMKM;

            // Filter by selectedCity (dari map click)
            if (this.selectedCity) {
                data = data.filter(
                    (umkm) =>
                        umkm.kabupaten === this.selectedCity.name ||
                        umkm.kab === this.selectedCity.name,
                );
            }

            // Filter by selectedKabupaten (dari dropdown)
            if (this.selectedKabupaten) {
                data = data.filter(
                    (umkm) =>
                        umkm.kabupaten?.includes(this.selectedKabupaten) ||
                        umkm.kab === this.selectedKabupaten ||
                        umkm.kabupaten === this.selectedKabupaten,
                );
            }

            // Tombol filter sektor/platform/jangkauan/sertifikasi
            if (this.jumlahFilterAktif) {
                data = data.filter((umkm) => this.cocokFilter(umkm));
            }

            return data;
        },

        // Tab Program: program BI yang pernah dituliskan UMKM (sesuai wilayah & filter aktif),
        // dikelompokkan per nama program, terbanyak dulu
        get programReferensi() {
            const grup = new Map();
            this.filteredDatabase.forEach((umkm) =>
                (umkm.program ?? []).forEach((nama) => {
                    const kunci = nama.toLowerCase();
                    if (!grup.has(kunci)) grup.set(kunci, { nama, umkm: [] });
                    grup.get(kunci).umkm.push(umkm);
                }),
            );
            return [...grup.values()].sort(
                (a, b) => b.umkm.length - a.umkm.length || a.nama.localeCompare(b.nama, "id"),
            );
        },

        // Tab Database menampilkan teks sambutan selama belum ada pilihan — saat masuk peta & setelah Reset
        get tampilSambutan() {
            return (
                !this.lihatDaftar &&
                !this.selectedCity &&
                !this.selectedKabupaten &&
                !this.jumlahFilterAktif &&
                !this.searchQuery
            );
        },

        get jumlahFilterAktif() {
            return Object.values(this.filter).reduce(
                (n, pilih) => n + pilih.length,
                0,
            );
        },

        cocokFilter(umkm) {
            return Object.entries(this.filter).every(
                ([grup, pilih]) =>
                    !pilih.length ||
                    pilih.some((kode) => umkm.f?.[grup]?.includes(kode)),
            );
        },

        get filteredTable() {
            let data = this.filteredDatabase;

            if (!this.searchQuery) return data;

            const query = this.searchQuery.toLowerCase();
            return data.filter(
                (umkm) =>
                    (umkm.nama && umkm.nama.toLowerCase().includes(query)) ||
                    (umkm.sektor &&
                        umkm.sektor.toLowerCase().includes(query)) ||
                    (umkm.kecamatan &&
                        umkm.kecamatan.toLowerCase().includes(query)) ||
                    (umkm.alamat && umkm.alamat.toLowerCase().includes(query)),
            );
        },

        get cityUMKMs() {
            if (!this.selectedCity) return [];
            return this.databaseUMKM.filter(
                (u) =>
                    u.kabupaten === this.selectedCity.name ||
                    u.kab === this.selectedCity.name,
            );
        },

        get totalUMKM() {
            return this.databaseUMKM.length;
        },

        // ==================== INITIALIZATION ====================
        async init() {
            try {
                console.log("🚀 Initializing UMKMLinked Dashboard...");

                if (this.hasInitialized) {
                    console.log("⚠️ Already initialized, skipping...");
                    return;
                }

                // Load data from MySQL
                await this.loadDataFromAPI();

                // Initialize map after data loaded
                setTimeout(() => {
                    this.initMap();
                    this.hasInitialized = true;
                }, 300);

                this.isLoading = false;
                console.log("✅ Dashboard initialized successfully");
                console.log(
                    `📊 Total UMKM loaded: ${this.databaseUMKM.length}`,
                );
            } catch (error) {
                console.error("❌ Initialization error:", error);
                this.isLoading = false;
                this.hasInitialized = false;
            }
        },

        // ==================== DATA LOADING ====================
        // Hanya data asli MySQL — tidak ada data contoh/acak agar angka di peta selalu benar.
        async loadDataFromAPI() {
            try {
                const dataUrl = document.querySelector(
                    'meta[name="bi-data-url"]',
                )?.content;
                if (!dataUrl) throw new Error("URL data tidak ditemukan");

                const response = await fetch(dataUrl, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (!Array.isArray(data.umkm))
                    throw new Error("Format data tidak valid");

                this.databaseUMKM = data.umkm;
                this.jumlahKab = data.jumlah || {};
                this.hitungOpsi = this.hitungPilihan(data.umkm);
                this.tidakDiketahui = data.tidak_diketahui || 0;
            } catch (error) {
                console.error("⚠️ Gagal memuat data peta:", error.message);
                this.loadError =
                    "Data UMKM gagal dimuat dari server. Muat ulang halaman untuk mencoba lagi.";
            }
        },

        // Jumlah UMKM per pilihan filter (angka di samping kotak centang)
        hitungPilihan(daftar) {
            const hasil = {};
            daftar.forEach((u) =>
                Object.entries(u.f ?? {}).forEach(([grup, kode]) =>
                    kode.forEach((k) => {
                        hasil[grup] ??= {};
                        hasil[grup][k] = (hasil[grup][k] ?? 0) + 1;
                    }),
                ),
            );
            return hasil;
        },

        // Jumlah UMKM di kota: agregat MySQL, atau hitungan sesuai filter bila ada yang dicentang
        jumlahDiKota(cityName) {
            if (this.jumlahFilterAktif) return this.umkmDiKota(cityName).length;
            return this.jumlahKab[cityName] ?? 0;
        },

        get jumlahKalbar() {
            return this.cities.reduce(
                (n, c) => n + this.jumlahDiKota(c.name),
                0,
            );
        },

        // UMKM yang menjadi cabang di kota (sesuai filter)
        umkmDiKota(cityName) {
            return this.databaseUMKM.filter(
                (u) => u.kab === cityName && this.cocokFilter(u),
            );
        },

        // ==================== MAP INITIALIZATION ====================
        initMap() {
            try {
                if (this.map) {
                    this.map.remove();
                    kotaMarker.clear();
                }

                this.map = L.map("map", {
                    minZoom: ZOOM_MIN,
                    maxZoom: ZOOM_MAKS,
                    zoomControl: true,
                    attributionControl: true,
                });
                this.map.attributionControl.setPrefix(false);
                this.map.fitBounds(BATAS_KALBAR);

                L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    maxNativeZoom: 19,
                    attribution: "© OpenStreetMap",
                }).addTo(this.map);

                // Pane: garis batas provinsi (di atas ubin) & cabang UMKM (di atas label kota)
                this.map.createPane("batasPane");
                this.map.createPane("radialLabelPane");

                this.muatBatasKalbar();
                this.addMarkersToMap();
                this.tambahLabelProvinsi();
                this.tataLabel();

                // Hanya kelas tampilan yang berubah setelah zoom — posisi tidak pernah dihitung ulang
                this.map.on("zoomend", () => this.tataLabel());
                this.map.on("click", () => this.tutupRadial());
                document.addEventListener("keydown", (e) => {
                    if (e.key === "Escape") this.tutupRadial();
                });

                // Dropdown "Wilayah" dipilih → kota/kabupaten itu "terklik" di peta (cabang UMKM terbuka);
                // dikosongkan → pilihan kota ditutup. pilihKota() mengisi dropdown balik (dua arah).
                this.$watch("selectedKabupaten", (nama) => {
                    if (!nama) {
                        this.tutupRadial();
                        return;
                    }
                    const city = this.cities.find((c) => c.name === nama);
                    if (city && this.radialCity?.name !== nama) this.pilihKota(city);
                });

                // Filter dicentang → angka label & cabang ikut menyesuaikan
                this.$watch("filter", () => {
                    this.perbaruiAngkaLabel();
                    if (this.radialCity) this.gambarRadial();
                    this.tataLabel();
                });
            } catch (error) {
                console.error("❌ Map error:", error);
            }
        },

        // ==================== BATAS PROVINSI KALBAR ====================
        async muatBatasKalbar() {
            const url = document.querySelector(
                'meta[name="kalbar-geojson-url"]',
            )?.content;
            if (!url) return;
            try {
                const r = await fetch(url);
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                batasLayer = L.geoJSON(await r.json(), {
                    pane: "batasPane",
                    interactive: false,
                    className: "kalbar-batas",
                    style: {
                        color: "#003066",
                        weight: 2.5,
                        opacity: 0.9,
                        fillColor: "#003066",
                        fillOpacity: 0.05,
                    },
                }).addTo(this.map);
            } catch (e) {
                console.warn("Garis batas Kalbar gagal dimuat:", e.message);
            }
        },

        batasKalbar() {
            return batasLayer?.getBounds() ?? L.latLngBounds(BATAS_KALBAR);
        },

        // ==================== PENANDA KOTA ====================
        // Satu penanda per kota: titik + garis + label (lengkap) + gelembung angka (ringkas)
        addMarkersToMap() {
            this.cities.forEach((city) => {
                const rad = (city.direction * Math.PI) / 180;
                const dx = Math.round(Math.cos(rad) * LABEL_JARAK);
                const dy = Math.round(-Math.sin(rad) * LABEL_JARAK);

                const marker = L.marker(city.coords, {
                    keyboard: false,
                    riseOnHover: true,
                    icon: L.divIcon({
                        className: "kota-icon",
                        html: `
                            <div class="kota">
                                <span class="kota__garis" style="width:${LABEL_JARAK}px;transform:rotate(${-city.direction}deg)"></span>
                                <span class="kota__titik"></span>
                                <span class="kota__ringkas" data-angka></span>
                                <div class="kota__label" style="left:${dx}px;top:${dy}px">
                                    <div class="city-label">
                                        <div class="city-label__name">${esc(city.name)}</div>
                                        <div class="city-label__count" data-jumlah></div>
                                    </div>
                                </div>
                            </div>`,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0],
                    }),
                }).addTo(this.map);

                marker.on("click", () => this.pilihKota(city));
                kotaMarker.set(city.name, {
                    city,
                    marker,
                    el: marker.getElement(),
                    dx,
                    dy,
                });
            });

            this.perbaruiAngkaLabel();
        },

        // Label tampilan Indonesia (zoom jauh): satu label untuk seluruh provinsi
        tambahLabelProvinsi() {
            provinsiMarker = L.marker(
                L.latLngBounds(BATAS_KALBAR).getCenter(),
                {
                    keyboard: false,
                    icon: L.divIcon({
                        className: "kota-icon",
                        html: `<div class="provinsi-label"><strong>Kalimantan Barat</strong><span data-jumlah-provinsi></span></div>`,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0],
                    }),
                },
            );
            provinsiMarker.on("click", () => this.focusKalbar());
        },

        perbaruiAngkaLabel() {
            kotaMarker.forEach(({ city, el }) => {
                const n = this.jumlahDiKota(city.name);
                el.querySelector("[data-jumlah]").textContent =
                    `${this.formatNumber(n)} UMKM`;
                el.querySelector("[data-angka]").textContent =
                    this.formatNumber(n);
                el.querySelector(".city-label").title =
                    `Klik untuk menampilkan ${n} UMKM`;
                el.classList.toggle("is-kosong", n === 0);
            });
            const elProv = provinsiMarker?.getElement() ?? null;
            const teks = `${this.formatNumber(this.jumlahKalbar)} UMKM${this.jumlahFilterAktif ? " · terfilter" : ""}`;
            if (elProv)
                elProv.querySelector("[data-jumlah-provinsi]").textContent =
                    teks;
            this._teksProvinsi = teks;
        },

        /**
         * Atur tampilan label sesuai zoom agar tidak tumpang tindih:
         * - zoom ≤ ZOOM_PROVINSI : semua kota disembunyikan, tampil satu label provinsi
         * - zoom < ZOOM_LABEL_LENGKAP : gelembung angka ringkas
         * - selebihnya : label lengkap
         * Label yang masih bertabrakan diturunkan (lengkap → ringkas → titik saja),
         * mendahulukan kota terpilih lalu kota dengan UMKM terbanyak.
         */
        tataLabel() {
            if (!this.map) return;
            const zoom = this.map.getZoom();

            const provinsi = zoom <= ZOOM_PROVINSI;
            if (provinsi && !this.map.hasLayer(provinsiMarker)) {
                provinsiMarker.addTo(this.map);
                provinsiMarker
                    .getElement()
                    .querySelector("[data-jumlah-provinsi]").textContent =
                    this._teksProvinsi ?? "";
            } else if (!provinsi && this.map.hasLayer(provinsiMarker)) {
                provinsiMarker.remove();
            }

            const terpasang = [];
            const tabrakan = (r) =>
                terpasang.some(
                    (t) =>
                        r.x1 < t.x2 &&
                        r.x2 > t.x1 &&
                        r.y1 < t.y2 &&
                        r.y2 > t.y1,
                );

            const urutan = [...kotaMarker.values()].sort((a, b) => {
                if (a.city.name === this.radialCity?.name) return -1;
                if (b.city.name === this.radialCity?.name) return 1;
                return (
                    this.jumlahDiKota(b.city.name) -
                    this.jumlahDiKota(a.city.name)
                );
            });

            urutan.forEach(({ city, el, dx, dy }) => {
                let mode = "sembunyi";
                if (!provinsi) {
                    const p = this.map.latLngToContainerPoint(city.coords);
                    const lbl = el.querySelector(".city-label");
                    const w = (lbl.offsetWidth || 120) + 8;
                    const h = (lbl.offsetHeight || 44) + 6;
                    const rLabel = {
                        x1: p.x + dx - w / 2,
                        x2: p.x + dx + w / 2,
                        y1: p.y + dy - h / 2,
                        y2: p.y + dy + h / 2,
                    };
                    const rRingkas = {
                        x1: p.x + 4,
                        x2: p.x + 40,
                        y1: p.y - 28,
                        y2: p.y - 4,
                    };
                    const paksa = city.name === this.radialCity?.name;

                    // Kota terpilih (cabang terbuka) selalu berlabel lengkap
                    if (
                        paksa ||
                        (zoom >= ZOOM_LABEL_LENGKAP && !tabrakan(rLabel))
                    ) {
                        mode = "lengkap";
                        terpasang.push(rLabel);
                    } else if (!tabrakan(rRingkas)) {
                        mode = "ringkas";
                        terpasang.push(rRingkas);
                    } else {
                        mode = "titik";
                    }
                }
                el.dataset.mode = mode;
            });
        },

        pilihKota(city) {
            // Klik kota yang sama lagi = tutup cabang
            if (this.radialCity?.name === city.name) {
                this.tutupRadial();
                return;
            }
            this.selectedCity = city;
            this.selectedKabupaten = city.name; // dropdown "Wilayah" ikut kota yang diklik
            this.activeTab = "database";
            this.updateCityStats(
                this.databaseUMKM.filter((u) => u.kab === city.name),
            );
            this.bukaRadial(city);
            this.map.flyTo(city.coords, Math.max(this.map.getZoom(), 10), {
                duration: 0.6,
            });
        },

        // ==================== CABANG UMKM (RADIAL) ====================
        bukaRadial(city) {
            this.radialCity = city;
            kotaMarker.forEach(({ city: c, el }) => {
                el.classList.toggle("is-aktif", c.name === city.name);
                el.classList.toggle("is-redup", c.name !== city.name);
            });
            this.gambarRadial();
            this.tataLabel();
        },

        tutupRadial() {
            if (!this.radialCity) return;
            this.radialCity = null;
            radialMarker?.remove();
            radialMarker = null;
            kotaMarker.forEach(({ el }) =>
                el.classList.remove("is-aktif", "is-redup"),
            );
            // Kota tidak lagi terpilih → dropdown "Wilayah" kembali kosong
            this.selectedCity = null;
            this.selectedKabupaten = "";
            this.tataLabel();
        },

        /**
         * Satu cabang per UMKM di kota terpilih, memancar dari titik koordinat kota.
         * Cabang (SVG) dan label nama (HTML) berada dalam satu penanda di koordinat kota,
         * jadi tidak perlu digambar ulang saat zoom. Sektor di arah label kota dibiarkan kosong;
         * label diputar searah cabang (dibalik di sisi kiri agar tetap terbaca).
         */
        gambarRadial() {
            radialMarker?.remove();
            radialMarker = null;
            const city = this.radialCity;
            if (!city || !this.map) return;

            const daftar = this.umkmDiKota(city.name);
            if (!daftar.length) return;

            const tampil = daftar.slice(0, CABANG_MAKS_LABEL);
            const sisa = daftar.length - tampil.length;
            const slot = tampil.length + (sisa > 0 ? 1 : 0);

            const celah = slot > 1 ? CELAH_LABEL_KOTA : 0;
            const rentang = 2 * Math.PI - celah;
            const mulai = (city.direction * Math.PI) / 180 + celah / 2;
            const panjang = Math.round(
                Math.min(
                    CABANG_MAKS,
                    Math.max(CABANG_MIN, (slot * CABANG_JARAK_LABEL) / rentang),
                ),
            );

            let garis = "";
            let daun = "";
            const cabang = (i, isi) => {
                const sudut = mulai + (rentang * (i + 0.5)) / slot;
                const x = +(Math.cos(sudut) * panjang).toFixed(1);
                const y = +(-Math.sin(sudut) * panjang).toFixed(1);
                const derajat = (sudut * 180) / Math.PI;
                const kiri = Math.cos(sudut) < 0;
                const putar = (kiri ? 180 - derajat : -derajat).toFixed(1); // CSS rotate: searah jarum jam

                garis +=
                    `<line class="radial-branch" data-i="${i}" x1="0" y1="0" x2="${x}" y2="${y}"/>` +
                    `<circle class="radial-dot" data-i="${i}" cx="${x}" cy="${y}" r="3.5"/>`;
                daun += `<div class="radial-leaf ${kiri ? "radial-leaf--kiri" : ""}" data-i="${i}"
                              style="left:${x}px;top:${y}px;--putar:${putar}deg">${isi}</div>`;
            };

            tampil.forEach((u, i) => {
                const nama =
                    u.nama.length > 28 ? u.nama.slice(0, 27) + "…" : u.nama;
                const status = ["Unggulan", "Berkembang"].includes(u.status)
                    ? u.status.toLowerCase()
                    : "dasar";
                cabang(
                    i,
                    // Label UMKM → Super Admin / Admin OPD (UMKM binaannya): halaman detail /peta-interaktif/umkm/{id};
                    // selain itu halaman publik UMKM di Semua Brand (tab yang sama)
                    `<a href="${esc(u.url || u.url_publik)}" class="radial-leaf__link radial-leaf__link--${status}"
                        data-nama-lengkap="${esc(u.nama)}" data-nama-pendek="${esc(nama)}"
                        title="${esc(u.nama)} — ${esc(u.sektor)} · ${esc(u.status)}${u.skor != null ? ` (skor ${esc(u.skor)})` : ""}. ${u.url ? "Klik untuk detail UMKM & rekomendasi program." : "Klik untuk membuka halaman UMKM di Semua Brand."}">${esc(nama)}</a>`,
                );
            });
            if (sisa > 0) {
                cabang(
                    tampil.length,
                    `<button type="button" class="radial-leaf__link radial-leaf__link--lainnya" data-lainnya>+${sisa} UMKM lainnya</button>`,
                );
            }

            const r = CABANG_MAKS + 10;
            radialMarker = L.marker(city.coords, {
                pane: "radialLabelPane",
                keyboard: false,
                interactive: false,
                icon: L.divIcon({
                    className: "radial-icon",
                    html: `<div class="radial">
                               <svg class="radial__svg" style="left:${-r}px;top:${-r}px" width="${2 * r}" height="${2 * r}"
                                    viewBox="${-r} ${-r} ${2 * r} ${2 * r}" aria-hidden="true">${garis}</svg>
                               ${daun}
                           </div>`,
                    iconSize: [0, 0],
                    iconAnchor: [0, 0],
                }),
            }).addTo(this.map);

            const el = radialMarker.getElement();
            // Klik/seret pada label tidak diteruskan ke peta (agar cabang tidak tertutup)
            el.querySelectorAll(".radial-leaf").forEach((leaf) => {
                L.DomEvent.disableClickPropagation(leaf);
                L.DomEvent.disableScrollPropagation(leaf);

                // Sorot: label membesar (CSS), cabang & titiknya ikut menyala, nama tampil lengkap
                const link = leaf.querySelector(".radial-leaf__link");
                const pasangan = el.querySelectorAll(
                    `[data-i="${leaf.dataset.i}"]`,
                );
                const sorot = (aktif) => {
                    pasangan.forEach((n) =>
                        n.classList.toggle("is-sorot", aktif),
                    );
                    if (link?.dataset.namaLengkap) {
                        link.textContent = aktif
                            ? link.dataset.namaLengkap
                            : link.dataset.namaPendek;
                    }
                };
                ["mouseenter", "focusin"].forEach((ev) =>
                    leaf.addEventListener(ev, () => sorot(true)),
                );
                ["mouseleave", "focusout"].forEach((ev) =>
                    leaf.addEventListener(ev, () => sorot(false)),
                );
            });
            el.querySelector("[data-lainnya]")?.addEventListener(
                "click",
                () => {
                    this.activeTab = "database";
                    this.sidebarCollapsed = false;
                },
            );
        },

        // ==================== SIDEBAR ACTIONS ====================
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            console.log(
                "Sidebar toggled:",
                this.sidebarCollapsed ? "Collapsed" : "Expanded",
            );

            // Trigger map resize after animation
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 350);
        },
        updateCityStats(umkms) {
            const stats = {
                dasar: 0,
                berkembang: 0,
                unggulan: 0,
            };

            umkms.forEach((u) => {
                if (u.status === "Dasar") {
                    stats.dasar++;
                } else if (u.status === "Berkembang") {
                    stats.berkembang++;
                } else if (u.status === "Unggulan") {
                    stats.unggulan++;
                }
            });

            this.currentStats = stats;
            console.log(`📊 Stats for ${this.selectedCity.name}:`, stats);
        },

        resetMap() {
            this.selectedCity = null;
            this.selectedKabupaten = "";
            this.filter = {
                sektor: [],
                platform: [],
                jangkauan: [],
                sertifikasi: [],
            };
            this.activeTab = "database";
            this.lihatDaftar = false; // kembali menampilkan teks sambutan
            this.currentStats = { dasar: 0, berkembang: 0, unggulan: 0 };
            this.searchQuery = "";
            jalanMarker?.remove();
            jalanMarker = null;
            this.tutupRadial();
            this.focusKalbar();
        },

        // Fokus ke seluruh wilayah Kalbar; garis batas provinsi disorot sesaat
        focusKalbar() {
            if (!this.map) return;
            this.map.flyToBounds(this.batasKalbar(), {
                padding: [24, 24],
                duration: 1,
            });
            batasLayer?.eachLayer((l) => {
                const el = l.getElement?.();
                if (!el) return;
                el.classList.remove("is-sorot");
                void el.getBoundingClientRect(); // ulangi animasi bila tombol ditekan lagi
                el.classList.add("is-sorot");
            });
        },

        // ==================== CARI ALAMAT UMKM ====================
        // Nama jalan dari alamat bebas, mis. "Jl. Purnama 2 GG. Indah no. 44" → "Jalan Purnama 2".
        // Alamat tanpa "Jl." (mis. "Parit Bugis RT. 002") → bagian awalnya ("Parit Bugis").
        namaJalan(alamat) {
            const teks = String(alamat ?? "").replace(/\s+/g, " ").trim();
            const jl = teks.match(/\b(?:jl|jln|jalan)\b\.?\s*(.+)/i);
            const nama = (jl ? jl[1] : teks)
                .replace(AKHIR_JALAN, "")
                .replace(/[\s.\-–]+$/, "");
            if (nama.length < 3) return "";
            return jl ? `Jalan ${nama}` : nama;
        },

        gmapUrl(umkm) {
            const kab = umkm.kab && !/tidak diketahui/i.test(umkm.kab) ? umkm.kab : "";
            const q = [umkm.alamat, kab, "Kalimantan Barat"].filter(Boolean).join(", ");
            return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`;
        },

        // Cari koordinat (OpenStreetMap Nominatim), dibatasi wilayah Kalbar
        async geocode(q) {
            if (jalanCache.has(q)) return jalanCache.get(q);
            const [[s, w], [n, e]] = BATAS_KALBAR;
            const params = new URLSearchParams({
                q,
                format: "jsonv2",
                limit: "1",
                countrycodes: "id",
                "accept-language": "id",
                viewbox: `${w},${n},${e},${s}`,
                bounded: "1",
            });
            const r = await fetch(`${NOMINATIM}?${params}`, { headers: { Accept: "application/json" } });
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const [hasil] = await r.json();
            const titik = hasil ? [+hasil.lat, +hasil.lon] : null;
            jalanCache.set(q, titik);
            return titik;
        },

        // Tombol pin di kartu UMKM: terbangkan peta ke jalan pada alamat UMKM
        async cariJalan(umkm) {
            const jalan = this.namaJalan(umkm.alamat);
            if (!jalan || !this.map || this.cariJalanId) return;

            this.cariJalanId = umkm.id;
            this.pesanPeta = "";
            // Di HP panel menutupi peta → tutup agar hasil pencarian terlihat
            if (window.matchMedia("(max-width: 1023px)").matches) this.sidebarCollapsed = true;
            const kab = umkm.kab?.replace(/^(Kota|Kab\.)\s+/i, "");
            const city = this.cities.find((c) => c.name === umkm.kab);

            try {
                let titik = null;
                for (const q of [kab && city ? `${jalan}, ${kab}` : null, jalan].filter(Boolean)) {
                    titik = await this.geocode(`${q}, Kalimantan Barat`);
                    if (titik) break;
                }

                jalanMarker?.remove();
                jalanMarker = null;

                if (!titik) {
                    this.pesanPeta = `"${jalan}" tidak ditemukan di peta.` + (city ? ` Menampilkan ${city.name}.` : "");
                    if (city) this.map.flyTo(city.coords, Math.max(this.map.getZoom(), 11), { duration: 0.8 });
                    return;
                }

                jalanMarker = L.circleMarker(titik, {
                    radius: 10,
                    color: "#b91c1c",
                    weight: 3,
                    fillColor: "#ef4444",
                    fillOpacity: 0.45,
                })
                    .bindPopup(
                        `<strong>${esc(umkm.nama)}</strong><br>${esc(jalan)}` +
                            `<br><small>Perkiraan lokasi dari nama jalan</small>` +
                            `<br><a href="${esc(this.gmapUrl(umkm))}" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>`,
                    )
                    .addTo(this.map);
                this.map.flyTo(titik, 16, { duration: 0.8 });
                this.map.once("moveend", () => jalanMarker?.openPopup());
            } catch (e) {
                console.warn("Pencarian jalan gagal:", e.message);
                this.pesanPeta = "Pencarian lokasi gagal. Periksa koneksi internet lalu coba lagi.";
            } finally {
                this.cariJalanId = null;
                if (this.pesanPeta) setTimeout(() => (this.pesanPeta = ""), 5000);
            }
        },

        // ==================== WATCHERS & EVENT LISTENERS ====================
        watchSearch() {
            // Watch for search query changes
            if (this.$watch) {
                this.$watch("searchQuery", (newVal) => {
                    console.log("🔍 Search updated:", newVal);
                });
            }
        },

        watchTab() {
            // Watch for tab changes
            if (this.$watch) {
                this.$watch("activeTab", (newTab) => {
                    console.log("📑 Tab changed to:", newTab);
                });
            }
        },

        // ==================== UTILITY METHODS ====================
        getCityCount(cityName) {
            return this.databaseUMKM.filter((u) => u.kab === cityName).length;
        },

        getUMKMStats() {
            return {
                total: this.databaseUMKM.length,
                dasar: this.databaseUMKM.filter((u) => u.status === "Dasar")
                    .length,
                berkembang: this.databaseUMKM.filter(
                    (u) => u.status === "Berkembang",
                ).length,
                unggulan: this.databaseUMKM.filter(
                    (u) => u.status === "Unggulan",
                ).length,
            };
        },

        getUMKMByCity(cityName) {
            return this.databaseUMKM.filter((u) => u.kab === cityName);
        },

        // ==================== HELPER METHODS ====================
        formatNumber(num) {
            return new Intl.NumberFormat("id-ID").format(num);
        },

        log(message, type = "info") {
            const timestamp = new Date().toLocaleTimeString("id-ID");
            const prefix =
                {
                    info: "ℹ️",
                    success: "✓",
                    warning: "⚠️",
                    error: "❌",
                }[type] || "ℹ️";

            console.log(`[${timestamp}] ${prefix} ${message}`);
        },
    };
};

// ==================== ALPINE INITIALIZATION ====================
document.addEventListener("DOMContentLoaded", () => {
    console.log("📄 DOM Content Loaded - Starting Alpine...");
    Alpine.start();
});

// Fallback initialization
document.addEventListener("alpine:init", () => {
    console.log("🚀 Alpine initialized");
});

// Handle errors globally
window.addEventListener("error", (event) => {
    console.error("🔴 Global Error:", event.error);
});

window.addEventListener("unhandledrejection", (event) => {
    console.error("🔴 Unhandled Promise Rejection:", event.reason);
});

export default window.umkmApp;
