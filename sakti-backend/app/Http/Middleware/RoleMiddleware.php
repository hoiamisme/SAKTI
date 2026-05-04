<?php
// =============================================================================
// File   : app/Http/Middleware/RoleMiddleware.php
// Fungsi : Middleware untuk membatasi akses route berdasarkan role user
//          (admin / penjaga). Dipakai di route group web.
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Satu atau lebih role yang diizinkan, contoh: 'admin' atau 'admin','penjaga'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Cek apakah role user termasuk dalam role yang diizinkan
        if (!in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden. Anda tidak memiliki akses ke halaman ini.',
                ], 403);
            }

            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
