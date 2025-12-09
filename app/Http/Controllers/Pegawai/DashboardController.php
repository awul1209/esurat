<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\Satker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;

        // 1. AMBIL SURAT PRIBADI (Delegasi Langsung)
        // Surat yang didelegasikan spesifik ke pegawai ini (tujuan_user_id)
        $suratPribadi = Surat::where('tujuan_user_id', $user->id)->get();

        // 2. AMBIL SURAT UMUM (Edaran dari Satker)
        // Surat yang diteruskan oleh Admin Satker ke "Semua Pegawai" (diteruskan_internal)
        $satker = Satker::find($satkerId);
        
        // Kita ambil surat edaran yang status pivot-nya 'diteruskan_internal'
        $suratUmum = $satker->suratEdaran()
                            ->wherePivot('status', 'diteruskan_internal')
                            ->get();

        // 3. GABUNGKAN KEDUANYA
        $allSuratMasuk = $suratPribadi->merge($suratUmum)->unique('id');


        // ==========================================
        // 1. DATA KARTU STATISTIK
        // ==========================================
        
        // PERBAIKAN: Mengubah nama variabel menjadi $totalSurat agar sesuai dengan View
        $totalSurat = $allSuratMasuk->count();

        // Surat Bulan Ini
        $suratBulanIni = $allSuratMasuk->filter(function ($surat) {
            return Carbon::parse($surat->tanggal_surat)->isCurrentMonth() && 
                   Carbon::parse($surat->tanggal_surat)->isCurrentYear();
        })->count();

        // Total Surat Eksternal
        $totalEksternal = $allSuratMasuk->where('tipe_surat', 'eksternal')->count();


        // ==========================================
        // 2. DATA PIE CHART (KOMPOSISI)
        // ==========================================
        
        $totalInternal = $allSuratMasuk->where('tipe_surat', 'internal')->count();
        
        $pieLabels = ['Eksternal', 'Internal'];
        $pieData   = [$totalEksternal, $totalInternal];


        // ==========================================
        // 3. DATA LINE CHART (TREN)
        // ==========================================
        
        $chartData = $allSuratMasuk->groupBy(function($date) {
            return Carbon::parse($date->tanggal_surat)->format('m'); 
        });

        $lineLabels = [];
        $lineData   = [];

        for ($i = 1; $i <= 12; $i++) {
            $bulan = str_pad($i, 2, '0', STR_PAD_LEFT);
            $namaBulan = Carbon::createFromFormat('m', $bulan)->isoFormat('MMMM');
            
            $lineLabels[] = $namaBulan;
            $lineData[]   = isset($chartData[$bulan]) ? $chartData[$bulan]->count() : 0;
        }

        // Pastikan view-nya mengarah ke 'pegawai.dashboard'
        return view('pegawai.dashboard', compact(
            'totalSurat',      // <-- Variabel ini sekarang bernama $totalSurat
            'suratBulanIni',
            'totalEksternal',
            'pieLabels',
            'pieData',
            'lineLabels',
            'lineData'
        ));
    }
}