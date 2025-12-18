<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat; // Surat Masuk (Eksternal)
use App\Models\SuratKeluar; // Surat Keluar/Masuk (Internal)
use App\Models\Satker;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;

        // ====================================================================
        // 1. PENGAMBILAN DATA (DATABASE QUERIES)
        // ====================================================================

        // A. Surat Masuk Eksternal (Dari Rektor/BAU via Disposisi)
        // Kita ambil ID dan Tanggal saja untuk performa, karena hanya butuh hitungan
        $suratDisposisi = Surat::select('id', 'diterima_tanggal')
            ->whereHas('disposisis', function ($q) use ($satkerId) {
                $q->where('tujuan_satker_id', $satkerId);
            })->get();

        // B. Surat Masuk Eksternal (Edaran)
        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()
            ->select('surats.id', 'surats.diterima_tanggal')
            ->get();

        // C. Surat Masuk Internal (Dari Satker Lain via Pivot)
        $suratMasukInternal = SuratKeluar::select('id', 'tanggal_surat')
            ->where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($satkerId) {
                $q->where('satkers.id', $satkerId);
            })->get();

        // D. Surat Keluar (Internal & Eksternal milik user ini)
        // Hitung total surat keluar (internal)
        $totalSuratKeluar = SuratKeluar::where('tipe_kirim', 'internal')
            ->where('user_id', $user->id)
            ->count();


        // ====================================================================
        // 2. PENGGABUNGAN DATA (STANDARISASI)
        // ====================================================================
        
        $allSuratMasuk = collect();

        // Helper untuk format data
        // Kita pakai 'diterima_tanggal' untuk Eksternal, dan 'tanggal_surat' untuk Internal (karena langsung sampai)
        $allSuratMasuk = $allSuratMasuk->merge($suratDisposisi->map(function($item){
            return ['tgl' => Carbon::parse($item->diterima_tanggal), 'tipe' => 'eksternal'];
        }));

        $allSuratMasuk = $allSuratMasuk->merge($suratEdaran->map(function($item){
            return ['tgl' => Carbon::parse($item->diterima_tanggal), 'tipe' => 'eksternal']; // Edaran dianggap eksternal/pusat
        }));

        $allSuratMasuk = $allSuratMasuk->merge($suratMasukInternal->map(function($item){
            return ['tgl' => Carbon::parse($item->tanggal_surat), 'tipe' => 'internal'];
        }));


        // ==========================================
        // 3. LOGIKA KARTU STATISTIK (KPI)
        // ==========================================
        
        $totalSuratDiterima = $allSuratMasuk->count();
        
        $suratBulanIni = $allSuratMasuk->filter(function ($item) {
            return $item['tgl']->isCurrentMonth() && $item['tgl']->isCurrentYear();
        })->count();


        // ==========================================
        // 4. LOGIKA PIE CHART (SUMBER SURAT)
        // ==========================================
        
        // Filter menggunakan Collection
        $countEksternal = $allSuratMasuk->where('tipe', 'eksternal')->count();
        $countInternal  = $allSuratMasuk->where('tipe', 'internal')->count();
        
        $pieLabels = ['Eksternal (Pusat)', 'Internal (Satker)'];
        $pieData   = [$countEksternal, $countInternal];


        // ==========================================
        // 5. LOGIKA LINE CHART (7 HARI TERAKHIR)
        // ==========================================
        
        $lineLabels = [];
        $lineData   = [];

        // Loop 7 hari ke belakang (termasuk hari ini)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $formattedDate = $date->format('Y-m-d'); // Format pembanding
            
            // Label Chart: "17 Des"
            $lineLabels[] = $date->isoFormat('D MMM'); 

            // Hitung surat pada tanggal tersebut
            $count = $allSuratMasuk->filter(function ($item) use ($formattedDate) {
                return $item['tgl']->format('Y-m-d') === $formattedDate;
            })->count();

            $lineData[] = $count;
        }

        return view('satker.dashboard', compact(
            'totalSuratDiterima',
            'suratBulanIni',
            'totalSuratKeluar',
            'pieLabels',   
            'pieData',     
            'lineLabels',  
            'lineData'     
        ));
    }
}