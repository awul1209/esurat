<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuratKeluar;
use Carbon\Carbon;
use App\Models\Satker;
use App\Models\User; // Untuk ambil no_hp
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\WablasTrait; // Asumsi Anda punya Helper/Trait untuk WA
use App\Services\WaService;

class SuratKeluarInternalController extends Controller
{
    // Gunakan Trait WA jika ada, atau panggil service WA Anda
    // use WablasTrait; 

   public function index(Request $request)
{
    $query = SuratKeluar::with(['penerimaInternal'])
                ->where('user_id', Auth::id())
                ->where('tipe_kirim', 'internal');

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    // PENCARIAN (Opsional, karena DataTables sudah punya search sendiri)
    // Tapi jika ingin search manual tetap ada:
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('perihal', 'like', "%{$search}%")
              ->orWhere('nomor_surat', 'like', "%{$search}%");
        });
    }

    // PERBAIKAN: Gunakan get() dan latest()
    // get(): Ambil semua data agar DataTables bisa mengatur halaman 5, 10, dst.
    // latest(): Agar yang baru diinput muncul paling atas.
    $suratKeluar = $query->latest()->get(); 

    return view('admin_rektor.surat_keluar_internal.index', compact('suratKeluar'));
}

// Contoh di AdminRektor/SuratKeluarInternalController.php

