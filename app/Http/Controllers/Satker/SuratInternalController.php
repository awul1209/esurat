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
use App\Services\WaService;
use Carbon\Carbon; // <--- INI YANG MENYEBABKAN ERROR SEBELUMNYA

class SuratInternalController extends Controller
{
// 1. UPDATE METHOD INDEX (Agar Filter Berjalan)
public function indexMasuk(Request $request)
    {
        $mySatkerId = Auth::user()->satker_id;

        // 1. SUMBER A: Surat dari Satker Lain (Tabel SuratKeluar)
        $queryReal = \App\Models\SuratKeluar::with(['user.satker']) 
            ->where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($mySatkerId) {
                $q->where('satkers.id', $mySatkerId);
            });

        // 2. SUMBER B: Surat Inputan Manual (Tabel Surat)
        $queryManual = \App\Models\Surat::with(['user.satker'])
            ->where('tipe_surat', 'internal')
            ->where('tujuan_satker_id', $mySatkerId);

        // Logika Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $queryReal->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
            $queryManual->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }
        
        // --- EKSEKUSI QUERY ---
        $dataReal = $queryReal->get();
        $dataManual = $queryManual->get();

        // --- [BAGIAN PENTING] MANIPULASI DATA SURAT KELUAR ---
        // Kita loop data dari SuratKeluar, lalu kita isi 'diterima_tanggal' pakai 'created_at'
        $dataReal->transform(function ($item) {
            // Mapping: diterima_tanggal <-- created_at
            $item->diterima_tanggal = $item->created_at;
            return $item;
        });
        // -----------------------------------------------------

        // 3. GABUNGKAN DATA (Merge) & Sortir
        // Sekarang kedua sumber data sudah punya 'diterima_tanggal' yang valid
        $suratMasuk = $dataReal->merge($dataManual)->sortByDesc('tanggal_surat');

        // Data Pendukung Modal
        $daftarSatker = \App\Models\Satker::where('id', '!=', $mySatkerId)->get();
        $daftarPegawai = \App\Models\User::where('satker_id', $mySatkerId)->where('id', '!=', Auth::id())->get();

        return view('satker.internal.surat_masuk_index', compact('suratMasuk', 'daftarSatker', 'daftarPegawai'));
    }

   // 2. PERBAIKAN METHOD EXPORT (GABUNGAN MANUAL & OTOMATIS)
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

        // --- 3. FILTER TANGGAL (Terapkan ke kedua query) ---
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $queryReal->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
            $queryManual->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // Ambil Data & Gabungkan
        $dataReal = $queryReal->get();
        $dataManual = $queryManual->get();
        
        // Gabungkan dan Urutkan berdasarkan tanggal terbaru (SAMA DENGAN INDEX)
        $data = $dataReal->merge($dataManual)->sortByDesc('tanggal_surat');

        // --- 4. STREAM CSV ---
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8", // Tambahkan charset
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM untuk UTF-8 agar Excel membacanya dengan benar
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Kolom Excel
            fputcsv($file, ['No', 'No Surat', 'Tanggal Surat', 'Perihal', 'Pengirim', 'Tipe', 'Link Surat']);

            // Isi Data
            foreach ($data as $index => $row) {
                // Logika Penentuan Pengirim
                if ($row instanceof \App\Models\Surat) {
                    // Manual
                    $pengirim = $row->surat_dari; 
                    $tipe     = "Input Manual";
                } else {
                    // Otomatis (SuratKeluar)
                    $pengirim = $row->user && $row->user->satker ? $row->user->satker->nama_satker : 'Tidak Diketahui';
                    $tipe     = "Kiriman Satker";
                }

                // Siapkan Link File (Gunakan url() agar link lengkap)
                $linkFile = $row->file_surat ? url('storage/' . $row->file_surat) : 'Tidak ada file';

                fputcsv($file, [
                    $index + 1,
                    $row->nomor_surat,
                    $row->tanggal_surat->format('d-m-Y'),
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


  public function indexKeluar(Request $request)
    {
        $userId = Auth::id();

        // 1. Mulai Query Builder
        $query = SuratKeluar::with(['penerimaInternal']) 
            ->where('tipe_kirim', 'internal')
            ->where('user_id', $userId);

        // 2. Cek Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [
                $request->start_date, 
                $request->end_date
            ]);
        }

        // 3. Ambil Data
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
                    $tujuan = $surat->tujuan_surat . ' (Via BAU)';
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
     * SIMPAN SURAT (DENGAN NOTIFIKASI WA)
     */
 public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            // Tambahkan 'unique:surat_keluars,nomor_surat'
            'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat',
            'perihal'           => 'required|string|max:255',
            'tujuan_satker_ids' => 'required|array|min:1', 
            'tanggal_surat'     => 'required|date',
            'file_surat'        => 'required|file|mimes:pdf,jpg,png|max:5120',
        ], [
            // Custom Error Message (Opsional, agar pesan lebih jelas)
            'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Harap gunakan nomor lain.',
            'tujuan_satker_ids.required' => 'Pilih setidaknya satu tujuan surat.'
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-internal', 'public');

        DB::transaction(function() use ($request, $user, $path) {
            
            // A. PISAHKAN INPUT
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

            // B. PROSES 1: SURAT KELUAR (Arsip Satker Pengirim)
            $displayTujuan = [];
            if (!empty($targetPimpinan)) {
                $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetPimpinan)) . " (Via BAU)";
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

            // --- 1. JIKA KIRIM KE SATKER LAIN (TERMASUK BAU SEBAGAI SATKER) ---
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->attach($satkerIds);

                // === NOTIFIKASI WA KE ADMIN SATKER PENERIMA ===
                try {
                    // Role yang boleh menerima notif
                    $rolePenerima = ['satker', 'bau', 'bapsi', 'admin'];

                    // Cari User berdasarkan ID Satker tujuannya
                    $penerimaNotif = User::whereIn('satker_id', $satkerIds)
                                         ->whereIn('role', $rolePenerima)
                                         ->get();

                    foreach ($penerimaNotif as $penerima) {
                        if ($penerima->no_hp) {
                            
                            // --- LOGIKA BARU (FORMAT KOMA) ---
                            // 1. Pecah string berdasarkan koma ","
                            $daftarNomor = explode(',', $penerima->no_hp);

                            $namaTujuan = $penerima->satker->nama_satker ?? 'Satker Tujuan';
                            $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : {$namaTujuan}
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek sistem e-Surat: {$link}";

                            // 2. Loop setiap nomor yang sudah dipecah
                            foreach ($daftarNomor as $nomor) {
                                // Bersihkan spasi (trim) agar " 08123" jadi "08123"
                                $nomorBersih = trim($nomor);
                                
                                // Bersihkan karakter non-angka jika perlu
                                $nomorBersih = preg_replace('/[^0-9]/', '', $nomorBersih);

                                if(!empty($nomorBersih)) {
                                    WaService::send($nomorBersih, $pesan);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) { /* Abaikan error WA */ }
            }

            // --- 2. JIKA KIRIM KE REKTOR (Via BAU) ---
            if (!empty($targetPimpinan)) {
                
                foreach ($targetPimpinan as $target) {
                    $tujuanTipe = ($target == 'universitas') ? 'universitas' : 'rektor';
                    $noAgendaSementara = 'PENDING-' . uniqid(); 

                    $suratMasuk = Surat::create([
                        'surat_dari'       => $user->satker->nama_satker ?? $user->name, 
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
                        'catatan'     => 'Surat dikirim ke BAU (No. Agenda belum diisi). Menunggu verifikasi BAU.'
                    ]);
                }

                // === NOTIFIKASI WA KE ADMIN BAU (KHUSUS REKTOR) ===
                try {
                    $adminBAU = User::where('role', 'bau')->first();
                    
                    if ($adminBAU && $adminBAU->no_hp) {
                        
                        // --- LOGIKA BARU (FORMAT KOMA) UNTUK BAU ---
                        $daftarNomorBAU = explode(',', $adminBAU->no_hp);

                        $pesan = 
"📩 *Notifikasi Surat Masuk Baru (Via BAU)*

Satker Tujuan : Rektor / Universitas
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek sistem e-Surat: {$link}";
                        
                        foreach ($daftarNomorBAU as $nomor) {
                            $nomorBersih = trim($nomor);
                            $nomorBersih = preg_replace('/[^0-9]/', '', $nomorBersih);

                            if(!empty($nomorBersih)) {
                                WaService::send($nomorBersih, $pesan);
                            }
                        }
                    }
                } catch (\Exception $e) { /* Abaikan error WA */ }
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
        $surat = SuratKeluar::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        if ($surat->file_surat && Storage::exists($surat->file_surat)) {
            Storage::delete($surat->file_surat);
        }
        $surat->delete();
        return redirect()->route('satker.surat-keluar.internal')->with('success', 'Surat berhasil dihapus.');
    }

    // --- METHOD BARU KHUSUS SURAT MASUK MANUAL ---

// --- METHOD STORE SURAT MASUK MANUAL (REVISI MODEL SURAT) ---

public function storeMasukManual(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'nomor_surat'    => 'required|string|max:255',
            'asal_satker_id' => 'required|exists:satkers,id', // Khas Internal: Pilih Satker
            'perihal'        => 'required|string',
            'tanggal_surat'  => 'required|date',
            'diterima_tanggal' => 'required|date', // Tambahkan ini agar sama dengan eksternal
            'file_surat'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            // Validasi Array Delegasi (Opsional)
            'delegasi_user_ids'=> 'nullable|array',
            'catatan_delegasi' => 'nullable|string',
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-masuk-internal', 'public');

        // Gunakan Transaksi DB
        DB::transaction(function() use ($request, $user, $path) {
            
            // TENTUKAN STATUS AWAL
            // Sama seperti Eksternal, kita set 'arsip_satker'
            $statusAwal = 'arsip_satker';

            // Ambil Nama Satker Pengirim (Khas Internal)
            $satkerPengirim = \App\Models\Satker::findOrFail($request->asal_satker_id);

            // A. Simpan Surat
            $surat = \App\Models\Surat::create([
                'user_id'          => $user->id,
                'tipe_surat'       => 'internal', // <--- Bedanya disini
                'nomor_surat'      => $request->nomor_surat,
                'surat_dari'       => $satkerPengirim->nama_satker, // Simpan Nama Satker
                'perihal'          => $request->perihal,
                'tanggal_surat'    => $request->tanggal_surat,
                'diterima_tanggal' => $request->diterima_tanggal,
                'file_surat'       => $path,
                'sifat'            => 'Asli',
                'no_agenda'        => 'MI-' . time(), // MI = Masuk Internal
                'tujuan_tipe'      => 'satker',
                'tujuan_satker_id' => $user->satker_id,
                'status'           => $statusAwal, // Status langsung Arsip/Selesai
            ]);

            // B. Proses Delegasi (Jika ada inputan pegawai)
            if ($request->has('delegasi_user_ids') && count($request->delegasi_user_ids) > 0) {
                
                $catatan = $request->catatan_delegasi;
                $userIds = $request->delegasi_user_ids;

                // Attach ke tabel pivot
                $surat->delegasiPegawai()->attach($userIds, [
                    'catatan'    => $catatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Simpan Riwayat
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input & Delegasi (Internal)',
                    'catatan'     => 'Surat internal diinput manual dan didelegasikan ke ' . count($userIds) . ' pegawai.'
                ]);

                // Notif WA (Opsional - Jika ada class WaService)
                try {
                    $pegawais = \App\Models\User::whereIn('id', $userIds)->get();
                    foreach ($pegawais as $p) {
                        if ($p->no_hp) {
                            $pesan = "📩 *Tugas Baru (Internal)*\n\nPerihal: {$surat->perihal}\nAsal: {$surat->surat_dari}\nSilakan cek sistem.";
                            \App\Services\WaService::send($p->no_hp, $pesan);
                        }
                    }
                } catch (\Exception $e) {}

            } else {
                // Jika tidak ada delegasi, catat sebagai Arsip Langsung
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input Manual (Arsip Internal)',
                    'catatan'     => 'Surat internal diinput manual dan langsung diarsipkan.'
                ]);
            }
        });

        return redirect()->back()->with('success', 'Surat masuk internal berhasil disimpan.');
    }

// --- UPDATE SURAT MASUK MANUAL ---

public function updateMasukManual(Request $request, $id)
{
    // Gunakan Model SURAT
    $surat = Surat::findOrFail($id);

    // Cek Kepemilikan (Pastikan yang edit adalah User di Satker tujuan yang sama)
    if ($surat->tujuan_satker_id != Auth::user()->satker_id) {
        return abort(403, 'Anda tidak berhak mengedit surat ini.');
    }

    $validator = Validator::make($request->all(), [
        'nomor_surat'    => 'required',
        'asal_satker_id' => 'required|exists:satkers,id',
        'perihal'        => 'required',
        'tanggal_surat'  => 'required|date',
        'file_surat'     => 'nullable|mimes:pdf,jpg,png|max:2048',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput()->with('error_code', 'edit')->with('edit_id', $id);
    }

    DB::beginTransaction();
    try {
        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat) Storage::delete('public/' . $surat->file_surat);
            $surat->file_surat = $request->file('file_surat')->store('surat-masuk', 'public');
        }

        $satkerPengirim = Satker::findOrFail($request->asal_satker_id);

        $surat->nomor_surat = $request->nomor_surat;
        $surat->surat_dari  = $satkerPengirim->nama_satker; // Update nama pengirim
        $surat->perihal     = $request->perihal;
        $surat->tanggal_surat = $request->tanggal_surat;
        
        $surat->save();

        DB::commit();
        return redirect()->back()->with('success', 'Data surat berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
    }
}

// --- HAPUS SURAT MASUK MANUAL ---

public function destroyMasukManual($id)
{
    $surat = Surat::findOrFail($id);

    if ($surat->tujuan_satker_id != Auth::user()->satker_id) {
        return abort(403, 'Anda tidak berhak menghapus surat ini.');
    }

    try {
        if ($surat->file_surat) Storage::delete('public/' . $surat->file_surat);
        
        // Hapus Delegasi
        $surat->delegasiPegawai()->detach();
        
        // Hapus Surat
        $surat->delete();

        return redirect()->back()->with('success', 'Surat berhasil dihapus.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
    }
}
}