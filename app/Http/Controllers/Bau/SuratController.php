<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\SuratKeluar;
use App\Models\Satker;
use App\Models\User;
use App\Models\RiwayatSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

// --- IMPORT PENTING ---
use App\Services\WaService;
use Carbon\Carbon; // <--- INI YANG MENYEBABKAN ERROR SEBELUMNYA

class SuratController extends Controller
{
    /**
     * Menampilkan Surat Masuk EKSTERNAL
     */
    public function indexEksternal()
    {
        $semuaSurat = Surat::whereIn('status', ['baru_di_bau', 'di_admin_rektor'])
                            ->where('tipe_surat', 'eksternal') 
                            ->latest('diterima_tanggal')
                            ->get(); 
                            
        return view('bau.surat_index', compact('semuaSurat'));
    }

    /**
     * Menampilkan Surat Masuk INTERNAL (Router)
     */
    public function indexInternal()
    {
        $semuaSurat = Surat::whereIn('status', ['baru_di_bau', 'di_admin_rektor'])
                            ->where('tipe_surat', 'internal')
                            ->latest('diterima_tanggal')
                            ->get(); 
        
        return view('bau.surat_index', compact('semuaSurat'));
    }

    /**
     * Menampilkan Inbox Khusus BAU (Tujuan Akhir)
     */
public function indexUntukBau(Request $request)
{
    $user = Auth::user();
    $bauSatkerId = $user->satker_id;

    // Filter Input
    $startDate = $request->start_date;
    $endDate = $request->end_date;
    $tipeFilter = $request->tipe_surat; // 'Internal' atau 'Eksternal'

    // ------------------------------------------------------------------
    // 1. QUERY SURAT MASUK (MANUAL / EKSTERNAL)
    // ------------------------------------------------------------------
    $qSurat = Surat::where('tujuan_tipe', 'satker')
                    ->where('tujuan_satker_id', $bauSatkerId);

    // Apply Filter ke Query Surat
    if ($startDate && $endDate) {
        $qSurat->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }
    if ($tipeFilter) {
        $qSurat->where('tipe_surat', strtolower($tipeFilter));
    }

    $suratEksternal = $qSurat->get()->map(function($item) {
        $item->jenis_surat = ucfirst($item->tipe_surat);
        $item->is_manual = true;
        $item->tgl_sort = $item->diterima_tanggal;
        return $item;
    });

    // ------------------------------------------------------------------
    // 2. QUERY SURAT INTERNAL (DARI SATKER LAIN via SYSTEM)
    // ------------------------------------------------------------------
    // Cek dulu: Jika filter dipilih "Eksternal", maka query ini TIDAK PERLU dijalankan
    $suratInternal = collect([]); // Default kosong

    if (!$tipeFilter || strtolower($tipeFilter) == 'internal') {
        
        $qInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->with(['user.satker']) // Eager load biar cepat
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            });

        // Apply Filter Tanggal ke Query Internal
        if ($startDate && $endDate) {
            $qInternal->whereBetween('tanggal_surat', [$startDate, $endDate]);
        }

        $suratInternal = $qInternal->get()->map(function($item) {
            $item->jenis_surat = 'Internal';
            $item->surat_dari = $item->user->satker->nama_satker ?? 'Satker Lain';
            $item->diterima_tanggal = $item->tanggal_surat; // Disamakan
            $item->is_manual = false;
            $item->tgl_sort = $item->tanggal_surat;
            // Pastikan struktur field penting ada untuk tabel
            return $item;
        });
    }

    // ------------------------------------------------------------------
    // 3. MERGE & SORT
    // ------------------------------------------------------------------
    $suratUntukBau = $suratEksternal->merge($suratInternal)->sortByDesc('tgl_sort');

    // 4. DATA PEGAWAI (Delegasi)
    $daftarPegawai = User::where('satker_id', $bauSatkerId)
                         ->where('id', '!=', $user->id)
                         ->get();

    // Ganti baris return view yang lama dengan ini:
return view('bau.surat_untuk_bau_index', compact('suratUntukBau', 'daftarPegawai'));
}

