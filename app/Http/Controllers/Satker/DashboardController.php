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

// A. SURAT MASUK EKSTERNAL (Hanya yang sudah diteruskan BAU ke Satker)
$suratEksternal = Surat::with(['riwayats'])->select('id', 'diterima_tanggal', 'tanggal_surat', 'perihal', 'nomor_surat', 'surat_dari', 'tipe_surat', 'status')
    ->where(function($q) use ($satkerId, $user) {
        // Filter: Hanya tampil jika Satker adalah tujuan disposisi
        $q->whereHas('disposisis', function ($sq) use ($satkerId) {
            $sq->where('tujuan_satker_id', $satkerId);
        })
        // Atau jika tujuan surat memang langsung ke Satker tersebut
        ->orWhere('tujuan_satker_id', $satkerId)
        // Atau surat yang diinput oleh user Satker itu sendiri
        ->orWhere('user_id', $user->id);
    })
    ->where(function($q) {
        $q->where('tipe_surat', '!=', 'internal')
          ->orWhereNull('tipe_surat');
    })
    // PERBAIKAN BUG:
    // Kecualikan status 'didisposisi' karena itu posisi surat masih di BAU (belum diteruskan)
    // Gunakan status yang menandakan surat sudah resmi di tangan Satker
    ->whereIn('status', ['di_satker', 'arsip_satker', 'diarsipkan', 'selesai_edaran','selesai'])
    ->get();

        // B. SURAT EDARAN
        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()
            ->select('surats.id', 'surats.diterima_tanggal', 'surats.tanggal_surat', 'surats.perihal', 'surats.nomor_surat', 'surats.surat_dari', 'surats.tipe_surat')
            ->get();

      // C. SURAT MASUK INTERNAL (OTOMATIS - Dari Satker Lain atau Rektor)
