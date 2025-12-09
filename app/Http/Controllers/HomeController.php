<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Bau\DashboardController; 
use App\Http\Controllers\AdminRektor\DashboardController as AdminRektorDashboard;
use App\Http\Controllers\Satker\DashboardController as SatkerDashboard; 
// (BARU) Import Pegawai Dashboard
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboard; 

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $role = Auth::user()->role;

        if ($role == 'bau') {
            return (new DashboardController)->index();

        } elseif ($role == 'admin_rektor') {
            return (new AdminRektorDashboard)->index();

        } elseif ($role == 'satker') {
            return (new SatkerDashboard)->index();
        
        // ====================================================
        // (BARU) Tambahkan case untuk 'pegawai'
        // ====================================================
        } elseif ($role == 'pegawai') {
            // Arahkan ke Dashboard Pegawai (KPI & Chart)
            return (new PegawaiDashboard)->index();

        } else {
            // Jika role tidak dikenali, logout
            Auth::logout();
            return redirect('/login')->with('error', 'Peran Anda tidak dikenali.');
        }
    }
}