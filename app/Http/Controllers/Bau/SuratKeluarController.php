<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SuratKeluar;
use App\Models\Surat;        
use App\Models\Satker;       
use App\Models\RiwayatSurat; 
use App\Models\User;         
use App\Services\WaService;
use Carbon\Carbon; // <--- PENTING UNTUK FORMAT TANGGAL

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;   
use Illuminate\Validation\Rule;

class SuratKeluarController extends Controller
{
    /**
     * Menampilkan Surat Keluar EKSTERNAL
     */
public function indexInternal(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    
    // Ambil ID User yang sedang login (BAU)
    $userId = \Illuminate\Support\Facades\Auth::id(); 

    $query = SuratKeluar::where('tipe_kirim', 'internal')
        ->where('user_id', $userId) // <--- TAMBAHKAN INI (Filter Pengirim)
        ->with('penerimaInternal'); 

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $suratKeluars = $query->latest()->get();

    return view('bau.surat_keluar.index', compact('suratKeluars'));
}

public function indexEksternal(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $query = SuratKeluar::where('tipe_kirim', 'eksternal');

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $suratKeluars = $query->latest()->get();

    return view('bau.surat_keluar.index', compact('suratKeluars'));
}

public function exportExcel(Request $request)
{
    $tipe = $request->input('type'); // 'internal' atau 'eksternal'
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // Query sesuai Tipe
    $query = SuratKeluar::where('tipe_kirim', $tipe)->with('penerimaInternal');

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $data = $query->latest()->get();

    // Generate CSV
    $filename = "Surat_Keluar_" . ucfirst($tipe) . "_" . date('Ymd_His') . ".csv";
    
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columns = ['No', 'Nomor Surat', 'Perihal', 'Tujuan', 'Tanggal Surat', 'Link File'];

    $callback = function() use($data, $columns) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns); // Header

        foreach ($data as $index => $item) {
            
            // Siapkan Text Tujuan
            $tujuanText = $item->tujuan_surat;
            if (empty($tujuanText) && $item->penerimaInternal->count() > 0) {
                $tujuanText = $item->penerimaInternal->pluck('nama_satker')->implode(', ');
            }

            $link = $item->file_surat ? asset('storage/' . $item->file_surat) : '-';
            
            fputcsv($file, [
                $index + 1,
                $item->nomor_surat,
                $item->perihal,
                $tujuanText,
                \Carbon\Carbon::parse($item->tanggal_surat)->format('d-m-Y'),
                $link
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * Form Buat Surat
     */
    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        return view('bau.surat_keluar.create', compact('daftarSatker'));
    }

    /**
     * Store + Notif WA (FORMAT DIPERBAIKI)
     */
  public function store(Request $request)
    {
        // 1. Validasi dengan Pesan Bahasa Indonesia
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nomor_surat'   => 'required|unique:surat_keluars,nomor_surat',
            'perihal'       => 'required',
            'tanggal_surat' => 'required|date',
            'file_surat'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ], [
            'nomor_surat.required'   => 'Nomor surat wajib diisi.',
            'nomor_surat.unique'     => 'Nomor surat ini sudah terdaftar di database.',
            'perihal.required'       => 'Perihal surat wajib diisi.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
            'file_surat.required'    => 'File surat wajib diupload.',
            'file_surat.mimes'       => 'Format file harus berupa: PDF, JPG, JPEG, atau PNG.',
            'file_surat.max'         => 'Ukuran file terlalu besar (Maksimal 5MB).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput(); 
        }

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-keluar', 'public');

        DB::transaction(function() use ($request, $user, $path) {
            
            // =================================================================
            // A. PILAH TUJUAN (FIX: CEK TIPE DULU AGAR TIDAK EROR SAAT EKSTERNAL)
            // =================================================================
            $satkerIds = [];     
            $targetRektor = [];  

            if ($request->tipe_kirim == 'internal') {
                // Ambil dari dropdown multi-select (tambahkan ?? [] agar aman jika kosong)
                $inputTujuan = $request->tujuan_satker_ids ?? [];
                
                foreach($inputTujuan as $val) {
                    if ($val == 'rektor' || $val == 'universitas') {
                        $targetRektor[] = $val;
                    } elseif (is_numeric($val)) {
                        $satkerIds[] = $val;
                    }
                }
            }

            // =================================================================
            // B. SIMPAN ARSIP SURAT KELUAR
            // =================================================================
            $displayTujuan = [];

            // 1. Text Internal (Rektor/Univ)
            if (!empty($targetRektor)) {
                $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetRektor));
            }
            
            // 2. Text Eksternal (Ambil dari input text biasa)
            if ($request->tipe_kirim == 'eksternal' && $request->tujuan_luar) {
                $displayTujuan[] = $request->tujuan_luar;
            }
            
            $suratKeluar = SuratKeluar::create([
                'user_id'          => $user->id,
                'tipe_kirim'       => $request->tipe_kirim, 
                'nomor_surat'      => $request->nomor_surat,
                'perihal'          => $request->perihal,
                'tanggal_surat'    => $request->tanggal_surat,
                'tujuan_satker_id' => null, 
                'tujuan_surat'     => !empty($displayTujuan) ? implode(', ', $displayTujuan) : null,
                'file_surat'       => $path,
            ]);

            // Data Umum untuk Notifikasi
            $tglSurat = \Carbon\Carbon::parse($request->tanggal_surat)->format('d-m-Y');
            $link = route('login');
            $namaPengirim = "Biro Administrasi Umum (BAU)"; 

            // =================================================================
            // SKENARIO 1: KIRIM KE SATKER LAIN (BAU -> SATKER)
            // =================================================================
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->attach($satkerIds);

                // KIRIM NOTIFIKASI WA
                try {
                    $penerimaSatkers = User::with('satker')
                                           ->whereIn('satker_id', $satkerIds)
                                           ->where('role', 'satker')
                                           ->get();

                    foreach ($penerimaSatkers as $penerima) {
                        if ($penerima->no_hp) {
                            $namaTujuan = $penerima->satker->nama_satker ?? 'Satker Tujuan';
                            $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : {$namaTujuan}
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                            
                            WaService::send($penerima->no_hp, $pesan);
                        }
                    }
                } catch (\Exception $e) {}
            }

            // =================================================================
            // SKENARIO 2: KIRIM KE REKTOR (BAU -> REKTOR)
            // =================================================================
            foreach ($targetRektor as $target) {
                
                $tujuanTipe = ($target == 'universitas') ? 'universitas' : 'rektor';
                $statusSurat = 'di_admin_rektor'; 

                $suratMasuk = Surat::create([
                    'surat_dari'       => $namaPengirim, 
                    'tipe_surat'       => 'internal', 
                    'nomor_surat'      => $request->nomor_surat,
                    'tanggal_surat'    => $request->tanggal_surat,
                    'perihal'          => $request->perihal,
                    'no_agenda'        => 'INT-BAU-' . time() . rand(10,99), 
                    'diterima_tanggal' => now(),
                    'sifat'            => 'Biasa',
                    'file_surat'       => $path,
                    'status'           => $statusSurat,
                    'user_id'          => $user->id, 
                    'tujuan_tipe'      => $tujuanTipe,
                    'tujuan_satker_id' => null,
                    'tujuan_user_id'   => null,
                ]);

                RiwayatSurat::create([
                    'surat_id'    => $suratMasuk->id,
                    'user_id'     => $user->id,
                    'status_aksi' => 'Surat Masuk Internal (Dari BAU)',
                    'catatan'     => 'Surat Internal dari BAU, langsung masuk ke Admin Rektor.'
                ]);

                // NOTIFIKASI WA KE ADMIN REKTOR
                try {
                    $adminRektor = User::where('role', 'admin_rektor')->first();
                    if ($adminRektor && $adminRektor->no_hp) {
                        $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : Rektor / Universitas
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                        
                        WaService::send($adminRektor->no_hp, $pesan);
                    }
                } catch (\Exception $e) {}
            }
        });

