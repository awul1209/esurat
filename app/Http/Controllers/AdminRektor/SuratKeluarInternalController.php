<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuratKeluar;
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

    public function create()
    {
        // Ambil semua satker kecuali satker admin rektor sendiri (opsional)
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
            // 2. Upload File
            $path = $request->file('file_surat')->store('surat_keluar', 'public');

            // 3. Simpan Data Surat
            $surat = SuratKeluar::create([
                'user_id'       => $user->id,
                'nomor_surat'   => $request->nomor_surat,
                'tanggal_surat' => $request->tanggal_surat,
                'perihal'       => $request->perihal,
                // 'isi_ringkas'   => $request->isi_ringkas, // Removed as per previous request
                'file_surat'    => $path,
                'tipe_kirim'    => 'internal',
                'status'        => 'terkirim'
            ]);

            // 4. Simpan Relasi (Pivot) ke Banyak Satker
            $surat->penerimaInternal()->attach($request->tujuan_satker_ids);

            // 5. LOGIKA NOTIFIKASI WA MASSAL (UPDATED)
            $this->kirimNotifikasiWa($surat, $request->tujuan_satker_ids);

            DB::commit();
            return redirect()->route('adminrektor.surat-keluar-internal.index')
                             ->with('success', 'Surat internal berhasil dikirim ke Satker tujuan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengirim surat: ' . $e->getMessage());
        }
    }

    // --- HELPER NOTIFIKASI WA (UPDATED TO MATCH SATKER LOGIC) ---
    private function kirimNotifikasiWa($surat, $satkerIds)
    {
        // 1. Ambil data pengirim (Admin Rektor)
        // Jika Admin Rektor punya satker, ambil namanya. Jika tidak, pakai nama user.
        $userPengirim = Auth::user();
        $namaPengirim = $userPengirim->satker->nama_satker ?? 'Rektorat / Admin Rektor'; 
        
        $tglSurat = \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y');
        $link = route('login'); // Link ke aplikasi

        // 2. Cari User Penerima (Admin/Operator di Satker Tujuan)
        // Filter user yang role-nya relevan (misal: satker, bau, bapsi, admin)
        $rolePenerima = ['satker', 'bau', 'bapsi', 'admin'];
        
        $penerimaNotif = User::whereIn('satker_id', $satkerIds)
                             ->whereIn('role', $rolePenerima)
                             ->whereNotNull('no_hp')
                             ->get();

        foreach ($penerimaNotif as $penerima) {
            
            // 3. Pecah Nomor HP (Support Multiple Numbers Separated by Comma)
            $daftarNomor = explode(',', $penerima->no_hp);
            $namaTujuan = $penerima->satker->nama_satker ?? 'Satker Tujuan';

            // 4. Susun Pesan
            $pesan = 
"📩 *Notifikasi Surat Masuk Internal (Dari Rektorat)*

Satker Tujuan : {$namaTujuan}
Tanggal Surat : {$tglSurat}
No. Surat     : {$surat->nomor_surat}
Perihal       : {$surat->perihal}
Pengirim      : {$namaPengirim}

Silakan cek sistem e-Surat untuk detail dan disposisi: {$link}";

            // 5. Loop Kirim ke Setiap Nomor
            foreach ($daftarNomor as $nomor) {
                // Bersihkan nomor
                $nomorBersih = trim($nomor);
                $nomorBersih = preg_replace('/[^0-9]/', '', $nomorBersih);

                if (!empty($nomorBersih)) {
                    try {
                        // Panggil Service WA Anda
                        WaService::send($nomorBersih, $pesan);
                    } catch (\Exception $e) {
                        // Log error jika perlu, tapi jangan hentikan proses transaksi
                        // \Log::error("Gagal kirim WA ke $nomorBersih: " . $e->getMessage());
                    }
                }
            }
        }
    }


    // Fungsi Export Excel (Stream)
    public function export(Request $request)
    {
        $query = SuratKeluar::with(['penerimaInternal'])
                    ->where('user_id', Auth::id())
                    ->where('tipe_kirim', 'internal');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        $data = $query->latest()->get();
        $fileName = 'Surat_Keluar_Internal_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM Fix
            fputcsv($file, ['No', 'No Surat', 'Tanggal', 'Perihal', 'Tujuan Satker', 'File Link']);

            foreach ($data as $index => $row) {
                // Gabungkan nama satker tujuan dipisah koma
                $tujuan = $row->penerimaInternal->pluck('nama_satker')->implode(', ');
                
                fputcsv($file, [
                    $index + 1,
                    $row->nomor_surat,
                    $row->tanggal_surat->format('d-m-Y'),
                    $row->perihal,
                    $tujuan,
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
}