public function create()
{
    $satkers = Satker::orderBy('nama_satker', 'asc')->get();
    return view('admin_rektor.surat_keluar_internal.create', compact('satkers'));
}

  public function store(Request $request)
{
    // 1. Validasi
    $request->validate([
        'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'tanggal_surat'     => 'required|date',
        'perihal'           => 'required|string|max:255',
        'file_surat'        => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        'tujuan_satker_ids' => 'required|array|min:1', 
        'tujuan_satker_ids.*' => 'exists:satkers,id',
    ], [
        'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Harap gunakan nomor lain.',
        'tujuan_satker_ids.required' => 'Pilih setidaknya satu tujuan surat.'
    ]);

    $user = Auth::user();
    
    DB::beginTransaction();
    try {
        // 2. Upload File (Disimpan di folder surat_keluar)
        $path = $request->file('file_surat')->store('sk_rektor_internal', 'public');

        // 3. Simpan Data Surat
        $surat = SuratKeluar::create([
            'user_id'       => $user->id,
            'nomor_surat'   => $request->nomor_surat,
            'tanggal_surat' => $request->tanggal_surat,
            'perihal'       => $request->perihal,
            'file_surat'    => $path,
            'tipe_kirim'    => 'internal',
            'status'        => 'pending' // STATUS AWAL PENDING (Menunggu BAU)
        ]);

        // 4. Simpan Relasi (Pivot) ke Banyak Satker
        $surat->penerimaInternal()->attach($request->tujuan_satker_ids);

        // ====================================================================
        // 5. NOTIFIKASI EMAIL KE BAU (VERIFIKASI INTERNAL)
        // ====================================================================
        $bauUserIds = \App\Models\User::where('role', 'bau')->pluck('id')->toArray();

        if (!empty($bauUserIds)) {
            $tglSurat = \Carbon\Carbon::parse($request->tanggal_surat)->format('d-m-Y');
            
            // Mengambil nama-nama satker tujuan untuk diinformasikan ke BAU
            $namaSatkers = \App\Models\Satker::whereIn('id', $request->tujuan_satker_ids)
                            ->pluck('nama_satker')
                            ->toArray();
            $tujuanStr = implode(', ', $namaSatkers);

            $details = [
                'subject'    => '🔔 Verifikasi Surat Internal Rektorat: ' . $request->perihal,
                'greeting'   => 'Yth. Tim BAU,',
                'body'       => "Terdapat pengajuan surat keluar INTERNAL dari Rektorat yang memerlukan verifikasi dan penerusan oleh BAU.\n\n" .
                                "No. Surat: {$request->nomor_surat}\n" .
                                "Tujuan Satker: {$tujuanStr}\n" .
                                "Tanggal Surat: {$tglSurat}\n" .
                                "Perihal: {$request->perihal}\n\n" .
                                "Mohon segera diproses agar surat dapat diterima oleh Satker tujuan.",
                'actiontext' => 'Proses Verifikasi BAU',
                'actionurl'  => route('login'), 
                'file_url'   => asset('storage/' . $path)
            ];

            \App\Helpers\EmailHelper::kirimNotif($bauUserIds, $details);
        }
        // ====================================================================

        DB::commit();
        
        return redirect()->route('adminrektor.surat-keluar-internal.index')
                         ->with('success', 'Surat internal berhasil diajukan. Menunggu verifikasi BAU.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal mengajukan surat: ' . $e->getMessage());
    }
}

   


    // Fungsi Export Excel (Stream)
   public function export(Request $request)
{
    // 1. Tambahkan eager loading 'riwayats.penerima' untuk mengambil data tujuan pegawai
    $query = \App\Models\SuratKeluar::with(['penerimaInternal', 'riwayats.penerima'])
                ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->where('tipe_kirim', 'internal');

    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    $data = $query->latest()->get();
    $fileName = 'Surat_Keluar_Internal_Rektor_' . date('Y-m-d_H-i') . '.csv';

    $headers = [
        "Content-type" => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM Fix untuk Excel
        fputcsv($file, ['No', 'No Surat', 'Tanggal', 'Perihal', 'Tujuan (Satker/Pegawai)', 'Status', 'File Link']);

        foreach ($data as $index => $row) {
            // 2. Logika Penentuan Tujuan yang Fleksibel
            $tujuan = '';

            // Cek jika tujuan ke Satker (Antar Satker)
            if ($row->penerimaInternal->isNotEmpty()) {
                $tujuan = $row->penerimaInternal->pluck('nama_satker')->implode(', ');
            } 
            // Cek jika tujuan langsung ke Pegawai (Direct/Personal) melalui riwayat_surats
            elseif ($row->riwayats->whereNotNull('penerima_id')->isNotEmpty()) {
                $tujuan = $row->riwayats->whereNotNull('penerima_id')
                            ->pluck('penerima.name')
                            ->unique()
                            ->implode(', ');
            } 
            // Fallback ke tujuan_surat jika teks manual diisi
            else {
                $tujuan = $row->tujuan_surat ?? '-';
            }
            
            fputcsv($file, [
                $index + 1,
                $row->nomor_surat,
                // Gunakan Carbon parse jika kolom bukan objek date otomatis
                \Carbon\Carbon::parse($row->tanggal_surat)->format('d-m-Y'),
                $row->perihal,
                $tujuan,
                $row->status,
                url('storage/' . $row->file_surat)
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

   

    // Tambahkan di dalam class SuratKeluarInternalController

public function edit($id)
{
    $surat = SuratKeluar::with('penerimaInternal')->findOrFail($id);
    $satkers = Satker::orderBy('nama_satker', 'asc')->get();
    
    // Ambil ID satker yang sudah dipilih sebelumnya untuk auto-select
    $selectedSatkers = $surat->penerimaInternal->pluck('id')->toArray();

    return view('admin_rektor.surat_keluar_internal.edit', compact('surat', 'satkers', 'selectedSatkers'));
}

public function update(Request $request, $id)
{
    $surat = SuratKeluar::findOrFail($id);

    $request->validate([
        // Ignore ID saat validasi unique update
        'nomor_surat' => 'required|string|max:255|unique:surat_keluars,nomor_surat,'.$id,
        'tanggal_surat' => 'required|date',
        'perihal' => 'required|string',
        'file_surat' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120', // Nullable karena edit
        'tujuan_satker_ids' => 'required|array',
        'tujuan_satker_ids.*' => 'exists:satkers,id',
    ]);

    DB::beginTransaction();
    try {
        // 1. Cek jika ada file baru
        if ($request->hasFile('file_surat')) {
            // Hapus file lama jika ada
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            // Upload baru
            $filePath = $request->file('file_surat')->store('surat_keluar', 'public');
            $surat->file_surat = $filePath;
        }

        // 2. Update Data Text
        $surat->update([
            'nomor_surat' => $request->nomor_surat,
            'tanggal_surat' => $request->tanggal_surat,
            'perihal' => $request->perihal,
            'isi_ringkas' => $request->isi_ringkas,
            // file_surat sudah dihandle diatas
        ]);

        // 3. Update Relasi Satker (Sync = hapus yang lama, masukkan yang baru)
        $surat->penerimaInternal()->sync($request->tujuan_satker_ids);

        DB::commit();
        return redirect()->route('adminrektor.surat-keluar-internal.index')
                         ->with('success', 'Data surat berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal update: ' . $e->getMessage());
    }
}

public function destroy($id)
{
    $surat = SuratKeluar::findOrFail($id);
    
    // Hapus File
    if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
        Storage::disk('public')->delete($surat->file_surat);
    }

    // Detach Pivot (Relasi) & Hapus Record
    $surat->penerimaInternal()->detach();
    $surat->delete();

    return redirect()->route('adminrektor.surat-keluar-internal.index')
                     ->with('success', 'Surat berhasil dihapus.');
}

public function getRiwayat($id)
{
    try {
        $suratKeluar = SuratKeluar::with('penerimaInternal')->findOrFail($id);
        $listRiwayat = [];

        $parseDate = function($val) {
            try { return $val ? Carbon::parse($val) : null; } catch (\Exception $e) { return null; }
        };

        // --- LOG 1: PENGAJUAN OLEH REKTOR ---
        $tglInput = $parseDate($suratKeluar->created_at);
        $listRiwayat[] = [
            'status_aksi' => 'Surat Diajukan',
            'catatan'     => 'Admin Rektor mengajukan surat internal. Menunggu verifikasi BAU.',
            'created_at'  => $tglInput ? $tglInput->toISOString() : null,
            'tanggal_f'   => $tglInput ? $tglInput->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
            'user'        => ['name' => 'Admin Rektor'] 
        ];

        // --- LOG 2: VERIFIKASI & PENERUSAN OLEH BAU ---
        // Log ini muncul jika status sudah 'proses' atau 'selesai'
        if ($suratKeluar->status !== 'pending') {
            $tglVerif = $parseDate($suratKeluar->updated_at); // Asumsi updated_at berubah saat BAU aksi
            
            $listRiwayat[] = [
                'status_aksi' => $suratKeluar->status == 'selesai' ? 'Diteruskan ke Satker' : 'Sedang Diverifikasi',
                'catatan'     => $suratKeluar->status == 'selesai' 
                                 ? 'Admin BAU telah memverifikasi dan meneruskan surat ke Satker tujuan.' 
                                 : 'Admin BAU sedang memverifikasi dokumen.',
                'created_at'  => $tglVerif ? $tglVerif->toISOString() : null,
                'tanggal_f'   => $tglVerif ? $tglVerif->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                'user'        => ['name' => 'Admin BAU']
            ];
        }

        // --- LOG 3: AKTIVITAS DI SISI SATKER (DIBACA/ARSIP) ---
        if ($suratKeluar->penerimaInternal->isNotEmpty()) {
            foreach ($suratKeluar->penerimaInternal as $penerima) {
                if (!$penerima->pivot) continue;
                
                $status = $penerima->pivot->is_read ?? 0;
                $rawTime = $penerima->pivot->updated_at;
                $waktu = $parseDate($rawTime);
                
                if ($status > 0) { // Hanya muncul jika sudah dibaca (1) atau diarsip (2)
                    $listRiwayat[] = [
                        'status_aksi' => $status == 2 ? 'Diterima & Diarsipkan' : 'Dibaca Satker',
                        'catatan'     => ($status == 2 ? 'Selesai diarsip oleh ' : 'Surat telah dibaca oleh ') . $penerima->nama_satker,
                        'created_at'  => $waktu ? $waktu->toISOString() : null,
                        'tanggal_f'   => $waktu ? $waktu->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                        'user'        => ['name' => 'Admin ' . $penerima->nama_satker]
                    ];
                }
            }
        }

        // Sorting agar urutan waktu benar (Tertua ke Terbaru)
        usort($listRiwayat, function($a, $b) {
            return strtotime($a['created_at']) <=> strtotime($b['created_at']);
        });

        return response()->json([
            'status'      => 'success',
            'nomor_surat' => $suratKeluar->nomor_surat,
            'riwayats'    => $listRiwayat
        ]);

    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
}