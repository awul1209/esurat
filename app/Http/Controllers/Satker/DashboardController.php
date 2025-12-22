<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat; 
use App\Models\SuratKeluar; 
use App\Models\Satker;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;
        $today = Carbon::now()->startOfDay();

        // ====================================================================
        // 1. PENGAMBILAN DATA SURAT MASUK (ALL SOURCES)
        // ====================================================================

        // A. SURAT MASUK EKSTERNAL (Disposisi + Langsung + Manual)
        $suratEksternal = Surat::select('id', 'diterima_tanggal', 'tanggal_surat', 'perihal', 'nomor_surat', 'surat_dari', 'tipe_surat')
            ->where(function($q) use ($satkerId, $user) {
                $q->whereHas('disposisis', function ($sq) use ($satkerId) {
                    $sq->where('tujuan_satker_id', $satkerId);
                })
                ->orWhere('tujuan_satker_id', $satkerId)
                ->orWhere('user_id', $user->id);
            })
            ->where(function($q) {
                $q->where('tipe_surat', '!=', 'internal')
                  ->orWhereNull('tipe_surat');
            })
            ->get();

        // B. SURAT EDARAN
        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()
            ->select('surats.id', 'surats.diterima_tanggal', 'surats.tanggal_surat', 'surats.perihal', 'surats.nomor_surat', 'surats.surat_dari', 'surats.tipe_surat')
            ->get();

        // C. SURAT MASUK INTERNAL (OTOMATIS - Dari Satker Lain)
        // Note: Kita ambil 'created_at' juga sesuai request untuk perhitungan "Hari Ini"
        $suratMasukInternalOtomatis = SuratKeluar::with('user.satker')
            ->select('id', 'tanggal_surat', 'perihal', 'nomor_surat', 'user_id', 'created_at') 
            ->where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($satkerId) {
                $q->where('satkers.id', $satkerId);
            })->get();

        // D. SURAT MASUK INTERNAL (MANUAL)
        $suratMasukInternalManual = Surat::select('id', 'diterima_tanggal', 'tanggal_surat', 'perihal', 'nomor_surat', 'surat_dari', 'tipe_surat')
            ->where('tipe_surat', 'internal')
            ->where('tujuan_satker_id', $satkerId)
            ->get();


        // ====================================================================
        // 2. MERGE & FORMATTING
        // ====================================================================
        
        $allSuratMasuk = collect();

        // Helper Format Data
        $formatData = function($item, $tipeLabel) {
            $pengirim = '-';
            
            // Logika Pengirim
            if ($tipeLabel == 'internal' && $item instanceof SuratKeluar) {
                 $pengirim = $item->user->satker->nama_satker ?? 'Internal Satker';
                 
                 // KHUSUS INTERNAL OTOMATIS: Tanggal Terima diambil dari created_at (Sesuai Request)
                 $tglTerima = Carbon::parse($item->created_at);
            } else {
                 $pengirim = $item->surat_dari ?? 'Eksternal';
                 // KHUSUS LAINNYA: Ambil dari diterima_tanggal
                 $tglTerima = isset($item->diterima_tanggal) ? Carbon::parse($item->diterima_tanggal) : Carbon::parse($item->tanggal_surat);
            }

            return [
                'id'           => $item->id,
                'unique_key'   => $tipeLabel . '_' . $item->id,
                'perihal'      => $item->perihal,
                'nomor_surat'  => $item->nomor_surat,
                'pengirim'     => $pengirim,
                'tgl_kegiatan' => Carbon::parse($item->tanggal_surat), // Untuk Agenda/Kalender
                'tgl_terima'   => $tglTerima,                          // Untuk Statistik Hari Ini/Bulanan
                'tipe'         => ucfirst($tipeLabel)
            ];
        };

        // Merge Semua
        $allSuratMasuk = $allSuratMasuk->merge($suratEksternal->map(fn($i) => $formatData($i, 'eksternal')));
        $allSuratMasuk = $allSuratMasuk->merge($suratEdaran->map(fn($i) => $formatData($i, 'eksternal')));
        $allSuratMasuk = $allSuratMasuk->merge($suratMasukInternalOtomatis->map(fn($i) => $formatData($i, 'internal')));
        $allSuratMasuk = $allSuratMasuk->merge($suratMasukInternalManual->map(fn($i) => $formatData($i, 'internal')));

        // Hapus Duplikat
        $allSuratMasuk = $allSuratMasuk->unique('unique_key')->values();


        // ==========================================
        // 3. LOGIKA CARD STATISTIK (4 CARD)
        // ==========================================
        
        // Card 1: Surat Masuk HARI INI (Internal + Eksternal)
        $suratMasukHariIni = $allSuratMasuk->filter(function ($item) use ($today) {
            return $item['tgl_terima']->isSameDay($today);
        })->count();

        // Card 2: Surat Masuk 1 BULAN TERAKHIR (30 Hari Terakhir)
        // Alternatif: Gunakan isCurrentMonth() jika ingin per bulan kalender
        $satuBulanLalu = Carbon::now()->subDays(30);
        $suratSebulanTerakhir = $allSuratMasuk->filter(function ($item) use ($satuBulanLalu) {
            return $item['tgl_terima']->gte($satuBulanLalu);
        })->count();

        // Card 3: Surat Keluar INTERNAL (Total)
        $totalKeluarInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->where('user_id', $user->id)
            ->count();

        // Card 4: Surat Keluar EKSTERNAL (Total)
        // Asumsi: SuratKeluar punya tipe_kirim = 'eksternal' atau sejenisnya
        $totalKeluarEksternal = SuratKeluar::where('tipe_kirim', 'eksternal')
            ->where('user_id', $user->id)
            ->count();


        // ==========================================
        // 4. CHART DATA
        // ==========================================
        
        // PIE CHART (Masuk Internal vs Eksternal)
        $countEksternal = $allSuratMasuk->where('tipe', 'Eksternal')->count();
        $countInternal  = $allSuratMasuk->where('tipe', 'Internal')->count();
        $pieLabels = ['Eksternal', 'Internal'];
        $pieData   = [$countEksternal, $countInternal];

        // LINE CHART (7 Hari Terakhir - Berdasarkan Tgl Terima)
        $lineLabels = [];
        $lineData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            $lineLabels[] = $date->isoFormat('D MMM'); 
            $count = $allSuratMasuk->filter(fn($item) => $item['tgl_terima']->format('Y-m-d') === $formattedDate)->count();
            $lineData[] = $count;
        }

        // AGENDA & KALENDER (Berdasarkan Tgl Kegiatan/Surat)
        $agendaData = $allSuratMasuk->filter(fn($item) => $item['tgl_kegiatan']->gte($today))->sortBy('tgl_kegiatan');
        $agendaGrouped = $agendaData->groupBy(fn($item) => $item['tgl_kegiatan']->format('Y-m-d'))->take(7);
        $agendaLabels = [];
        $agendaValues = [];
        foreach ($agendaGrouped as $dateStr => $items) {
            $agendaLabels[] = Carbon::parse($dateStr)->isoFormat('D MMM');
            $agendaValues[] = count($items);
        }

        $calendarEvents = [];
        foreach ($allSuratMasuk as $surat) {
            $warna = $surat['tgl_kegiatan']->lt($today) ? '#858796' : '#4e73df';
            $calendarEvents[] = [
                'title'           => \Illuminate\Support\Str::limit($surat['perihal'], 20),
                'start'           => $surat['tgl_kegiatan']->format('Y-m-d'),
                'backgroundColor' => $warna,
                'borderColor'     => $warna,
                'extendedProps'   => [
                    'nomor_surat'  => $surat['nomor_surat'],
                    'perihal_full' => $surat['perihal'],
                    'pengirim'     => $surat['pengirim'],
                    'tipe'         => $surat['tipe']
                ],
                'allDay' => true
            ];
        }

        return view('satker.dashboard', compact(
            'suratMasukHariIni', 'suratSebulanTerakhir', 
            'totalKeluarInternal', 'totalKeluarEksternal',
            'pieLabels', 'pieData', 'lineLabels', 'lineData',
            'agendaLabels', 'agendaValues', 'calendarEvents'
        ));
    }
}