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

// use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
// 1. UPDATE METHOD INDEX (Agar Filter Berjalan & Mendukung Delegasi)
public function indexMasuk(Request $request)
{
    $mySatkerId = Auth::user()->satker_id;

    // 1. SUMBER A: Surat dari Satker Lain atau REKTOR
    $queryReal = \App\Models\SuratKeluar::with(['user.satker', 'penerimaInternal', 'riwayats.penerima']) 
        ->where('tipe_kirim', 'internal')
        ->whereHas('penerimaInternal', function($q) use ($mySatkerId) {
            $q->where('satker_id', $mySatkerId);
        })
        ->where(function($q) {
            $q->whereHas('user', function($u) {
                $u->where('role', 'admin_rektor');
            })->where('status', 'selesai')->whereNotNull('tanggal_terusan');
            
            $q->orWhereHas('user', function($u) {
                $u->where('role', '!=', 'admin_rektor');
            });
        });

    // 2. SUMBER B: Surat Inputan Manual
    $queryManual = \App\Models\Surat::with(['user.satker', 'riwayats.penerima'])
        ->where('tipe_surat', 'internal')
        ->where('tujuan_satker_id', $mySatkerId);

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $queryReal->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        $queryManual->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }
    
    $dataReal = $queryReal->get();
    $dataManual = $queryManual->get();

    // Transform Sumber A (Sistem)
    $dataReal->transform(function ($item) use ($mySatkerId) {
        $myPivot = $item->penerimaInternal->where('id', $mySatkerId)->first();
        $item->is_read_internal = $myPivot ? $myPivot->pivot->is_read : 0;
        $item->diterima_tanggal = $item->tanggal_terusan ?? $item->created_at;
        $item->is_manual = false; // Penanda bukan manual
        return $item;
    });

    // Transform Sumber B (Manual) - PENYESUAIAN DISINI
    $dataManual->transform(function ($item) {
        $item->is_manual = true; // Penanda manual agar tombol muncul
        $item->diterima_tanggal = $item->diterima_tanggal ?? $item->created_at;
        return $item;
    });

    $suratMasuk = $dataReal->merge($dataManual)->sortByDesc('diterima_tanggal');
    $daftarSatker = \App\Models\Satker::where('id', '!=', $mySatkerId)->get();

    $pegawaiList = \App\Models\User::where('satker_id', $mySatkerId)
        ->where('id', '!=', Auth::id())
        ->whereIn('role', ['pegawai', 'dosen'])
        ->orderBy('name', 'asc')
        ->get();

    return view('satker.internal.surat_masuk_index', compact(
        'suratMasuk', 
        'daftarSatker', 
        'pegawaiList',
    ));
}


   // 2. PERBAIKAN METHOD EXPORT (FINAL & RAPI)
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

    // delegasi surat masuk internal
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

 public function indexKeluar(Request $request)
{
    $userId = Auth::id();

    // Pastikan relasi penerimaInternal (Satker) dan riwayats tetap dimuat
    $query = SuratKeluar::with(['penerimaInternal', 'riwayats.penerima.satker']) 
        ->where('tipe_kirim', 'internal')
        ->where('user_id', $userId);

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [
            $request->start_date, 
            $request->end_date
        ]);
    }

    $suratKeluar = $query->latest('tanggal_surat')->get();

    return view('satker.internal.surat_keluar_index', compact('suratKeluar'));
}

  public function exportKeluar(Request $request)
    {
        // 1. Ambil Input & User
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $userId    = Auth::id();

        // 2. Definisi Class Export (Anonymous Class)
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

            // A. QUERY DATA
            public function collection()
            {
                $query = SuratKeluar::with(['penerimaInternal'])
                    ->where('tipe_kirim', 'internal')
                    ->where('user_id', $this->userId);

                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
                }

                return $query->latest('tanggal_surat')->get();
            }

            // B. HEADER KOLOM (SESUAI REQUEST)
            public function headings(): array
            {
                return [
                    'No',
                    'No Surat',
                    'Perihal',
                    'Tanggal Kirim',
                    'Tujuan',
                    'Link Surat (Download)', // Kolom Baru
                ];
            }

            // C. ISI DATA PER BARIS
            public function map($surat): array
            {
                // 1. Logika Nama Tujuan
                $tujuan = '-';
                if (!empty($surat->tujuan_surat)) {
                    $tujuan = $surat->tujuan_surat;
                } elseif ($surat->penerimaInternal->count() > 0) {
                    // Ambil nama satker, pisahkan koma
                    $tujuan = $surat->penerimaInternal->pluck('nama_satker')->join(', ');
                }

                // 2. Generate Link File Lengkap
                // Menggunakan url() agar jadi http://domain.com/storage/...
                $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

                // Nomor Urut
                static $no = 0;
                $no++;

                return [
                    $no,
                    $surat->nomor_surat,
                    $surat->perihal,
                    // Format Tanggal (d-m-Y)
                    \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'), 
                    $tujuan,
                    $linkFile, // Link dimasukkan di sini
                ];
            }

            // D. STYLING HEADER (BOLD)
            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]], // Baris 1 Bold
                ];
            }
        };

        // 3. Download File
        $namaFile = 'Laporan_Surat_Keluar_Internal_' . date('d-m-Y_H-i') . '.xlsx';
        return Excel::download($export, $namaFile);
    }

    public function create()
    {
        $mySatkerId = Auth::user()->satker_id;
        $daftarSatker = Satker::where('id', '!=', $mySatkerId)->orderBy('nama_satker')->get();

        return view('satker.internal.create', compact('daftarSatker'));
    }

    /**
     * SIMPAN SURAT (DENGAN NOTIFIKASI email)
     */

    // SURAT KELUAR INTERNAL
 public function store(Request $request)
{
    // 1. Validasi
    $request->validate([
        'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'perihal'           => 'required|string|max:255',
        'tujuan_satker_ids' => 'required|array|min:1', 
        'tanggal_surat'     => 'required|date',
        'file_surat'        => 'required|file|mimes:pdf,jpg,png|max:5120',
        'tujuan_user_ids'   => 'nullable|array', // Tambahan validasi untuk ID pegawai
    ], [
        'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Harap gunakan nomor lain.',
        'tujuan_satker_ids.required' => 'Pilih setidaknya satu tujuan surat.'
    ]);

    $user = Auth::user();
    $path = $request->file('file_surat')->store('surat_keluar_internal_satker', 'public');

    DB::transaction(function() use ($request, $user, $path) {
        
        // A. PISAHKAN INPUT
        $inputTujuan = $request->tujuan_satker_ids;
        $satkerIds = [];
        $targetPimpinan = []; 
        $isPegawaiSpesifik = false;

        foreach($inputTujuan as $val) {
            if ($val == 'rektor' || $val == 'universitas') {
                $targetPimpinan[] = $val;
            } elseif ($val == 'pegawai_spesifik') {
                $isPegawaiSpesifik = true;
            } elseif (is_numeric($val)) {
                $satkerIds[] = $val;
            }
        }

        // B. PROSES 1: SURAT KELUAR (Arsip Satker Pengirim)
        $displayTujuan = [];
        if (!empty($targetPimpinan)) {
            $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetPimpinan)) . " (Via BAU)";
        }
        
        // Tambahkan keterangan di arsip jika ada tujuan pegawai spesifik
        if ($isPegawaiSpesifik && $request->has('tujuan_user_ids')) {
            $countPegawai = count($request->tujuan_user_ids);
            $displayTujuan[] = "{$countPegawai} Pegawai Spesifik";
        }

        $suratKeluar = SuratKeluar::create([
            'user_id'          => $user->id,
            'tipe_kirim'       => 'internal',
            'nomor_surat'      => $request->nomor_surat,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'tujuan_satker_id' => null, 
            'tujuan_surat'     => !empty($displayTujuan) ? implode(', ', $displayTujuan) : null, 
            'file_surat'       => $path,
        ]);

        // Persiapan Data Notifikasi
        $namaPengirim = $user->satker->nama_satker ?? $user->name;
        $tglSurat = \Carbon\Carbon::parse($request->tanggal_surat)->format('d-m-Y');
        $link = route('login'); 

        // --- 1. JIKA KIRIM KE SATKER LAIN (NOTIFIKASI EMAIL) ---
        if (!empty($satkerIds)) {
            $suratKeluar->penerimaInternal()->attach($satkerIds);

            $rolePenerima = ['satker', 'bau', 'bapsi', 'admin'];
            $penerimaNotifIds = \App\Models\User::whereIn('satker_id', $satkerIds)
                                            ->whereIn('role', $rolePenerima)
                                            ->pluck('id')
                                            ->toArray();

            if (!empty($penerimaNotifIds)) {
                $details = [
                    'subject'    => 'Surat Masuk Baru: ' . $request->perihal,
                    'greeting'   => 'Halo Tim Satker,',
                    'body'       => "Anda menerima surat masuk baru dari {$namaPengirim}. \n\n" .
                                    "No. Surat: {$request->nomor_surat}\n" .
                                    "Tanggal Surat: {$tglSurat}\n" .
                                    "Perihal: {$request->perihal}",
                    'actiontext' => 'Buka Inbox Surat Masuk',
                    'actionurl'  => $link,
                    'file_url'   => asset('storage/' . $path)
                ];
                \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
            }
        }

        // --- 2. JIKA KIRIM KE PEGAWAI SPESIFIK (NOTIFIKASI EMAIL KE PEGAWAI) ---
if ($isPegawaiSpesifik && !empty($request->tujuan_user_ids)) {
    foreach ($request->tujuan_user_ids as $penerimaUserId) {
        // A. Simpan ke tabel surat (sebagai Inbox Pegawai)
        $suratMasukPegawai = Surat::create([
            'surat_dari'       => $namaPengirim, 
            'tipe_surat'       => 'internal', 
            'nomor_surat'      => $request->nomor_surat,
            'tanggal_surat'    => $request->tanggal_surat,
            'perihal'          => $request->perihal,
            'no_agenda'        => 'PEGAWAI-' . strtoupper(uniqid()), 
            'diterima_tanggal' => now(),
            'sifat'            => 'Asli',
            'file_surat'       => $path,
            'status'           => 'proses', 
            'user_id'          => $user->id, 
            'tujuan_tipe'      => 'pegawai',
            'tujuan_satker_id' => null,
            'tujuan_user_id'   => $penerimaUserId,
        ]);

        // B. Simpan ke Riwayat (PENTING: Isi surat_keluar_id dan penerima_id)
        RiwayatSurat::create([
            'surat_id'         => $suratMasukPegawai->id, // Untuk tracking di inbox pegawai
            'surat_keluar_id'  => $suratKeluar->id,      // PENTING: Untuk tracking di arsip satker
            'user_id'          => $user->id,             // Aktor (pengirim)
            'penerima_id'      => $penerimaUserId,       // PENTING: Target pegawai
            'status_aksi'      => 'Surat Masuk Internal (Personal)',
            'catatan'          => 'Surat dikirim langsung ke Pegawai spesifik.',
            'is_read'          => 0                      // Status awal: Terkirim (Belum diterima)
        ]);
    }

    // Notifikasi Email ke Pegawai
    $detailsPegawai = [
        'subject'    => 'Surat Masuk Pribadi Baru: ' . $request->perihal,
        'greeting'   => 'Halo Bapak/Ibu,',
        'body'       => "Anda menerima surat internal baru yang ditujukan langsung kepada Anda dari {$namaPengirim}.\n\n" .
                        "No. Surat: {$request->nomor_surat}\n" .
                        "Perihal: {$request->perihal}",
        'actiontext' => 'Lihat Surat Saya',
        'actionurl'  => $link,
        'file_url'   => asset('storage/' . $path)
    ];

    \App\Helpers\EmailHelper::kirimNotif($request->tujuan_user_ids, $detailsPegawai);
}

        // --- 3. JIKA KIRIM KE REKTOR (Via BAU - NOTIFIKASI EMAIL KE BAU) ---
        if (!empty($targetPimpinan)) {
            foreach ($targetPimpinan as $target) {
                $tujuanTipe = ($target == 'universitas') ? 'universitas' : 'rektor';
                $noAgendaSementara = 'PENDING-' . uniqid(); 

                $suratMasuk = Surat::create([
                    'surat_dari'       => $namaPengirim, 
                    'tipe_surat'       => 'internal', 
                    'nomor_surat'      => $request->nomor_surat,
                    'tanggal_surat'    => $request->tanggal_surat,
                    'perihal'          => $request->perihal,
                    'no_agenda'        => $noAgendaSementara, 
                    'diterima_tanggal' => now(),
                    'sifat'            => 'Biasa',
                    'file_surat'       => $path,
                    'status'           => 'baru_di_bau', 
                    'user_id'          => $user->id, 
                    'tujuan_tipe'      => $tujuanTipe,
                    'tujuan_satker_id' => null,
                    'tujuan_user_id'   => null,
                ]);

                RiwayatSurat::create([
                    'surat_id'    => $suratMasuk->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Surat Masuk Internal',
                    'catatan'     => 'Surat dikirim ke BAU. Menunggu verifikasi BAU.'
                ]);
            }

            $adminBAUIds = \App\Models\User::where('role', 'bau')->pluck('id')->toArray();
            if (!empty($adminBAUIds)) {
                $detailsBAU = [
                    'subject'    => 'Pemberitahuan Surat Masuk Baru (Tujuan Rektor)',
                    'greeting'   => 'Halo Tim BAU,',
                    'body'       => "Ada surat internal baru dari {$namaPengirim} yang ditujukan ke Rektor/Universitas. Surat ini memerlukan verifikasi Anda sebelum diteruskan.\n\n" .
                                    "No. Surat: {$request->nomor_surat}\n" .
                                    "Perihal: {$request->perihal}",
                    'actiontext' => 'Verifikasi Surat di BAU',
                    'actionurl'  => $link,
                    'file_url'   => asset('storage/' . $path)
                ];
                \App\Helpers\EmailHelper::kirimNotif($adminBAUIds, $detailsBAU);
            }
        }

    }); 

    return redirect()->route('satker.surat-keluar.internal')
                     ->with('success', 'Surat berhasil dikirim.');
}

    public function edit($id)
    {
        $surat = SuratKeluar::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $mySatkerId = Auth::user()->satker_id;
        $daftarSatker = Satker::where('id', '!=', $mySatkerId)->orderBy('nama_satker')->get();
        $selectedSatkerIds = $surat->penerimaInternal->pluck('id')->toArray();

        return view('satker.internal.edit', compact('surat', 'daftarSatker', 'selectedSatkerIds'));
    }

   public function update(Request $request, $id)
    {
        $suratKeluar = SuratKeluar::findOrFail($id);
        $user = Auth::user();

        // 1. Simpan Nomor Surat Lama (PENTING untuk mencari pasangan di tabel surats)
        $oldNomorSurat = $suratKeluar->nomor_surat;

        // 2. Validasi
        $request->validate([
            // Unique: Abaikan ID surat ini agar tidak dianggap duplikat dengan dirinya sendiri
            'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat,' . $suratKeluar->id,
            'perihal'           => 'required|string|max:255',
            'tujuan_satker_ids' => 'required|array|min:1', 
            'tanggal_surat'     => 'required|date', 
            'file_surat'        => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ], [
            'nomor_surat.unique' => 'Nomor surat ini sudah digunakan.'
        ]);

        DB::transaction(function() use ($request, $suratKeluar, $user, $oldNomorSurat) {
            
            // A. KELOLA FILE (Jika ada upload baru)
            if ($request->hasFile('file_surat')) {
                // Hapus file lama fisik
                if ($suratKeluar->file_surat && Storage::disk('public')->exists($suratKeluar->file_surat)) {
                    Storage::disk('public')->delete($suratKeluar->file_surat);
                }
                // Simpan file baru
                $path = $request->file('file_surat')->store('surat-internal', 'public');
                $suratKeluar->file_surat = $path;
            }

            // B. PROSES TUJUAN (Pisahkan Rektor & Satker)
            $inputTujuan = $request->tujuan_satker_ids;
            $satkerIds = [];
            $targetPimpinan = []; 

            foreach($inputTujuan as $val) {
                if ($val == 'rektor' || $val == 'universitas') {
                    $targetPimpinan[] = $val;
                } elseif (is_numeric($val)) {
                    $satkerIds[] = $val;
                }
            }

            // Display Tujuan String
            $displayTujuan = [];
            if (!empty($targetPimpinan)) {
                $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetPimpinan)) . " (Via BAU)";
            }

            // C. UPDATE DATA UTAMA (SURAT KELUAR)
            $suratKeluar->update([
                'nomor_surat'      => $request->nomor_surat,
                'perihal'          => $request->perihal,
                'tanggal_surat'    => $request->tanggal_surat,
                'tujuan_surat'     => !empty($displayTujuan) ? implode(', ', $displayTujuan) : null,
                // file_surat sudah dihandle otomatis via object property di atas jika berubah
            ]);

            // D. SYNC PIVOT SATKER (Jika ada tujuan satker)
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->sync($satkerIds);
            } else {
                $suratKeluar->penerimaInternal()->detach();
            }

            // ==========================================================
            // E. SINKRONISASI KE TABEL SURATS (SURAT MASUK REKTOR/BAU)
            // ==========================================================
            // Cari surat di tabel 'surats' yang berasal dari user ini DAN nomor suratnya masih yang LAMA
            // Kita cari berdasarkan 'user_id' (pengirim) dan 'nomor_surat' (lama)
            
            $relatedSurats = Surat::where('user_id', $user->id)
                                  ->where('tipe_surat', 'internal')
                                  ->where('nomor_surat', $oldNomorSurat) // Cari pakai nomor lama
                                  ->get();

            if ($relatedSurats->count() > 0) {
                foreach ($relatedSurats as $suratMasuk) {
                    $suratMasuk->update([
                        'nomor_surat'   => $request->nomor_surat,   // Update ke Nomor Baru
                        'tanggal_surat' => $request->tanggal_surat, // Update Tanggal
                        'perihal'       => $request->perihal,       // Update Perihal
                        'file_surat'    => $suratKeluar->file_surat // Update Path File (sama dengan surat keluar)
                    ]);
                }
            }

        });

        return redirect()->route('satker.surat-keluar.internal')
                         ->with('success', 'Data surat berhasil diperbarui dan disinkronkan.');
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

    // --- METHOD BARU KHUSUS SURAT MASUK MANUAL ---

