<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Bau\DashboardController; 
use App\Http\Controllers\AdminRektor\DashboardController as AdminRektorDashboard;
use App\Http\Controllers\Satker\DashboardController as SatkerDashboard; 
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboard; 

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $role = $user->role;
        $jabatan_id = $user->jabatan_id;

        // 1. ROLE: BAU (Gerbang Utama)
        if ($role == 'bau') {
            return (new DashboardController)->index();

        // 2. ROLE: ADMIN REKTOR (Shared Inbox Rektorat)
        } elseif ($role == 'admin_rektor') {
            return (new AdminRektorDashboard)->index();

        // 3. ROLE: ADMIN SATKER (BARU - Sebelumnya 'satker')
        // Ini adalah perbaikan agar Admin TU Fakultas bisa masuk
        } elseif ($role == 'admin_satker') {
            return (new SatkerDashboard)->index();
        
        // 4. ROLE: PIMPINAN (Rektor, Warek, Dekan, Wadek, Kabiro)
        } elseif ($role == 'pimpinan') {
            /**
             * LOGIKA REDIRECT PIMPINAN:
             * - Pimpinan Utama (Rektor ID 1, Dekan ID 5, Kabiro ID 7) diarahkan ke dashboard unit (Shared Inbox)
             * - Pimpinan Bidang (Warek ID 2,3,4, Wadek ID 6) diarahkan ke dashboard personal (Filtered Inbox)
             */
            if (in_array($jabatan_id, [1, 5, 7])) {
                // Jika Rektor, gunakan dashboard Admin Rektor
                if ($user->satker_id == 1) {
                    return (new AdminRektorDashboard)->index();
                }
                // Jika Dekan/Kabiro, gunakan dashboard Satker
                return (new SatkerDashboard)->index();
            } else {
                // Wakil (Warek/Wadek) diarahkan ke dashboard personal seperti pegawai
                return (new SatkerDashboard)->index();
            }

        // 5. ROLE: PEGAWAI
        } elseif ($role == 'pegawai') {
            return (new PegawaiDashboard)->index();

        } else {
            // Jika role tidak dikenali, logout
            Auth::logout();
            return redirect('/login')->with('error', 'Peran Anda tidak dikenali.');
        }
    }
}