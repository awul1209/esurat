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
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;

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
    $suratInternal = collect([]); 

    if (!$tipeFilter || strtolower($tipeFilter) == 'internal') {
        $qInternal = SuratKeluar::where('tipe_kirim', 'internal')
            ->with(['user.satker'])
            ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                $q->where('satkers.id', $bauSatkerId);
            });

        if ($startDate && $endDate) {
            $qInternal->whereBetween('tanggal_surat', [$startDate, $endDate]);
        }

        $suratInternal = $qInternal->get()->map(function($item) {
            $item->jenis_surat = 'Internal';
            $item->surat_dari = $item->user->satker->nama_satker ?? 'Rektor';
            $item->diterima_tanggal = $item->tanggal_surat;
            $item->is_manual = false;
            $item->tgl_sort = $item->tanggal_surat;
            return $item;
        });
    }

    // ------------------------------------------------------------------
    // 3. MERGE & SORT
    // ------------------------------------------------------------------
    $suratUntukBau = $suratEksternal->merge($suratInternal)->sortByDesc('tgl_sort');

    // ------------------------------------------------------------------
    // 4. DATA PEGAWAI (Delegasi)
    // ------------------------------------------------------------------
    $pegawaiList = User::where('satker_id', $bauSatkerId)
                     ->where('id', '!=', $user->id)
                     ->get();

    // ------------------------------------------------------------------
    // 5. DATA SATKER (UNTUK INPUT MANUAL INTERNAL) - PERBAIKAN DISINI
    // ------------------------------------------------------------------
    $satkers = \App\Models\Satker::orderBy('nama_satker', 'asc')->get();

    // Tambahkan 'satkers' ke dalam compact
    return view('bau.surat_untuk_bau_index', compact('suratUntukBau', 'pegawaiList', 'satkers'));
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
// delegasi inbox BAU
public function delegate(Request $request)
{
    $request->validate([
        'id'          => 'required',
        'target_tipe' => 'required|in:pribadi,semua',
        'klasifikasi' => 'required_if:target_tipe,pribadi',
        'asal_tabel'  => 'required|in:surat,surat_keluar',
        'user_id'     => 'required_if:target_tipe,pribadi|array'
    ]);

    try {
        \DB::beginTransaction();

        $admin = Auth::user();
        $idSurat = $request->id;
        
        // 1. Identifikasi Model (Surat Manual vs Surat Keluar Internal)
        $surat = ($request->asal_tabel == 'surat_keluar') 
            ? \App\Models\SuratKeluar::findOrFail($idSurat) 
            : \App\Models\Surat::findOrFail($idSurat);

        $penerimaNotifIds = [];

        // 2. Tentukan Nilai Default Kategori (Pribadi vs Umum)
        $klasifikasi = ($request->target_tipe == 'semua') 
            ? 'Informasi Umum' 
            : $request->klasifikasi;
            
        $catatan = ($request->target_tipe == 'semua') 
            ? 'Surat ini disebarluaskan kepada seluruh pegawai BAU untuk diketahui.' 
            : $request->catatan;

        // 3. Eksekusi Berdasarkan Tipe Target
        if ($request->target_tipe == 'pribadi') {
            // --- SKENARIO PRIBADI (Delegasi Spesifik) ---
            $userIds = $request->input('user_id', []);
            foreach ($userIds as $userId) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'        => $request->asal_tabel == 'surat' ? $surat->id : null,
                    'surat_keluar_id' => $request->asal_tabel == 'surat_keluar' ? $surat->id : null,
                    'user_id'         => $admin->id,
                    'penerima_id'     => $userId,
                    'status_aksi'     => 'Delegasi: ' . $klasifikasi, // Kata kunci 'Delegasi' memicu Card Pribadi
                    'catatan'         => $catatan,
                ]);
                $penerimaNotifIds[] = $userId;
            }
        } else {
            // --- SKENARIO UMUM (Sebar ke Semua Pegawai BAU) ---
            $semuaPegawaiBAU = \App\Models\User::where('satker_id', $admin->satker_id)
                                ->where('role', 'pegawai')
                                ->get();

            foreach ($semuaPegawaiBAU as $pegawai) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'        => $request->asal_tabel == 'surat' ? $surat->id : null,
                    'surat_keluar_id' => $request->asal_tabel == 'surat_keluar' ? $surat->id : null,
                    'user_id'         => $admin->id,
                    'penerima_id'     => $pegawai->id,
                    'status_aksi'     => 'Informasi Umum: ' . $klasifikasi, // Kata kunci 'Informasi Umum' memicu Card Umum
                    'catatan'         => $catatan,
                ]);
                $penerimaNotifIds[] = $pegawai->id;
            }
        }
