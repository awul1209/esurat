<?php

namespace App\Http\Controllers\Satker;

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

        // 1. AMBIL SURAT DARI JALUR DISPOSISI
        $suratDisposisi = Surat::whereHas('disposisis', function ($q) use ($satkerId) {
            $q->where('tujuan_satker_id', $satkerId);
        })->get();

        // 2. AMBIL SURAT DARI JALUR EDARAN
        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()->get();

        // 3. GABUNGKAN KEDUANYA
        $allSuratMasuk = $suratDisposisi->merge($suratEdaran)->unique('id');


        // ==========================================
        // 1. DATA UNTUK KARTU (CARD) STATISTIK
        // ==========================================
        
        $totalSuratDiterima = $allSuratMasuk->count();
        
        // Surat Bulan Ini
        $suratBulanIni = $allSuratMasuk->filter(function ($surat) {
            return Carbon::parse($surat->tanggal_surat)->isCurrentMonth() && 
                   Carbon::parse($surat->tanggal_surat)->isCurrentYear();
        })->count();

        // Total Surat Eksternal
        $totalEksternal = $allSuratMasuk->where('tipe_surat', 'eksternal')->count();


        // ==========================================
        // 2. DATA UNTUK PIE CHART (KOMPOSISI SURAT)
        // ==========================================
        
        // Kita bandingkan Eksternal vs Internal
        $totalInternal = $allSuratMasuk->where('tipe_surat', 'internal')->count();
        
        // Variabel ini wajib ada agar tidak error "$pieLabels undefined"
        $pieLabels = ['Eksternal', 'Internal'];
        $pieData   = [$totalEksternal, $totalInternal];


        // ==========================================
        // 3. DATA UNTUK LINE CHART (TREN SURAT)
        // ==========================================
        
        // Mengelompokkan data berdasarkan Bulan
        $chartData = $allSuratMasuk->groupBy(function($date) {
            return Carbon::parse($date->tanggal_surat)->format('m'); 
        });

        // Variabel ini wajib bernama $lineLabels dan $lineData sesuai view Anda
        $lineLabels = [];
        $lineData   = [];

        // Loop 12 Bulan (Jan - Des)
        for ($i = 1; $i <= 12; $i++) {
            $bulan = str_pad($i, 2, '0', STR_PAD_LEFT);
            $namaBulan = Carbon::createFromFormat('m', $bulan)->isoFormat('MMMM');
            
            $lineLabels[] = $namaBulan;
            $lineData[]   = isset($chartData[$bulan]) ? $chartData[$bulan]->count() : 0;
        }

        return view('satker.dashboard', compact(
            'totalSuratDiterima',
            'suratBulanIni',
            'totalEksternal',
            'pieLabels',   // <-- Data Pie Chart
            'pieData',     // <-- Data Pie Chart
            'lineLabels',  // <-- Data Line Chart (sebelumnya $bulanChart)
            'lineData'     // <-- Data Line Chart (sebelumnya $jumlahChart)
        ));
    }
}