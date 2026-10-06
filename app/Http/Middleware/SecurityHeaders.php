<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons web.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');                 // cegah MIME sniffing
        $h->set('X-Frame-Options', 'SAMEORIGIN');                     // cegah clickjacking
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->remove('X-Powered-By');
        // Header versi PHP ditambahkan PHP sendiri (expose_php), di luar objek respons Laravel
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        // CSP: hanya sumber yang memang dipakai situs. Skrip pihak ketiga hanya Leaflet (unpkg);
        // 'unsafe-eval' dibutuhkan Alpine.js, 'unsafe-inline' untuk skrip/gaya kecil di view.
        // Dilewati saat server Vite dev (npm run dev) aktif karena asetnya dari host lain.
        if (! Vite::isRunningHot() && ! $h->has('Content-Security-Policy')) {
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://unpkg.com",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https:",               // foto produk (Drive, dll.) & ubin peta OSM
                "connect-src 'self' https://nominatim.openstreetmap.org", // cari jalan di peta
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ]));
        }

        // HSTS hanya lewat HTTPS (production)
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman admin & JSON berisi data pribadi: jangan disimpan cache browser/proxy
        if ($request->user() || $request->is('admin/*', 'superadmin/*', 'profile')) {
            $h->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