// 4. UPDATE STATUS & PIVOT DI BAU
        // Menandakan bahwa BAU sudah memproses surat ini (Delegasi/Arsip)
        $bauSatkerId = auth()->user()->satker_id;

        if ($request->asal_tabel == 'surat_keluar') {
            // Update Status Utama
            $surat->update(['status' => 'Delegasi/Sebar']);

            // UPDATE PIVOT (Jika BAU adalah penerima di tabel surat_keluar_internal_penerima)
            if ($surat->penerimaInternal()->where('satker_id', $bauSatkerId)->exists()) {
                $surat->penerimaInternal()->updateExistingPivot($bauSatkerId, [
                    'is_read' => 2 
                ]);
            }
        } else {
            // Untuk Surat Eksternal/Manual (Tabel surats)
            $surat->update(['status' => 'Selesai di BAU']);
        }

        \DB::commit();

        // 5. NOTIFIKASI EMAIL (Optional - Gunakan Helper yang sudah ada)
        if (!empty($penerimaNotifIds)) {
            $details = [
                'subject'    => '['.strtoupper($request->target_tipe).']: ' . $surat->perihal,
                'greeting'   => 'Halo,',
                'body'       => "Anda menerima " . ($request->target_tipe == 'semua' ? "informasi" : "delegasi") . " surat baru di unit BAU. \n\n" .
                                "No. Surat: {$surat->nomor_surat}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Instruksi: " . $klasifikasi,
                'actiontext' => 'Buka Dashboard',
                'actionurl'  => route('login'),
                'file_url'   => $surat->file_surat ? asset('storage/' . $surat->file_surat) : null
            ];
            // Pastikan Helper Email Anda tersedia
            if (class_exists('\App\Helpers\EmailHelper')) {
                \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
            }
        }

        return redirect()->back()->with('success', 'Surat berhasil ' . ($request->target_tipe == 'semua' ? 'disebarkan ke semua pegawai BAU.' : 'didelegasikan ke pegawai terkait.'));

    } catch (\Exception $e) {
        \DB::rollBack();
        return redirect()->back()->with('error', 'Gagal melakukan delegasi: ' . $e->getMessage());
    }
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
        'tipe_surat'       => 'required|in:internal,eksternal',
        'nomor_surat'      => 'required|string',
        'perihal'          => 'required|string',
        'tanggal_surat'    => 'required|date',
        'diterima_tanggal' => 'required|date',
        'file_surat'       => 'required|file|mimes:pdf,jpg,png|max:10240',
        'target_tipe'      => 'required|in:arsip,pribadi,semua',
        // Validasi kondisional
        'surat_dari'       => 'required_if:tipe_surat,eksternal|nullable|string',
        'satker_asal_id'   => 'required_if:tipe_surat,internal|nullable|exists:satkers,id',
        'delegasi_user_ids'=> 'required_if:target_tipe,pribadi|array',
        'catatan_delegasi' => 'nullable|string',
    ]);

    $user = Auth::user();
    
    // Tentukan asal surat (Teks atau Nama Satker)
    $asalSurat = $request->surat_dari;
    if ($request->tipe_surat == 'internal') {
        $satkerAsal = \App\Models\Satker::find($request->satker_asal_id);
        $asalSurat = $satkerAsal->nama_satker;
    }

    $path = $request->file('file_surat')->store('sm_bau_ineks', 'public');

    try {
        DB::beginTransaction();
        
        // Status: Jika didelegasi/disebar, otomatis 'Selesai di BAU' agar sinkron dengan tabel inbox
        $statusAwal = ($request->target_tipe == 'arsip') ? 'Selesai di BAU' : 'Selesai di BAU';

        // 1. Simpan ke Tabel Surat
        $surat = \App\Models\Surat::create([
            'user_id'          => $user->id,
            'tipe_surat'       => $request->tipe_surat,
            'nomor_surat'      => $request->nomor_surat,
            'surat_dari'       => $asalSurat,
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

        $penerimaNotifIds = [];
        $klasifikasi = 'Informasi Umum'; // Default

        // 2. PROSES DISTRIBUSI & LOG RIWAYAT
        if ($request->target_tipe == 'pribadi') {
            $klasifikasi = 'Delegasi Pribadi';
            foreach ($request->delegasi_user_ids as $penerimaId) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $penerimaId,
                    'status_aksi' => 'Delegasi: ' . $klasifikasi,
                    'catatan'     => $request->catatan_delegasi ?? 'Surat dicatat dan didelegasikan.'
                ]);
                $penerimaNotifIds[] = $penerimaId;
            }
        } elseif ($request->target_tipe == 'semua') {
            $klasifikasi = 'Informasi Umum';
            $pegawaiBAU = \App\Models\User::where('satker_id', $user->satker_id)
                            ->where('id', '!=', $user->id)
                            ->get();

            foreach ($pegawaiBAU as $pegawai) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $pegawai->id,
                    'status_aksi' => 'Informasi Umum: ' . $klasifikasi,
                    'catatan'     => $request->catatan_delegasi ?? 'Surat disebarluaskan untuk seluruh pegawai.'
                ]);
                $penerimaNotifIds[] = $pegawai->id;
            }
        } else {
            // Hanya Arsip
            \App\Models\RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $user->id,
                'status_aksi' => 'Input Manual (Arsip)',
                'catatan'     => 'Surat dicatat manual sebagai arsip BAU.'
            ]);
        }

        DB::commit();

        // 3. KIRIM NOTIFIKASI EMAIL (Menggunakan EmailHelper yang sudah ada)
        if (!empty($penerimaNotifIds)) {
            $details = [
                'subject'    => '[' . strtoupper($request->target_tipe) . ']: ' . $surat->perihal,
                'greeting'   => 'Halo,',
                'body'       => "Admin BAU baru saja mencatat surat masuk dan memprosesnya untuk Anda.\n\n" .
                                "No. Surat: {$surat->nomor_surat}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Instruksi: " . ($request->catatan_delegasi ?? $klasifikasi),
                'actiontext' => 'Lihat di Dashboard',
                'actionurl'  => route('login'),
                'file_url'   => asset('storage/' . $surat->file_surat)
            ];
            
            \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
        }

        return redirect()->back()->with('success', 'Surat berhasil dicatat dan diproses.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}

    /**
     * UPDATE: Hanya untuk Surat Manual BAU
     */
  public function updateInbox(Request $request, $id)
{
    $surat = \App\Models\Surat::findOrFail($id);
    $user = Auth::user();

    // 1. Proteksi Kepemilikan & Delegasi
    // Cek apakah ada riwayat delegasi/sebar (Admin tidak boleh edit jika pegawai sudah terima)
    $isAlreadyProcessed = $surat->riwayats->where('user_id', $user->id)->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'delegasi') || str_contains($aksi, 'informasi');
    })->isNotEmpty();

    if ($surat->user_id != $user->id || $isAlreadyProcessed) {
        return redirect()->back()->with('error', 'Maaf, surat tidak dapat diedit karena sudah didelegasikan atau bukan milik Anda.');
    }

    // 2. Validasi Input (Sesuai Logic Store)
    $request->validate([
        'tipe_surat'       => 'required|in:internal,eksternal',
        'nomor_surat'      => 'required|string',
        'perihal'          => 'required|string',
        'tanggal_surat'    => 'required|date',
        'diterima_tanggal' => 'required|date',
        'file_surat'       => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        // Validasi kondisional asal surat
        'surat_dari'       => 'required_if:tipe_surat,eksternal|nullable|string',
        'satker_asal_id'   => 'required_if:tipe_surat,internal|nullable|exists:satkers,id',
    ]);

    try {
        DB::beginTransaction();

        // 3. Tentukan Asal Surat (Internal vs Eksternal)
        $asalSurat = $request->surat_dari;
        if ($request->tipe_surat == 'internal') {
            $satkerAsal = \App\Models\Satker::find($request->satker_asal_id);
            $asalSurat = $satkerAsal->nama_satker;
        }

        $data = [
            'tipe_surat'       => $request->tipe_surat,
            'nomor_surat'      => $request->nomor_surat,
            'surat_dari'       => $asalSurat,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'diterima_tanggal' => $request->diterima_tanggal,
        ];

        // 4. Handle File Update
        if ($request->hasFile('file_surat')) {
            // Hapus file lama jika ada
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('sm_bau_ineks', 'public');
        }

        // 5. Eksekusi Update
        $surat->update($data);

        // 6. Catat Log Riwayat Perubahan
        \App\Models\RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Update Data Surat',
            'catatan'     => 'Admin BAU memperbarui detail informasi surat manual.'
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Data surat manual berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
    }
}

    /**
     * DESTROY: Hapus Surat Manual BAU
     */
