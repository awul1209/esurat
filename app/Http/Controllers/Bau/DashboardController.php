<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
// use App\Models\Disposisi; // Model ini tidak lagi dipakai untuk chart
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str; // Tambahkan Str untuk limit text
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now()->startOfDay();

        // ====================================================================
        // 1. DATA 4 KARTU KPI (SESUAI ALUR KERJA BAU)
        // ====================================================================

        // Card 1: Surat Masuk untuk Rektor/Univ yang BELUM DITERUSKAN (Masih 'baru_di_bau')
        $untukRektorPending = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->where('status', 'baru_di_bau')
            ->count();

        // Card 2: Surat Masuk Khusus untuk BAU (Inbox)
        // Asumsi: Surat untuk BAU bisa berstatus 'baru_di_bau' atau 'di_satker' (jika sudah dibuka)
        $inboxBau = Surat::where('tujuan_tipe', 'bau')
            ->whereIn('status', ['baru_di_bau', 'di_satker']) 
            ->count();

        // Card 3: Sudah Diteruskan ke Rektor (Menunggu Disposisi)
        // Status surat sudah berubah menjadi 'di_admin_rektor'
        $sudahKeRektor = Surat::where('status', 'di_admin_rektor')->count();

        // Card 4: Sudah Didisposisi Rektor, Perlu Diteruskan ke Satker
        // Status 'didisposisi' artinya sudah balik ke BAU dan menunggu aksi lanjutan
        $siapKeSatker = Surat::where('status', 'didisposisi')->count();


        // ====================================================================
        // 2. DATA LINE CHART (TREN 7 HARI: REKTOR VS BAU)
        // ====================================================================
        
        $lineLabels = [];
        $dataRektor = []; // Garis Biru
        $dataBau    = []; // Garis Kuning

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            
            $lineLabels[] = $date->isoFormat('D MMM'); 

            // Hitung Surat Rektor per tanggal
            $countRektor = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
                ->whereDate('diterima_tanggal', $formattedDate)
                ->count();
            $dataRektor[] = $countRektor;

            // Hitung Surat BAU per tanggal
            $countBau = Surat::where('tujuan_tipe', 'bau')
                ->whereDate('diterima_tanggal', $formattedDate)
                ->count();
            $dataBau[] = $countBau;
        }


        // ====================================================================
        // 3. DATA BAR CHART (KOMPOSISI DETIL: REKTOR & BAU x INTERNAL/EKSTERNAL)
        // ====================================================================
        
        $rektorInternal = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->where('tipe_surat', 'internal')->count();
            
        $rektorEksternal = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->where('tipe_surat', '!=', 'internal')->count(); // Asumsi selain internal adalah eksternal

        $bauInternal = Surat::where('tujuan_tipe', 'bau')
            ->where('tipe_surat', 'internal')->count();

        $bauEksternal = Surat::where('tujuan_tipe', 'bau')
            ->where('tipe_surat', '!=', 'internal')->count();

        // Urutan Data: [Rektor Int, Rektor Eks, BAU Int, BAU Eks]
        $komposisiData = [$rektorInternal, $rektorEksternal, $bauInternal, $bauEksternal];


        // ====================================================================
        // 4. DATA TABEL AKSI CEPAT (SURAT PENDING DI BAU)
        // ====================================================================
        
        // Ambil surat yang menumpuk di BAU (Baru Masuk atau Balikan Disposisi)
        $suratPending = Surat::whereIn('status', ['baru_di_bau', 'didisposisi'])
            ->orderBy('status', 'desc') // didisposisi dulu (prioritas), baru baru_di_bau
            ->latest('diterima_tanggal')
            ->take(5)
            ->get();


        // ====================================================================
        // 5. DATA KALENDER (PENGGANTI DISTRIBUSI SATKER)
        // ====================================================================
        // Mengambil Inbox BAU (Internal/Eksternal) yang tanggal suratnya >= Hari Ini
        
        $agendaBau = Surat::where('tujuan_tipe', 'bau') // Kusus Inbox BAU
            ->whereDate('tanggal_surat', '>=', $today)  // Tanggal Surat >= Hari Ini
            ->get();

        $calendarEvents = [];
        foreach ($agendaBau as $surat) {
            // Warna Kuning/Oren khas BAU
            $warna = '#f6c23e'; 

            $calendarEvents[] = [
                'title'           => Str::limit($surat->perihal, 20),
                'start'           => Carbon::parse($surat->tanggal_surat)->format('Y-m-d'),
                'backgroundColor' => $warna,
                'borderColor'     => $warna,
                'textColor'       => '#000', // Teks hitam biar kontras dengan kuning
                'extendedProps'   => [
                    'nomor_surat'  => $surat->nomor_surat,
                    'perihal_full' => $surat->perihal,
                    'pengirim'     => $surat->surat_dari,
                    'tipe'         => ucfirst($surat->tipe_surat)
                ],
                'allDay' => true
            ];
        }


        // ====================================================================
        // 6. RETURN VIEW
        // ====================================================================
        return view('bau.dashboard', compact(
            'untukRektorPending', 'inboxBau', 'sudahKeRektor', 'siapKeSatker',
            'lineLabels', 'dataRektor', 'dataBau',
            'komposisiData',
            'suratPending',
            'calendarEvents' // <-- Variabel baru untuk Kalender
        ));
    }
}