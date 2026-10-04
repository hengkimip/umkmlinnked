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
const BATAS_KALBAR = [[-3.04, 108.82], [2.0, 114.2]];

// Objek Leaflet disimpan di luar state Alpine agar tidak dibungkus proxy
const kotaMarker = new Map(); // nama kota → { city, marker, el }
let radialMarker = null;
let provinsiMarker = null;
let batasLayer = null;

const esc = (s) =>
    String(s ?? "").replace(
        /[&<>"']/g,
        (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
    );

window.umkmApp = () => {
    return {
        // ==================== STATE ====================
        activeTab: "dashboard",
        sidebarCollapsed: false,
        selectedCity: null,
        selectedKabupaten: "",
        selectedSektor: "", // kategori UMKM (kode sektor dari database)
        sektorList: [], // [{ kode, label, jumlah }] dari database
        databaseUMKM: [],
        jumlahKab: {}, // jumlah UMKM per kabupaten/kota (agregat MySQL)
        jumlahSektor: {}, // { sektor: { kota: n } } (agregat MySQL)
        tidakDiketahui: 0,
        loadError: "",
        radialCity: null,
        searchQuery: "",
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
            { name: "Kota Pontianak", ibukota: "Pontianak", coords: [-0.0263, 109.3425], direction: 160 },
            { name: "Kab. Kubu Raya", ibukota: "Sungai Raya", coords: [-0.1, 109.383], direction: 225 },
            { name: "Kab. Mempawah", ibukota: "Mempawah", coords: [0.3614, 108.9592], direction: 165 },
            { name: "Kota Singkawang", ibukota: "Singkawang", coords: [0.9061, 108.9872], direction: 180 },
            { name: "Kab. Sambas", ibukota: "Sambas", coords: [1.3631, 109.2963], direction: 90 },
            { name: "Kab. Bengkayang", ibukota: "Bengkayang", coords: [0.8167, 109.4833], direction: 60 },
            { name: "Kab. Landak", ibukota: "Ngabang", coords: [0.3833, 109.95], direction: 100 },
            { name: "Kab. Sanggau", ibukota: "Sanggau", coords: [0.1333, 110.5833], direction: 60 },
            { name: "Kab. Sekadau", ibukota: "Sekadau", coords: [0.0333, 110.95], direction: 300 },
            { name: "Kab. Sintang", ibukota: "Sintang", coords: [0.0667, 111.5], direction: 40 },
            { name: "Kab. Kapuas Hulu", ibukota: "Putussibau", coords: [0.8333, 112.9333], direction: 180 },
            { name: "Kab. Melawi", ibukota: "Nanga Pinoh", coords: [-0.3333, 111.7333], direction: 340 },
            { name: "Kab. Kayong Utara", ibukota: "Sukadana", coords: [-1.25, 109.95], direction: 200 },
            { name: "Kab. Ketapang", ibukota: "Ketapang", coords: [-1.85, 109.9833], direction: 0 },
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

            // Filter kategori UMKM (sektor)
            if (this.selectedSektor) {
                data = data.filter((umkm) => umkm.sektor_kode === this.selectedSektor);
            }

            return data;
        },

        get labelSektor() {
            return this.sektorList.find((s) => s.kode === this.selectedSektor)?.label ?? "";
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
                const dataUrl = document.querySelector('meta[name="bi-data-url"]')?.content;
                if (!dataUrl) throw new Error("URL data tidak ditemukan");

                const response = await fetch(dataUrl, {
                    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    credentials: "same-origin",
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (!Array.isArray(data.umkm)) throw new Error("Format data tidak valid");

                this.databaseUMKM = data.umkm;
                this.jumlahKab = data.jumlah || {};
                this.jumlahSektor = data.jumlah_sektor || {};
                this.sektorList = data.sektor || [];
                this.tidakDiketahui = data.tidak_diketahui || 0;
            } catch (error) {
                console.error("⚠️ Gagal memuat data peta:", error.message);
                this.loadError = "Data UMKM gagal dimuat dari server. Muat ulang halaman untuk mencoba lagi.";
            }
        },

        // Jumlah UMKM di kota (sesuai filter kategori bila aktif) — dari agregat MySQL
        jumlahDiKota(cityName) {
            if (this.selectedSektor) return this.jumlahSektor[this.selectedSektor]?.[cityName] ?? 0;
            return this.jumlahKab[cityName] ?? 0;
        },

        get jumlahKalbar() {
            return this.cities.reduce((n, c) => n + this.jumlahDiKota(c.name), 0);
        },

        // UMKM yang menjadi cabang di kota (sesuai filter kategori)
        umkmDiKota(cityName) {
            return this.databaseUMKM.filter(
                (u) => u.kab === cityName && (!this.selectedSektor || u.sektor_kode === this.selectedSektor),
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

                // Filter kategori → angka label & cabang ikut menyesuaikan
                this.$watch("selectedSektor", () => {
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
            const url = document.querySelector('meta[name="kalbar-geojson-url"]')?.content;
            if (!url) return;
            try {
                const r = await fetch(url);
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                batasLayer = L.geoJSON(await r.json(), {
                    pane: "batasPane",
                    interactive: false,
                    className: "kalbar-batas",
                    style: { color: "#003066", weight: 2.5, opacity: 0.9, fillColor: "#003066", fillOpacity: 0.05 },
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
                kotaMarker.set(city.name, { city, marker, el: marker.getElement(), dx, dy });
            });

            this.perbaruiAngkaLabel();
        },

        // Label tampilan Indonesia (zoom jauh): satu label untuk seluruh provinsi
        tambahLabelProvinsi() {
            provinsiMarker = L.marker(L.latLngBounds(BATAS_KALBAR).getCenter(), {
                keyboard: false,
                icon: L.divIcon({
                    className: "kota-icon",
                    html: `<div class="provinsi-label"><strong>Kalimantan Barat</strong><span data-jumlah-provinsi></span></div>`,
                    iconSize: [0, 0],
                    iconAnchor: [0, 0],
                }),
            });
            provinsiMarker.on("click", () => this.focusKalbar());
        },

        perbaruiAngkaLabel() {
            kotaMarker.forEach(({ city, el }) => {
                const n = this.jumlahDiKota(city.name);
                el.querySelector("[data-jumlah]").textContent = `${this.formatNumber(n)} UMKM`;
                el.querySelector("[data-angka]").textContent = this.formatNumber(n);
                el.querySelector(".city-label").title = `Klik untuk menampilkan ${n} UMKM`;
                el.classList.toggle("is-kosong", n === 0);
            });
            const elProv = provinsiMarker?.getElement() ?? null;
            const teks = `${this.formatNumber(this.jumlahKalbar)} UMKM${this.labelSektor ? " · " + this.labelSektor : ""}`;
            if (elProv) elProv.querySelector("[data-jumlah-provinsi]").textContent = teks;
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
                provinsiMarker.getElement().querySelector("[data-jumlah-provinsi]").textContent = this._teksProvinsi ?? "";
            } else if (!provinsi && this.map.hasLayer(provinsiMarker)) {
                provinsiMarker.remove();
            }

            const terpasang = [];
            const tabrakan = (r) =>
                terpasang.some((t) => r.x1 < t.x2 && r.x2 > t.x1 && r.y1 < t.y2 && r.y2 > t.y1);

            const urutan = [...kotaMarker.values()].sort((a, b) => {
                if (a.city.name === this.radialCity?.name) return -1;
                if (b.city.name === this.radialCity?.name) return 1;
                return this.jumlahDiKota(b.city.name) - this.jumlahDiKota(a.city.name);
            });

            urutan.forEach(({ city, el, dx, dy }) => {
                let mode = "sembunyi";
                if (!provinsi) {
                    const p = this.map.latLngToContainerPoint(city.coords);
                    const lbl = el.querySelector(".city-label");
                    const w = (lbl.offsetWidth || 120) + 8;
                    const h = (lbl.offsetHeight || 44) + 6;
                    const rLabel = { x1: p.x + dx - w / 2, x2: p.x + dx + w / 2, y1: p.y + dy - h / 2, y2: p.y + dy + h / 2 };
                    const rRingkas = { x1: p.x + 4, x2: p.x + 40, y1: p.y - 28, y2: p.y - 4 };
                    const paksa = city.name === this.radialCity?.name;

                    // Kota terpilih (cabang terbuka) selalu berlabel lengkap
                    if (paksa || (zoom >= ZOOM_LABEL_LENGKAP && !tabrakan(rLabel))) {
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
            this.activeTab = "database";
            this.updateCityStats(this.databaseUMKM.filter((u) => u.kab === city.name));
            this.bukaRadial(city);
            this.map.flyTo(city.coords, Math.max(this.map.getZoom(), 10), { duration: 0.6 });
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
            kotaMarker.forEach(({ el }) => el.classList.remove("is-aktif", "is-redup"));
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
                Math.min(CABANG_MAKS, Math.max(CABANG_MIN, (slot * CABANG_JARAK_LABEL) / rentang)),
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

                garis += `<line class="radial-branch" data-i="${i}" x1="0" y1="0" x2="${x}" y2="${y}"/>`
                       + `<circle class="radial-dot" data-i="${i}" cx="${x}" cy="${y}" r="3.5"/>`;
                daun += `<div class="radial-leaf ${kiri ? "radial-leaf--kiri" : ""}" data-i="${i}"
                              style="left:${x}px;top:${y}px;--putar:${putar}deg">${isi}</div>`;
            };

            tampil.forEach((u, i) => {
                const nama = u.nama.length > 28 ? u.nama.slice(0, 27) + "…" : u.nama;
                const status = ["Unggulan", "Berkembang"].includes(u.status) ? u.status.toLowerCase() : "dasar";
                cabang(
                    i,
                    `<a href="${esc(u.url)}" target="_blank" rel="noopener" class="radial-leaf__link radial-leaf__link--${status}"
                        data-nama-lengkap="${esc(u.nama)}" data-nama-pendek="${esc(nama)}"
                        title="${esc(u.nama)} — ${esc(u.sektor)} · ${esc(u.status)} (skor ${esc(u.skor)}). Klik untuk detail & rekomendasi program.">${esc(nama)}</a>`,
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
                const pasangan = el.querySelectorAll(`[data-i="${leaf.dataset.i}"]`);
                const sorot = (aktif) => {
                    pasangan.forEach((n) => n.classList.toggle("is-sorot", aktif));
                    if (link?.dataset.namaLengkap) {
                        link.textContent = aktif ? link.dataset.namaLengkap : link.dataset.namaPendek;
                    }
                };
                ["mouseenter", "focusin"].forEach((ev) => leaf.addEventListener(ev, () => sorot(true)));
                ["mouseleave", "focusout"].forEach((ev) => leaf.addEventListener(ev, () => sorot(false)));
            });
            el.querySelector("[data-lainnya]")?.addEventListener("click", () => {
                this.activeTab = "database";
                this.sidebarCollapsed = false;
            });
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
            this.selectedSektor = "";
            this.activeTab = "dashboard";
            this.currentStats = { dasar: 0, berkembang: 0, unggulan: 0 };
            this.searchQuery = "";
            this.tutupRadial();
            this.focusKalbar();
        },

        // Fokus ke seluruh wilayah Kalbar; garis batas provinsi disorot sesaat
        focusKalbar() {
            if (!this.map) return;
            this.map.flyToBounds(this.batasKalbar(), { padding: [24, 24], duration: 1 });
            batasLayer?.eachLayer((l) => {
                const el = l.getElement?.();
                if (!el) return;
                el.classList.remove("is-sorot");
                void el.getBoundingClientRect(); // ulangi animasi bila tombol ditekan lagi
                el.classList.add("is-sorot");
            });
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
