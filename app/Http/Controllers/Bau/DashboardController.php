<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\SuratKeluar; // Pastikan Model SuratKeluar di-use
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now()->startOfDay();
        $startOfYear = Carbon::now()->startOfYear(); 
        
        $user = Auth::user();
        $bauSatkerId = $user->satker_id;

        // ====================================================================
        // 1. DATA 4 KARTU KPI
        // ====================================================================

        // Card 1: Masuk (Untuk Rektor) - HARI INI
        // Logika: Surat masuk (eksternal/internal) yang ditujukan ke Rektor/Univ, diterima HARI INI.
        $untukRektorPending = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->whereDate('diterima_tanggal', $today)
            ->count();

        // Card 2: Inbox BAU (Gabungan Eksternal & Internal)
        // A. Eksternal
        $inboxEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)->count();
        // B. Internal (Pakai SuratKeluar Relasi Pivot)
        $inboxInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            })
            ->count();

        $inboxBau = $inboxEksternal + $inboxInternal;

        // Card 3: Sedang di Rektor (Menunggu Disposisi)
        $sudahKeRektor = Surat::where('status', 'di_admin_rektor')->count();

        // Card 4: Siap ke Satker (Hasil Disposisi yang balik ke BAU)
        $siapKeSatker = Surat::where('status', 'didisposisi')->count();


       // ====================================================================
        // 2. DATA LINE CHART (TREN 7 HARI) - PERBAIKAN DI SINI
        // ====================================================================
        
        $lineLabels = [];
        $dataRektor = []; 
        $dataBau    = []; 

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            
            $lineLabels[] = $date->isoFormat('D MMM'); 

            // Tren Rektor (Eksternal)
            $countRektor = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
                ->whereDate('diterima_tanggal', $formattedDate)
                ->count();
            $dataRektor[] = $countRektor;

            // Tren BAU (Eksternal + Internal)
            // A. Eksternal (Berdasarkan diterima_tanggal)
            $dailyEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
                ->whereDate('diterima_tanggal', $formattedDate)
                ->count();
            
            // B. Internal (PERBAIKAN: Gunakan created_at)
            // Agar surat yang diinput hari ini (meski tanggal suratnya lampau) tetap muncul di grafik hari ini.
            $dailyInternal = SuratKeluar::where('tipe_kirim', 'internal')
                ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                    $q->where('satkers.id', $bauSatkerId);
                })
                ->whereDate('created_at', $formattedDate) 
                ->count();

            $dataBau[] = $dailyEksternal + $dailyInternal;
        }


        // ====================================================================
        // 3. DATA BAR CHART (KOMPOSISI DETIL)
        // ====================================================================
        
        // --- A. REKTOR (Ambil dari Logika Riwayat Terusan) ---
        // Filter Status: SUDAH DITERUSKAN (didisposisi, di_satker, arsip_satker, dll)
        $statusTerusan = ['didisposisi', 'di_satker', 'arsip_satker', 'diarsipkan', 'selesai_edaran'];
        
        $rektorInternal = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->whereIn('status', $statusTerusan)
            ->where('tipe_surat', 'internal')
            ->count();
            
        $rektorEksternal = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
            ->whereIn('status', $statusTerusan)
            ->where('tipe_surat', '!=', 'internal')
            ->count();

        // --- B. BAU (Ambil dari Logika Inbox BAU) ---
        
        // BAU Internal (Dari SuratKeluar Pivot)
        $bauInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            })
            ->count();

        // BAU Eksternal (Dari Tabel Surat - Manual/Eksternal)
        $bauEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
            ->where(function($q) {
                $q->where('tipe_surat', '!=', 'internal') // Pastikan bukan internal
                  ->orWhereNull('tipe_surat');
            })
            ->count();

        $komposisiData = [$rektorInternal, $rektorEksternal, $bauInternal, $bauEksternal];


      // ====================================================================
        // 4. DATA TABEL AKSI CEPAT (SURAT PENDING DI BAU)
        // ====================================================================
        
        $suratPending = Surat::where(function($query) use ($bauSatkerId) {
            
            // KONDISI 1: Balikan dari Rektor (Status 'didisposisi')
            // Tugas BAU: Meneruskan ke Satker tujuan sesuai disposisi
            $query->where('status', 'didisposisi')

            // KONDISI 2: Surat Baru Masuk untuk Rektor/Universitas (Internal/Eksternal)
            // Tugas BAU: Verifikasi dan Teruskan ke Admin Rektor
            ->orWhere(function($sub) {
                $sub->where('status', 'baru_di_bau')
                    ->whereIn('tujuan_tipe', ['rektor', 'universitas']);
            })

            // KONDISI 3: Surat Baru Masuk khusus untuk BAU sendiri
            // Tugas BAU: Memproses surat untuk dirinya sendiri
            ->orWhere(function($sub) use ($bauSatkerId) {
                $sub->where('status', 'baru_di_bau')
                    ->where('tujuan_satker_id', $bauSatkerId);
            });

        })
        // Urutkan: Prioritaskan yang 'didisposisi' (Paling Urgent) atau berdasarkan tanggal terbaru
        ->orderByRaw("FIELD(status, 'didisposisi', 'baru_di_bau')") 
        ->latest('diterima_tanggal')
        ->take(5)
        ->get();


        // ====================================================================
        // 5. DATA KALENDER (AGENDA - >= HARI INI)
        // ====================================================================
        
        $calendarEvents = [];

        // A. Agenda Eksternal (Inbox BAU)
        $agendaEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
            ->whereDate('tanggal_surat', '>=', $today) // >= HARI INI
            ->get();

        foreach ($agendaEksternal as $surat) {
            $warna = '#f6c23e'; // Kuning
            $calendarEvents[] = [
                'title'           => Str::limit($surat->perihal, 20),
                'start'           => Carbon::parse($surat->tanggal_surat)->format('Y-m-d'),
                'backgroundColor' => $warna,
                'borderColor'     => $warna,
                'textColor'       => '#000',
                'extendedProps'   => [
                    'nomor_surat'  => $surat->nomor_surat,
                    'perihal_full' => $surat->perihal,
                    'pengirim'     => $surat->surat_dari,
                    'tipe'         => ucfirst($surat->tipe_surat ?? 'Eksternal')
                ],
                'allDay' => true
            ];
        }

        // B. Agenda Internal (Dari SuratKeluar Pivot)
        $agendaInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->with(['user.satker']) // Load Relasi untuk nama pengirim
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            })
            ->whereDate('tanggal_surat', '>=', $today) // >= HARI INI
            ->get();

        foreach ($agendaInternal as $sk) {
            $warna = '#13e195ff'; // Hijau
            $namaPengirim = $sk->user->satker->nama_satker ?? 'Satker Lain';

            $calendarEvents[] = [
                'title'           => Str::limit($sk->perihal, 20),
                'start'           => Carbon::parse($sk->tanggal_surat)->format('Y-m-d'),
                'backgroundColor' => $warna,
                'borderColor'     => $warna,
                'textColor'       => '#fff',
                'extendedProps'   => [
                    'nomor_surat'  => $sk->nomor_surat,
                    'perihal_full' => $sk->perihal,
                    'pengirim'     => $namaPengirim,
                    'tipe'         => 'Internal'
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
            'calendarEvents'
        ));
    }
}