<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use App\Exports\SuratKeluarInternalExport; // Pastikan nanti file ini dibuat
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\SuratTemplate;

// use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use setasign\Fpdi\Fpdi;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Helpers\BarcodeHelper;


// =============================================

// Import Model
use App\Models\SuratKeluar;
use App\Models\Satker;
use App\Models\Surat; 
use App\Models\RiwayatSurat;
use App\Models\User;


// Import Service WA & Carbon (PENTING)
use App\Helpers\EmailHelper;
use App\Services\WaService;
use Carbon\Carbon; // <--- INI YANG MENYEBABKAN ERROR SEBELUMNYA

class SuratInternalController extends Controller
{
// kumpulan metod untuk surat masuk internal
public function indexMasuk(Request $request)
{
    $user = auth()->user();
    $mySatkerId = $user->satker_id;
    $myId = $user->id;
    
    // Cek apakah yang login adalah pimpinan (Wadek/Dekan)
    $isPimpinan = ($user->role == 'pimpinan');

   // 1. SUMBER A: Surat dari Satker Lain atau REKTOR
$queryReal = \App\Models\SuratKeluar::with(['user.satker', 'penerimaInternal', 'riwayats.penerima']) 
    ->where('tipe_kirim', 'internal')
    ->whereHas('penerimaInternal', function($q) use ($mySatkerId) {
        $q->where('satker_id', $mySatkerId);
    })
    ->where(function($q) use ($isPimpinan, $myId) {
        // Kondisi 1: Tujuan Utama
        $q->whereHas('surats', function($sq) use ($isPimpinan, $myId) {
            $sq->whereHas('penerima', function($u) use ($isPimpinan, $myId) {
                if ($isPimpinan) {
                    $u->where('id', $myId);
                } else {
                    $u->whereIn('role', ['admin_satker', 'pimpinan', 'bau', 'admin_rektor']); 
                }
            });
        });

        // PERBAIKAN UNTUK ID 670: Cek Riwayat (Disposisi/Delegasi)
        if ($isPimpinan) {
            $q->orWhereHas('riwayats', function($rq) use ($myId) {
                $rq->where('penerima_id', $myId)
                   ->where(function($sub) {
                       $sub->where('status_aksi', 'like', '%Disposisi%') // Menangkap "Disposisi: Hadiri"
                           ->orWhere('status_aksi', 'like', '%Delegasi%')
                           ->orWhere('status_aksi', 'like', '%Teruskan%');
                   });
            });
        }
    });

// 2. SUMBER B: Surat Inputan Manual
$queryManual = \App\Models\Surat::with(['user.satker', 'riwayats.penerima'])
    ->where('tipe_surat', 'internal')
    ->where('tujuan_satker_id', $mySatkerId)
    ->where(function($q) use ($isPimpinan, $myId) {
        if ($isPimpinan) {
            $q->where('tujuan_user_id', $myId)
            // PERBAIKAN UNTUK ID 670:
            ->orWhereHas('riwayats', function($rq) use ($myId) {
                $rq->where('penerima_id', $myId)
                   ->where(function($sub) {
                       $sub->where('status_aksi', 'like', '%Disposisi%')
                           ->orWhere('status_aksi', 'like', '%Delegasi%');
                   });
            });
        }
    });

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $queryReal->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        $queryManual->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }
    
    $dataReal = $queryReal->get();
    $dataManual = $queryManual->get();

    // Transform Data (tetap sama seperti sebelumnya)
    $dataReal->transform(function ($item) use ($mySatkerId) {
        $myPivot = $item->penerimaInternal->where('id', $mySatkerId)->first();
        $item->is_read_internal = $myPivot ? $myPivot->pivot->is_read : 0;
        $item->diterima_tanggal = $item->tanggal_terusan ?? $item->created_at;
        $item->is_manual = false;
        return $item;
    });

    $dataManual->transform(function ($item) {
        $item->is_manual = true;
        $item->diterima_tanggal = $item->diterima_tanggal ?? $item->created_at;
        return $item;
    });

    $suratMasuk = $dataReal->merge($dataManual)->sortByDesc('diterima_tanggal');

    // Data pendukung untuk Modal Disposisi/Delegasi
    $daftarSatker = \App\Models\Satker::where('id', '!=', $mySatkerId)->get();
    $pegawaiList = \App\Models\User::where('satker_id', $mySatkerId)
        ->where('id', '!=', $myId)
        ->whereIn('role', ['pegawai', 'dosen'])
        ->orderBy('name', 'asc')
        ->get();

    $wadekList = \App\Models\User::where('satker_id', $mySatkerId)
        ->whereIn('role', ['wadek', 'pimpinan']) 
        ->where('id', '!=', $myId)
        ->get();

    return view('satker.internal.surat_masuk_index', compact(
        'suratMasuk', 
        'daftarSatker', 
        'pegawaiList',
        'wadekList'
    ));
}


   // metod export surat masuk internal
   public function exportMasuk(Request $request)
{
    $mySatkerId = Auth::user()->satker_id;
    $fileName = 'Surat_Masuk_Internal_' . date('Y-m-d_H-i') . '.csv';

    // --- 1. SUMBER A: Surat dari Satker Lain (Tabel SuratKeluar) ---
    $queryReal = \App\Models\SuratKeluar::with(['user.satker'])
        ->where('tipe_kirim', 'internal')
        ->whereHas('penerimaInternal', function($q) use ($mySatkerId) {
            $q->where('satkers.id', $mySatkerId);
        });

    // --- 2. SUMBER B: Surat Inputan Manual (Tabel Surat) ---
    $queryManual = \App\Models\Surat::where('tipe_surat', 'internal')
        ->where('tujuan_satker_id', $mySatkerId);

    // --- 3. FILTER TANGGAL ---
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $queryReal->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        $queryManual->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    // Ambil Data & Gabungkan
    $dataReal = $queryReal->get();
    $dataManual = $queryManual->get();
    
    // Gabungkan, Urutkan, dan gunakan values() untuk me-reset index array agar terurut dari 0
    $data = $dataReal->merge($dataManual)->sortByDesc('tanggal_surat')->values();

    // --- 4. STREAM CSV ---
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');
        
        // Tambahkan BOM untuk UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Kolom Excel
        fputcsv($file, ['No', 'No Surat', 'Tanggal Surat', 'Perihal', 'Pengirim', 'Tipe', 'Link Surat']);

        // Variabel counter nomor urut
        $no = 1;

        // Isi Data
        foreach ($data as $row) {
            // Logika Penentuan Pengirim
            if ($row instanceof \App\Models\Surat) {
                $pengirim = $row->surat_dari; 
                $tipe     = "Input Manual";
            } else {
                $pengirim = $row->user && $row->user->satker ? $row->user->satker->nama_satker : 'Rektorat';
                $tipe     = "Kiriman Satker";
            }

            // Format Tanggal (Cek apakah instance Carbon, jika tidak parse manual)
            $tglSurat = ($row->tanggal_surat instanceof \Carbon\Carbon) 
                        ? $row->tanggal_surat->format('d-m-Y') 
                        : date('d-m-Y', strtotime($row->tanggal_surat));

            // Siapkan Link File
            $linkFile = $row->file_surat ? url('storage/' . $row->file_surat) : 'Tidak ada file';

            fputcsv($file, [
                $no++, // Gunakan increment agar pasti urut 1, 2, 3...
                $row->nomor_surat,
                $tglSurat,
                $row->perihal,
                $pengirim,
                $tipe,
                $linkFile
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    // metod delegasi surat masuk internal
public function delegate(Request $request)
{
    $request->validate([
        'id'          => 'required',
        'target_tipe' => 'required|in:pribadi,semua',
        // Klasifikasi hanya wajib jika target_tipe adalah pribadi
        'klasifikasi' => 'required_if:target_tipe,pribadi',
        'asal_tabel'  => 'required|in:surat,surat_keluar',
        'user_id'     => 'required_if:target_tipe,pribadi|array'
    ]);

    try {
        \DB::beginTransaction();

        $admin = Auth::user();
        $idSurat = $request->id;
        
        // Identifikasi Model
        $surat = ($request->asal_tabel == 'surat_keluar') 
            ? \App\Models\SuratKeluar::findOrFail($idSurat) 
            : \App\Models\Surat::findOrFail($idSurat);

        $penerimaNotifIds = [];

        // Tentukan nilai default jika menyebar ke semua pegawai
        $klasifikasi = ($request->target_tipe == 'semua') 
            ? 'Informasi Umum' 
            : $request->klasifikasi;
            
        $catatan = ($request->target_tipe == 'semua') 
            ? 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.' 
            : $request->catatan;

        if ($request->target_tipe == 'pribadi') {
            // LOGIKA PRIBADI (Disposisi Spesifik)
            $userIds = $request->input('user_id', []);
            foreach ($userIds as $userId) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'        => $request->asal_tabel == 'surat' ? $surat->id : null,
                    'surat_keluar_id' => $request->asal_tabel == 'surat_keluar' ? $surat->id : null,
                    'user_id'         => $admin->id,
                    'penerima_id'     => $userId,
                    'status_aksi'     => 'Disposisi: ' . $klasifikasi, 
                    'catatan'         => $catatan,
                ]);
                $penerimaNotifIds[] = $userId;
            }
        } else {
            // LOGIKA SEBAR SEMUA (Bukan Disposisi, tapi Penyebaran Informasi)
            $semuaPegawai = \App\Models\User::where('satker_id', $admin->satker_id)
                                ->where('role', 'pegawai')
                                ->get();

            foreach ($semuaPegawai as $pegawai) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'        => $request->asal_tabel == 'surat' ? $surat->id : null,
                    'surat_keluar_id' => $request->asal_tabel == 'surat_keluar' ? $surat->id : null,
                    'user_id'         => $admin->id,
                    'penerima_id'     => $pegawai->id,
                    'status_aksi'     => 'Informasi: ' . $klasifikasi,
                    'catatan'         => $catatan,
                ]);
                $penerimaNotifIds[] = $pegawai->id;
            }
        }

        // --- UPDATE STATUS DI SATKER ---
