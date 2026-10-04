<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