// ======================================================================
// METHOD BARU: EXPORT EXCEL (LOGIKA SAMA PERSIS DENGAN INDEX)
// ======================================================================
public function exportInbox(Request $request)
{
    $user = Auth::user();
    $bauSatkerId = $user->satker_id;
    
    $startDate = $request->start_date;
    $endDate = $request->end_date;
    $tipeFilter = $request->tipe_surat;

    // --- LOGIKA QUERY SAMA (COPY DARI INDEX) ---
    
    // A. Query Surat
    $qSurat = Surat::where('tujuan_tipe', 'satker')->where('tujuan_satker_id', $bauSatkerId);
    if ($startDate && $endDate) $qSurat->whereBetween('tanggal_surat', [$startDate, $endDate]);
    if ($tipeFilter) $qSurat->where('tipe_surat', strtolower($tipeFilter));
    
    $dataSurat = $qSurat->get()->map(function($item) {
        $item->jenis_surat = ucfirst($item->tipe_surat);
        $item->tgl_sort = $item->diterima_tanggal;
        return $item;
    });

    // B. Query Internal
    $dataInternal = collect([]);
    if (!$tipeFilter || strtolower($tipeFilter) == 'internal') {
        $qInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->with(['user.satker'])
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            });
        if ($startDate && $endDate) $qInternal->whereBetween('tanggal_surat', [$startDate, $endDate]);
        
        $dataInternal = $qInternal->get()->map(function($item) {
            $item->jenis_surat = 'Internal';
            $item->surat_dari = $item->user->satker->nama_satker ?? 'Satker Lain';
            $item->diterima_tanggal = $item->tanggal_surat;
            $item->tgl_sort = $item->tanggal_surat;
            return $item;
        });
    }

    // C. Merge & Sort
    $dataExport = $dataSurat->merge($dataInternal)->sortByDesc('tgl_sort');

    // --- GENERATE CSV ---
    $filename = "Inbox_BAU_" . date('Ymd_His') . ".csv";
    $headers = [
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma" => "no-cache", "Cache-Control" => "must-revalidate, post-check=0, pre-check=0", "Expires" => "0"
    ];
    $columns = ['No', 'Tipe', 'Asal Surat', 'Nomor Surat', 'Perihal', 'Tgl Surat', 'Tgl Diterima', 'Link File'];

    $callback = function() use($dataExport, $columns) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);
        
        $no = 1;
        foreach ($dataExport as $item) {
            $link = $item->file_surat ? asset('storage/' . $item->file_surat) : '-';
            fputcsv($file, [
                $no++,
                $item->jenis_surat,
                $item->surat_dari,
                $item->nomor_surat,
                $item->perihal,
                Carbon::parse($item->tanggal_surat)->format('d-m-Y'),
                $item->diterima_tanggal ? Carbon::parse($item->diterima_tanggal)->format('d-m-Y') : '-',
                $link
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
    // Tambahkan method ini di dalam class
public function checkDuplicate(Request $request)
{
    // Validasi input dari AJAX
    $field = $request->field; // 'nomor_surat' atau 'no_agenda'
    $value = $request->value;

    // Cek di database
    $exists = Surat::where($field, $value)->exists();

    return response()->json(['exists' => $exists]);
}

    public function storeInbox(Request $request)
    {
        $request->validate([
            'tipe_surat'    => 'required|in:internal,eksternal',
            'nomor_surat'   => 'required|string',
            'surat_dari'    => 'required|string',
            'perihal'       => 'required|string',
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat'    => 'required|file|mimes:pdf,jpg,png|max:10240',
            // Validasi Delegasi Opsional
            'delegasi_user_ids' => 'nullable|array',
            'catatan_delegasi'  => 'nullable|string',
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-masuk-bau', 'public');

        // GUNAKAN TRANSACTION AGAR AMAN
        DB::transaction(function() use ($request, $user, $path) {
            
            // Cek apakah ada delegasi?
            // Jika ada delegasi, status langsung 'arsip_satker' (dianggap diproses)
            // Jika tidak, status 'di_satker' (masih di inbox admin)
            $isDelegated = ($request->has('delegasi_user_ids') && count($request->delegasi_user_ids) > 0);
            $statusAwal = $isDelegated ? 'arsip_satker' : 'di_satker';

            // 1. Simpan Surat
            $surat = Surat::create([
                'user_id'          => $user->id,
                'tipe_surat'       => $request->tipe_surat,
                'nomor_surat'      => $request->nomor_surat,
                'surat_dari'       => $request->surat_dari,
                'perihal'          => $request->perihal,
                'tanggal_surat'    => $request->tanggal_surat,
                'diterima_tanggal' => $request->diterima_tanggal,
                'file_surat'       => $path,
                'sifat'            => 'Biasa',
                'no_agenda'        => 'BAU-' . time(),
                'tujuan_tipe'      => 'satker',
                'tujuan_satker_id' => $user->satker_id,
                'status'           => $statusAwal, 
            ]);

            // 2. Proses Delegasi (Jika user memilih pegawai)
            if ($isDelegated) {
                $surat->delegasiPegawai()->attach($request->delegasi_user_ids, [
                    'catatan'    => $request->catatan_delegasi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Log Riwayat
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input & Delegasi',
                    'catatan'     => 'Surat dicatat dan didelegasikan ke pegawai.'
                ]);
            } else {
                // Log Riwayat Biasa
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Input Manual',
                    'catatan'     => 'Surat dicatat manual oleh BAU.'
                ]);
            }
        });

        return redirect()->back()->with('success', 'Surat berhasil disimpan.');
    }

    /**
     * UPDATE: Hanya untuk Surat Manual BAU
     */
    public function updateInbox(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);
        
        // Proteksi: Jangan edit surat disposisi rektor, hanya inputan sendiri
        if($surat->user_id != Auth::id()){
             return redirect()->back()->with('error', 'Tidak bisa mengedit surat kiriman/disposisi.');
        }

        $request->validate([
            'nomor_surat' => 'required',
            'surat_dari' => 'required',
            'perihal' => 'required',
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $data = $request->except(['file_surat', '_token', '_method']);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
            $data['file_surat'] = $request->file('file_surat')->store('surat-masuk-bau', 'public');
        }

        $surat->update($data);
        return redirect()->back()->with('success', 'Data surat diperbarui.');
    }

    /**
     * DESTROY: Hapus Surat Manual BAU
     */
    public function destroyInbox($id)
    {
        $surat = Surat::findOrFail($id);
        
        if($surat->user_id != Auth::id()){
             return redirect()->back()->with('error', 'Tidak bisa menghapus surat kiriman/disposisi.');
        }

        if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
        $surat->delete();

        return redirect()->back()->with('success', 'Surat dihapus.');
    }

    /**
     * Menampilkan Halaman Disposisi
     */
    public function showDisposisi()
    {
        $suratDisposisi = Surat::with('disposisis.tujuanSatker', 'disposisis.klasifikasi')
                            ->whereIn('status', ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'])
                            ->latest('diterima_tanggal')
                            ->get(); 
        return view('bau.disposisi_index', compact('suratDisposisi'));
    }

    /**
     * Menampilkan Riwayat
     */
 public function showRiwayat(Request $request) 
{
    $user = Auth::user();
    $bauSatkerId = $user->satker_id;

    // 1. Ambil Input
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $tipeSurat = $request->input('tipe_surat'); // <--- TAMBAHAN BARU

    // 2. Query Dasar
    $query = \App\Models\Surat::with(['disposisis.tujuanSatker', 'user'])
        ->whereIn('status', [
            'selesai', 'selesai_edaran', 'diarsipkan', 'disimpan', 
            'arsip_satker', 'di_satker',
        ])
        ->where(function($q) use ($bauSatkerId) {
            $q->where('tujuan_satker_id', '!=', $bauSatkerId)
              ->orWhereNull('tujuan_satker_id');
        })
        ->where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereIn('tujuan_tipe', ['rektor', 'universitas'])
              ->orWhereHas('riwayats', function($subQ) {
                  $subQ->where('status_aksi', 'Disposisi Rektor');
              });
        });

    // 3. FILTER TANGGAL
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    // 4. FILTER TIPE SURAT (BARU)
    if ($tipeSurat && $tipeSurat != 'semua') {
        $query->where('tipe_surat', $tipeSurat);
    }

    // 5. Eksekusi
    $suratSelesai = $query->latest('diterima_tanggal')->get();
    
    return view('bau.riwayat_index', compact('suratSelesai', 'startDate', 'endDate', 'tipeSurat'));
}

public function exportRiwayatExcel(Request $request)
{
    $user = Auth::user();
    $bauSatkerId = $user->satker_id;
    
    // Ambil Input
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $tipeSurat = $request->input('tipe_surat'); // <--- TAMBAHAN BARU

    // Query (Sama Persis)
    $query = \App\Models\Surat::with(['disposisis.tujuanSatker', 'user'])
        ->whereIn('status', [
            'selesai', 'selesai_edaran', 'diarsipkan', 'disimpan', 
            'arsip_satker', 'di_satker', 'didisposisi'
        ])
        ->where(function($q) use ($bauSatkerId) {
            $q->where('tujuan_satker_id', '!=', $bauSatkerId)
              ->orWhereNull('tujuan_satker_id');
        })
        ->where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereIn('tujuan_tipe', ['rektor', 'universitas'])
              ->orWhereHas('riwayats', function($subQ) {
                  $subQ->where('status_aksi', 'Disposisi Rektor');
              });
        });

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    // Filter Tipe (BARU)
    if ($tipeSurat && $tipeSurat != 'semua') {
        $query->where('tipe_surat', $tipeSurat);
    }

    $data = $query->latest('diterima_tanggal')->get();

    // Generate CSV
    $filename = "Rekap_Surat_BAU_" . date('Ymd_His') . ".csv";
    
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columns = ['No.', 'No Surat', 'Tanggal Surat', 'Tipe', 'Perihal', 'Pengirim', 'Link File'];

    $callback = function() use($data, $columns) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns); 

        foreach ($data as $index => $item) {
            $link = $item->file_surat ? asset('storage/' . $item->file_surat) : '-';
            
            fputcsv($file, [
                $index + 1,
                $item->nomor_surat,
                $item->tanggal_surat->format('d-m-Y'),
                ucfirst($item->tipe_surat), // Tambah kolom tipe di excel
                $item->perihal,
                $item->surat_dari,
                $link
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function showRiwayatDetail(Surat $surat)
    {
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }

    // --- CRUD ---

    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')->with('satker')->orderBy('name', 'asc')->get();
        return view('bau.input_surat', compact('daftarSatker', 'daftarPegawai'));
    }



  public function store(Request $request)
    {
        // 1. VALIDASI (TETAP SAMA)
        $validatedData = $request->validate([
            'surat_dari' => 'required|string|max:255',
            'tipe_surat' => 'required|in:eksternal,internal',
            'nomor_surat' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'perihal' => 'required|string',
            'no_agenda' => 'required|string|max:255|unique:surats,no_agenda',
            'diterima_tanggal' => 'required|date',
            'sifat' => 'required|string',
            'file_surat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'tujuan_tipe' => 'required|in:rektor,universitas,satker,pegawai,edaran_semua_satker',
            'tujuan_satker_id' => 'required_if:tujuan_tipe,satker|nullable|exists:satkers,id',
            'tujuan_user_id' => 'required_if:tujuan_tipe,pegawai|nullable|exists:users,id',
        ],[
            'no_agenda.unique' => 'Nomor Agenda tersebut sudah terdaftar di sistem.',
            'nomor_surat.unique' => 'Nomor Surat tersebut sudah terdaftar.',
        ]);

        $filePath = $request->file('file_surat')->store('surat', 'public');

        // 2. LOGIKA STATUS (TETAP SAMA)
        $status = 'baru_di_bau'; 
        $tujuan_satker_id = null;
        $tujuan_user_id = null;
        $inputTipe = $request->input('tujuan_tipe');
        $statusAksi = 'Surat diinput (Draft)';

        if ($inputTipe == 'rektor' || $inputTipe == 'universitas') {
            $status = 'di_admin_rektor'; 
            $statusAksi = 'Surat diinput dan diteruskan ke Admin Rektor';
        } elseif ($inputTipe == 'satker') {
            $status = 'di_satker'; 
            $tujuan_satker_id = $validatedData['tujuan_satker_id'];
            $statusAksi = 'Surat dikirim langsung ke Satker';
        } elseif ($inputTipe == 'pegawai') {
            $status = 'di_satker'; 
            $tujuan_user_id = $validatedData['tujuan_user_id'];
            $statusAksi = 'Surat dikirim langsung ke Pegawai';
        } elseif ($inputTipe == 'edaran_semua_satker') {
            $status = 'di_satker';
            $statusAksi = 'Surat Edaran dikirim ke semua satker';
        }

        // 3. SIMPAN SURAT (TETAP SAMA)
        $surat = Surat::create([
            'surat_dari' => $validatedData['surat_dari'],
            'tipe_surat' => $validatedData['tipe_surat'],
            'nomor_surat' => $validatedData['nomor_surat'],
            'tanggal_surat' => $validatedData['tanggal_surat'],
            'perihal' => $validatedData['perihal'],
            'no_agenda' => $validatedData['no_agenda'],
            'diterima_tanggal' => $validatedData['diterima_tanggal'],
            'sifat' => $validatedData['sifat'],
            'file_surat' => $filePath,
            'status' => $status,
            'user_id' => Auth::id(),
            'tujuan_tipe' => $inputTipe,
            'tujuan_satker_id' => $tujuan_satker_id,
            'tujuan_user_id' => $tujuan_user_id,
        ]);

        RiwayatSurat::create([
            'surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Input Surat', 'catatan' => $statusAksi
        ]);

        if ($inputTipe == 'edaran_semua_satker') {
            $semuaSatkerId = Satker::pluck('id');
            $surat->satkerPenerima()->attach($semuaSatkerId, ['status' => 'terkirim']); 
        }

        // ====================================================================
        // 4. NOTIFIKASI WA (PERBAIKAN UTAMA: SPLIT NOMOR HP)
        // ====================================================================
        
        $rawTargets = []; // Penampung user target (User Object, Nama Tujuan)

        // A. Ambil User Target berdasarkan Tipe
        if ($inputTipe == 'rektor' || $inputTipe == 'universitas') {
            $users = User::where('role', 'admin_rektor')->get();
            foreach($users as $u) {
                $rawTargets[] = ['user' => $u, 'nama_tujuan' => 'Rektor / Universitas (Admin)'];
            }
        } 
        elseif ($inputTipe == 'satker' && $tujuan_satker_id) {
            $users = User::where('role', 'satker')->where('satker_id', $tujuan_satker_id)->get();
            foreach($users as $u) {
                $namaSatker = $u->satker->nama_satker ?? 'Satker';
                $rawTargets[] = ['user' => $u, 'nama_tujuan' => $namaSatker];
            }
        } 
        elseif ($inputTipe == 'pegawai' && $tujuan_user_id) {
            $u = User::find($tujuan_user_id);
            if($u) {
                $rawTargets[] = ['user' => $u, 'nama_tujuan' => $u->name];
            }
        }

        // B. Proses Split Nomor HP dan Masukkan ke Array Final
        $finalTargets = []; // Array [no_hp, nama_tujuan] yang siap kirim

        foreach ($rawTargets as $target) {
            $user = $target['user'];
            $namaTujuan = $target['nama_tujuan'];

            if (!empty($user->no_hp)) {
                // 1. Pecah string berdasarkan koma (explode)
                $nomorList = explode(',', $user->no_hp);

                // 2. Loop setiap nomor hasil pecahan
                foreach ($nomorList as $nomor) {
                    // Bersihkan spasi (trim) jika ada "081xx, 082xx"
                    $nomor = trim($nomor);
                    
                    if (!empty($nomor)) {
                        $finalTargets[] = [
                            'hp' => $nomor,
                            'nama' => $namaTujuan
                        ];
                    }
                }
            }
        }

        // 5. KIRIM PESAN KE SEMUA NOMOR (LOOPING FINAL)
        $tglSurat = \Carbon\Carbon::parse($validatedData['tanggal_surat'])->format('d-m-Y'); 
        $link = route('login'); 

        foreach ($finalTargets as $ft) {
            try {
                $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : {$ft['nama']}
Tanggal Surat : {$tglSurat}
No. Surat     : {$validatedData['nomor_surat']}
Perihal       : {$validatedData['perihal']}
Pengirim      : {$validatedData['surat_dari']}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                
                // Kirim ke nomor yang sudah di-split
                WaService::send($ft['hp'], $pesan);
            
            } catch (\Exception $e) {
                // Silent error
            }
        }

        $route = ($validatedData['tipe_surat'] == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Surat berhasil disimpan.');
    }


    public function edit(Surat $surat)
    {
        $allowedStatuses = ['baru_di_bau', 'di_admin_rektor', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'Status surat tidak valid untuk diedit.');
        }
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')->with('satker')->orderBy('name', 'asc')->get();
        return view('bau.surat_edit', compact('surat', 'daftarSatker', 'daftarPegawai'));
    }

    public function update(Request $request, Surat $surat)
    {
        $allowedStatuses = ['baru_di_bau', 'di_admin_rektor', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->route('bau.surat.eksternal')->with('error', 'Status surat tidak valid.');
        }
        
        $validatedData = $request->validate([
            'surat_dari'  => 'required|string|max:255',
            'nomor_surat' => 'required|string|max:255',
            'perihal'     => 'required|string',
            'no_agenda'   => ['required', Rule::unique('surats')->ignore($surat->id)],
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'sifat' => 'required|string',
        ]);

        $surat->update($validatedData);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::exists($surat->file_surat)) {
                Storage::delete($surat->file_surat);
            }
            $path = $request->file('file_surat')->store('surat-masuk', 'public');
            $surat->update(['file_surat' => $path]);
        }

        $statusDisposisi = ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (in_array($surat->status, $statusDisposisi)) {
            return redirect()->route('bau.disposisi.index')->with('success', 'Data surat diperbarui.');
        }
        
        $route = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Data surat diperbarui.');
    }

