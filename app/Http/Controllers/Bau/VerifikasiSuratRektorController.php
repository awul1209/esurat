<?php

namespace App\Http\Controllers\Bau;


use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VerifikasiSuratRektorController extends Controller
{
    /**
     * Menampilkan daftar permohonan surat dari Rektor (Internal & Eksternal)
     */
  public function index(Request $request)
{
    $query = SuratKeluar::with('user')
        ->whereHas('user', function($q) {
            $q->where('role', 'admin_rektor');
        })
        ->where('tipe_kirim', 'eksternal')
        // PERBAIKAN: Ambil status pending DAN proses agar tetap muncul di daftar kerja
        ->whereIn('status', ['pending', 'proses']); 

    // Filter Tanggal
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    $suratRektor = $query->latest()->get();

    return view('bau.verifikasi_rektor.index', compact('suratRektor'));
}

/**
 * Fungsi Log Riwayat untuk Modal
 */
public function getLog($id)
{
    try {
        $surat = SuratKeluar::findOrFail($id);
        $listRiwayat = [];

        $listRiwayat[] = [
            'status_aksi' => 'Permohonan Masuk',
            'catatan'     => 'Surat dari Rektor menunggu verifikasi BAU.',
            'created_at'  => $surat->created_at->toISOString(),
            'user_name'   => $surat->user->name ?? 'Admin Rektor'
        ];

        return response()->json([
            'nomor_surat' => $surat->nomor_surat,
            'riwayats'    => $listRiwayat
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * Logika klik tombol "Teruskan"
     */
    public function teruskan($id)
    {
        $surat = SuratKeluar::findOrFail($id);

        DB::beginTransaction();
        try {
            // Update status dan jam update (sebagai jam diteruskan)
            $surat->update([
                'status' => 'terkirim',
                'updated_at' => Carbon::now() 
            ]);

            // Jika ini surat Internal, pastikan pivot tabel juga terupdate atau terpicu
            // sehingga satker tujuan bisa melihatnya di dashboard mereka.

            DB::commit();
            return back()->with('success', 'Surat berhasil diteruskan. Jam tercatat: ' . Carbon::now()->isoFormat('HH.mm'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal meneruskan surat: ' . $e->getMessage());
        }
    }

    // export
    public function export(Request $request)
{
    // 1. Query Data Permohonan Rektor (Eksternal & Pending)
    $query = SuratKeluar::with('user')
        ->whereHas('user', function($q) {
            $q->where('role', 'admin_rektor');
        })
        ->where('tipe_kirim', 'eksternal')
        ->where('status', 'pending');

    // Filter Tanggal jika diisi
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    $data = $query->latest()->get();
    $fileName = 'Antrean_Surat_Rektor_Eksternal_' . date('Y-m-d_H-i') . '.csv';

    // 2. Header Browser
    $headers = [
        "Content-type" => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    // 3. Callback Stream Data
    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');
        
        // BOM untuk Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
        
        // Header Kolom
        fputcsv($file, ['No', 'Nomor Surat', 'Perihal', 'Tujuan Eksternal', 'Tgl Surat', 'Waktu Masuk']);

        foreach ($data as $index => $row) {
            fputcsv($file, [
                $index + 1,
                $row->nomor_surat,
                $row->perihal,
                $row->tujuan_luar,
                \Carbon\Carbon::parse($row->tanggal_surat)->format('d-m-Y'),
                $row->created_at->format('d-m-Y H:i') . ' WIB'
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function proses($id)
{
    $surat = SuratKeluar::findOrFail($id);
    $surat->update([
        'status' => 'proses',
        'updated_at' => now() // Mencatat waktu mulai diproses
    ]);
    return back()->with('success', 'Surat sedang diproses.');
}

public function selesai($id)
{
    $surat = SuratKeluar::findOrFail($id);
    $surat->update([
        'status' => 'selesai',
        'updated_at' => now() // Mencatat waktu selesai/dikirim
    ]);
    return back()->with('success', 'Surat telah selesai dikirim dan diarsipkan.');
}
}