// --- UPDATE STATUS DI SATKER ---
if ($request->asal_tabel == 'surat_keluar') {
    /**
     * Sesuai instruksi: 
     * Saat didelegasikan/disebarkan, Admin Satker dianggap sudah memproses.
     * Maka is_read di tabel pivot surat_keluar_internal_penerima menjadi 2 (Selesai).
     */
    $surat->penerimaInternal()->updateExistingPivot($admin->satker_id, [
        'is_read' => 2 
    ]);
    
    // Opsional: Jika Anda ingin status global surat_keluars juga berubah 
    // hanya jika surat ini ditujukan khusus untuk satker tersebut (bukan univ).
    $surat->update(['status' => 'selesai']);

} else {
    // Untuk Surat dari Univ/Eksternal (tabel surats)
    $surat->update(['status' => 'selesai']);
}

        \DB::commit();

        // --- KIRIM EMAIL ---
        if (!empty($penerimaNotifIds)) {
            $details = [
                'subject'    => '['.strtoupper($request->target_tipe).']: ' . $surat->perihal,
                'greeting'   => 'Halo,',
                'body'       => "Anda menerima " . ($request->target_tipe == 'semua' ? "informasi" : "disposisi") . " surat baru dari {$admin->name}. \n\n" .
                                "No. Surat: {$surat->nomor_surat}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Instruksi: " . $klasifikasi,
                'actiontext' => 'Lihat Surat',
                'actionurl'  => route('login'),
                'file_url'   => $surat->file_surat ? asset('storage/' . $surat->file_surat) : null
            ];
            \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
        }

        return redirect()->back()->with('success', 'Surat berhasil ' . ($request->target_tipe == 'semua' ? 'disebarkan ke semua pegawai.' : 'didisposisikan.'));

    } catch (\Exception $e) {
        \DB::rollBack();
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}

// arsip surat masuk internal
public function arsipkan($id)
{
    try {
        // 1. Cari Suratnya
        $surat = \App\Models\SuratKeluar::findOrFail($id);
        
        // 2. Ambil ID Satker User yang sedang login
        $satkerSaya = Auth::user()->satker_id;

        // 3. Update HANYA data pivot milik Satker ini
        // updateExistingPivot(id_relasi, [data_baru])
        $surat->penerimaInternal()->updateExistingPivot($satkerSaya, [
            'is_read' => 2 // Kita set 2 sebagai kode "Diarsipkan"
        ]);
        $surat->update([
            'status' => 'selesai'
        ]);

        return redirect()->back()->with('success', 'Surat berhasil diarsipkan dari inbox Satker Anda.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal memproses: ' . $e->getMessage());
    }
}

// log surat masuk internal
public function getRiwayatMasuk($id)
{
    try {
        $listRiwayat = [];
        $nomor = '-';

        // 1. Coba cari di SuratKeluar (Surat kiriman satker lain)
        $suratKeluar = \App\Models\SuratKeluar::with(['user.satker', 'riwayats.penerima', 'riwayats.user'])->find($id);

        if ($suratKeluar) {
            $nomor = $suratKeluar->nomor_surat;
            
            $namaPengirim = 'Satker Pengirim';
            if ($suratKeluar->user && $suratKeluar->user->satker) {
                $namaPengirim = $suratKeluar->user->satker->nama_satker;
            }

            // --- A. Log Pengiriman ---
            $listRiwayat[] = [
                'status_aksi' => 'Surat Masuk',
                'catatan'     => 'Surat diterima dari ' . $namaPengirim,
                'created_at'  => $suratKeluar->created_at->toISOString(),
                'tanggal_f'   => $suratKeluar->created_at->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user'        => ['name' => 'Admin ' . $namaPengirim]
            ];

            // --- B. Log Aktivitas SAYA (Satker Penerima) ---
            $myPivot = $suratKeluar->penerimaInternal()
                                   ->where('satker_id', auth()->user()->satker_id)
                                   ->first();

            if ($myPivot) {
                $waktu = $myPivot->pivot->updated_at ?? $myPivot->pivot->created_at;
                $waktuISO = $waktu ? \Carbon\Carbon::parse($waktu)->toISOString() : null;
                $waktuFmt = $waktu ? \Carbon\Carbon::parse($waktu)->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-';

                // Log untuk Dibaca
                if ($myPivot->pivot->is_read >= 1) { 
                    $listRiwayat[] = [
                        'status_aksi' => 'Dibaca',
                        'catatan'     => 'Surat telah dibuka/dibaca oleh Admin Satker.',
                        'created_at'  => \Carbon\Carbon::parse($myPivot->pivot->created_at)->toISOString(), // Gunakan created_at pivot untuk log baca pertama
                        'tanggal_f'   => \Carbon\Carbon::parse($myPivot->pivot->created_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                        'user'        => ['name' => auth()->user()->name]
                    ];
                }

                // Log untuk Diarsipkan Manual
                if ($myPivot->pivot->is_read == 2) {
                    $listRiwayat[] = [
                        'status_aksi' => 'Diarsipkan',
                        'catatan'     => 'Surat telah diselesaikan/diarsipkan oleh Satker Anda.',
                        'created_at'  => $waktuISO,
                        'tanggal_f'   => $waktuFmt,
                        'user'        => ['name' => auth()->user()->name]
                    ];
                }
            }

            // --- C. Tambahan Log Delegasi ke Pegawai (Riwayat) ---
            $myRiwayats = $suratKeluar->riwayats->where('user_id', auth()->id());
            foreach ($myRiwayats as $riwayat) {
                if (stripos($riwayat->status_aksi, 'Delegasi') !== false) {
                    $namaPegawai = $riwayat->penerima->name ?? 'Pegawai';
                    $listRiwayat[] = [
                        'status_aksi' => 'Didelegasikan ke: ' . $namaPegawai,
                        'catatan'     => $riwayat->catatan,
                        'created_at'  => $riwayat->created_at->toISOString(),
                        'tanggal_f'   => $riwayat->created_at->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                        'user'        => ['name' => auth()->user()->name]
                    ];
                }
            }

        } else {
            // --- KASUS 2: SURAT INPUTAN MANUAL ---
            $suratManual = \App\Models\Surat::with('user')->find($id);
            if ($suratManual) {
                $nomor = $suratManual->nomor_surat;
                $listRiwayat[] = [
                    'status_aksi' => 'Input Manual',
                    'catatan'     => 'Surat dicatat secara manual ke dalam sistem.',
                    'created_at'  => $suratManual->created_at->toISOString(),
                    'tanggal_f'   => $suratManual->created_at->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                    'user'        => ['name' => $suratManual->user->name ?? 'Saya']
                ];
            } else {
                return response()->json(['error' => true, 'message' => 'Data surat tidak ditemukan'], 404);
            }
        }

        // Urutkan Timeline
        usort($listRiwayat, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        return response()->json([
            'status'      => 'success',
            'nomor_surat' => $nomor,
            'perihal'     => $suratKeluar ? $suratKeluar->perihal : ($suratManual->perihal ?? '-'),
            'riwayats'    => $listRiwayat
        ]);

    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => 'Server Error: ' . $e->getMessage()], 500);
    }
}


