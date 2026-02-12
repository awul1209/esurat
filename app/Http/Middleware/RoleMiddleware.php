<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // 2. PROTEKSI STATUS AKTIF:
        // Jika user tidak aktif, paksa logout dan arahkan kembali ke login
        if (!$user->is_active) {
            Auth::logout();
            return redirect('/login')->with('error', 'Akun Anda sudah dinonaktifkan. Silakan hubungi Admin BAU.');
        }

        // 3. Cek apakah role user sesuai dengan daftar yang diminta route
        if (in_array($user->role, $roles)) {
            return $next($request); // Lanjut, akses diizinkan
        }

        // 4. Jika role tidak sesuai, tolak akses (403 Forbidden)
        abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
}