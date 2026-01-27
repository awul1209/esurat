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
// 1. DATA 6 KARTU KPI (DIPERBARUI)
// ====================================================================

// Card 1: Masuk (Untuk Rektor) - HARI INI
$untukRektorPending = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
    ->whereDate('diterima_tanggal', $today)
    ->count();

// Card 2: Surat Keluar Rektor - HARI INI (BARU)
$keluarRektorHariIni = SuratKeluar::whereHas('user', function($q) {
        $q->where('role', 'admin_rektor');
    })
    ->whereDate('created_at', $today)
    ->count();

// Card 3: Inbox BAU (Gabungan Eksternal & Internal) - HARI INI
$inboxEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
    ->whereDate('diterima_tanggal', $today)
    ->count();

$inboxInternal = SuratKeluar::where('tipe_kirim', 'internal')
    ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
        $q->where('satkers.id', $bauSatkerId);
    })
    ->whereDate('created_at', $today)
    ->count();

$inboxBau = $inboxEksternal + $inboxInternal;

// Card 4: Surat Keluar BAU - HARI INI (BARU)
$keluarBauHariIni = SuratKeluar::where('user_id', $user->id)
    ->whereDate('created_at', $today)
    ->count();

// Card 5: Sedang di Rektor (Menunggu Disposisi)
$sudahKeRektor = Surat::where('status', 'di_admin_rektor')->count();

// Card 6: Siap ke Satker (Hasil Disposisi yang balik ke BAU)
$siapKeSatker = Surat::where('status', 'didisposisi')->count();

       // --- DATA BARIS KEDUA: PENDING TASK (4 CARD) ---

// Card 1: Surat Masuk Rektor Perlu Ditangani (Status di_admin_rektor)
// 1. Surat Masuk Rektor yang perlu ditangani (Antrean Verifikasi dari Satker lain)
$rektorMasukPending = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
    ->where('status', 'baru_di_bau')
    ->where('user_id', '!=', $user->id) // Sesuai permintaan: Kecualikan surat yang dibuat oleh BAU sendiri
    ->count();

// Card 2: Surat Keluar Rektor Internal Perlu Ditangani (Misal status 'Draft' atau 'Proses')
$rektorKeluarInternalPending = SuratKeluar::whereHas('user', function($q) {
        $q->where('role', 'admin_rektor');
    })
    ->where('tipe_kirim', 'internal')
    ->whereIn('status', ['Draft', 'Proses','pending']) // Sesuaikan dengan status di sistem Anda
    ->count();

// Card 3: Surat Keluar Rektor Eksternal Perlu Ditangani
$rektorKeluarEksternalPending = SuratKeluar::whereHas('user', function($q) {
        $q->where('role', 'admin_rektor');
    })
    ->where('tipe_kirim', 'eksternal')
    ->whereIn('status', ['Draft', 'Proses','pending'])
    ->count();

// Card 4: Inbox BAU Perlu Ditangani (Status Baru/Belum Diarsipkan)
// 1. Hitung Pending dari Tabel Surat (Manual/Eksternal)
// Gunakan whereIn untuk mengecek banyak status sekaligus
$pendingManual = \App\Models\Surat::where('tujuan_satker_id', $bauSatkerId)
    ->whereIn('status', ['baru_di_bau', 'terkirim']) 
    ->count();

// 2. Hitung Pending dari Tabel SuratKeluar (Kiriman Sistem/Internal)
// Surat dianggap pending di BAU jika statusnya masih 'proses' atau 'terkirim' ke BAU
$pendingSistem = \App\Models\SuratKeluar::where('tipe_kirim', 'internal')
    ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
        $q->where('satker_id', $bauSatkerId);
    })
    ->whereIn('status', ['proses', 'terkirim']) // Sesuaikan dengan status di tabel surat_keluars Anda
    ->count();

// 3. Gabungkan Total Pending
$bauInboxPending = $pendingManual + $pendingSistem;




      // ====================================================================
// 2. DATA LINE CHART (TREN 7 HARI) - UPDATE: TAMBAH SURAT KELUAR
// ====================================================================

