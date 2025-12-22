<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\SuratKeluar; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Jangan lupa import Auth
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = Auth::user(); // Ambil User yang sedang login (Admin Rektor)
        $today = Carbon::now()->startOfDay();

        // Status yang dianggap surat sudah "Tuntas" di tangan Rektor/Admin
        $statusSelesai = ['didisposisi', 'di_satker', 'selesai', 'arsip_satker', 'disimpan', 'diarsipkan', 'selesai_edaran'];
        
        // Scope Surat untuk Rektor
        $scopeRektor = ['rektor', 'universitas'];

        // ====================================================
        // 1. DATA 4 KARTU KPI
        // ====================================================

        // CARD 1: Surat Masuk Hari Ini (Semua Status)
        $suratMasukHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->whereDate('diterima_tanggal', $today)
            ->where('status', '!=', 'baru_di_bau') 
            ->count();

        // CARD 2: Perlu Disposisi Hari Ini
        $perluDisposisiHariIni = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->whereDate('diterima_tanggal', $today)
            ->where('status', 'di_admin_rektor') 
            ->count();

        // CARD 3: Sudah Disposisi / Ditangani (Total)
        $sudahDisposisi = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->whereIn('status', $statusSelesai)
            ->count();

        // CARD 4: Surat Keluar Hari Ini (PERBAIKAN: Filter User ID Rektor)
        // Hanya hitung surat yang user_id nya adalah SAYA (Admin Rektor)
        $suratKeluarHariIni = SuratKeluar::where('user_id', $user->id) 
            ->whereDate('created_at', $today)
            ->count();


        // ====================================================
        // 2. DATA CHARTS UTAMA
        // ====================================================

        // PIE CHART: Komposisi Internal vs Eksternal
        $pieChartData = Surat::whereIn('tujuan_tipe', $scopeRektor)
            ->where('status', '!=', 'baru_di_bau')
            ->select('tipe_surat', DB::raw('count(*) as jumlah'))
            ->groupBy('tipe_surat')
            ->get();

        $pieLabels = $pieChartData->pluck('tipe_surat')->map(fn($t) => ucfirst($t));
        $pieData   = $pieChartData->pluck('jumlah');

        // LINE CHART: Tren 7 Hari Terakhir
        $lineLabels = [];
        $lineData   = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            
            $lineLabels[] = $date->isoFormat('D MMM'); 
            
            $count = Surat::whereIn('tujuan_tipe', $scopeRektor)
                ->whereDate('diterima_tanggal', $formattedDate)
                ->where('status', '!=', 'baru_di_bau')
                ->count();
            
            $lineData[] = $count;
        }


        // ====================================================
        // 3. AGENDA & KALENDER (FILTER ARSIP REKTOR)
        // ====================================================
        
        // Ambil surat masuk yang sudah selesai/diarsipkan oleh Rektor
        $agendaQuery = Surat::with(['riwayats'])
            ->whereIn('tujuan_tipe', $scopeRektor)
            ->where(function($q) {
                $q->where('status', 'selesai')
                  ->orWhere('status', 'diarsipkan');
            })
            ->get();

        // A. DATA CHART AGENDA (Agenda Mendatang)
        $agendaChartData = $agendaQuery->filter(function($item) use ($today) {
            return Carbon::parse($item->tanggal_surat)->gte($today);
        })->sortBy('tanggal_surat');

        $agendaGrouped = $agendaChartData->groupBy(fn($item) => Carbon::parse($item->tanggal_surat)->format('Y-m-d'))->take(7);
        $agendaLabels = [];
        $agendaValues = [];
        foreach ($agendaGrouped as $dateStr => $items) {
            $agendaLabels[] = Carbon::parse($dateStr)->isoFormat('D MMM');
            $agendaValues[] = count($items);
        }

        // B. DATA KALENDER (FullCalendar)
        $calendarEvents = [];
        foreach ($agendaQuery as $surat) {
            $tglKegiatan = Carbon::parse($surat->tanggal_surat);
            $warna = $tglKegiatan->lt($today) ? '#858796' : '#1cc88a'; 

            $calendarEvents[] = [
                'title'           => \Illuminate\Support\Str::limit($surat->perihal, 20),
                'start'           => $tglKegiatan->format('Y-m-d'),
                'backgroundColor' => $warna,
                'borderColor'     => $warna,
                'extendedProps'   => [
                    'nomor_surat'  => $surat->nomor_surat,
                    'perihal_full' => $surat->perihal,
                    'pengirim'     => $surat->surat_dari,
                    'tipe'         => ucfirst($surat->tipe_surat)
                ],
                'allDay' => true
            ];
        }

        return view('admin_rektor.dashboard', compact(
            'suratMasukHariIni', 'perluDisposisiHariIni', 'sudahDisposisi', 'suratKeluarHariIni',
            'pieLabels', 'pieData', 'lineLabels', 'lineData',
            'agendaLabels', 'agendaValues', 'calendarEvents'
        ));
    }
}