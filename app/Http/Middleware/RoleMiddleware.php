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
    public function handle(Request $request, Closure $next, $role): Response
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        // 2. Ambil user yang sedang login
        $user = Auth::user();

        // 3. Cek apakah role user sesuai dengan yang diminta route
        // Contoh pemanggilan: middleware('role:bau') -> $role = 'bau'
        // Kita gunakan explode agar bisa support banyak role jika perlu (misal: 'role:admin,bau')
        $roles = explode(',', $role);

        if (in_array($user->role, $roles)) {
            return $next($request); // Lanjut, akses diizinkan
        }

        // 4. Jika role tidak sesuai, tolak akses (403 Forbidden)
        abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
}