$lineLabels = [];
$dataRektorMasuk = []; 
$dataBauMasuk    = []; 
$dataRektorKeluar = []; // Array baru
$dataBauKeluar    = []; // Array baru

for ($i = 6; $i >= 0; $i--) {
    $date = Carbon::now()->subDays($i);
    $formattedDate = $date->format('Y-m-d');
    
    $lineLabels[] = $date->isoFormat('D MMM'); 

    // 1. Tren Rektor MASUK
    $dataRektorMasuk[] = Surat::whereIn('tujuan_tipe', ['rektor', 'universitas'])
        ->whereDate('diterima_tanggal', $formattedDate)
        ->count();

    // 2. Tren BAU MASUK (Eksternal + Internal Masuk)
    $dailyInEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
        ->whereDate('diterima_tanggal', $formattedDate)
        ->count();
    
    $dailyInInternal = SuratKeluar::where('tipe_kirim', 'internal')
        ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
            $q->where('satkers.id', $bauSatkerId);
        })
        ->whereDate('created_at', $formattedDate) 
        ->count();
    $dataBauMasuk[] = $dailyInEksternal + $dailyInInternal;

    // 3. Tren Rektor KELUAR (BARU)
    $dataRektorKeluar[] = SuratKeluar::whereHas('user', function($q) {
            $q->where('role', 'admin_rektor');
        })
        ->whereDate('created_at', $formattedDate)
        ->count();

    // 4. Tren BAU KELUAR (BARU)
    $dataBauKeluar[] = SuratKeluar::where('user_id', $user->id)
        ->whereDate('created_at', $formattedDate)
        ->count();
}


       // ====================================================================
// 3. DATA PIE CHART (KOMPOSISI SURAT BAU)
// ====================================================================

// 1. Surat Keluar Internal (Dibuat oleh BAU ke Satker lain)
$bauKeluarInternal = SuratKeluar::where('user_id', $user->id)
    ->where('tipe_kirim', 'internal')
    ->count();

// 2. Surat Keluar Eksternal (Dibuat oleh BAU ke pihak Luar)
$bauKeluarEksternal = SuratKeluar::where('user_id', $user->id)
    ->where('tipe_kirim', 'eksternal')
    ->count();

// 3. Surat Masuk Internal (Dari Satker lain ditujukan ke BAU)
$bauMasukInternal = SuratKeluar::where('tipe_kirim', 'internal')
    ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
        $q->where('satkers.id', $bauSatkerId);
    })
    ->count();

// 4. Surat Masuk Eksternal (Dari pihak luar langsung ke BAU)
$bauMasukEksternal = Surat::where('tujuan_satker_id', $bauSatkerId)
    ->where(function($q) {
        $q->where('tipe_surat', '!=', 'internal')
          ->orWhereNull('tipe_surat');
    })
    ->count();

// Kirim ke variabel komposisiData
$komposisiData = [$bauKeluarInternal, $bauKeluarEksternal, $bauMasukInternal, $bauMasukEksternal];


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
// ====================================================================
// 6. RETURN VIEW (PASTIKAN SEMUA VARIABEL MASUK & TIDAK ADA DUPLIKASI)
// ====================================================================
return view('bau.dashboard', compact(
    // Baris 1: 6 Kartu KPI
    'untukRektorPending', 
    'keluarRektorHariIni', 
    'inboxBau', 
    'keluarBauHariIni', 
    'sudahKeRektor', 
    'siapKeSatker',

    // Baris 2: 4 Kartu Pending Task (Tambahkan ini)
    'rektorMasukPending', 
    'rektorKeluarInternalPending', 
    'rektorKeluarEksternalPending', 
    'bauInboxPending',

    // Data Chart (Line & Bar)
    'lineLabels', 
    'dataRektorMasuk', 
    'dataRektorKeluar', 
    'dataBauMasuk', 
    'dataBauKeluar',
    'komposisiData',

    // Data Lainnya
    'suratPending', 
    'calendarEvents'
));
    }
}