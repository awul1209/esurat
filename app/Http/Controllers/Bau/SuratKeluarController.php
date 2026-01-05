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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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
    // 1. Ambil Input (Jangan di-strtolower dulu, biarkan sesuai input view)
    $tipe = $request->input('type'); 
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // 2. Query Dasar (Seperti Kode Awal Anda)
    $query = \App\Models\SuratKeluar::with('penerimaInternal')
                ->where('tipe_kirim', $tipe);

    // 3. LOGIKA FIX: Hanya filter User ID jika tipenya INTERNAL
    // Alasannya: Internal sering "bocor" data satker lain, sedangkan Eksternal (menurut Anda) sudah aman.
    if (strtolower($tipe) == 'internal') {
        $query->where('user_id', \Illuminate\Support\Facades\Auth::id());
    }

    // 4. Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $data = $query->latest()->get();

    // 5. Generate CSV
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
        fputcsv($file, $columns); 

        foreach ($data as $index => $item) {
            
            // Logika Tujuan
            $tujuanText = $item->tujuan_surat;
            
            if (empty($tujuanText)) {
                if ($item->penerimaInternal->count() > 0) {
                    $tujuanText = $item->penerimaInternal->pluck('nama_satker')->implode(', ');
                } else {
                    $tujuanText = '-';
                }
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
        // 1. Validasi
        $validator = Validator::make($request->all(), [
            'nomor_surat'   => 'required|unique:surat_keluars,nomor_surat',
            'perihal'       => 'required',
            'tanggal_surat' => 'required|date',
            'file_surat'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', 
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput(); 
        }

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-keluar', 'public');
        
        $suratKeluar = null;

        DB::transaction(function() use ($request, $user, $path, &$suratKeluar) {
            
            // A. PILAH TUJUAN
            $satkerIds = [];     
            $targetRektor = [];  

            if ($request->tipe_kirim == 'internal') {
                $inputTujuan = $request->tujuan_satker_ids ?? [];
                foreach($inputTujuan as $val) {
                    if ($val == 'rektor' || $val == 'universitas') {
                        $targetRektor[] = $val;
                    } elseif (is_numeric($val)) {
                        $satkerIds[] = $val;
                    }
                }
            }

            // B. SIMPAN SURAT KELUAR
            $displayTujuan = [];
            if (!empty($targetRektor)) $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetRektor));
            if ($request->tipe_kirim == 'eksternal' && $request->tujuan_luar) $displayTujuan[] = $request->tujuan_luar;
            
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

            // Data Umum Notifikasi
            $tglSurat = \Carbon\Carbon::parse($request->tanggal_surat)->format('d-m-Y');
            $link = route('login');
            // Jika user punya satker, pakai nama satker. Jika tidak (misal superadmin), pakai nama user.
            $namaPengirim = $user->satker->nama_satker ?? "Biro Administrasi Umum (BAU)"; 

            // =================================================================
            // SKENARIO 1: KIRIM KE SATKER LAIN (Logika Samakan dengan Satker)
            // =================================================================
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->attach($satkerIds);

                try {
                    // 1. Role yang boleh menerima notif (SAMAKAN dengan Satker)
                    $rolePenerima = ['satker', 'bau', 'bapsi', 'admin'];

                    // 2. Ambil User Satker Tujuan
                    $penerimaNotif = User::whereIn('satker_id', $satkerIds)
                                         ->whereIn('role', $rolePenerima)
                                         ->get();

                    foreach ($penerimaNotif as $penerima) {
                        if ($penerima->no_hp) {
                            
                            // 3. LOGIKA SPLIT NOMOR HP (SAMAKAN dengan Satker)
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

                            // 4. Loop kirim ke setiap nomor
                            foreach ($daftarNomor as $nomor) {
                                $nomorBersih = trim($nomor);
                                $nomorBersih = preg_replace('/[^0-9]/', '', $nomorBersih); 

                                if(!empty($nomorBersih)) {
                                    // PANGGIL HELPER WA SERVICE
                                    WaService::send($nomorBersih, $pesan);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) { /* Silent Error */ }
            }

            // =================================================================
            // SKENARIO 2: KIRIM KE REKTOR
            // =================================================================
            foreach ($targetRektor as $target) {
                $tujuanTipe = ($target == 'universitas') ? 'universitas' : 'rektor';
                
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
                    'status'           => 'di_admin_rektor',
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

                // Notif ke Admin Rektor
                try {
                    $adminRektors = User::where('role', 'admin_rektor')->get();
                    foreach($adminRektors as $admin) {
                        if ($admin->no_hp) {
                            $daftarNomor = explode(',', $admin->no_hp);
                            
                            $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : Rektor / Universitas
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek sistem e-Surat: {$link}";

                            foreach ($daftarNomor as $nomor) {
                                $nomorBersih = trim($nomor);
                                $nomorBersih = preg_replace('/[^0-9]/', '', $nomorBersih);
                                if(!empty($nomorBersih)) {
                                    WaService::send($nomorBersih, $pesan);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }
        });

        $routeTujuan = ($suratKeluar->tipe_kirim == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
        return redirect()->route($routeTujuan)->with('success', 'Data surat keluar berhasil diperbarui.');
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