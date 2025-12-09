<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\Disposisi; // <-- TAMBAHKAN INI
use Illuminate\Support\Facades\DB; // <-- TAMBAHKAN INI
use Carbon\Carbon; // <-- TAMBAHKAN INI

class DashboardController extends Controller
{
    public function index()
    {
        // === 1. DATA UNTUK KARTU KPI (Key Performance Indicator) ===
        $totalSurat = Surat::count();
        $baruDiBau = Surat::where('status', 'baru_di_bau')->count();
        $diAdminRektor = Surat::where('status', 'di_admin_rektor')->count();
        $didisposisi = Surat::where('status', 'didisposisi')->count(); 
        // (Status 'selesai' kita anggap sebagai riwayat, tidak di KPI utama)

        
        // === 2. DATA UNTUK TABEL (Surat terbaru di daftar kerja BAU) ===
        // Ambil surat 'baru_di_bau' ATAU 'didisposisi' (sesuai alur terakhir kita)
        $suratTerbaru = Surat::with('disposisis.tujuanSatker')
                            ->whereIn('status', ['baru_di_bau', 'didisposisi'])
                            ->latest('diterima_tanggal')
                            ->take(5) // Ambil 5 saja untuk dashboard
                            ->get();
        
        // === 3. DATA UNTUK LINE CHART (Tren 7 Hari) ===
        $lineDataRaw = Surat::where('created_at', '>=', Carbon::now()->subDays(6))
                            ->groupBy('tanggal')
                            ->orderBy('tanggal', 'asc')
                            ->get([
                                DB::raw('DATE(created_at) as tanggal'),
                                DB::raw('COUNT(*) as jumlah')
                            ]);
        
        $lineLabels = [];
        $lineData = [];
        $tanggalDataMap = $lineDataRaw->pluck('jumlah', 'tanggal');
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->format('Y-m-d');
            $lineLabels[] = Carbon::now()->subDays($i)->isoFormat('DD MMM');
            $lineData[] = $tanggalDataMap[$tanggal] ?? 0;
        }

        // === 4. DATA UNTUK PIE CHART (Internal vs Eksternal) ===
        $pieChartData = Surat::select('tipe_surat', DB::raw('count(*) as jumlah'))
                            ->groupBy('tipe_surat')
                            ->get();

        $pieLabels = $pieChartData->pluck('tipe_surat')->map(function ($tipe) {
            return ucwords($tipe); // 'internal' -> 'Internal'
        });
        $pieData = $pieChartData->pluck('jumlah');

        // === 5. DATA UNTUK BAR CHART (Distribusi per Satker) ===
        // Ini adalah ide Anda: Menghitung jumlah surat yang telah diteruskan ke tiap Satker
        $barChartData = Disposisi::join('satkers', 'disposisis.tujuan_satker_id', '=', 'satkers.id')
                            ->select('satkers.nama_satker', DB::raw('COUNT(disposisis.id) as jumlah'))
                            ->groupBy('satkers.nama_satker')
                            ->orderBy('jumlah', 'desc') // Tampilkan dari terbanyak
                            ->take(10) // Ambil 10 Satker teratas
                            ->get();

        $barLabels = $barChartData->pluck('nama_satker');
        $barData = $barChartData->pluck('jumlah');

        
        // 6. Kirim semua data ke view 'bau.dashboard'
        return view('bau.dashboard', compact(
            'totalSurat', 
            'baruDiBau', 
            'diAdminRektor', 
            'didisposisi',
            'suratTerbaru',
            'lineLabels', 'lineData',
            'pieLabels', 'pieData',
            'barLabels', 'barData'
        ));
    }
}