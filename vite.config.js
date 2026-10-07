import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/umkm-card.css",
                "resources/css/direktori-layout.css",
                "resources/css/beranda.css",
                "resources/css/berita.css",
                "resources/css/bi-map.css",
                "resources/css/peta-tailwind.css",
                "resources/css/publik.css",
                "resources/js/app.js",
                "resources/js/direktori.js",
                "resources/js/bi-map.js",
            ],
            refresh: true,
        }),
    ],
    // Build produksi: buang console.log/debug (log diagnostik peta) agar skrip lebih ringan
    // dan isi data tidak tercetak di konsol browser. console.warn/error tetap ada.
    build: {
        rolldownOptions: {
            treeshake: { manualPureFunctions: ["console.log", "console.debug"] },
        },
    },
});