$suratMasukInternalOtomatis = SuratKeluar::with('user.satker')
    // TAMBAHKAN 'tanggal_terusan' di sini
    ->select('id', 'tanggal_surat', 'perihal', 'nomor_surat', 'user_id', 'created_at', 'status', 'tanggal_terusan') 
    ->where('tipe_kirim', 'internal')
    ->whereHas('penerimaInternal', function($q) use ($satkerId) {
        $q->where('satkers.id', $satkerId);
    })
    ->where(function($q) {
        $q->where(function($sq) {
            $sq->whereHas('user', function($u) {
                $u->where('role', 'admin_rektor');
            })->where('status', 'selesai'); 
        })
        ->orWhereHas('user', function($u) {
            $u->where('role', '!=', 'admin_rektor');
        });
    })
    ->get();

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
$formatData = function($item, $tipeLabel) use ($user) {
    $pengirim = '-';
    $tglBAU = null; 
    $tglTerima = null;

    // Logika Pengirim & Tanggal Terima
    if ($tipeLabel == 'internal' && $item instanceof SuratKeluar) {
        $pengirim = $item->user->satker->nama_satker ?? 'Rektorat';
        
        if ($item->user && $item->user->role == 'admin_rektor') {
            $tglTerima = $item->tanggal_terusan ? Carbon::parse($item->tanggal_terusan) : Carbon::parse($item->created_at);
            $tglBAU = $item->tanggal_terusan ? Carbon::parse($item->tanggal_terusan)->isoFormat('D MMMM Y, HH.mm') : null;
        } else {
            $tglTerima = Carbon::parse($item->created_at);
        }
    } else {
        // --- PERBAIKAN UNTUK SURAT EKSTERNAL ---
        $pengirim = $item->surat_dari ?? 'Eksternal';
        
        // 1. Cek apakah ada riwayat surat diteruskan ke satker saya
        // Kita ambil riwayat terbaru di mana statusnya mengandung kata "Satker" (Diteruskan ke Satker)
        $riwayatTerusan = $item->riwayats
            ->where('status_aksi', 'like', '%Satker%')
            ->sortByDesc('created_at')
            ->first();

        if ($riwayatTerusan) {
            // Jika ada riwayat, tglTerima adalah waktu saat BAU/Rektor kirim ke Satker
            $tglTerima = Carbon::parse($riwayatTerusan->created_at);
            $tglBAU = $tglTerima->isoFormat('D MMMM Y, HH.mm'); 
        } else {
            // Fallback: Jika riwayat tidak ditemukan (misal input manual satker), gunakan diterima_tanggal
            $tglTerima = isset($item->diterima_tanggal) ? Carbon::parse($item->diterima_tanggal) : Carbon::parse($item->tanggal_surat);
        }
    }

    return [
        'id'           => $item->id,
        'unique_key'   => $tipeLabel . '_' . $item->id,
        'perihal'      => $item->perihal,
        'nomor_surat'  => $item->nomor_surat,
        'pengirim'     => $pengirim,
        'tgl_kegiatan' => Carbon::parse($item->tanggal_surat),
        'tgl_terima'   => $tglTerima, // Sekarang berisi jam real dari riwayat
        'tgl_bau'      => $tglBAU, 
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
        // 3. LOGIKA CARD STATISTIK (6 CARD BARU)
        // ==========================================

        // Batas Waktu (1 Bulan lalu)
        $satuBulanLalu = Carbon::now()->subDays(30);

        // 1. Semua Surat Masuk INTERNAL
        $totalMasukInternal = $allSuratMasuk->where('tipe', 'Internal')->count();

        // 2. Semua Surat Masuk EKSTERNAL
        $totalMasukEksternal = $allSuratMasuk->where('tipe', 'Eksternal')->count();

        // 3. Surat Masuk 1 BULAN TERAKHIR
        $totalMasukSebulan = $allSuratMasuk->filter(function ($item) use ($satuBulanLalu) {
            return $item['tgl_terima']->gte($satuBulanLalu);
        })->count();

        // 4. Semua Surat Keluar INTERNAL
        $totalKeluarInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->where('user_id', $user->id)
            ->count();

        // 5. Semua Surat Keluar EKSTERNAL
        $totalKeluarEksternal = SuratKeluar::where('tipe_kirim', 'eksternal')
            ->where('user_id', $user->id)
            ->count();

        // 6. Data Surat Keluar 1 BULAN TERAKHIR (Gabungan Internal & Eksternal)
        $totalKeluarSebulan = SuratKeluar::where('user_id', $user->id)
            ->whereDate('tanggal_surat', '>=', $satuBulanLalu)
            ->count();


        
      // ==========================================
        // 4. LOG ACTIVITY (FORMAT TIMELINE DETAIL)
        // ==========================================
        
       $activityLogs = collect();

// --- A. SURAT MASUK (5 Terakhir) ---
$suratMasukRecent = $allSuratMasuk->sortByDesc('tgl_terima')->take(10);

foreach ($suratMasukRecent as $sm) {
    $url = $sm['tipe'] == 'Internal' ? route('satker.surat-masuk.internal') : route('satker.surat-masuk.eksternal');
    
    $history = [];
    $realSurat = \App\Models\Surat::find($sm['id']);

    // Pastikan surat eksternal yang tampil di log BUKAN berstatus 'didisposisi'
if ($sm['tipe'] !== 'Eksternal') {
        $history[] = [
            'waktu' => \Carbon\Carbon::parse($sm['tgl_terima'])->isoFormat('D MMMM Y, HH.mm') . ' WIB',
            'status' => 'Surat Masuk (' . $sm['tipe'] . ')',
            'ket'    => 'Diterima dari: ' . $sm['pengirim'],
            'aktor'  => 'Sistem / Admin Pengirim'
        ];
    }

    // 2. LOGIKA PENGECEKAN STATUS (DIBEDAKAN)
    if ($sm['tipe'] == 'Internal') {
        $suratInternal = \App\Models\SuratKeluar::with(['penerimaInternal' => function($q) use ($user) {
            $q->where('satker_id', $user->satker_id);
        }, 'riwayats' => function($q) use ($user) {
            $q->where('user_id', $user->id)->where('status_aksi', 'like', '%Delegasi%')->latest();
        }])->find($sm['id']);

        if ($suratInternal && $suratInternal->penerimaInternal->isNotEmpty()) {
            $pivot = $suratInternal->penerimaInternal->first()->pivot;
            $statusRead = $pivot->is_read;
            $waktuUpdate = $pivot->updated_at ? \Carbon\Carbon::parse($pivot->updated_at) : now();

            // --- PERBAIKAN LOGIKA STATUS ---
            if ($statusRead == 3) {
                // Ambil data delegasi terakhir dari riwayat untuk detail aktor/keterangan
                $delegasi = $suratInternal->riwayats->first();
                $namaPegawai = $delegasi && $delegasi->penerima ? $delegasi->penerima->name : 'Pegawai';
                
                $history[] = [
                    'waktu'  => $waktuUpdate->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                    'status' => 'Didelegasikan',
                    'ket'    => 'Surat telah didelegasikan ke: ' . $namaPegawai,
                    'aktor'  => 'Admin Satker Anda'
                ];
            } elseif ($statusRead == 2) {
                $history[] = [
                    'waktu'  => $waktuUpdate->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                    'status' => 'Diarsipkan/Selesai',
                    'ket'    => 'Surat telah ditandai selesai/arsip oleh Anda.',
                    'aktor'  => 'Admin Satker Anda'
                ];
            } elseif ($statusRead == 1) {
                $history[] = [
                    'waktu'  => $waktuUpdate->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                    'status' => 'Sudah Dibaca',
                    'ket'    => 'Surat sudah dibuka/dibaca.',
                    'aktor'  => 'Admin Satker Anda'
                ];
            } else {
                $history[] = [
                    'waktu'  => 'Saat ini',
                    'status' => 'Menunggu Tindakan',
                    'ket'    => 'Belum ada aktivitas lanjutan',
                    'aktor'  => '-'
                ];
            }
        } else {
            $realSuratWithRiwayat = \App\Models\Surat::with(['riwayats.user'])->find($sm['id']);
            if($realSuratWithRiwayat) {
                $this->cekRiwayatManual($realSuratWithRiwayat, $history);
            }
        }
    } else {
        // KASUS 2: SURAT EKSTERNAL
        $realSuratWithRiwayat = \App\Models\Surat::with(['riwayats.user'])->find($sm['id']);
        if($realSuratWithRiwayat) {
            $this->cekRiwayatManual($realSuratWithRiwayat, $history);
        }
    }

    // Sortir history agar yang terbaru muncul di atas (index 0)
    usort($history, function($a, $b) {
        return strtotime($b['waktu']) - strtotime($a['waktu']);
    });

    $activityLogs->push([
        'id'        => $sm['id'],
        'kategori'  => 'Surat Masuk',
        'tipe'      => $sm['tipe'],
        'judul'     => $sm['perihal'],
        'url'       => $url,
        'color'     => $sm['tipe'] == 'Internal' ? 'primary' : 'success',
        'sort_date' => $sm['tgl_terima'],
        'tgl_bau'   => $sm['tgl_bau'] ?? null,
        'history'   => $history
    ]);
}
        // --- B. SURAT KELUAR (5 Terakhir) ---
        $suratKeluarRecent = SuratKeluar::with(['user', 'penerimaInternal'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        foreach ($suratKeluarRecent as $sk) {
            $url = $sk->tipe_kirim == 'internal' ? route('satker.surat-keluar.internal') : route('satker.surat-keluar.eksternal.index');
            
            $history = [];

            // 1. History Awal (Surat Dikirim)
            $history[] = [
                'waktu'  => $sk->created_at->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                'status' => 'Surat Keluar (' . ucfirst($sk->tipe_kirim) . ')',
                'ket'    => 'Surat dibuat dan dikirim',
                'aktor'  => $sk->user ? $sk->user->name : 'Anda'
            ];

           // 2. History Terakhir (LOGIKA BARU - CEK SURATS & PIVOT DETAIL)
            if ($sk->tipe_kirim == 'internal') {
                
                // --- A. CEK APAKAH SURAT SUDAH DITERUSKAN BAU KE TUJUAN AKHIR (REKTOR/UNIV) ---
                // Kita cek berdasarkan nomor surat di tabel 'surats'
                $cekDiSuratMasuk = \App\Models\Surat::with(['riwayats.user'])
                    ->where('nomor_surat', $sk->nomor_surat)
                    ->first();

                if ($cekDiSuratMasuk && $cekDiSuratMasuk->riwayats->isNotEmpty()) {
                    $lastLog = $cekDiSuratMasuk->riwayats->sortByDesc('created_at')->first();
                    
                    $history[] = [
                        'waktu'  => \Carbon\Carbon::parse($lastLog->created_at)->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                        'status' => $lastLog->status_aksi,
                        'ket'    => $lastLog->catatan ?? 'Surat sedang diproses di unit tujuan.',
                        'aktor'  => $lastLog->user ? $lastLog->user->name : 'Admin Tujuan'
                    ];
                } 
                else {
                    // --- B. JIKA BELUM DITERUSKAN, CEK INTERAKSI DI TABEL PIVOT (ANTAR SATKER) ---
                    $interaksiTerakhir = $sk->penerimaInternal
                        ->filter(function($penerima) {
                            return $penerima->pivot->is_read > 0;
                        })
                        ->sortByDesc('pivot.updated_at');

                    if ($interaksiTerakhir->isNotEmpty()) {
                        $latestItem = $interaksiTerakhir->first();
                        $waktuUpdate = \Carbon\Carbon::parse($latestItem->pivot->updated_at);
                        
                        if ($latestItem->pivot->is_read == 2) {
                            $listPengarsip = $sk->penerimaInternal->where('pivot.is_read', 2)->pluck('nama_satker')->toArray();
                            $namaAktor = count($listPengarsip) > 2 ? $listPengarsip[0] . ', ' . $listPengarsip[1] . ' & ' . (count($listPengarsip) - 2) . ' lainnya' : implode(', ', $listPengarsip);

                            $history[] = [
                                'waktu'  => $waktuUpdate->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                                'status' => 'Selesai / Diarsipkan',
                                'ket'    => 'Surat telah diterima dan diarsipkan',
                                'aktor'  => $namaAktor
                            ];
                        } 
                        elseif ($latestItem->pivot->is_read == 1) {
                            $listPembaca = $sk->penerimaInternal->where('pivot.is_read', '>=', 1)->pluck('nama_satker')->toArray();
                            $namaAktor = count($listPembaca) > 2 ? $listPembaca[0] . ', ' . $listPembaca[1] . ' & ' . (count($listPembaca) - 2) . ' lainnya' : implode(', ', $listPembaca);

                            $history[] = [
                                'waktu'  => $waktuUpdate->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                                'status' => 'Sedang Dibaca',
                                'ket'    => 'Surat sedang ditinjau oleh penerima',
                                'aktor'  => $namaAktor
                            ];
                        }
                    } 
                    else {
                        // --- C. BELUM ADA RESPON SAMA SEKALI (MENUNGGU BAU) ---
                        $listTujuan = $sk->penerimaInternal->pluck('nama_satker')->toArray();
                        
                        if (!empty($listTujuan)) {
                            $namaTujuan = implode(', ', $listTujuan);
                            if (count($listTujuan) > 2) {
                                $namaTujuan = $listTujuan[0] . ', ' . $listTujuan[1] . ' & ' . (count($listTujuan) - 2) . ' lainnya';
                            }
                        } else {
                            $namaTujuan = $sk->tujuan_surat ?? 'Rektorat / Universitas';
                        }

                        $history[] = [
                            'waktu'  => 'Menunggu',
                            'status' => 'Terkirim',
                            'ket'    => 'Menunggu respon atau verifikasi BAU',
                            'aktor'  => $namaTujuan
                        ];
                    }
                }

            } else {
                // Eksternal Manual (Tetap)
                $history[] = [
                    'waktu'  => 'Status',
                    'status' => 'Terkirim Eksternal',
                    'ket'    => 'Surat telah dikirim ke pihak luar',
                    'aktor'  => $sk->tujuan_surat ?? 'Pihak Luar'
                ];
            }

            $activityLogs->push([
                'id'        => $sk->id,
                'kategori'  => 'Surat Keluar',
                'tipe'      => ucfirst($sk->tipe_kirim),
                'judul'     => $sk->perihal,
                'url'       => $url,
                'color'     => $sk->tipe_kirim == 'internal' ? 'warning' : 'danger',
                'sort_date' => $sk->created_at,
                'history'   => $history
            ]);
        }

        // C. Gabung & Sortir
        $activityLogs = $activityLogs->sortByDesc('sort_date')->take(10);

        // ==========================================
        // 4. CHART DATA
        // ==========================================
        
        // ====================================================

        // DATA PIE CHART (KOMPOSISI DETAIL SATKER)
        // ====================================================

        // 1. Definisikan Label
        $pieLabels = ['Masuk Internal', 'Masuk Eksternal', 'Keluar Internal', 'Keluar Eksternal'];

        // 2. Susun Data (Gunakan variabel yang sudah Anda hitung untuk Card)
// Urutan: Masuk Int (0), Masuk Eks (1), Keluar Int (2), Keluar Eks (3)
$pieData = [
    $totalMasukInternal ?? 0,   // Biru
    $totalMasukEksternal ?? 0,  // Hijau
    $totalKeluarInternal ?? 0,  // Kuning
    $totalKeluarEksternal ?? 0  // Merah
];


// ====================================================
// DATA AGENDA CHART (DASHBOARD SATKER)
// ====================================================

$today = \Carbon\Carbon::now()->startOfDay();

// 1. Ambil data surat yang tanggalnya >= hari ini
$agendaMendatang = $allSuratMasuk->filter(function($item) use ($today) {
    return \Carbon\Carbon::parse($item['tgl_kegiatan'])->gte($today);
})->sortBy('tgl_kegiatan');

// 2. Kelompokkan berdasarkan tanggal (ambil 12 hari ke depan/data unik)
$agendaGrouped = $agendaMendatang->groupBy(function($item) {
    return \Carbon\Carbon::parse($item['tgl_kegiatan'])->format('Y-m-d');
})->take(12); // Menampilkan maksimal 12 bar data agar rapi

$agendaLabels = [];
$agendaInternalData = []; // Untuk Grafik Batang (Internal)
$agendaEksternalData = []; // Untuk Grafik Garis (Eksternal)

foreach ($agendaGrouped as $dateStr => $items) {
    $agendaLabels[] = \Carbon\Carbon::parse($dateStr)->isoFormat('DD MMM');
    
    // Hitung per tipe
    $agendaInternalData[] = $items->filter(fn($i) => $i['tipe'] == 'Internal')->count();
    $agendaEksternalData[] = $items->filter(fn($i) => $i['tipe'] == 'Eksternal')->count();
}



        // AGENDA & KALENDER (Berdasarkan Tgl Kegiatan/Surat)
// 1. Tentukan batas hari ini
$today = \Carbon\Carbon::now()->startOfDay();

// 2. Filter data: tanggal_surat >= hari ini
$agendaMendatang = $allSuratMasuk->filter(function($item) use ($today) {
    return $item['tgl_kegiatan']->gte($today);
})->sortBy('tgl_kegiatan');

// 3. Grouping berdasarkan tanggal untuk sumbu X
$agendaGrouped = $agendaMendatang->groupBy(fn($item) => $item['tgl_kegiatan']->format('Y-m-d'))->take(10);

$agendaLabels = [];
$agendaInternalData = []; 
$agendaEksternalData = []; 

foreach ($agendaGrouped as $dateStr => $items) {
    // Label Tanggal (Contoh: 18 Jan)
    $agendaLabels[] = \Carbon\Carbon::parse($dateStr)->isoFormat('DD MMM');
    
    // Hitung jumlah masing-masing tipe di tanggal tersebut
    $agendaInternalData[] = $items->where('tipe', 'Internal')->count();
    $agendaEksternalData[] = $items->where('tipe', 'Eksternal')->count();
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


        // 3. Pastikan dikirim melalui compact
        return view('satker.dashboard', compact(
            'totalMasukInternal', 'totalMasukEksternal', 'totalMasukSebulan',
            'totalKeluarInternal', 'totalKeluarEksternal', 'totalKeluarSebulan',
            'pieLabels', 'pieData', 
            'calendarEvents', 'activityLogs', 'agendaLabels', 'agendaInternalData', 'agendaEksternalData'
        ));
    }
private function cekRiwayatManual($realSurat, &$history) {
    if ($realSurat && $realSurat->riwayats->isNotEmpty()) {
        $semuaRiwayat = $realSurat->riwayats;
        
        // 1. Ambil Riwayat TERAKHIR dari PUSAT (BAU/Rektor)
        $logPusat = $semuaRiwayat->filter(function($r) {
            return in_array($r->user->role ?? '', ['bau', 'admin_rektor', 'admin']);
        })->sortByDesc('created_at')->first();

        if ($logPusat) {
            $history[] = [
                'waktu'  => \Carbon\Carbon::parse($logPusat->created_at)->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                'status' => $logPusat->status_aksi,
                'ket'    => $logPusat->catatan ?? 'Status diperbarui',
                'aktor'  => $logPusat->user ? $logPusat->user->name : 'Admin BAU'
            ];
        }

        // 2. Cari Riwayat TERAKHIR milik SATKER (Untuk menentukan apakah sudah selesai/delegasi)
        $logSatker = $semuaRiwayat->filter(function($r) {
            return ($r->user->role ?? '') == 'satker';
        })->sortByDesc('created_at')->first();

        if ($logSatker) {
            $aksiSatker = strtolower($logSatker->status_aksi);
            $statusTampil = "Selesai/Diarsipkan";
            $keterangan = "Surat telah ditandai selesai/arsip oleh Anda.";

            // Jika statusnya mengandung kata delegasi atau informasi umum (sebar)
            if (str_contains($aksiSatker, 'delegasi') || str_contains($aksiSatker, 'informasi umum')) {
                $statusTampil = "Sudah Didelegasikan/Disebar";
                $keterangan = "Surat telah diteruskan ke pegawai internal unit Anda.";
            }

            $history[] = [
                'waktu'  => \Carbon\Carbon::parse($logSatker->created_at)->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                'status' => $statusTampil,
                'ket'    => $keterangan,
                'aktor'  => 'Admin Satker Anda'
            ];
        } else {
            // 3. Jika BELUM ada riwayat dari Satker, munculkan "Perlu Tindakan"
            $history[] = [
                'waktu'  => 'Penting',
                'status' => 'Perlu Tindakan',
                'ket'    => 'Surat sudah diteruskan ke unit Anda, silakan baca dan tentukan delegasi atau arsipkan.',
                'aktor'  => 'Admin Satker (Anda)'
            ];
        }
    } else {
        $history[] = [
            'waktu'  => 'Saat ini',
            'status' => 'Menunggu Tindakan',
            'ket'    => 'Belum ada aktivitas lanjutan dari pusat/BAU',
            'aktor'  => '-'
        ];
    }
}
    
}