// --- METHOD STORE SURAT MASUK MANUAL (REVISI MODEL SURAT) ---

// SURAT MASUK INTERNAL
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

// log status satker surat kelaur internal
public function getRiwayatStatus($id)
{
    try {
        // Load relasi penerimaInternal agar data pivot tersedia
        $suratKeluar = \App\Models\SuratKeluar::with('penerimaInternal')->findOrFail($id);
        $listRiwayat = [];

        // --- A. LOG AWAL: SURAT DIKIRIM ---
        $listRiwayat[] = [
            'status_aksi' => 'Surat Dikirim',
            'catatan'     => 'Surat dikirim dari Satker asal.',
            'created_at'  => $suratKeluar->created_at->toISOString(),
            'user'        => ['name' => 'Admin Pengirim'] 
        ];

        // --- B. LOGIKA SURAT KE REKTOR/BAU (Pusat) ---
        $suratMasukPusat = \App\Models\Surat::where('nomor_surat', $suratKeluar->nomor_surat)
                            ->with('riwayats.user')
                            ->first();

        if ($suratMasukPusat) {
            foreach($suratMasukPusat->riwayats as $log) {
                $listRiwayat[] = [
                    'status_aksi' => $log->status_aksi,
                    'catatan'     => $log->catatan,
                    'created_at'  => $log->created_at->toISOString(),
                    'user'        => ['name' => $log->user ? $log->user->name : 'Sistem Pusat']
                ];
            }
        }

        // --- C. LOGIKA SURAT ANTAR SATKER (Penerima Langsung) ---
        if ($suratKeluar->penerimaInternal->isNotEmpty()) {
            foreach ($suratKeluar->penerimaInternal as $penerima) {
                $status = $penerima->pivot->is_read ?? 0;
                $labelSatker = $penerima->nama_satker;

                // Log: Kapan Surat Masuk ke Inbox Satker Tujuan
                $listRiwayat[] = [
                    'status_aksi' => 'Diterima di ' . $labelSatker,
                    'catatan'     => 'Surat telah masuk ke daftar surat masuk ' . $labelSatker,
                    'created_at'  => $penerima->pivot->created_at->toISOString(),
                    'user'        => ['name' => 'Sistem']
                ];

                // Log: Jika Sudah Dibaca
                if ($status >= 1) {
                    $listRiwayat[] = [
                        'status_aksi' => 'Dibaca oleh ' . $labelSatker,
                        'catatan'     => 'Surat telah dibuka dan dibaca oleh Admin ' . $labelSatker,
                        'created_at'  => $penerima->pivot->updated_at->toISOString(),
                        'user'        => ['name' => 'Admin ' . $labelSatker]
                    ];
                }

                // Log: Jika Sudah Diarsipkan
                if ($status == 2) {
                    $listRiwayat[] = [
                        'status_aksi' => 'Selesai / Arsip ' . $labelSatker,
                        'catatan'     => 'Surat telah ditindaklanjuti dan diarsipkan oleh ' . $labelSatker,
                        'created_at'  => $penerima->pivot->updated_at->toISOString(),
                        'user'        => ['name' => 'Admin ' . $labelSatker]
                    ];
                }
            }
        }

        // --- D. URUTKAN LOG (TIME ASCENDING) ---
        // Menghapus duplikasi jika ada (berdasarkan catatan & waktu yang sama persis)
        $uniqueRiwayat = collect($listRiwayat)->unique(function ($item) {
            return $item['status_aksi'].$item['created_at'];
        })->values()->toArray();

        usort($uniqueRiwayat, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        return response()->json([
            'nomor_surat' => $suratKeluar->nomor_surat,
            'riwayats'    => $uniqueRiwayat
        ]);

    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
}
// arsip
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
}