// METOD UNTUK MENGETAHUI KAPAN SATKER MEMBACA SURAT DARI REKTOR LANGSUNG
public function show($id)
{
    $surat = \App\Models\SuratKeluar::findOrFail($id);
    $satkerId = Auth::user()->satker_id;

    // Ambil data pivot untuk cek status saat ini
    $pivotData = $surat->penerimaInternal()->where('satker_id', $satkerId)->first();

    // Jika statusnya masih 0 (Baru), ubah jadi 1 (Dibaca)
    if ($pivotData && $pivotData->pivot->is_read == 0) {
        $surat->penerimaInternal()->updateExistingPivot($satkerId, [
            'is_read' => 1,
            'updated_at' => now() // Penting agar terekam di Log BAU
        ]);
    }

    return view('satker.surat_masuk.show', compact('surat'));
}


// MANUAL SURAT MASUK
// metod untuk simpan  SURAT MASUK INTERNAL manual
public function storeMasukManual(Request $request)
{
    // 1. Validasi Input sesuai alur baru
    $request->validate([
        'nomor_surat'      => 'required|string|max:255',
        'asal_satker_id'   => 'required|exists:satkers,id',
        'perihal'          => 'required|string',
        'tanggal_surat'    => 'required|date',
        'diterima_tanggal' => 'required|date',
        'file_surat'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'target_tipe'      => 'required|in:arsip,pribadi,semua',
        // Validasi delegasi jika memilih 'pribadi'
        'delegasi_user_ids'=> 'required_if:target_tipe,pribadi|array',
        'catatan_delegasi' => 'nullable|string',
    ]);

    $user = Auth::user();
    $path = $request->file('file_surat')->store('surat_masuk_internal_satker', 'public');

    try {
        DB::beginTransaction();

        // Ambil Nama Satker Pengirim
        $satkerPengirim = \App\Models\Satker::findOrFail($request->asal_satker_id);

        // A. Simpan Surat Utama
        $surat = \App\Models\Surat::create([
            'user_id'          => $user->id,
            'tipe_surat'       => 'internal',
            'nomor_surat'      => $request->nomor_surat,
            'surat_dari'       => $satkerPengirim->nama_satker,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'diterima_tanggal' => $request->diterima_tanggal,
            'file_surat'       => $path,
            'sifat'            => 'Asli',
            'no_agenda'        => 'MI-' . time(),
            'tujuan_tipe'      => 'satker',
            'tujuan_satker_id' => $user->satker_id,
            'status'           => 'arsip_satker', // Otomatis dianggap diproses oleh admin
        ]);

        $penerimaNotifIds = [];
        $statusAksiLog = '';

        // B. PROSES DISTRIBUSI & EMAIL
        if ($request->target_tipe == 'pribadi') {
            // --- DISPOSISI PRIBADI ---
            $statusAksiLog = 'Input & Disposisi Pribadi';
            $penerimaNotifIds = $request->delegasi_user_ids;

            foreach ($penerimaNotifIds as $pId) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $pId,
                    'status_aksi' => 'Disposisi: ' . $statusAksiLog,
                    'catatan'     => $request->catatan_delegasi ?? 'Surat didelegasikan kepada Anda.'
                ]);
            }
            // Attach ke pivot untuk sinkronisasi relasi
            $surat->delegasiPegawai()->attach($penerimaNotifIds, [
                'catatan' => $request->catatan_delegasi,
                'created_at' => now(), 'updated_at' => now()
            ]);

        } elseif ($request->target_tipe == 'semua') {
            // --- SEBAR KE SEMUA PEGAWAI SATKER ---
            $statusAksiLog = 'Input & Informasi Umum';
            $pegawaiSatker = \App\Models\User::where('satker_id', $user->satker_id)
                                ->where('id', '!=', $user->id)
                                ->get();

            foreach ($pegawaiSatker as $p) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $p->id,
                    'status_aksi' => 'Informasi Umum: ' . $statusAksiLog,
                    'catatan'     => $request->catatan_delegasi ?? 'Informasi untuk seluruh pegawai.'
                ]);
                $penerimaNotifIds[] = $p->id;
            }
            // Attach ke pivot
            $surat->delegasiPegawai()->attach($penerimaNotifIds, [
                'catatan' => $request->catatan_delegasi,
                'created_at' => now(), 'updated_at' => now()
            ]);

        } else {
            // --- HANYA ARSIP ---
            $statusAksiLog = 'Input Manual (Arsip)';
            \App\Models\RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $user->id,
                'status_aksi' => $statusAksiLog,
                'catatan'     => 'Surat internal diinput manual sebagai arsip.'
            ]);
        }

        DB::commit();

        // C. KIRIM EMAIL NOTIFIKASI
        if (!empty($penerimaNotifIds)) {
            $details = [
                'subject'    => '[DISPOSISI INTERNAL]: ' . $surat->perihal,
                'greeting'   => 'Halo,',
                'body'       => "Admin Satker baru saja mencatat surat masuk internal dan mendisposisikannya kepada Anda.\n\n" .
                                "No. Surat: {$surat->nomor_surat}\n" .
                                "Asal Satker: {$surat->surat_dari}\n" .
                                "Instruksi: " . ($request->catatan_delegasi ?? 'Segera cek sistem.'),
                'actiontext' => 'Lihat Surat',
                'actionurl'  => route('login'),
                'file_url'   => asset('storage/' . $surat->file_surat)
            ];

            // Panggil EmailHelper yang sudah ada
            \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
        }

        return redirect()->back()->with('success', 'Surat masuk internal berhasil dicatat dan diproses.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memproses surat: ' . $e->getMessage());
    }
}

// --- UPDATE SURAT MASUK MANUAL ---
public function updateMasukManual(Request $request, $id)
{
    // 1. Ambil data surat
    $surat = \App\Models\Surat::findOrFail($id);
    $user = Auth::user();

    // 2. Proteksi Ganda: Cek Kepemilikan Satker & Cek apakah sudah diproses (Delegasi/Sebar)
    $isAlreadyProcessed = $surat->riwayats->where('user_id', $user->id)->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'disposisi') || str_contains($aksi, 'informasi');
    })->isNotEmpty();

    if ($surat->tujuan_satker_id != $user->satker_id || $isAlreadyProcessed) {
        return redirect()->back()->with('error', 'Maaf, surat tidak dapat diedit karena sudah didisposisikan/disebar atau Anda tidak memiliki akses.');
    }

    // 3. Validasi Input
    $validator = Validator::make($request->all(), [
        'nomor_surat'    => 'required|string|max:255',
        'asal_satker_id' => 'required|exists:satkers,id',
        'perihal'        => 'required|string',
        'tanggal_surat'  => 'required|date',
        'file_surat'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Limit 10MB
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('error_code', 'edit')
            ->with('edit_id', $id);
    }

    DB::beginTransaction();
    try {
        // 4. Handle Pengirim (Satker)
        $satkerPengirim = \App\Models\Satker::findOrFail($request->asal_satker_id);

        // 5. Handle File Update
        if ($request->hasFile('file_surat')) {
            // Hapus file lama jika ada
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            // Simpan file baru di folder yang sama dengan store
            $surat->file_surat = $request->file('file_surat')->store('surat_masuk_internal_satker', 'public');
        }

        // 6. Update Data Utama
        $surat->nomor_surat   = $request->nomor_surat;
        $surat->surat_dari    = $satkerPengirim->nama_satker; 
        $surat->perihal       = $request->perihal;
        $surat->tanggal_surat = $request->tanggal_surat;
        
        $surat->save();

        // 7. Catat Riwayat Perubahan (Audit Trail)
        \App\Models\RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Update Data Manual',
            'catatan'     => 'Admin Satker memperbarui detail informasi surat manual internal.'
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Data surat manual berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
    }
}

