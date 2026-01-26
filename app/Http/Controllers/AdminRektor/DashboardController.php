<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\SuratKeluar; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = Auth::user(); 
        $today = Carbon::now()->startOfDay();
        $scopeRektor = ['rektor', 'universitas'];
        $statusSelesai = ['didisposisi', 'di_satker', 'selesai', 'arsip_satker', 'disimpan', 'diarsipkan', 'selesai_edaran'];

        // ====================================================
        // 1. DATA 6 KARTU KPI (UPDATE)
        // ====================================================

        // CARD 1: Masuk Internal Hari Ini
        $masukInternalHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->where('tipe_surat', 'internal')
            ->whereDate('diterima_tanggal', $today)
            ->where('status', '!=', 'baru_di_bau') 
            ->count();

        // CARD 2: Masuk Eksternal Hari Ini
        $masukEksternalHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->where('tipe_surat', '!=', 'internal')
            ->whereDate('diterima_tanggal', $today)
            ->where('status', '!=', 'baru_di_bau') 
            ->count();

        // CARD 3: Keluar Internal Hari Ini
        $keluarInternalHariIni = SuratKeluar::where('user_id', $user->id)
            ->where('tipe_kirim', 'internal')
            ->whereDate('created_at', $today)
            ->count();

        // CARD 4: Keluar Eksternal Hari Ini
        $keluarEksternalHariIni = SuratKeluar::where('user_id', $user->id)
            ->where('tipe_kirim', 'eksternal')
            ->whereDate('created_at', $today)
            ->count();

        // CARD 5: Perlu Ditangani (Surat Masuk Hari Ini yang statusnya 'di_admin_rektor' / Belum diproses)
        $perluDitanganiHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->whereDate('diterima_tanggal', $today)
            ->where('status', 'di_admin_rektor') 
            ->count();

        // CARD 6: Sudah Ditangani Hari Ini (Surat Masuk Hari Ini yang sudah masuk status selesai/disposisi)
        $sudahDitanganiHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->whereDate('diterima_tanggal', $today)
            ->whereIn('status', $statusSelesai)
            ->count();

        // --- SISA CODE (Charts & Agenda) TETAP SAMA SEPERTI SEBELUMNYA ---
        // (Pastikan variabel $scopeRektor tetap tersedia untuk query chart di bawah)

        // line CHART
// A. Total Surat Masuk (Semua yang sudah di tangan Rektor/Admin)
$totalMasukInternal = Surat::whereIn('tujuan_tipe', $scopeRektor)
    ->where('tipe_surat', 'internal')
    ->where('status', '!=', 'baru_di_bau')
    ->count();

$totalMasukEksternal = Surat::whereIn('tujuan_tipe', $scopeRektor)
    ->where('tipe_surat', '!=', 'internal')
    ->where('status', '!=', 'baru_di_bau')
    ->count();

// B. Total Surat Keluar (Dibuat oleh Admin Rektor)
$totalKeluarInternal = SuratKeluar::where('user_id', $user->id)
    ->where('tipe_kirim', 'internal')
    ->count();

$totalKeluarEksternal = SuratKeluar::where('user_id', $user->id)
    ->where('tipe_kirim', 'eksternal')
    ->count();

// Susun Label dan Data untuk JavaScript
$pieLabels = ['Masuk Internal', 'Masuk Eksternal', 'Keluar Internal', 'Keluar Eksternal'];
$pieData   = [$totalMasukInternal, $totalMasukEksternal, $totalKeluarInternal, $totalKeluarEksternal];

        // LINE CHART
      $lineLabels = [];
$dataMasukInternal = [];
$dataMasukEksternal = [];
$dataKeluarInternal = [];
$dataKeluarEksternal = [];

for ($i = 6; $i >= 0; $i--) {
    $date = Carbon::now()->subDays($i);
    $formattedDate = $date->format('Y-m-d');
    $lineLabels[] = $date->isoFormat('D MMM'); 

    // Masuk Internal
    $dataMasukInternal[] = Surat::whereIn('tujuan_tipe', $scopeRektor)
        ->where('tipe_surat', 'internal')
        ->whereDate('diterima_tanggal', $formattedDate)
        ->where('status', '!=', 'baru_di_bau')->count();

    // Masuk Eksternal
    $dataMasukEksternal[] = Surat::whereIn('tujuan_tipe', $scopeRektor)
        ->where('tipe_surat', '!=', 'internal')
        ->whereDate('diterima_tanggal', $formattedDate)
        ->where('status', '!=', 'baru_di_bau')->count();

    // Keluar Internal
    $dataKeluarInternal[] = SuratKeluar::where('user_id', $user->id)
        ->where('tipe_kirim', 'internal')
        ->whereDate('created_at', $formattedDate)->count();

    // Keluar Eksternal
    $dataKeluarEksternal[] = SuratKeluar::where('user_id', $user->id)
        ->where('tipe_kirim', 'eksternal')
        ->whereDate('created_at', $formattedDate)->count();
}

// Data Pie Chart (Tetap Gunakan Variabel yang ada di return)
$pieData = [$masukInternalHariIni, $masukEksternalHariIni, $keluarInternalHariIni, $keluarEksternalHariIni];
$pieLabels = ['Masuk Internal', 'Masuk Eksternal', 'Keluar Internal', 'Keluar Eksternal'];

        // AGENDA
       // Ambil surat yang diteruskan BAU ke Rektor dan statusnya masih 'di_admin_rektor'
       $status_kalender =['selesai'];
$aksiCepat = Surat::whereIn('tujuan_tipe', $scopeRektor)
    ->where('status', 'di_admin_rektor')
    ->latest() // Urutkan dari yang terbaru
    ->take(5)  // Batasi 5 surat saja agar tabel tidak terlalu panjang
    ->get();

$agendaQuery = Surat::whereIn('tujuan_tipe', $scopeRektor)
    ->whereIn('status', $status_kalender) // Mengambil surat yang sudah tuntas/diarsip
    ->get();

$calendarEvents = [];
foreach ($agendaQuery as $surat) {
    $tglKegiatan = Carbon::parse($surat->tanggal_surat);
    $warna = $tglKegiatan->lt($today) ? '#858796' : '#1cc88a'; 

    $calendarEvents[] = [
        'title' => \Illuminate\Support\Str::limit($surat->perihal, 20),
        'start' => $tglKegiatan->format('Y-m-d'),
        'backgroundColor' => $warna,
        'borderColor' => $warna,
        'extendedProps' => [
            'nomor_surat' => $surat->nomor_surat,
            'perihal_full' => $surat->perihal,
            'pengirim' => $surat->surat_dari,
            'tipe' => ucfirst($surat->tipe_surat)
        ],
        'allDay' => true
    ];
}

return view('admin_rektor.dashboard', compact(
    'masukInternalHariIni', 'masukEksternalHariIni', 'keluarInternalHariIni', 'keluarEksternalHariIni',
    'perluDitanganiHariIni', 'sudahDitanganiHariIni',
    'lineLabels', 'dataMasukInternal', 'dataMasukEksternal', 'dataKeluarInternal', 'dataKeluarEksternal',
    'pieLabels', 'pieData', 'calendarEvents','aksiCepat',
));
    }
}