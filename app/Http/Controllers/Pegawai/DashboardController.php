<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\Satker;
use App\Models\SuratKeluar;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;
        $satker = Satker::find($satkerId);

        // --- 1. PENGAMBILAN DATA DASAR ---

        // A. Surat Eksternal & Internal Personal (Dari Tabel surats)
$allInbox = Surat::where(function($query) use ($user) {
        // 1. Cek jika ditujukan langsung ke User ID
        $query->where('tujuan_user_id', $user->id)
              // 2. ATAU Cek jika ada di pivot delegasiPegawai
              ->orWhereHas('delegasiPegawai', function($q) use ($user) {
                  $q->where('user_id', $user->id);
              })
              // 3. ATAU Cek jika user terdaftar sebagai penerima di tabel riwayat_surats
              // Ini penting untuk menangkap delegasi/sebar dari input manual BAU
              ->orWhereHas('riwayats', function($q) use ($user) {
                  $q->where('penerima_id', $user->id);
              });
    })
    ->with(['riwayats' => function($q) use ($user) {
        // Ambil semua riwayat untuk surat tersebut, tapi prioritaskan riwayat untuk user ini
        $q->orderBy('created_at', 'desc');
    }])
    ->get();

        // B. Surat Internal Satker (Fallback/Backup jika ada data di surat_keluars)
        $allInternalArsip = SuratKeluar::whereHas('riwayats', function($q) use ($user) {
                $q->where('penerima_id', $user->id);
            })
            ->with(['riwayats' => function($q) use ($user) {
                $q->where('penerima_id', $user->id)->orderBy('created_at', 'desc');
            }, 'user.satker'])
            ->get();

        // C. Surat Edaran (Umum Murni dari Rektorat/BAU)
        $suratEdaran = $satker ? $satker->suratEdaran()
            ->wherePivot('status', 'diteruskan_internal')
            ->with(['riwayats.user'])
            ->get() : collect();

        // --- 2. LOGIKA PEMISAHAN PRIBADI VS UMUM ---
        $suratPribadi = collect();
        $suratUmum = collect();

        // Gabungkan Inbox dan Arsip Internal, lalu hilangkan duplikat berdasarkan nomor surat
        $combinedSurat = $allInbox->concat($allInternalArsip)->unique('nomor_surat');

        foreach ($combinedSurat as $s) {
            $riwayatLog = $s->riwayats->where('penerima_id', $user->id)->first();
            
            // Tentukan Kategori
            $kategori = 'Pribadi'; 
            if ($riwayatLog) {
                if (stripos($riwayatLog->status_aksi, 'Sebar') !== false || 
                    stripos($riwayatLog->status_aksi, 'Umum') !== false) {
                    $kategori = 'Umum';
                }
            }

            $s->kategori_view = $kategori;
            
            if ($kategori == 'Pribadi') {
                $suratPribadi->push($s);
            } else {
                $suratUmum->push($s);
            }
        }

        // Tambahkan Surat Edaran ke kategori Umum
        foreach ($suratEdaran as $se) {
            $se->kategori_view = 'Umum';
            $suratUmum->push($se);
        }

        $allSuratMasuk = $suratPribadi->merge($suratUmum);

        // --- 3. STATISTIK KARTU (KPI) ---
        $suratPribadiCount = $suratPribadi->count();
        $suratUmumCount    = $suratUmum->count();
        $totalSuratMasuk   = $allSuratMasuk->count();
        $totalSuratKeluar  = SuratKeluar::where('user_id', $user->id)->count();

        // --- 4. PROSES LOG AKTIVITAS (TIMELINE) ---
        $activityLogs = collect();

        foreach ($allSuratMasuk as $surat) {
            $history = [];
            $targetUrl = ($surat->kategori_view == 'Pribadi') 
                ? route('pegawai.surat.pribadi') 
                : route('pegawai.surat.umum');

            if ($surat->riwayats->isNotEmpty()) {
                $riwayats = $surat->riwayats->where('penerima_id', $user->id)->take(3);
                foreach ($riwayats as $log) {
                    $statusText = $log->status_aksi;
                    
                    // Penyesuaian teks berdasarkan fitur baru
                    if (stripos($log->status_aksi, 'Personal') !== false) {
                        $statusText = ($log->is_read == 2) ? 'Surat Diterima (Selesai)' : 'Surat Langsung Masuk (Menunggu)';
                    } elseif (stripos($log->status_aksi, 'Delegasi') !== false) {
                        $statusText = 'Menerima Delegasi Satker';
                    }

                    $history[] = [
                        'waktu'  => Carbon::parse($log->created_at)->isoFormat('D MMMM Y, HH:mm'),
                        'status' => $statusText,
                        'ket'    => $log->catatan ?? 'Silakan periksa detail surat',
                        'aktor'  => $log->user->name ?? 'Sistem'
                    ];
                }
            }

            $activityLogs->push([
                'id'        => $surat->id,
                'judul'     => $surat->perihal,
                'tipe'      => $surat->kategori_view,
                'url'       => $targetUrl,
                'color'     => $surat->kategori_view == 'Pribadi' ? 'primary' : 'info',
                'sort_date' => $surat->updated_at ?? $surat->created_at,
                'history'   => $history
            ]);
        }

        $activityLogs = $activityLogs->sortByDesc('sort_date')->take(8);

        // --- 5. DATA CALENDAR ---
        $events = [];
        foreach ($allSuratMasuk as $surat) {
            $events[] = [
                'id'      => $surat->id,
                'title'   => $surat->perihal,
                'start'   => Carbon::parse($surat->tanggal_surat)->format('Y-m-d'),
                'color'   => $surat->kategori_view == 'Pribadi' ? '#5e72e4' : '#11cdef',
                'extendedProps' => [
                    'nomor'      => $surat->nomor_surat,
                    'pengirim'   => $surat->surat_dari ?? ($surat->user->satker->nama_satker ?? 'Internal'),
                    'tipe'       => $surat->kategori_view,
                    'tgl_format' => Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM Y'),
                    'url_view'   => ($surat->kategori_view == 'Pribadi') ? route('pegawai.surat.pribadi') : route('pegawai.surat.umum')
                ]
            ];
        }

        return view('pegawai.dashboard', compact(
            'suratPribadiCount',
            'suratUmumCount',
            'totalSuratMasuk',
            'totalSuratKeluar',
            'activityLogs',
            'events'
        ));
    }
}