// --- HAPUS SURAT MASUK MANUAL ---
public function destroyMasukManual($id)
{
    // 1. Ambil data surat
    $surat = \App\Models\Surat::findOrFail($id);
    $user = Auth::user();

    // 2. Proteksi Ganda: Cek Kepemilikan & Cek apakah sudah diproses (Delegasi/Sebar)
    $isAlreadyProcessed = $surat->riwayats->where('user_id', $user->id)->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'disposisi') || str_contains($aksi, 'informasi');
    })->isNotEmpty();

    if ($surat->tujuan_satker_id != $user->satker_id || $isAlreadyProcessed) {
        return redirect()->back()->with('error', 'Tidak bisa menghapus surat yang sudah didisposisikan/disebar atau bukan milik Anda.');
    }

    try {
        DB::beginTransaction();

        // 3. Eksekusi Soft Delete
        // JANGAN hapus file surat dan JANGAN detach delegasi agar bisa di-restore nantinya
        $surat->delete();

        // 4. Catat Riwayat Penghapusan
        \App\Models\RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Hapus ke Tempat Sampah',
            'catatan'     => 'Admin Satker memindahkan surat manual internal ke tempat sampah.'
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memindahkan ke tempat sampah: ' . $e->getMessage());
    }
}





















// METOD UNTUK SURAT KELUAR INTERNAL
// kumpulan metod untuk surat keluar internal
public function indexKeluar(Request $request)
{
    $user = Auth::user();
    $satkerId = $user->satker_id;

    // Tambahkan riwayats.penerima agar nama pegawai langsung ter-load
    $query = SuratKeluar::with([
            'penerimaInternal', 
            'riwayats.penerima' // Load data pegawai penerima langsung
        ]) 
        ->where('tipe_kirim', 'internal')
        ->whereHas('user', function($q) use ($satkerId) {
            $q->where('satker_id', $satkerId);
        });

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [
            $request->start_date, 
            $request->end_date
        ]);
    }

    // Gunakan latest() untuk mengurutkan berdasarkan input terbaru
    $suratKeluar = $query->latest()->get();

    return view('satker.internal.surat_keluar_index', compact('suratKeluar'));
}

// metod export surat keluar internal
 public function exportKeluar(Request $request)
{
    $startDate = $request->start_date;
    $endDate   = $request->end_date;
    $userId    = Auth::id();

    $export = new class($startDate, $endDate, $userId) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
        
        protected $startDate;
        protected $endDate;
        protected $userId;

        public function __construct($startDate, $endDate, $userId)
        {
            $this->startDate = $startDate;
            $this->endDate   = $endDate;
            $this->userId    = $userId;
        }

        public function collection()
        {
            // Ambil riwayat untuk cek tujuan langsung (direct)
            $query = SuratKeluar::with(['penerimaInternal', 'riwayats.penerima'])
                ->where('tipe_kirim', 'internal')
                ->where('user_id', $this->userId);

            if ($this->startDate && $this->endDate) {
                $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
            }

            return $query->latest('tanggal_surat')->get();
        }

        public function headings(): array
        {
            return ['No', 'No Surat', 'Perihal', 'Tanggal Kirim', 'Tujuan', 'Link Surat'];
        }

        public function map($surat): array
        {
            $tujuan = '-';

            // LOGIKA: Ambil riwayat yang dibuat oleh pembuat surat ini (Direct Send)
            // Bukan hasil delegasi orang lain.
            $directRecipient = $surat->riwayats
                ->where('user_id', $this->userId) // Harus dibuat oleh user yang sama dengan pembuat surat
                ->whereNotNull('penerima_id');

            if ($directRecipient->isNotEmpty()) {
                // Jika ditemukan pengiriman langsung ke pegawai saat surat dibuat
                $tujuan = $directRecipient->map(function($r) {
                    return $r->penerima->name ?? '-';
                })->unique()->join(', ');
            } elseif ($surat->penerimaInternal->count() > 0) {
                // Jika tidak ke pegawai, cek apakah ke Satker
                $tujuan = $surat->penerimaInternal->pluck('nama_satker')->join(', ');
            } else {
                // Fallback ke kolom teks manual
                $tujuan = $surat->tujuan_surat ?? '-';
            }

            $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

            static $no = 0;
            $no++;

            return [
                $no,
                $surat->nomor_surat,
                $surat->perihal,
                \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'), 
                $tujuan,
                $linkFile,
            ];
        }

        public function styles(Worksheet $sheet)
        {
            return [1 => ['font' => ['bold' => true]]];
        }
    };

    $namaFile = 'Laporan_Surat_Keluar_Internal_' . date('d-m-Y_H-i') . '.xlsx';
    return Excel::download($export, $namaFile);
}