// --- PERBAIKAN DI SINI (REDIRECT DINAMIS) ---
    $routeTujuan = ($suratKeluar->tipe_kirim == 'internal') 
        ? 'bau.surat-keluar.internal' 
        : 'bau.surat-keluar.eksternal';

    return redirect()->route($routeTujuan)
        ->with('success', 'Data surat keluar berhasil diperbarui.');
    }

    public function edit(SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403, 'Anda tidak diizinkan mengedit surat ini.');
        }
        return view('bau.surat_keluar.edit', compact('suratKeluar'));
    }

    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nomor_surat' => [
                'required', 'string', 'max:255',
                Rule::unique('surat_keluars')->ignore($suratKeluar->id),
            ],
            'tanggal_surat' => 'required|date',
            'tujuan_surat' => 'nullable|string|max:255',
            'perihal' => 'required|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        
        $suratKeluar->nomor_surat = $validated['nomor_surat'];
        $suratKeluar->tanggal_surat = $validated['tanggal_surat'];
        
        if($request->filled('tujuan_surat')) {
            $suratKeluar->tujuan_surat = $validated['tujuan_surat'];
        }
        
        $suratKeluar->perihal = $validated['perihal'];

        if ($request->hasFile('file_surat')) {
            if ($suratKeluar->file_surat) Storage::disk('public')->delete($suratKeluar->file_surat);
            $filePath = $request->file('file_surat')->store('surat-keluar', 'public');
            $suratKeluar->file_surat = $filePath;
        }
        
        $suratKeluar->save();

        $route = ($suratKeluar->tipe_kirim == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
        return redirect()->route($route)->with('success', 'Data surat keluar berhasil diperbarui.');
    }

   public function destroy($id)
    {
        // 1. Cari Data Surat
        $surat = SuratKeluar::findOrFail($id);

        // --- PENYEBAB FORBIDDEN ADA DI SINI ---
        // Cek apakah ada kodingan seperti ini? Jika ada, HAPUS atau KOMENTAR saja.
        // if ($surat->user_id != Auth::id()) {
        //     abort(403, 'Anda tidak berhak menghapus surat ini.');
        // }
        // ---------------------------------------

        // 2. Hapus File Fisik (Storage)
        if ($surat->file_surat && \Illuminate\Support\Facades\Storage::disk('public')->exists($surat->file_surat)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($surat->file_surat);
        }

        // 3. Simpan Tipe untuk Redirect (sebelum dihapus)
        $routeTujuan = ($surat->tipe_kirim == 'internal') 
            ? 'bau.surat-keluar.internal' 
            : 'bau.surat-keluar.eksternal';

        // 4. Hapus Data di Database (termasuk relasi pivot jika ada)
        $surat->penerimaInternal()->detach(); // Hapus relasi ke satker (jika internal)
        $surat->delete();

        // 5. Redirect Kembali
        return redirect()->route($routeTujuan)->with('success', 'Arsip surat berhasil dihapus.');
    }

    public function checkDuplicate(Request $request)
{
    $exists = \App\Models\SuratKeluar::where('nomor_surat', $request->value)->exists();
    return response()->json(['exists' => $exists]);
}
}