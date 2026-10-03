// resources/js/bi-map.js

import Alpine from "alpinejs";

window.umkmApp = () => {
    return {
        // ==================== STATE ====================
        activeTab: "dashboard",
        sidebarCollapsed: false,
        selectedCity: null,
        selectedKabupaten: "",
        selectedTier: "",
        databaseUMKM: [],
        searchQuery: "",
        isLoading: true,
        markerRefs: [],
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

        tiers: [
            { value: "", label: "Semua Tier" },
            { value: "Dasar", label: "Dasar" },
            { value: "Berkembang", label: "Berkembang" },
            { value: "Unggulan", label: "Unggulan" },
        ],
        // ==================== CITIES DATA - 14 KOTA/KAB KALBAR ====================
        cities: [
            {
                name: "Kota Pontianak",
                coords: [-0.026, 109.335],
                direction: 170,
            }, // Barat Daya
            {
                name: "Kab. Kubu Raya",
                coords: [-0.912, 109.669],
                direction: 180,
            }, // Barat Daya
            {
                name: "Kab. Mempawah",
                coords: [0.348, 109.07],
                direction: 150,
            }, // Barat Daya
            {
                name: "Kota Singkawang",
                coords: [0.905, 109.08],
                direction: 130,
            }, // Barat
            { name: "Kab. Sambas", coords: [1.251, 109.335], direction: 90 }, // Timur Laut
            {
                name: "Kab. Bengkayang",
                coords: [0.612, 109.627],
                direction: 40,
            }, // Timur Laut
            { name: "Kab. Landak", coords: [0.157, 109.844], direction: 50 }, // Timur Laut
            { name: "Kab. Sanggau", coords: [-0.893, 110.683], direction: 350 }, // Timur
            { name: "Kab. Sekadau", coords: [-0.13, 110.659], direction: 0 }, // Tenggara
            { name: "Kab. Sintang", coords: [0.524, 111.472], direction: 0 }, // Timur Laut
            {
                name: "Kab. Kapuas Hulu",
                coords: [0.887, 111.927],
                direction: 45,
            }, // Timur Laut
            { name: "Kab. Melawi", coords: [-0.25, 111.717], direction: 350 }, // Timur
            {
                name: "Kab. Kayong Utara",
                coords: [-1.254, 109.852],
                direction: 220,
            }, // Barat Daya
            { name: "Kab. Ketapang", coords: [-1.305, 110.21], direction: 270 }, // Barat Daya
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

            // Filter by selectedTier
            if (this.selectedTier) {
                data = data.filter((umkm) => umkm.status === this.selectedTier);
            }

            console.log(
                `📊 Filtered: ${data.length} UMKM (Kab: ${this.selectedKabupaten}, Tier: ${this.selectedTier})`,
            );
            return data;
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
        async loadDataFromAPI() {
            try {
                const dataUrl = document.querySelector(
                    'meta[name="bi-data-url"]',
                )?.content;

                if (!dataUrl) {
                    console.warn("⚠️ No data URL found in meta tag");
                    this.databaseUMKM = this.generateMockData();
                    return;
                }

                console.log("📡 Fetching UMKM data from:", dataUrl);
                const response = await fetch(dataUrl, {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });

                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }

                const data = await response.json();

                // Handle different response formats
                if (Array.isArray(data)) {
                    this.databaseUMKM = data;
                } else if (data.data && Array.isArray(data.data)) {
                    this.databaseUMKM = data.data;
                } else if (data.umkm && Array.isArray(data.umkm)) {
                    this.databaseUMKM = data.umkm;
                } else {
                    throw new Error("Invalid data format");
                }

                // Validate data
                if (this.databaseUMKM.length === 0) {
                    console.warn("⚠️ No data received, using mock data");
                    this.databaseUMKM = this.generateMockData();
                } else {
                    console.log(
                        `✓ Successfully loaded ${this.databaseUMKM.length} UMKM from MySQL`,
                    );
                }
            } catch (error) {
                console.error("⚠️ API Error:", error.message);
                console.log("Using mock data as fallback");
                this.databaseUMKM = this.generateMockData();
            }
        },

        // ==================== MOCK DATA GENERATOR ====================
        generateMockData() {
            const sectors = [
                "Kerajinan",
                "Pertanian",
                "Kuliner",
                "Perdagangan",
                "Teknologi",
            ];
            const statuses = ["Dasar", "Berkembang", "Unggulan"];

            let data = [];

            this.cities.forEach((city) => {
                const umkmCount = Math.floor(Math.random() * 15) + 8;

                for (let i = 0; i < umkmCount; i++) {
                    const status =
                        statuses[Math.floor(Math.random() * statuses.length)];
                    let skor;

                    if (status === "Unggulan") {
                        skor = Math.floor(Math.random() * 25) + 75;
                    } else if (status === "Berkembang") {
                        skor = Math.floor(Math.random() * 30) + 45;
                    } else {
                        skor = Math.floor(Math.random() * 45);
                    }

                    data.push({
                        id: `mock-${city.name}-${i}`,
                        nama: `UMKM ${city.name} #${i + 1}`,
                        kab: city.name,
                        sektor: sectors[
                            Math.floor(Math.random() * sectors.length)
                        ],
                        status: status,
                        skor: skor,
                        analisis: "Baik",
                        alamat: `Jl. Raya ${city.name} No. ${i + 1}`,
                    });
                }
            });

            console.log(`✓ Generated ${data.length} mock UMKM data`);
            return data;
        },

        // ==================== MAP INITIALIZATION ====================
        initMap() {
            try {
                if (this.map) {
                    this.map.remove();
                    this.markerRefs = [];
                }

                console.log("🗺️ Initializing Leaflet map...");

                this.map = L.map("map", {
                    center: [0, 110],
                    zoom: 8,
                    minZoom: 6,
                    maxZoom: 13,
                    zoomControl: true,
                    attributionControl: false,
                });

                // Setup panes untuk proper layering
                if (!this.map.getPane("shadowPane")) {
                    this.map.createPane("shadowPane");
                    this.map.getPane("shadowPane").style.zIndex = 2;
                }
                if (!this.map.getPane("overlayPane")) {
                    this.map.createPane("overlayPane");
                    this.map.getPane("overlayPane").style.zIndex = 4;
                }
                if (!this.map.getPane("markerPane")) {
                    this.map.createPane("markerPane");
                    this.map.getPane("markerPane").style.zIndex = 6;
                }
                if (!this.map.getPane("popupPane")) {
                    this.map.createPane("popupPane");
                    this.map.getPane("popupPane").style.zIndex = 700;
                }

                // Add tile layer
                L.tileLayer(
                    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                    {
                        maxZoom: 19,
                        attribution: "© OpenStreetMap",
                    },
                ).addTo(this.map);

                // Add markers SETELAH panes setup
                this.addMarkersToMap();

                console.log(
                    `✅ Map initialized with ${this.markerRefs.length} markers`,
                );
            } catch (error) {
                console.error("❌ Map error:", error);
            }
        },

        // ==================== ADD MARKERS & LABELS ====================
        addMarkersToMap() {
            console.log("Starting marker addition...");

            this.cities.forEach((city, idx) => {
                try {
                    const cityUMKMs = this.databaseUMKM.filter(
                        (u) => u.kab === city.name,
                    );
                    const jumlahUmkm = cityUMKMs.length;

                    console.log(
                        `City ${idx}: ${city.name} → ${jumlahUmkm} UMKM`,
                    );

                    // ===== STEP 1: ADD CONNECTOR LINE FIRST (akan di belakang) =====
                    const direction = (city.direction * Math.PI) / 180;
                    const distance = 100;

                    const markerPixel = this.map.latLngToContainerPoint(
                        city.coords,
                    );
                    const labelPixel = L.point(
                        markerPixel.x + Math.cos(direction) * distance,
                        markerPixel.y - Math.sin(direction) * distance,
                    );

                    const labelLatLng =
                        this.map.containerPointToLatLng(labelPixel);

                    // Add connector LINE dengan pane yang tepat
                    const connectorPolyline = L.polyline(
                        [city.coords, labelLatLng],
                        {
                            color: "#003066",
                            weight: 3,
                            dashArray: "8, 6",
                            opacity: 0.65,
                            interactive: false,
                            pane: "shadowPane", // Pane terdalam
                            className: "connector-line-custom",
                        },
                    );

                    // Add ke map PERTAMA KALI (sehingga render di belakang)
                    connectorPolyline.addTo(this.map);

                    // ===== STEP 2: ADD CIRCLE MARKER =====
                    const marker = L.circleMarker(city.coords, {
                        radius: 7,
                        fillColor: "#003066",
                        color: "#ffffff",
                        weight: 1.5,
                        opacity: 1,
                        fillOpacity: 0.95,
                        pane: "markerPane",
                        className: "city-marker-custom",
                    }).addTo(this.map);

                    // ===== STEP 3: ADD LABEL MARKER TERAKHIR (akan paling depan) =====
                    const label = L.marker(labelLatLng, {
                        icon: L.divIcon({
                            className: "custom-label-icon",
                            html: `
                        <div class="city-label">
                            <div class="city-label__name">${city.name}</div>
                            <div class="city-label__count">${jumlahUmkm} UMKM</div>
                        </div>
                    `,
                            iconSize: [120, 44],
                            iconAnchor: [60, 22],
                        }),
                        pane: "popupPane", // Pane paling depan
                    }).addTo(this.map);

                    // ===== CLICK HANDLERS =====
                    const selectCity = () => {
                        this.selectedCity = city;
                        this.activeTab = "database"; // ← AUTO SWITCH KE DATABASE TAB
                        const umkmsInCity = this.databaseUMKM.filter(
                            (u) =>
                                u.kabupaten === city.name ||
                                u.kab === city.name,
                        );
                        this.updateCityStats(umkmsInCity);
                        this.map.flyTo(city.coords, 10, { duration: 0.5 });
                        console.log(
                            `✅ Selected ${city.name}, showing ${umkmsInCity.length} UMKM`,
                        );
                    };

                    marker.on("click", selectCity);
                    label.on("click", selectCity);

                    this.markerRefs.push({
                        city,
                        marker,
                        label,
                        connector: connectorPolyline,
                    });
                } catch (error) {
                    console.warn(
                        `Error adding marker for ${city.name}:`,
                        error,
                    );
                }
            });

            console.log(`✅ Added ${this.markerRefs.length} markers`);
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
            console.log("🔄 Resetting all filters...");
            this.selectedCity = null;
            this.selectedKabupaten = "";
            this.selectedTier = "";
            this.activeTab = "dashboard";
            this.currentStats = { dasar: 0, berkembang: 0, unggulan: 0 };
            this.searchQuery = "";

            if (this.map) {
                this.map.flyTo([0, 110], 8, { duration: 0.5 });
            }

            console.log(`✅ Map reset - Showing all ${this.databaseUMKM.length} UMKM`);
        },

        focusKalbar() {
            if (this.map) {
                this.map.flyTo([0, 110], 10, { duration: 1 });
                console.log("📍 Focused on Kalimantan Barat");
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
