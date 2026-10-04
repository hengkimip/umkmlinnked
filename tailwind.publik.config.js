import base from './tailwind.config.js';

/**
 * Tailwind untuk halaman publik. Halaman publik memakai sistem desain sendiri
 * (direktori-layout.css) dan hanya sedikit utilitas Tailwind, jadi cukup memindai
 * view publik — CSS turun dari ±69 KB (app.css) menjadi beberapa KB saja.
 * Plugin & tema sama dengan konfigurasi utama agar tampilan tidak berubah.
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/layouts/public.blade.php',
        './resources/views/public/**/*.blade.php',
        './resources/views/components/public/**/*.blade.php',
        './resources/views/components/umkm-card.blade.php',
    ],
    theme: base.theme,
    plugins: base.plugins,
};
