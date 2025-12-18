<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Daftar status yang dianggap "Sudah Masuk / Pernah Masuk" ke Admin Rektor
        // Kita KECUALIKAN 'baru_di_bau' agar surat yang belum diteruskan BAU tidak muncul.
        $statusRektor = [
            'di_admin_rektor', // Sedang di meja Rektor
            'didisposisi',     // Sudah disposisi, balik ke BAU
            'di_satker',       // Sudah sampai Satker tujuan
            'selesai',         // Selesai Arsip
            'arsip_satker',
            'disimpan',
            'diarsipkan',
            'selesai_edaran'
        ];

        // ====================================================
        // 1. DATA KARTU KPI
        // ====================================================
        $perluDisposisi = Surat::where('status', 'di_admin_rektor')->count();
        
        // Menghitung yang sudah selesai diproses oleh Rektor
        $sudahDisposisi = Surat::whereIn('status', [
            'didisposisi', 'selesai', 'di_satker', 'arsip_satker', 'disimpan', 'diarsipkan', 'selesai_edaran'
        ])->count();
        
        $totalDiterima = $perluDisposisi + $sudahDisposisi;

        // ====================================================
        // 2. DATA TABEL "TINDAKAN CEPAT"
        // ====================================================
        $suratBaru = Surat::where('status', 'di_admin_rektor')
                        ->latest('diterima_tanggal')
                        ->take(5)
                        ->get();

        // ====================================================
        // 3. DATA PIE CHART (Komposisi: Internal vs Eksternal)
        // ====================================================
        
        $pieChartData = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
                            ->whereIn('status', $statusRektor) // [PERBAIKAN] Tambahkan Filter Status
                            ->select('tipe_surat', DB::raw('count(*) as jumlah'))
                            ->groupBy('tipe_surat')
                            ->get();

        $pieLabels = $pieChartData->pluck('tipe_surat')->map(function ($tipe) {
            return ucfirst($tipe); 
        });
        $pieData = $pieChartData->pluck('jumlah');

        // ====================================================
        // 4. DATA LINE CHART (Tren 7 Hari Terakhir)
        // ====================================================
        
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();
        
        // Query Surat Rektor 7 Hari Terakhir
        $lineDataRaw = Surat::where('diterima_tanggal', '>=', $sevenDaysAgo)
                            ->whereIn('tujuan_tipe', ['rektor', 'universitas'])
                            ->whereIn('status', $statusRektor) // [PERBAIKAN] Tambahkan Filter Status
                            ->select(
                                DB::raw('DATE(diterima_tanggal) as tanggal'), 
                                DB::raw('count(*) as jumlah')
                            )
                            ->groupBy('tanggal')
                            ->get();

        $dataMap = $lineDataRaw->pluck('jumlah', 'tanggal')->toArray();

        $lineLabels = [];
        $lineData = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dateString = $dateObj->format('Y-m-d'); 
            
            $lineLabels[] = $dateObj->isoFormat('dddd, D MMM'); 
            $lineData[] = $dataMap[$dateString] ?? 0;
        }

        return view('admin_rektor.dashboard', compact(
            'perluDisposisi',
            'sudahDisposisi',
            'totalDiterima',
            'suratBaru',
            'pieLabels',
            'pieData',
            'lineLabels',
            'lineData'
        ));
    }
}