<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin yang akunnya atau OPD-nya dinonaktifkan Super Admin langsung dikeluarkan,
 * walaupun sesi login-nya masih berjalan.
 */
class PastikanAksesAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdminAny() && ($alasan = $user->alasanAksesDitolak())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => $alasan], 401)
                : redirect()->route('login')->withErrors(['email' => $alasan]);
        }

        return $next($request);
    }
}