public function destroyInbox($id)
{
    $surat = \App\Models\Surat::findOrFail($id);
    $user = Auth::user();

    // 1. Proteksi: Cek apakah surat ini didelegasikan/disebar
    $isAlreadyProcessed = $surat->riwayats->where('user_id', $user->id)->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'delegasi') || str_contains($aksi, 'informasi');
    })->isNotEmpty();

    // 2. Proteksi Kepemilikan & Status
    if ($surat->user_id != $user->id || $isAlreadyProcessed) {
        return redirect()->back()->with('error', 'Tidak bisa menghapus surat yang sudah didelegasikan atau bukan milik Anda.');
    }

    // 3. Eksekusi Soft Delete (Masuk ke Tempat Sampah)
    // Jangan hapus file di storage agar surat bisa di-restore
    $surat->delete(); 

    // 4. Catat Log Riwayat (Opsional)
    \App\Models\RiwayatSurat::create([
        'surat_id'    => $surat->id,
        'user_id'     => $user->id,
        'status_aksi' => 'Hapus ke Tempat Sampah',
        'catatan'     => 'Admin BAU memindahkan surat manual ke tempat sampah.'
    ]);

    return redirect()->back()->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');
}

    /**
     * Menampilkan Halaman RIWAYAT TERUSAN BAU
     */
 public function showRiwayat(Request $request) 
    {
        // 1. Ambil Input Filter
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $tipeSurat = $request->input('tipe_surat');

        // 2. Query Dasar (DISAMAKAN DENGAN ADMIN REKTOR)
        $query = \App\Models\Surat::with(['disposisis.tujuanSatker', 'tujuanSatker', 'tujuanUser'])
            // Filter: Hanya surat untuk Rektor/Univ
            ->whereIn('tujuan_tipe', ['rektor', 'universitas'])
            // Filter: Hanya status yang menunjukkan surat SUDAH diteruskan
            ->whereIn('status', [
                'di_satker',        // Sudah diterima Satker
                'arsip_satker',     // Sudah diselesaikan Satker
                'diarsipkan',     // Surat yang kusus lainnya/ormawa
                'selesai_edaran',    // Edaran selesai
                'selesai'
            ]);

        // 3. Filter Tanggal
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
        }

        // 4. Filter Tipe Surat
        if ($tipeSurat && $tipeSurat != 'semua') {
            $query->where('tipe_surat', $tipeSurat);
        }

        // 5. Eksekusi
        $suratSelesai = $query->latest('diterima_tanggal')->get();
        
        return view('bau.riwayat_index', compact('suratSelesai', 'startDate', 'endDate', 'tipeSurat'));
    }

    /**
     * Export Excel Riwayat Terusan
     */
   public function exportRiwayatExcel(Request $request)
{
    $user = Auth::user();
    $bauSatkerId = $user->satker_id;
    
    // 1. Ambil Input
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $tipeSurat = $request->input('tipe_surat'); 

    // 2. Query
    $query = \App\Models\Surat::with(['disposisis.tujuanSatker', 'user'])
        // --- PERBAIKAN FILTER STATUS ---
        // Hanya ambil status proses disposisi.
        // Status 'selesai', 'diarsipkan', 'disimpan' DIHAPUS (karena masuk Arsip Rektor)
        ->whereIn('status', [
            'di_satker', 
            'arsip_satker', 
            'diarsipkan', 
            'selesai_edaran',
            'selesai'
        ])
        // --- LOGIKA QUERY ANDA SEBELUMNYA (DIPERTAHANKAN) ---
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

    // 3. Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    // 4. Filter Tipe
    if ($tipeSurat && $tipeSurat != 'semua') {
        $query->where('tipe_surat', $tipeSurat);
    }

    $data = $query->latest('diterima_tanggal')->get();

    // 5. Generate CSV
    $filename = "Rekap_Riwayat_Disposisi_BAU_" . date('Ymd_His') . ".csv";
    
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // Tambahkan kolom 'Tujuan Disposisi'
    $columns = ['No.', 'No Surat', 'Tanggal Surat', 'Tipe', 'Perihal', 'Pengirim', 'Tujuan Disposisi', 'Status Terakhir', 'Link File'];

    $callback = function() use($data, $columns) {
        $file = fopen('php://output', 'w');
        
        // Tambahkan BOM agar Excel bisa baca karakter utf-8 dengan benar
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
        
        fputcsv($file, $columns); 

        foreach ($data as $index => $item) {
            $link = $item->file_surat ? asset('storage/' . $item->file_surat) : '-';
            
            // --- LOGIKA MENGAMBIL NAMA TUJUAN DISPOSISI ---
            $tujuanText = '-';
            $targets = [];
            foreach($item->disposisis as $d) {
                if($d->tujuanSatker) {
                    $targets[] = $d->tujuanSatker->nama_satker;
                } elseif($d->disposisi_lain) {
                    $targets[] = $d->disposisi_lain;
                }
            }
            if(count($targets) > 0) {
                $tujuanText = implode(', ', $targets);
            } else {
                // Cek jika tipe edaran
                if($item->tujuan_tipe == 'edaran_semua_satker') {
                    $tujuanText = 'Semua Satker (Edaran)';
                } else {
                    $tujuanText = 'Menunggu Disposisi';
                }
            }

            fputcsv($file, [
                $index + 1,
                $item->nomor_surat,
                $item->tanggal_surat->format('d-m-Y'),
                ucfirst($item->tipe_surat),
                $item->perihal,
                $item->surat_dari,
                $tujuanText, // Masukkan data tujuan
                $item->status,
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
        // PERBAIKAN: Filter agar riwayat internal Satker tidak ikut terbaca
        // Kita hanya mengambil riwayat yang status_aksinya TIDAK mengandung kata kunci delegasi pegawai
        $query->where(function($q) {
            $q->where('status_aksi', 'not like', '%Informasi Umum%')
              ->where('status_aksi', 'not like', '%delegasi%')
              ->where('status_aksi', 'not like', '%Diarsipkan/Selesai di Satker%')
              ->where('status_aksi', 'not like', '%disposisi%');
        })
        // Atau jika Anda ingin lebih aman, pastikan pengirimnya hanya dari role Pusat
        // ->whereHas('user', function($u) {
        //     $u->whereIn('role', ['admin', 'bau', 'admin_rektor']);
        // })
        ->latest(); 
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


// STORE SURAT MASUK EKSTERNAL & INTERNAL
public function store(Request $request)
{
    // 1. VALIDASI
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

    // 2. LOGIKA STATUS & PENENTUAN TARGET
    $status = 'baru_di_bau'; 
    $tujuan_satker_id = null;
    $tujuan_user_id = null;
    $inputTipe = $request->input('tujuan_tipe');
    $statusAksi = 'Surat diinput (Draft)';
    $targetUserIds = []; // Penampung ID User untuk Email
    $namaTujuanEmail = '';

    if ($inputTipe == 'rektor' || $inputTipe == 'universitas') {
        $status = 'di_admin_rektor'; 
        $statusAksi = 'Surat diinput dan diteruskan ke Admin Rektor';
        $targetUserIds = \App\Models\User::where('role', 'admin_rektor')->pluck('id')->toArray();
        $namaTujuanEmail = 'Rektor / Universitas';
        
    } elseif ($inputTipe == 'satker') {
        $status = 'di_satker'; 
        $tujuan_satker_id = $validatedData['tujuan_satker_id'];
        $statusAksi = 'Surat dikirim langsung ke Satker';
        $targetUserIds = \App\Models\User::where('role', 'satker')->where('satker_id', $tujuan_satker_id)->pluck('id')->toArray();
        $namaTujuanEmail = 'Satker Tujuan';
        
    } elseif ($inputTipe == 'pegawai') {
        $status = 'di_satker'; 
        $tujuan_user_id = $validatedData['tujuan_user_id'];
        $statusAksi = 'Surat dikirim langsung ke Pegawai';
        $targetUserIds = [$tujuan_user_id];
        $namaTujuanEmail = 'Pegawai Terkait';
        
    } elseif ($inputTipe == 'edaran_semua_satker') {
        $status = 'di_satker';
        $statusAksi = 'Surat Edaran dikirim ke semua satker';
        // Opsional: Jika edaran ke semua, ambil semua admin satker
        $targetUserIds = \App\Models\User::where('role', 'satker')->pluck('id')->toArray();
        $namaTujuanEmail = 'Seluruh Satker (Edaran)';
    }

    // 3. SIMPAN SURAT
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
        'surat_id' => $surat->id, 
        'user_id' => Auth::id(), 
        'status_aksi' => 'Input Surat', 
        'catatan' => $statusAksi
    ]);

    if ($inputTipe == 'edaran_semua_satker') {
        $semuaSatkerId = Satker::pluck('id');
        $surat->satkerPenerima()->attach($semuaSatkerId, ['status' => 'terkirim']); 
    }

    // ====================================================================
    // 4. NOTIFIKASI EMAIL (MENGGANTIKAN LOGIKA WA YANG PANJANG)
    // ====================================================================
    if (!empty($targetUserIds)) {
        $tglSurat = \Carbon\Carbon::parse($validatedData['tanggal_surat'])->format('d-m-Y'); 
        $link = route('login');

        $details = [
            'subject'    => '📩 Surat Masuk Baru: ' . $validatedData['perihal'],
            'greeting'   => 'Halo Bapak/Ibu,',
            'body'       => "Sistem e-Surat menginformasikan adanya surat masuk baru dengan rincian sebagai berikut:\n\n" .
                            "Tujuan: {$namaTujuanEmail}\n" .
                            "No. Surat: {$validatedData['nomor_surat']}\n" .
                            "Perihal: {$validatedData['perihal']}\n" .
                            "Pengirim: {$validatedData['surat_dari']}\n" .
                            "Tanggal Surat: {$tglSurat}\n\n" .
                            "Silakan klik tombol di bawah untuk menindaklanjuti.",
            'actiontext' => 'Lihat Surat',
            'actionurl'  => $link,
            'file_url'   => asset('storage/' . $filePath) // Link download langsung
        ];

        // Kirim ke semua target (Email 1 & 2 mereka otomatis ditangani Helper)
        \App\Helpers\EmailHelper::kirimNotif($targetUserIds, $details);
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
    // NOTIFIKASI EMAIL KE ADMIN REKTOR (MENGGANTIKAN WA)
    // =========================================================
    
    // 1. Ambil semua ID Admin Rektor
    $adminRektorIds = \App\Models\User::where('role', 'admin_rektor')->pluck('id')->toArray();

    if (!empty($adminRektorIds)) {
        $tglSurat = $surat->tanggal_surat->format('d-m-Y');
        $link = route('login');

        // 2. Siapkan Detail Email
        $details = [
            'subject'    => 'Pemberitahuan Surat Masuk Rektor: ' . $surat->perihal,
            'greeting'   => 'Halo Tim Admin Rektorat,',
            'body'       => "BAU telah meneruskan surat masuk baru untuk Bapak Rektor/Universitas.\n\n" .
                            "No. Surat: {$surat->nomor_surat}\n" .
                            "Pengirim: {$surat->surat_dari}\n" .
                            "Tanggal Surat: {$tglSurat}\n" .
                            "Perihal: {$surat->perihal}",
            'actiontext' => 'Lihat Surat Masuk',
            'actionurl'  => $link,
            'file_url'   => asset('storage/' . $surat->file_surat) // Menambahkan link download langsung
        ];

        // 3. Kirim via Helper (Otomatis ke Email 1 & Email 2 semua admin rektor)
        \App\Helpers\EmailHelper::kirimNotif($adminRektorIds, $details);
    }

    $route = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
    return redirect()->route($route)->with('success', 'Berhasil diteruskan.');
}

   public function forwardToSatker(Request $request, Surat $surat)
{
    $validStatuses = ['baru_di_bau', 'didisposisi', 'menunngu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];

    if (!in_array($surat->status, $validStatuses)) {
         return back()->with('error', 'Status surat tidak valid untuk diteruskan.');
    }

    $surat->update(['status' => 'di_satker']);

    $tujuanList = [];
    $targetUserIds = []; // Menampung ID User untuk dikirim via Helper

    // 1. LOGIKA IDENTIFIKASI PENERIMA
    if ($surat->disposisis->count() > 0) {
        foreach($surat->disposisis as $d) {
            if ($d->tujuanSatker) {
                $tujuanList[] = $d->tujuanSatker->nama_satker;
                
                // Ambil semua ID admin di satker tersebut
                $ids = User::where('role', 'satker')
                           ->where('satker_id', $d->tujuan_satker_id)
                           ->pluck('id')
                           ->toArray();
                $targetUserIds = array_merge($targetUserIds, $ids);
            }
        }
    } elseif ($surat->tujuan_satker_id) {
        $tujuanList[] = $surat->tujuanSatker->nama_satker;
        
        $ids = User::where('role', 'satker')
                   ->where('satker_id', $surat->tujuan_satker_id)
                   ->pluck('id')
                   ->toArray();
        $targetUserIds = array_merge($targetUserIds, $ids);
    }

    // Bersihkan duplikasi ID jika satu user masuk di beberapa kriteria
    $targetUserIds = array_unique($targetUserIds);

    // 2. KIRIM NOTIFIKASI EMAIL
    if (!empty($targetUserIds)) {
        $tglSurat = $surat->tanggal_surat->format('d-m-Y'); 
        $link = route('login');
        $namaTujuanString = implode(', ', array_unique($tujuanList));

        $details = [
            'subject'    => '📩 Disposisi Surat Baru: ' . $surat->perihal,
            'greeting'   => 'Halo Bapak/Ibu di Satker Tujuan,',
            'body'       => "BAU telah meneruskan disposisi surat untuk ditindaklanjuti oleh unit Anda.\n\n" .
                            "No. Surat: {$surat->nomor_surat}\n" .
                            "Asal Surat: {$surat->surat_dari}\n" .
                            "Perihal: {$surat->perihal}\n" .
                            "Tanggal Surat: {$tglSurat}\n\n" .
                            "Silakan login ke sistem untuk melihat detail instruksi disposisi.",
            'actiontext' => 'Lihat Surat Masuk',
            'actionurl'  => $link,
            'file_url'   => asset('storage/' . $surat->file_surat)
        ];

        \App\Helpers\EmailHelper::kirimNotif($targetUserIds, $details);
    }

    // 3. CATAT RIWAYAT & REDIRECT
    $namaTujuanString = implode(', ', array_unique($tujuanList)) ?: 'Tujuan';
    RiwayatSurat::create([
        'surat_id' => $surat->id, 
        'user_id' => Auth::id(), 
        'status_aksi' => 'Dikirim ke Satker/Penerima', 
        'catatan' => 'Dikirim ke: ' . $namaTujuanString
    ]);

    return redirect()->route('bau.disposisi.index')->with('success', 'Surat berhasil dikirim ke: ' . $namaTujuanString);
}

    public function selesaikanLainnya(Request $request, Surat $surat)
    {
        $surat->update(['status' => 'diarsipkan']);
        RiwayatSurat::create(['surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Selesai (Manual)', 'catatan' => 'Diarsipkan oleh BAU.']);
        return redirect()->route('bau.disposisi.index')->with('success', 'Berhasil ditandai selesai.');
    }

    // HALAMAN DISPOSISI BAU
    public function showDisposisi()
    {
        // Ambil surat yang statusnya 'didisposisi' (artinya sudah dari Rektor)
        // Kita load relasi 'disposisis' untuk melihat instruksi Rektor
        $suratDisposisi = Surat::with(['disposisis.tujuanSatker'])
                            ->where('status', 'didisposisi')
                            ->latest('updated_at') // Urutkan dari yang baru didisposisi
                            ->get();

        return view('bau.disposisi_index', compact('suratDisposisi'));
    }

   // arsip
 public function arsipkan($id)
    {
        try {
            // 1. Cek dulu di tabel Surat (Biasanya Surat Eksternal / Input Manual)
            $surat = \App\Models\Surat::find($id);
            $isSuratMasukTable = true; // Flag untuk menandai sumber tabel

            // 2. Jika tidak ketemu di Surat, cek di SuratKeluar (Surat Internal dari Satker lain)
            if (!$surat) {
                $surat = \App\Models\SuratKeluar::find($id);
                $isSuratMasukTable = false; // Sumber dari tabel surat_keluars
            }

            // 3. Jika masih tidak ketemu di kedua tabel, baru error
            if (!$surat) {
                return redirect()->back()->with('error', 'Gagal arsip: Data surat tidak ditemukan (ID: ' . $id . ')');
            }

            // 4. Update Status Utama (Main Table)
            $surat->update([
                'status' => 'Selesai di BAU' 
            ]);

            // --- [FIX] UPDATE PIVOT JIKA SURAT INTERNAL ---
            if ($surat instanceof \App\Models\SuratKeluar) {
                $bauSatkerId = auth()->user()->satker_id;

                if ($surat->penerimaInternal()->where('satker_id', $bauSatkerId)->exists()) {
                    $surat->penerimaInternal()->updateExistingPivot($bauSatkerId, [
                        'is_read' => 2 
                    ]);
                }
            }
            // ----------------------------------------------

            // 5. Catat Log Riwayat
            // FIX: Hanya buat riwayat di tabel riwayat_surats JIKA surat berasal dari tabel 'surats'
            // Karena tabel riwayat_surats secara database terkunci (Foreign Key) ke tabel 'surats'
            if ($isSuratMasukTable && method_exists($surat, 'riwayats')) {
                $surat->riwayats()->create([
                    'user_id'     => auth()->id(),
                    'status_aksi' => 'Selesai / Diarsipkan BAU',
                    'catatan'     => 'Surat telah diterima dan diarsipkan oleh BAU.'
                ]);
            }

            return redirect()->back()->with('success', 'Surat berhasil diarsipkan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // log untuk BAU
    // File: app/Http/Controllers/Bau/SuratController.php

public function showRiwayatDetailBau($id)
{
    try {
        $listRiwayat = [];
        $nomorSurat = '-';
        $perihal = '-';

        // -----------------------------------------------------------
        // SKENARIO 1: Cek di Tabel SURAT (Eksternal / Manual Input)
        // -----------------------------------------------------------
        $surat = \App\Models\Surat::with(['riwayats.user'])->find($id);

        if ($surat) {
            $nomorSurat = $surat->nomor_surat;
            $perihal = $surat->perihal;

            $parseDate = function($val) {
                try { return $val ? \Carbon\Carbon::parse($val) : null; } catch (\Exception $e) { return null; }
            };

            foreach ($surat->riwayats as $riwayat) {
                $waktu = $parseDate($riwayat->created_at);
                $listRiwayat[] = [
                    'status_aksi' => $riwayat->status_aksi,
                    'catatan'     => $riwayat->catatan,
                    'created_at'  => $waktu ? $waktu->toISOString() : null,
                    'tanggal_f'   => $waktu ? $waktu->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                    'user'        => ['name' => $riwayat->user ? $riwayat->user->name : 'Sistem']
                ];
            }
        } 
        
        // -----------------------------------------------------------
        // SKENARIO 2: Jika tidak ada di Surat, Cek SURAT KELUAR (Internal Satker)
        // -----------------------------------------------------------
        else {
            // [PERBAIKAN] Gunakan 'user.satker' karena relasi satker ada via user
            $suratInternal = \App\Models\SuratKeluar::with(['user.satker', 'penerimaInternal'])->find($id);

            if ($suratInternal) {
                $nomorSurat = $suratInternal->nomor_surat;
                $perihal = $suratInternal->perihal;

                // [PERBAIKAN] Ambil nama satker via User
                $namaPengirim = 'Satker Pengirim';
                if ($suratInternal->user && $suratInternal->user->satker) {
                    $namaPengirim = $suratInternal->user->satker->nama_satker;
                }

                $parseDate = function($val) {
                    try { return $val ? \Carbon\Carbon::parse($val) : null; } catch (\Exception $e) { return null; }
                };

                // A. Log Masuk (Saat Satker mengirim)
                $tglKirim = $parseDate($suratInternal->created_at);
                $listRiwayat[] = [
                    'status_aksi' => 'Surat Masuk (Internal)',
                    'catatan'     => 'Surat diterima dari ' . $namaPengirim,
                    'created_at'  => $tglKirim ? $tglKirim->toISOString() : null,
                    'tanggal_f'   => $tglKirim ? $tglKirim->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                    'user'        => ['name' => 'Admin ' . $namaPengirim]
                ];

                // B. Log Aktivitas BAU (Cek Pivot BAU)
                $mySatkerId = auth()->user()->satker_id; 
                $myPivot = $suratInternal->penerimaInternal->where('id', $mySatkerId)->first();

                if ($myPivot && $myPivot->pivot) {
                    $status = $myPivot->pivot->is_read; 
                    $waktuRaw = $myPivot->pivot->updated_at ?? $myPivot->pivot->created_at;
                    $waktu = $parseDate($waktuRaw);
                    
                    $tglFmt = $waktu ? $waktu->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-';
                    $tglISO = $waktu ? $waktu->toISOString() : null;

                    if ($status == 2) {
                         $listRiwayat[] = [
                            'status_aksi' => 'Diarsipkan',
                            'catatan'     => 'Surat telah diterima dan diarsipkan oleh BAU.',
                            'created_at'  => $tglISO,
                            'tanggal_f'   => $tglFmt,
                            'user'        => ['name' => auth()->user()->name]
                        ];
                    } elseif ($status == 1) {
                         $listRiwayat[] = [
                            'status_aksi' => 'Dibaca',
                            'catatan'     => 'Surat telah dibaca.',
                            'created_at'  => $tglISO,
                            'tanggal_f'   => $tglFmt,
                            'user'        => ['name' => auth()->user()->name]
                        ];
                    }
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Data surat tidak ditemukan (ID: '.$id.')'], 404);
            }
        }

        // Sorting
        usort($listRiwayat, function($a, $b) {
            $t1 = $a['created_at'] ? strtotime($a['created_at']) : 0;
            $t2 = $b['created_at'] ? strtotime($b['created_at']) : 0;
            return $t1 <=> $t2;
        });

        return response()->json([
            'status'      => 'success',
            'nomor_surat' => $nomorSurat,
            'perihal'     => $perihal,
            'riwayats'    => $listRiwayat
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error', 
            'message' => 'Server Error: ' . $e->getMessage()
        ], 500);
    }
}
}