public function destroy(Surat $surat)
    {
        // 1. Cek Validasi Status
        if (!in_array($surat->status, ['baru_di_bau', 'di_admin_rektor'])) {
            return redirect()->back()->with('error', 'Gagal hapus. Status surat tidak mengizinkan.');
        }

        try {
            // 2. Hapus File Fisik (Jika ada)
            if($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }

            // 3. [SOLUSI UTAMA] HAPUS DATA RELASI DULU
            // Hapus semua riwayat yang terkait dengan surat ini
            $surat->riwayats()->delete(); 
            
            // Hapus semua disposisi yang terkait (jika ada) agar aman
            $surat->disposisis()->delete(); 

            // 4. Hapus Surat Utama
            $surat->delete();

            return redirect()->back()->with('success', 'Berhasil dihapus beserta riwayatnya.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus: ' . $e->getMessage());
        }
    }

    // --- ACTIONS ---

   public function forwardToRektor(Request $request, Surat $surat)
    {
        if ($surat->status != 'baru_di_bau') return back()->with('error', 'Sudah diproses.');
        
        $surat->update(['status' => 'di_admin_rektor']);
        
        RiwayatSurat::create([
            'surat_id' => $surat->id, 
            'user_id' => Auth::id(), 
            'status_aksi' => 'Diteruskan ke Admin Rektor', 
            'catatan' => 'Diteruskan ke Admin Rektor.'
        ]);
        
        // =========================================================
        // WA KE REKTOR (DIPERBAIKI: MULTIPLE ADMIN & MULTI NOMOR)
        // =========================================================
        try {
            $tglSurat = $surat->tanggal_surat->format('d-m-Y'); 
            $link = route('login');

            // 1. Ambil semua admin rektor
            $adminRektors = User::where('role', 'admin_rektor')->get();
            $nomorHpList = [];

            // 2. Kumpulkan semua nomor HP (Split koma)
            foreach($adminRektors as $admin) {
                if ($admin->no_hp) {
                    $pecahan = explode(',', $admin->no_hp);
                    foreach($pecahan as $hp) {
                        $nomorHpList[] = trim($hp);
                    }
                }
            }

            // 3. Kirim Pesan
            foreach ($nomorHpList as $hpTarget) {
                if(empty($hpTarget)) continue;

                $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : Rektor / Universitas
Tanggal Surat : {$tglSurat}
No. Surat     : {$surat->nomor_surat}
Perihal       : {$surat->perihal}
Pengirim      : {$surat->surat_dari}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";

                WaService::send($hpTarget, $pesan);
            }

        } catch (\Exception $e) {
            // Silent error
        }

        $route = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Berhasil diteruskan.');
    }

    public function forwardToSatker(Request $request, Surat $surat)
    {
        $validStatuses = ['baru_di_bau', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];

        if (!in_array($surat->status, $validStatuses)) {
             return back()->with('error', 'Status surat tidak valid untuk diteruskan.');
        }

        $statusAkhir = ($surat->tujuan_tipe == 'edaran_semua_satker') ? 'di_satker' : 'di_satker';
        $surat->update(['status' => $statusAkhir]);

        $tujuanList = [];
        
        // Array untuk menampung target kirim [hp, nama_satker]
        // Bedanya disini nanti kita tampung dulu user-nya, baru di split nomornya
        $targetUsers = []; 
        
        // Logika Ambil Penerima (Disposisi / Langsung)
        if ($surat->disposisis->count() > 0) {
            foreach($surat->disposisis as $d) {
                if ($d->tujuanSatker) {
                    $tujuanList[] = $d->tujuanSatker->nama_satker;
                    
                    // Ambil SEMUA admin di satker tersebut
                    $admins = User::where('role', 'satker')->where('satker_id', $d->tujuan_satker_id)->get();
                    foreach($admins as $u) {
                        $targetUsers[] = ['user' => $u, 'nama_satker' => $d->tujuanSatker->nama_satker];
                    }
                }
            }
        } elseif ($surat->tujuan_satker_id) {
            $tujuanList[] = $surat->tujuanSatker->nama_satker;
            
            // Ambil SEMUA admin di satker tersebut
            $admins = User::where('role', 'satker')->where('satker_id', $surat->tujuan_satker_id)->get();
            foreach($admins as $u) {
                $targetUsers[] = ['user' => $u, 'nama_satker' => $surat->tujuanSatker->nama_satker];
            }
        }

        // =========================================================
        // KIRIM WA KE SATKER TUJUAN (DIPERBAIKI: SPLIT NOMOR)
        // =========================================================
        
        $finalTargets = []; // Array [hp, nama_satker] siap kirim

        // 1. Proses Split Nomor HP
        foreach ($targetUsers as $item) {
            $user = $item['user'];
            $namaSatker = $item['nama_satker'];

            if ($user->no_hp) {
                $pecahan = explode(',', $user->no_hp);
                foreach($pecahan as $hp) {
                    $hp = trim($hp);
                    if(!empty($hp)) {
                        $finalTargets[] = ['hp' => $hp, 'nama_satker' => $namaSatker];
                    }
                }
            }
        }

        // 2. Kirim Pesan
        foreach ($finalTargets as $target) {
            try {
                $tglSurat = $surat->tanggal_surat->format('d-m-Y'); 
                $link = route('login');

                // Pesan Disposisi (Sedikit Beda)
                $pesan = 
"📩 *Notifikasi Disposisi Surat*

Satker Tujuan : {$target['nama_satker']}
Tanggal Surat : {$tglSurat}
No. Surat     : {$surat->nomor_surat}
Perihal       : {$surat->perihal}
Pengirim      : {$surat->surat_dari}
Status        : Diteruskan oleh BAU

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";

                WaService::send($target['hp'], $pesan);
            
            } catch (\Exception $e) {}
        }

        $namaTujuanString = implode(', ', $tujuanList) ?: 'Tujuan';
        RiwayatSurat::create(['surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Dikirim ke Satker/Penerima', 'catatan' => 'Dikirim ke: ' . $namaTujuanString]);

        return redirect()->route('bau.disposisi.index')->with('success', 'Surat berhasil dikirim ke: ' . $namaTujuanString);
    }

    public function selesaikanLainnya(Request $request, Surat $surat)
    {
        $surat->update(['status' => 'diarsipkan']);
        RiwayatSurat::create(['surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Selesai (Manual)', 'catatan' => 'Diarsipkan oleh BAU.']);
        return redirect()->route('bau.disposisi.index')->with('success', 'Berhasil ditandai selesai.');
    }
}