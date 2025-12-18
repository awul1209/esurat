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
    public function indexEksternal()
    {
        $suratKeluars = SuratKeluar::where('user_id', Auth::id())
                                ->where('tipe_kirim', 'eksternal') 
                                ->latest('tanggal_surat')
                                ->get();
                                
        return view('bau.surat_keluar.index', compact('suratKeluars'));
    }

    /**
     * Menampilkan Surat Keluar INTERNAL
     */
    public function indexInternal()
    {
        $suratKeluars = SuratKeluar::where('user_id', Auth::id())
                                ->where('tipe_kirim', 'internal') 
                                ->latest('tanggal_surat')
                                ->get();
                                
        return view('bau.surat_keluar.index', compact('suratKeluars'));
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
        $request->validate([
            'nomor_surat'       => 'required|string|max:255|unique:surat_keluars,nomor_surat',
            'perihal'           => 'required|string',
            'tanggal_surat'     => 'required|date',
            'file_surat'        => 'required|file|mimes:pdf,jpg,png|max:5120',
            'tujuan_satker_ids' => 'required|array|min:1',
            'tipe_kirim'        => 'required|in:internal,eksternal', 
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-keluar', 'public');

        DB::transaction(function() use ($request, $user, $path) {
            
            // A. PILAH TUJUAN
            $inputTujuan = $request->tujuan_satker_ids;
            $satkerIds = [];     
            $targetRektor = [];  

            foreach($inputTujuan as $val) {
                if ($val == 'rektor' || $val == 'universitas') {
                    $targetRektor[] = $val;
                } elseif (is_numeric($val)) {
                    $satkerIds[] = $val;
                }
            }

            // B. SIMPAN ARSIP SURAT KELUAR
            $displayTujuan = [];
            if (!empty($targetRektor)) {
                $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetRektor));
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
            $tglSurat = Carbon::parse($request->tanggal_surat)->format('d-m-Y');
            $link = route('login');
            $namaPengirim = "Biro Administrasi Umum (BAU)"; // Hardcode karena ini controller BAU

            // =================================================================
            // SKENARIO 1: KIRIM KE SATKER LAIN (BAU -> SATKER)
            // =================================================================
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->attach($satkerIds);

                // KIRIM NOTIFIKASI WA (FORMAT LENGKAP)
                try {
                    // Ambil User Admin Satker Tujuan beserta Data Satkernya
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

                // NOTIFIKASI WA KE ADMIN REKTOR (FORMAT LENGKAP)
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

        $route = ($request->tipe_kirim == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
        return redirect()->route($route)->with('success', 'Surat berhasil dikirim.');
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

    public function destroy(SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->user_id != Auth::id()) {
            abort(403);
        }

        $tipe = $suratKeluar->tipe_kirim;

        try {
            if($suratKeluar->file_surat) {
                Storage::disk('public')->delete($suratKeluar->file_surat);
            }
            $suratKeluar->delete();
            
            $route = ($tipe == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
            return redirect()->route($route)->with('success', 'Surat keluar berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus surat: ' . $e->getMessage());
        }
    }
}