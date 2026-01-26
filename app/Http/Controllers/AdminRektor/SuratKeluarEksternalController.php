<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuratKeluar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuratKeluarEksternalController extends Controller
{
    /**
     * Menampilkan halaman index (Daftar Surat).
     */
    public function index(Request $request)
    {
        $query = SuratKeluar::where('user_id', Auth::id())
                    ->where('tipe_kirim', 'eksternal'); // Filter khusus Eksternal

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // Ambil data (menggunakan get() agar DataTables di view bekerja maksimal)
        $suratKeluar = $query->latest()->get();

        return view('admin_rektor.surat_keluar_eksternal.index', compact('suratKeluar'));
    }

    /**
     * Menampilkan form buat surat baru.
     */
    public function create()
    {
        return view('admin_rektor.surat_keluar_eksternal.create');
    }

    /**
     * Menyimpan data surat ke database (Tanpa Notifikasi WA).
     */
 public function store(Request $request)
{
    $request->validate([
        'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'tanggal_surat' => 'required|date',
        'perihal'       => 'required|string|max:255',
        'tujuan_luar'   => 'required|string|max:255',
        'via'           => 'required|string',
        'file_surat'    => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
    ]);

    DB::beginTransaction();
    try {
        $path = $request->file('file_surat')->store('sk_rektor_eksternal', 'public');

        $surat = SuratKeluar::create([
            'user_id'          => Auth::id(),
            'nomor_surat'      => $request->nomor_surat,
            'tanggal_surat'    => $request->tanggal_surat,
            'perihal'          => $request->perihal,
            'tujuan_luar'      => $request->tujuan_luar, 
            'tipe_kirim'       => 'eksternal',
            'file_surat'       => $path,
            'status'           => 'pending', 
            'via'              => $request->via,
        ]);

        // =========================================================
        // NOTIFIKASI EMAIL KE BAU
        // =========================================================
        $bauUserIds = \App\Models\User::where('role', 'bau')->pluck('id')->toArray();

        if (!empty($bauUserIds)) {
            $details = [
                'subject'    => '📄 Pengajuan Surat Eksternal Rektor: ' . $request->perihal,
                'greeting'   => 'Yth. Tim BAU,',
                'body'       => "Admin Rektorat telah mengajukan surat keluar eksternal baru yang memerlukan tindakan Anda untuk diteruskan ke pihak luar.\n\n" .
                                "No. Surat: {$request->nomor_surat}\n" .
                                "Tujuan: {$request->tujuan_luar}\n" .
                                "Via: " . ucfirst($request->via) . "\n" .
                                "Perihal: {$request->perihal}",
                'actiontext' => 'Proses di BAU',
                'actionurl'  => route('login'), // Arahkan ke dashboard BAU
                'file_url'   => asset('storage/' . $path)
            ];

            \App\Helpers\EmailHelper::kirimNotif($bauUserIds, $details);
        }
        // =========================================================

        DB::commit();
        return redirect()->route('adminrektor.surat-keluar-eksternal.index')
                         ->with('success', 'Surat berhasil diajukan ke BAU untuk diteruskan.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}

    /**
     * Menampilkan form edit.
     */
    public function edit($id)
    {
        $surat = SuratKeluar::where('tipe_kirim', 'eksternal')->findOrFail($id);
        return view('admin_rektor.surat_keluar_eksternal.edit', compact('surat'));
    }

    /**
     * Update data surat.
     */
    public function update(Request $request, $id)
    {
        $surat = SuratKeluar::where('tipe_kirim', 'eksternal')->findOrFail($id);

        $request->validate([
            'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat,'.$id,
            'tanggal_surat' => 'required|date',
            'perihal'       => 'required|string|max:255',
            'tujuan_luar'   => 'required|string|max:255',
            'file_surat'    => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // Cek Upload File Baru
            if ($request->hasFile('file_surat')) {
                // Hapus file lama
                if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                    Storage::disk('public')->delete($surat->file_surat);
                }
                $surat->file_surat = $request->file('file_surat')->store('surat_keluar_eksternal', 'public');
            }

            // Update Data
            $surat->update([
                'nomor_surat'   => $request->nomor_surat,
                'tanggal_surat' => $request->tanggal_surat,
                'perihal'       => $request->perihal,
                'tujuan_luar'   => $request->tujuan_luar,
            ]);

            DB::commit();
            return redirect()->route('adminrektor.surat-keluar-eksternal.index')
                             ->with('success', 'Data surat eksternal diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Hapus surat.
     */
    public function destroy($id)
    {
        $surat = SuratKeluar::where('tipe_kirim', 'eksternal')->findOrFail($id);

        if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
            Storage::disk('public')->delete($surat->file_surat);
        }

        $surat->delete();

        return redirect()->route('adminrektor.surat-keluar-eksternal.index')
                         ->with('success', 'Surat eksternal berhasil dihapus.');
    }

    /**
     * Export data ke Excel/CSV
     */
    public function export(Request $request)
    {
        // 1. Query Data (Sama dengan Index)
        $query = SuratKeluar::where('user_id', Auth::id())
                    ->where('tipe_kirim', 'eksternal');

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        $data = $query->latest()->get();
        $fileName = 'Surat_Keluar_Eksternal_' . date('Y-m-d_H-i') . '.csv';

        // 2. Header Browser agar download file
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
            
            // Fix untuk Excel agar bisa baca karakter khusus (BOM)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            
            // Header Kolom Excel
            fputcsv($file, ['No', 'No Surat', 'Tanggal Surat', 'Perihal', 'Tujuan Eksternal', 'Link File']);

            foreach ($data as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->nomor_surat,
                    \Carbon\Carbon::parse($row->tanggal_surat)->format('d-m-Y'),
                    $row->perihal,
                    $row->tujuan_luar, // Ambil dari kolom manual
                    $row->file_surat ? url('storage/' . $row->file_surat) : '-'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
 * Mengambil data riwayat log untuk Modal di Rektor.
 */
public function getLog($id) {
    $surat = SuratKeluar::with('user')->findOrFail($id);
    $history = [];

    // Step 1: Input
    $history[] = [
        'status_aksi' => 'Permohonan Dibuat',
        'catatan'     => "Dikirim via: " . ($surat->via ?? '-'),
        // Kita kirim string yang sudah diformat oleh Carbon
        'tanggal_f'   => $surat->created_at->isoFormat('D MMMM Y, HH.mm') . ' WIB',
        'user_name'   => $surat->user->name ?? 'Admin Rektor'
    ];

    // Step 2: Proses
    if (in_array($surat->status, ['proses', 'selesai'])) {
        $history[] = [
            'status_aksi' => 'Diproses BAU',
            'catatan'     => 'BAU sedang menyiapkan dokumen dan pengiriman.',
            'tanggal_f'   => $surat->updated_at->isoFormat('D MMMM Y, HH.mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];
    }

    // Step 3: Selesai
    if ($surat->status == 'selesai') {
        $history[] = [
            'status_aksi' => 'Selesai / Diarsipkan',
            'catatan'     => 'Surat sudah dikirim ke tujuan dan diarsipkan.',
            'tanggal_f'   => $surat->updated_at->isoFormat('D MMMM Y, HH.mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];
    }

    return response()->json(['riwayats' => $history]);
}
}