// log surat keluar internal
public function getRiwayatStatus($id)
{
    try {
        $suratKeluar = \App\Models\SuratKeluar::with([
            'penerimaInternal', 
            'user.satker', 
            'riwayats.penerima', 
            'riwayats.user',
            'validasis.pimpinan'
        ])->findOrFail($id);
        
        $listRiwayat = [];
        $formatDate = function($date) {
            return $date ? $date->toISOString() : null;
        };

        $pimpinanIds = $suratKeluar->validasis->pluck('pimpinan_id')->toArray();

        // --- 1. LOG UTAMA: SURAT DIBUAT ---
        $listRiwayat[] = [
            'status_aksi' => 'Surat Dibuat',
            'catatan'     => 'Surat resmi telah dibuat dan masuk antrean sistem.',
            'created_at'  => $formatDate($suratKeluar->created_at),
            'user'        => ['name' => $suratKeluar->user->name ?? 'Pengirim']
        ];

        // --- 2. LOG VALIDASI (MENGETAHUI) ---
        foreach ($suratKeluar->validasis as $v) {
            if ($v->status !== 'pending') {
                $listRiwayat[] = [
                    'status_aksi' => ($v->status === 'setuju') ? 'Disetujui (Mengetahui)' : 'Minta Revisi',
                    'catatan'     => $v->catatan ?? (($v->status === 'setuju') ? 'Dokumen telah divalidasi dan disetujui.' : 'Pimpinan meminta perbaikan dokumen.'),
                    'created_at'  => $formatDate($v->updated_at),
                    'user'        => ['name' => $v->pimpinan->name ?? 'Pimpinan']
                ];
            }
        }

        // Identifikasi apakah ada pegawai yang sudah menerima (is_read == 2)
        $riwayatsPegawaiDirect = $suratKeluar->riwayats->whereNotNull('penerima_id')
            ->filter(function($r) use ($pimpinanIds) {
                return !in_array($r->penerima_id, $pimpinanIds);
            });
            
        $isPegawaiSudahTerima = $riwayatsPegawaiDirect->where('is_read', 2)->isNotEmpty();

        // --- 3. JALUR DISTRIBUSI ---
        if ($suratKeluar->status === 'Terkirim' || $suratKeluar->is_final == 1) {
            
            if ($isPegawaiSudahTerima) {
                // JIKA PEGAWAI SUDAH TERIMA: Hanya tampilkan log penerimaan pegawai
                foreach ($riwayatsPegawaiDirect as $log) {
                    if ($log->is_read == 2) {
                        $listRiwayat[] = [
                            'status_aksi' => 'Diterima oleh: ' . ($log->penerima->name ?? 'Pegawai'),
                            'catatan'     => 'Surat telah diterima dan diverifikasi oleh pegawai.',
                            'created_at'  => $formatDate($log->updated_at),
                            'user'        => ['name' => $log->penerima->name ?? 'Pegawai']
                        ];
                    }
                }
            } else {
                // JIKA BELUM ADA PEGAWAI TERIMA: Baru cek jalur Satker/BAU
                foreach ($suratKeluar->penerimaInternal as $penerima) {
                    $pivot = $penerima->pivot;
                    if ($pivot->is_read == 2) {
                        $listRiwayat[] = [
                            'status_aksi' => 'Selesai di ' . $penerima->nama_satker,
                            'catatan'     => 'Surat telah selesai ditindaklanjuti oleh unit.',
                            'created_at'  => $formatDate($pivot->updated_at),
                            'user'        => ['name' => 'Admin ' . $penerima->nama_satker]
                        ];
                    }
                }

                // Logika Rektorat/BAU
                $tujuanTeks = strtolower($suratKeluar->tujuan_surat);
                if (str_contains($tujuanTeks, 'rektor') || str_contains($tujuanTeks, 'univ') || str_contains($tujuanTeks, 'bau')) {
                    $suratPusat = \App\Models\Surat::where('nomor_surat', $suratKeluar->nomor_surat)->with('riwayats.user')->first();
                    if ($suratPusat) {
                        foreach($suratPusat->riwayats as $log) {
                            if (stripos($log->status_aksi, 'Digital') !== false || stripos($log->status_aksi, 'Baru') !== false) continue;
                            $listRiwayat[] = [
                                'status_aksi' => 'BAU: ' . $log->status_aksi,
                                'catatan'     => $log->catatan,
                                'created_at'  => $formatDate($log->created_at),
                                'user'        => ['name' => $log->user ? $log->user->name : 'Sistem BAU']
                            ];
                        }
                    }
                }
            }
        }

        $sorted = collect($listRiwayat)
            ->whereNotNull('created_at')
            ->sortBy('created_at')
            ->values()
            ->all();

        return response()->json([
            'nomor_surat' => $suratKeluar->nomor_surat,
            'status_saat_ini' => $suratKeluar->status,
            'riwayats'    => $sorted
        ]);

    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
}


// metod create untuk surat keluar internal 
public function create()
{
    // 1. Ambil Satker user yang sedang login
    $mySatkerId = Auth::user()->satker_id;

    // 2. Ambil data Satker untuk tujuan internal (untuk diproses AJAX di view)
    $daftarSatker = \App\Models\Satker::orderBy('nama_satker')->get();

    // 3. Ambil pimpinan untuk pilihan "Mengetahui / Validasi"
    $pimpinans = \App\Models\User::with('jabatan')
        ->where('role', 'pimpinan')
        ->get();

    // 4. Ambil SEMUA Satker untuk pilihan "Tembusan Tambahan" (Pastikan variabel bernama $satkers)
    $satkers = \App\Models\Satker::orderBy('nama_satker')->get();

    // 5. Kirim variabel ke view
    return view('satker.internal.create', compact('daftarSatker', 'pimpinans', 'satkers'));
}

    /**
     * SIMPAN SURAT (DENGAN NOTIFIKASI email & LOGIC EDITOR TTD)
     */
    // metod simpan surat keluar internal
public function store(Request $request)
{
    // dd($request->all());
    // 1. Validasi Data
    $request->validate([
        'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'perihal'           => 'required|string|max:255',
        'tanggal_surat'     => 'required|date',
        'sifat'             => 'required|in:Biasa,Penting,Rahasia,Segera',
        'password'          => 'nullable|string|min:6',
        'file_surat'        => 'required|file|mimes:pdf|max:5120',
        'tujuan_user_ids'   => 'required|array|min:1', 
        'metode_ttd'        => 'required|in:manual,qr_png',
    ], [
        'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Harap gunakan nomor lain.',
        'file_surat.mimes'   => 'Format file harus PDF.',
        'tujuan_user_ids.required' => 'Pilih setidaknya satu pejabat/pegawai tujuan.'
    ]);

    $user = Auth::user();
    $file = $request->file('file_surat');
    $fileName = time() . '_' . \Illuminate\Support\Str::slug($request->nomor_surat) . '.pdf';
    $path = $file->storeAs('surat_keluar_internal_satker', $fileName, 'public');

    $suratKeluarId = null;
// Definisikan hasPimpinan di luar transaksi agar bisa di-use
    $hasPimpinan = $request->has('pimpinan_ids') && is_array($request->pimpinan_ids) && count($request->pimpinan_ids) > 0;
\DB::transaction(function() use ($request, $user, $path, $hasPimpinan, &$suratKeluarId) {
        
        // A. AMBIL NAMA-NAMA PENERIMA
        $penerimaUtama = \App\Models\User::find($request->tujuan_user_ids[0]); // Ambil penerima pertama sebagai referensi satker
        $penerimaNames = \App\Models\User::whereIn('id', $request->tujuan_user_ids)->pluck('name')->toArray();
        $displayTujuan = implode(', ', $penerimaNames);

        $hasPimpinan = $request->has('pimpinan_ids') && count($request->pimpinan_ids) > 0;
        $statusAwal = $hasPimpinan ? 'Pending' : 'Terkirim';
        $tokenSurat = bin2hex(random_bytes(16));

       // B. CREATE SURAT KELUAR
        $suratKeluar = \App\Models\SuratKeluar::create([
            'user_id'          => $user->id,
            'tipe_kirim'       => 'internal',
            'nomor_surat'      => $request->nomor_surat,
            'perihal'          => $request->perihal,
            'sifat'            => $request->sifat, 
            'tanggal_surat'    => $request->tanggal_surat,
            'tujuan_surat'     => $displayTujuan,
            'file_surat'       => $path,
            'metode_ttd'       => $request->metode_ttd,
            'qrcode_hash'      => \Illuminate\Support\Str::random(40),
            'token'            => $tokenSurat,
            'password'         => $request->password,
            'status'           => $hasPimpinan ? 'Pending' : 'Terkirim',
            'is_final'         => ($request->metode_ttd == 'manual' && !$hasPimpinan) ? 1 : 0,
            'tujuan_satker_id' => $penerimaUtama->satker_id ?? null, 
        ]);

        $suratKeluarId = $suratKeluar->id;

        // --- 1. PROSES KHUSUS PENERIMA (PEGAWAI) ---
        foreach ($request->tujuan_user_ids as $penerimaId) {
            $p = \App\Models\User::find($penerimaId);
            
            // Tentukan status untuk Pegawai
            if ($request->metode_ttd == 'qr_png') {
                $statusAksiPenerima = 'Menunggu Tanda Tangan Digital';
            } else {
                $statusAksiPenerima = $hasPimpinan ? 'Ditandatangani (Menunggu Validasi Pimpinan)' : 'Surat Masuk Langsung';
            }

            \App\Models\RiwayatSurat::create([
                'surat_keluar_id' => $suratKeluar->id,
                'user_id'         => $user->id,
                'penerima_id'     => $penerimaId,
                'status_aksi'     => $statusAksiPenerima,
                'created_at'      => now(),
            ]);

            // Pivot Satker
            if ($p && $p->satker_id) {
                \DB::table('surat_keluar_internal_penerima')->updateOrInsert(
                    ['surat_keluar_id' => $suratKeluar->id, 'satker_id' => $p->satker_id],
                    ['is_read' => 0, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

       // --- C. PROSES KHUSUS PIMPINAN (MENGETAHUI) ---
    if ($hasPimpinan) {
        foreach ($request->pimpinan_ids as $pId) {
            /** * PERBAIKAN LOGIKA: 
             * Jika menggunakan TTD Digital (qr_png), status validasi diset 'waiting_signature'
             * agar TIDAK MUNCUL di dashboard pimpinan sebelum TTD selesai.
             * Jika TTD Manual (Sudah ada ttd basah), langsung set 'pending'.
             */
            $statusValidasiAwal = ($request->metode_ttd == 'qr_png') ? 'waiting_signature' : 'pending';

            $suratKeluar->validasis()->create([
                'pimpinan_id' => $pId,
                'status'      => $statusValidasiAwal
            ]);

            \App\Models\RiwayatSurat::create([
                'surat_keluar_id' => $suratKeluar->id,
                'user_id'         => $user->id,
                'penerima_id'     => $pId,
                'status_aksi'     => 'Mengetahui', 
                'is_read'         => 0,
                'created_at'      => now(),
            ]);
        }
    }

        // D. Logika Tembusan
        $pilihanTembusan = $request->tembusan_ids ?? [];
        $bau = \App\Models\Satker::where('nama_satker', 'LIKE', '%BAU%')->first();
        if ($bau && !in_array('satker_'.$bau->id, $pilihanTembusan)) {
            $pilihanTembusan[] = 'satker_'.$bau->id;
        }

        foreach ($pilihanTembusan as $val) {
            $dataTembusan = ['surat_keluar_id' => $suratKeluar->id, 'user_id' => null, 'satker_id' => null];
            if (str_starts_with($val, 'user_')) {
                $dataTembusan['user_id'] = str_replace('user_', '', $val);
            } else {
                $dataTembusan['satker_id'] = str_replace('satker_', '', $val);
            }
            \App\Models\SuratTembusan::create($dataTembusan);
        }

      // E. Distribusi Langsung ke Inbox (HANYA JIKA TIDAK ADA MENGETAHUI & TTD MANUAL)
        if (!$hasPimpinan && $request->metode_ttd == 'manual') {
            $penerimaIdsForEmail = []; // Array untuk menampung ID penerima agar email tidak dikirim berulang kali

            foreach ($request->tujuan_user_ids as $penerimaId) {
                $penerima = \App\Models\User::find($penerimaId);
                if (!$penerima) continue;

                // 1. Create data di tabel surats (Inbox)
                \App\Models\Surat::create([
                    'surat_dari'       => $user->satker->nama_satker ?? $user->name,
                    'tipe_surat'       => 'internal',
                    'sifat'            => $request->sifat,
                    'nomor_surat'      => $request->nomor_surat,
                    'tanggal_surat'    => $request->tanggal_surat,
                    'perihal'          => $request->perihal,
                    'no_agenda'        => 'INT-' . strtoupper(uniqid()),
                    'file_surat'       => $path,
                    'status'           => 'proses',
                    'user_id'          => $user->id,
                    'tujuan_user_id'   => $penerimaId,
                    'diterima_tanggal' => now(), 
                ]);

                // 2. Insert ke Pivot Satker agar muncul di Dashboard Satker tujuan
                \DB::table('surat_keluar_internal_penerima')->updateOrInsert(
                    ['surat_keluar_id' => $suratKeluar->id, 'satker_id' => $penerima->satker_id],
                    ['is_read' => 0, 'created_at' => now(), 'updated_at' => now()]
                );

                // 3. Masukkan ID ke array untuk notif email
                $penerimaIdsForEmail[] = $penerimaId;
            }

            // --- KIRIM NOTIFIKASI EMAIL KE PENERIMA (SESUAI CONTOH ANDA) ---
            if (!empty($penerimaIdsForEmail)) {
                $details = [
                    'subject'    => '✉️ SURAT INTERNAL BARU: ' . $request->perihal,
                    'greeting'   => 'Halo, Bapak/Ibu,',
                    'body'       => "Anda telah menerima SURAT INTERNAL BARU dengan rincian sebagai berikut:\n\n" .
                                    "Asal Surat: " . ($user->satker->nama_satker ?? $user->name) . "\n" .
                                    "No. Surat: {$request->nomor_surat}\n" .
                                    "Sifat: {$request->sifat}\n" .
                                    "Perihal: {$request->perihal}\n\n" .
                                    "Mohon segera login ke aplikasi e-Surat untuk melihat rincian dokumen.",
                    'actiontext' => 'Buka e-Surat',
                    'actionurl'  => route('login'),
                    'file_url'   => asset('storage/' . $path)
                ];

                \App\Helpers\EmailHelper::kirimNotif($penerimaIdsForEmail, $details);
            }
        }
    });

    // 3. Redirect Dinamis
    if ($request->metode_ttd == 'qr_png') {
        return redirect()->route('satker.surat-keluar.internal.editor', ['id' => $suratKeluarId])
                         ->with('success', 'Draf berhasil disimpan. Silakan atur posisi barcode.');
    }

    return redirect()->route('satker.surat-keluar.internal')
                     ->with('success', $request->has('pimpinan_ids') ? 'Surat menunggu validasi pimpinan.' : 'Surat berhasil dikirim.');
}

public function edit($id)
{
    // 1. Eager load relasi agar data pimpinan, satker tujuan, dan riwayat pegawai terbaca semua
    $surat = \App\Models\SuratKeluar::with([
            'validasis.pimpinan', 
            'penerimaInternal', 
            'riwayats.penerima'
        ])
        ->where('id', $id)
        ->where('user_id', \Auth::id())
        ->firstOrFail();

    $user = \Auth::user();

    // 2. Daftar Satker untuk pilihan "Tujuan Unit" (Kecuali satker sendiri)
    $daftarSatker = \App\Models\Satker::where('id', '!=', $user->satker_id)
        ->orderBy('nama_satker')
        ->get();

    // 3. Daftar Pimpinan untuk pilihan "Mengetahui" & "Tembusan"
    // PERBAIKAN: Menggunakan kolom 'level' sesuai database Anda
    $pimpinans = \App\Models\User::whereHas('jabatan', function($q) {
            $q->where('level', '<', 3); 
        })
        ->with('jabatan')
        ->get();

    // 4. Daftar Semua Satker untuk pilihan "Tembusan Unit"
    $satkers = \App\Models\Satker::orderBy('nama_satker')->get();

    return view('satker.internal.edit', compact(
        'surat', 
        'daftarSatker', 
        'pimpinans', 
        'satkers'
    ));
}

 public function update(Request $request, $id)
{
    $suratKeluar = SuratKeluar::findOrFail($id);
    $user = Auth::user();
    $oldNomorSurat = $suratKeluar->nomor_surat;

    $request->validate([
        'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat,' . $suratKeluar->id,
        'perihal'           => 'required|string|max:255',
        'tanggal_surat'     => 'required|date',
        'tujuan_user_ids'   => 'required|array|min:1', // Menggunakan user_ids dari UI berjenjang
        'pimpinan_ids'      => 'nullable|array',
        'tembusan_ids'      => 'nullable|array',
        'file_surat'        => 'nullable|file|mimes:pdf|max:5120',
    ]);

    DB::transaction(function() use ($request, $suratKeluar, $user, $oldNomorSurat) {
        
        // A. KELOLA FILE
        if ($request->hasFile('file_surat')) {
            if ($suratKeluar->file_surat && Storage::disk('public')->exists($suratKeluar->file_surat)) {
                Storage::disk('public')->delete($suratKeluar->file_surat);
            }
            $suratKeluar->file_surat = $request->file('file_surat')->store('surat-internal', 'public');
        }

        // B. UPDATE DATA UTAMA
        $suratKeluar->update([
            'nomor_surat'   => $request->nomor_surat,
            'perihal'       => $request->perihal,
            'sifat'         => $request->sifat,
            'tanggal_surat' => $request->tanggal_surat,
            'status'        => 'Pending', // Reset ke Pending untuk divalidasi ulang
        ]);

        // C. RESET RIWAYAT LAMA (Kecuali yang sifatnya permanen jika ada)
        // Kita hapus riwayat tujuan, pimpinan, dan tembusan lama agar bisa di-insert ulang yang baru
        $suratKeluar->riwayats()->whereIn('status_aksi', [
            'Ditandatangani (Menunggu Validasi Pimpinan)',
            'Surat Masuk Langsung',
            'Tembusan Surat'
        ])->delete();

        // D. INSERT ULANG TUJUAN (PEGAWAI)
        $targetUserIds = array_unique($request->tujuan_user_ids);
        foreach ($targetUserIds as $targetUserId) {
            RiwayatSurat::create([
                'surat_keluar_id' => $suratKeluar->id,
                'user_id'         => $user->id,
                'penerima_id'     => $targetUserId,
                'status_aksi'     => 'Surat Masuk Langsung',
                'is_read'         => 0
            ]);
        }

        // E. UPDATE / SYNC VALIDASI PIMPINAN
        // Hapus validasi lama yang statusnya 'revisi' atau 'pending'
        $suratKeluar->validasis()->delete();
        if ($request->pimpinan_ids) {
            foreach ($request->pimpinan_ids as $pId) {
                $suratKeluar->validasis()->create([
                    'pimpinan_id' => $pId,
                    'status'      => 'pending'
                ]);
                
                // Tambahkan ke riwayat untuk tracking validasi
                RiwayatSurat::create([
                    'surat_keluar_id' => $suratKeluar->id,
                    'user_id'         => $user->id,
                    'penerima_id'     => $pId,
                    'status_aksi'     => 'Ditandatangani (Menunggu Validasi Pimpinan)',
                ]);
            }
        }

        // F. INSERT TEMBUSAN
        if ($request->tembusan_ids) {
            foreach ($request->tembusan_ids as $tId) {
                // Logika parsing user_ atau satker_ dari Select2
                $penerimaId = str_replace('user_', '', $tId);
                if (is_numeric($penerimaId)) {
                    RiwayatSurat::create([
                        'surat_keluar_id' => $suratKeluar->id,
                        'user_id'         => $user->id,
                        'penerima_id'     => $penerimaId,
                        'status_aksi'     => 'Tembusan Surat',
                    ]);
                }
            }
        }

        // G. LOG PERBAIKAN
        RiwayatSurat::create([
            'surat_keluar_id' => $suratKeluar->id,
            'user_id'         => $user->id,
            'status_aksi'     => 'Surat Diperbaiki',
            'catatan'         => 'Satker telah memperbaiki dokumen sesuai instruksi revisi.',
        ]);

        // H. SINKRONISASI KE TABEL SURATS (BAU)
        Surat::where('user_id', $user->id)
              ->where('nomor_surat', $oldNomorSurat)
              ->update([
                  'nomor_surat'   => $request->nomor_surat,
                  'tanggal_surat' => $request->tanggal_surat,
                  'perihal'       => $request->perihal,
                  'file_surat'    => $suratKeluar->file_surat
              ]);
    });

    return redirect()->route('satker.surat-keluar.internal')
                     ->with('success', 'Surat berhasil diperbarui dan dikirim ulang.');
}

public function destroy($id)
{
    // 1. Cari data milik user yang sedang login
    $surat = SuratKeluar::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

    // 2. LOGIKA SOFT DELETE (MASUK TEMPAT SAMPAH)
    // PENTING: Jangan Storage::delete() di sini agar file tetap ada jika surat di-restore.
    $surat->delete(); 

    // 3. Simpan Tipe untuk Redirect Dinamis
    $routeTujuan = ($surat->tipe_kirim == 'internal') 
        ? 'satker.surat-keluar.internal' 
        : 'satker.surat-keluar.eksternal';

    return redirect()->route($routeTujuan)->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');
}

   
/**
 * Membantu generate QR dengan celah tengah agar logo tidak menimpa modul
 */
private function generateQrWithCelah($data, $logoPath, $savePath, $isUrl, $logoSize = 80)
{
    // Memanggil helper dengan parameter punchout = true agar ada celah putih bersih
    \App\Helpers\BarcodeHelper::generateQrWithLogo($data, $logoPath, $savePath, $isUrl, true, $logoSize);
}

/**
 * Fungsi pembantu eksekusi API agar kode bersih dan tidak duplikasi
 */
private function executeDocVerifyApi($data)
{
    $apiUrl = 'https://docverify.wiraraja.ac.id/api/createv2.php';
    $apiKey = 'c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1';

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'X-API-KEY: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false 
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}


public function processSignature(Request $request, $id)
{
 // 1. Inisialisasi Data Dasar
    $surat = \App\Models\SuratKeluar::findOrFail($id);
    $user = Auth::user();
    $satker = $user->satker;

    if (!$satker || empty($satker->token_code)) {
        return back()->with('error', 'Gagal: Token Code Satker tidak ditemukan di database.');
    }

    $tokenLegal = bin2hex(random_bytes(16)); 
    $tokenTtd = bin2hex(random_bytes(16));   

    $positions = json_decode($request->positions, true);
    $canvasW = floatval($positions['width']);
    $canvasH = floatval($positions['height']);

    $pdfLocalPath = storage_path('app/public/' . $surat->file_surat);
    $hashSha256 = hash_file('sha256', $pdfLocalPath);
    $pdfUrl = asset('storage/' . $surat->file_surat);

    // --- 2. DAFTARKAN KEABSAHAN (Tepat 11 Field sesuai generate_via_api_v2.php) ---
    $postDataLegal = [
        'token'           => $tokenLegal,
        'token_code'      => $satker->token_code, 
        'document_number' => $surat->nomor_surat,
        'document_type'   => 'Surat Keluar Internal',
        'title'           => "Verifikasi Keabsahan Dokumen",
        'issued_date'     => date('Y-m-d', strtotime($surat->tanggal_surat)),
        'issued_by'       => $satker->nama_satker ?? 'Universitas Wiraraja',
        'owner_name'      => "Universitas Wiraraja",
        'pdf_path'        => $pdfUrl,
        'pdf_password'    => $surat->password ?? null, // Mengambil password surat sesuai instruksi
        'hash_sha256'     => $hashSha256
    ];

    $responseLegal = $this->executeDocVerifyApi($postDataLegal);
    if (!$responseLegal['success']) {
        return back()->with('error', 'Gagal API Keabsahan: ' . $responseLegal['message']);
    }

    // --- 3. DAFTARKAN PERSONAL TTD ---
    $postDataTtd = $postDataLegal;
    $postDataTtd['token'] = $tokenTtd;
    $postDataTtd['document_number'] = $surat->nomor_surat . "/TTD/" . time(); 
    $postDataTtd['owner_name'] = $user->name . " (NIP: " . ($user->nip ?? '-') . ")"; 

    $responseTtd = $this->executeDocVerifyApi($postDataTtd);
    if (!$responseTtd['success']) {
        return back()->with('error', 'Gagal API TTD: ' . $responseTtd['message']);
    }

    // --- 4. GENERATE BARCODE (Final Style) ---
    $urlLegal = "https://docverify.wiraraja.ac.id/v/" . $tokenLegal; 
    $urlTtd = "https://docverify.wiraraja.ac.id/v/" . $tokenTtd; 

    $pathL = storage_path('app/public/temp_qr_legal_' . $id . '.png');
    $pathT = storage_path('app/public/temp_qr_ttd_' . $id . '.png');

 // Gunakan variabel path yang jelas
    $logoSatkerPath = ($satker && $satker->logo_satker) ? storage_path('app/public/' . $satker->logo_satker) : null;
    $logoUserPath = storage_path('app/public/icon/iconn.png'); 

    // Cek fisik file sekali lagi
    if (!file_exists($logoUserPath)) {
        $logoUserPath = $logoSatkerPath; // Jika icon user tidak ada, pakai logo satker
    }

    // Panggil helper dengan variabel yang sudah didefinisikan
    \App\Helpers\BarcodeHelper::generateQrWithLabel($urlLegal, $logoSatkerPath, $pathL, $urlLegal, 40, true);
    \App\Helpers\BarcodeHelper::generateQrWithLabel($urlTtd, $logoUserPath, $pathT, "", 40, false);
    
    // --- 5. PROSES PDF (FPDI) ---
    $pdf = new \setasign\Fpdi\Fpdi();
    $pageCount = $pdf->setSourceFile($pdfLocalPath);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);
        $ratioX = $size['width'] / $canvasW; $ratioY = $size['height'] / $canvasH;
        
        if (isset($positions['pages'][$pageNo])) {
            $state = $positions['pages'][$pageNo];
            if (isset($state['ttd']) && $state['ttd']['show']) {
                $pdf->Image($pathT, floatval($state['ttd']['x']) * $ratioX, floatval($state['ttd']['y']) * $ratioY, 25, 25);
            }
// Keabsahan: Lebar 80 (lebih lebar ke samping), Tinggi 28
if (isset($state['qr']) && $state['qr']['show']) {
    $pdf->Image($pathL, floatval($state['qr']['x']) * $ratioX, floatval($state['qr']['y']) * $ratioY, 80, 28);
}
        }
    }

   // --- 6. SIMPAN & UPDATE ---
    $fileName = 'FINAL_' . time() . '_' . basename($surat->file_surat);
    $savePath = 'surat_keluar_internal_satker/' . $fileName;
    $pdf->Output(storage_path('app/public/' . $savePath), 'F');
    @unlink($pathL); @unlink($pathT);

    $surat->update(['file_surat' => $savePath, 'is_final' => 1, 'status' => 'Terkirim']);

    // dd($request->all());
// --- 6. SIMPAN & UPDATE DB UTAMA ---

    // A. UPDATE STATUS VALIDASI PIMPINAN (PENTING!)
    // Ubah status dari 'waiting_signature' menjadi 'pending' 
    // agar surat muncul di dashboard pimpinan setelah TTD staf selesai.
    \DB::table('surat_validasis')
        ->where('surat_keluar_id', $surat->id)
        ->where('status', 'waiting_signature')
        ->update(['status' => 'pending']);

    // B. CEK APAKAH ADA PIMPINAN (Untuk menentukan status surat utama)
    // Sekarang kita cek pimpinan yang statusnya sudah jadi 'pending'
    $isWaitingValidation = \DB::table('surat_validasis')
        ->where('surat_keluar_id', $surat->id)
        ->where('status', 'pending')
        ->exists();

    // C. UPDATE DATA SURAT KELUAR
    $surat->update([
        'file_surat' => $savePath,
        'is_final'   => 1,
        'status'     => $isWaitingValidation ? 'Pending' : 'Terkirim'
    ]);

    // D. UPDATE RIWAYAT (Opsional untuk tracking)
    // Mengubah riwayat penerima agar tahu bahwa TTD sudah dibubuhkan
    \App\Models\RiwayatSurat::where('surat_keluar_id', $surat->id)
        ->where('status_aksi', 'Menunggu Tanda Tangan Digital')
        ->update([
            'status_aksi' => $isWaitingValidation 
                ? 'Ditandatangani (Menunggu Validasi Pimpinan)' 
                : 'Surat Masuk Langsung'
        ]);

    // --- GATEKEEPER: JANGAN DISTRIBUSI JIKA MASIH ADA PIMPINAN ---
    if (!$isWaitingValidation) {

        // --- 7. LOGIKA DISTRIBUSI SATKER TUJUAN ---
        $satkerTujuanIds = \App\Models\RiwayatSurat::where('surat_keluar_id', $surat->id)
            ->join('users', 'riwayat_surats.penerima_id', '=', 'users.id')
            ->whereNotNull('users.satker_id')
            ->pluck('users.satker_id')
            ->unique();

        if ($surat->tujuan_satker_id) {
            $satkerTujuanIds->push($surat->tujuan_satker_id);
        }

        foreach ($satkerTujuanIds->unique() as $satkerId) {
            \DB::table('surat_keluar_internal_penerima')->updateOrInsert(
                ['surat_keluar_id' => $surat->id, 'satker_id' => $satkerId],
                ['is_read' => 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // --- 8. LOGIKA DISTRIBUSI PERSONAL ---
        $riwayatPenerima = \App\Models\RiwayatSurat::where('surat_keluar_id', $surat->id)->get();
        $penerimaIds = [];

        foreach ($riwayatPenerima as $riwayat) {
            $penerima = \App\Models\User::find($riwayat->penerima_id);
            if (!$penerima) continue;

            $namaPengirim = $user->satker->nama_satker ?? $user->name;
            
            $suratMasuk = \App\Models\Surat::create([
                'surat_dari'       => $namaPengirim, 
                'tipe_surat'       => 'internal', 
                'nomor_surat'      => $surat->nomor_surat,
                'tanggal_surat'    => $surat->tanggal_surat,
                'perihal'          => $surat->perihal,
                'sifat'            => $surat->sifat ?? 'Asli',
                'no_agenda'        => 'INT-' . strtoupper(uniqid()), 
                'diterima_tanggal' => now(),
                'file_surat'       => $savePath, 
                'status'           => 'proses', 
                'user_id'          => $surat->user_id, 
                'tujuan_user_id'   => $penerima->id,
            ]);

            $riwayat->update([
                'surat_id'    => $suratMasuk->id,
                'status_aksi' => 'Selesai (Ditandatangani Digital)'
            ]);

            $penerimaIds[] = $penerima->id;
        }

        // 9. Kirim Notifikasi Email Massal
        if (!empty($penerimaIds)) {
            \App\Helpers\EmailHelper::kirimNotif($penerimaIds, [
                'subject'    => 'Surat Masuk Digital: ' . $surat->perihal,
                'body'       => "Surat dari " . ($user->satker->nama_satker ?? $user->name) . " telah tersedia.",
                'actiontext' => 'Lihat Inbox',
                'actionurl'  => route('login'),
                'file_url'   => asset('storage/' . $savePath)
            ]);
        }
    }else {
    // JIKA MASIH MENUNGGU VALIDASI: Update riwayat 
    // Tambahkan filter status_aksi agar tidak merubah baris milik pimpinan (Mengetahui)
    \App\Models\RiwayatSurat::where('surat_keluar_id', $surat->id)
        ->where('user_id', $user->id)
        ->where('status_aksi', '!=', 'Mengetahui') // <--- TAMBAHKAN BARIS INI
        ->update([
            'status_aksi' => 'Ditandatangani (Menunggu Validasi Pimpinan)',
            'catatan'     => 'Surat berhasil ditandatangani, menunggu persetujuan pimpinan.'
        ]);
}

    return redirect()->route('satker.surat-keluar.internal')
                     ->with('success', $isWaitingValidation ? 'Surat berhasil ttd, menunggu validasi pimpinan.' : 'Surat berhasil dikirim ke tujuan.');
}



/**
 * Logika Notifikasi Email & Pembuatan Inbox Penerima
 */
private function sendNotificationsAfterSigning($suratKeluar)
{
    $user = Auth::user();
    $namaPengirim = $user->satker->nama_satker ?? $user->name;

    // Ambil daftar penerima yang sudah kita simpan di method store tadi
    $riwayats = RiwayatSurat::where('surat_keluar_id', $suratKeluar->id)->get();
    $tujuanUserIds = $riwayats->pluck('penerima_id')->toArray();

    foreach ($riwayats as $riwayat) {
        $penerima = User::find($riwayat->penerima_id);
        if (!$penerima) continue;

        // A. Isi Tabel Pivot agar muncul di menu Satker Penerima
        \DB::table('surat_keluar_internal_penerima')->insert([
            'surat_keluar_id' => $suratKeluar->id,
            'satker_id'       => $penerima->satker_id,
            'is_read'         => 0, // Belum dibaca
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // B. Buat Inbox Personal (Tabel surat)
      $status = (in_array($penerima->role, ['rektor', 'pimpinan_pusat'])) ? 'baru_di_bau' : 'proses';

$suratMasuk = Surat::create([
    'surat_dari'      => $namaPengirim, 
    'tipe_surat'      => 'internal', 
    'nomor_surat'     => $suratKeluar->nomor_surat,
    'tanggal_surat'   => $suratKeluar->tanggal_surat,
    'perihal'         => $suratKeluar->perihal,
    'sifat'           => 'biasa', // TAMBAHKAN INI (Sesuaikan: biasa/penting/rahasia)
    'no_agenda'       => 'INT-' . strtoupper(uniqid()), 
    'diterima_tanggal'=> now(),
    'file_surat'      => $suratKeluar->file_surat, 
    'status'          => $status, 
    'user_id'         => $user->id, 
    'tujuan_user_id'  => $penerima->id,
]);

        // C. Update Riwayat agar terhubung ke Surat Masuk
        $riwayat->update(['surat_id' => $suratMasuk->id, 'status_aksi' => 'Kirim Surat Internal']);
    }

    // C. Kirim Notif Email
    \App\Helpers\EmailHelper::kirimNotif($tujuanUserIds, [
        'subject' => 'Surat Masuk: ' . $suratKeluar->perihal,
        'greeting' => 'Halo,',
        'body' => "Surat resmi dari {$namaPengirim} telah ditandatangani digital.",
        'actiontext' => 'Lihat Surat',
        'actionurl' => route('login'),
        'file_url' => asset('storage/' . $suratKeluar->file_surat)
    ]);
}


// app/Http/Controllers/SatkerSuratInternalController.php

public function resend($id)
{
    $surat = \App\Models\SuratKeluar::findOrFail($id);

    \DB::transaction(function () use ($surat) {
        // 1. Reset status validasi pimpinan dari 'revisi' ke 'pending'
        \App\Models\SuratValidasi::where('surat_keluar_id', $surat->id)
            ->where('status', 'revisi')
            ->update([
                'status' => 'pending',
                'catatan' => null,
                'updated_at' => now()
            ]);

        // 2. Set status surat induk kembali ke proses validasi
        $surat->update([
            'status' => 'Pending',
            'is_final' => 0
        ]);

        // 3. Catat Riwayat
        \App\Models\RiwayatSurat::create([
            'surat_keluar_id' => $surat->id,
            'user_id' => \Auth::id(),
            'status_aksi' => 'Kirim Ulang Revisi',
            'catatan' => 'Dokumen telah diperbaiki dan dikirim ulang ke pimpinan.'
        ]);
    });

    return redirect()->back()->with('success', 'Surat berhasil dikirim kembali.');
}

  /**
         * Halaman Editor UNTUK BUBUHKAN TTD (Baru)
         */
        public function editorLayout($id)
        {
            $surat = SuratKeluar::findOrFail($id);
            $templates = SuratTemplate::where('satker_id', Auth::user()->satker_id)->get();
            
            // Pastikan view ini ada di resources/views/satker/internal/bubuhkan_ttd.blade.php
            return view('satker.internal.bubuhkan_ttd', compact('surat', 'templates'));
        }
}
      
        
     