import base from './tailwind.config.js';

/**
 * Tailwind khusus halaman peta interaktif — pengganti Tailwind CDN (±400 KB skrip yang
 * menyusun CSS di browser). Hanya memindai view peta sehingga CSS-nya kecil, dan
 * tanpa plugin forms agar tampilan sama persis dengan versi CDN sebelumnya.
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/admin/bi-map/index.blade.php',
        './resources/js/bi-map.js',
    ],
    theme: base.theme,
    plugins: [],
};
