<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard untuk Admin Rektor.
     */
    public function index()
    {
        // ====================================================
        // 1. DATA UNTUK KARTU KPI (DIPERBARUI)
        // ====================================================

        // Data 1: Surat yang perlu disposisi (pekerjaan saat ini)
        $perluDisposisi = Surat::where('status', 'di_admin_rektor')->count();
        
        // Data 2: Surat yang sudah selesai didisposisi (total)
        // PERBAIKAN: Hitung 'didisposisi' (menunggu BAU) + 'selesai' (final)
        $sudahDisposisi = Surat::whereIn('status', ['didisposisi', 'selesai'])->count();

        // Data 3: Total surat yang pernah ditangani
        $totalDiterima = $perluDisposisi + $sudahDisposisi;


        // ====================================================
        // 2. DATA UNTUK TABEL "TINDAKAN CEPAT"
        // (Tidak berubah, tetap ambil yang status 'di_admin_rektor')
        // ====================================================
        $suratBaru = Surat::where('status', 'di_admin_rektor')
                        ->latest('diterima_tanggal')
                        ->take(5)
                        ->get();


        // ====================================================
        // 3. DATA UNTUK PIE CHART (DIPERBARUI)
        // ====================================================
        
        // PERBAIKAN: Tambahkan status 'selesai' ke dalam query
        $pieChartData = Surat::whereIn('status', ['di_admin_rektor', 'didisposisi', 'selesai'])
                            ->select('tipe_surat', DB::raw('count(*) as jumlah'))
                            ->groupBy('tipe_surat')
                            ->get();

        $pieLabels = $pieChartData->pluck('tipe_surat')->map(function ($tipe) {
            return ucwords($tipe); 
        });
        $pieData = $pieChartData->pluck('jumlah');


        // ====================================================
        // 4. DATA UNTUK LINE CHART (DIPERBARUI)
        // ====================================================
        
        // PERBAIKAN: Tambahkan status 'selesai' ke dalam query
        $lineDataRaw = Surat::where('diterima_tanggal', '>=', Carbon::now()->subDays(6))
                            ->whereIn('status', ['di_admin_rektor', 'didisposisi', 'selesai']) // <-- Perubahan di sini
                            ->groupBy('tanggal')
                            ->orderBy('tanggal', 'asc')
                            ->get([
                                DB::raw('DATE(diterima_tanggal) as tanggal'),
                                DB::raw('COUNT(*) as jumlah')
                            ]);

        // Siapkan 7 hari terakhir sebagai label
        $lineLabels = [];
        $lineData = [];
        $tanggalDataMap = $lineDataRaw->pluck('jumlah', 'tanggal');

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->format('Y-m-d');
            $lineLabels[] = Carbon::now()->subDays($i)->isoFormat('DD MMM');
            $lineData[] = $tanggalDataMap[$tanggal] ?? 0; 
        }


        // ====================================================
        // 5. KIRIM SEMUA DATA KE VIEW
        // ====================================================
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