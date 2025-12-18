<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
    public function indexMasuk()
    {
        $mySatkerId = Auth::user()->satker_id;

        $suratMasuk = SuratKeluar::with(['user.satker'])
            ->where('tipe_kirim', 'internal')
            ->whereHas('penerimaInternal', function($q) use ($mySatkerId) {
                $q->where('satkers.id', $mySatkerId);
            })
            ->latest('tanggal_surat')
            ->get();

        return view('satker.internal.surat_masuk_index', compact('suratMasuk'));
    }

    public function indexKeluar()
    {
        $userId = Auth::id();

        $suratKeluar = SuratKeluar::with(['penerimaInternal']) 
            ->where('tipe_kirim', 'internal')
            ->where('user_id', $userId)
            ->latest('tanggal_surat')
            ->get();

        return view('satker.internal.surat_keluar_index', compact('suratKeluar'));
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
            'nomor_surat'       => 'required|string|max:255',
            'perihal'           => 'required|string|max:255',
            'tujuan_satker_ids' => 'required|array|min:1', 
            'tanggal_surat'     => 'required|date',
            'file_surat'        => 'required|file|mimes:pdf,jpg,png|max:5120',
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
            // Gunakan Carbon untuk format tanggal
            $tglSurat = Carbon::parse($request->tanggal_surat)->format('d-m-Y');
            $link = route('login'); // Link ke sistem

            // --- 1. JIKA KIRIM KE SATKER LAIN ---
            if (!empty($satkerIds)) {
                $suratKeluar->penerimaInternal()->attach($satkerIds);

                // === NOTIFIKASI WA KE ADMIN SATKER PENERIMA ===
                try {
                    // Ambil Data Penerima (Satker & Adminnya)
                    $penerimaSatkers = Satker::whereIn('id', $satkerIds)->get();

                    foreach ($penerimaSatkers as $satkerTujuan) {
                        // Cari admin satker tersebut
                        $admin = User::where('role', 'satker')
                                     ->where('satker_id', $satkerTujuan->id)
                                     ->first();

                        if ($admin && $admin->no_hp) {
                            $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : {$satkerTujuan->nama_satker}
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                            
                            WaService::send($admin->no_hp, $pesan);
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
                        'status'           => 'baru_di_bau', // Berhenti di BAU
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

                // === NOTIFIKASI WA KE ADMIN BAU ===
                try {
                    $adminBAU = User::where('role', 'bau')->first();
                    if ($adminBAU && $adminBAU->no_hp) {
                        $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : Rektor / Universitas (Via BAU)
Tanggal Surat : {$tglSurat}
No. Surat     : {$request->nomor_surat}
Perihal       : {$request->perihal}
Pengirim      : {$namaPengirim}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                        
                        WaService::send($adminBAU->no_hp, $pesan);
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
        $surat = SuratKeluar::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'nomor_surat'         => 'required|string|max:255',
            'perihal'             => 'required|string|max:255',
            'tujuan_satker_ids'   => 'required|array|min:1',
            'tanggal_surat'       => 'required|date',
            'file_surat'          => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::exists($surat->file_surat)) {
                Storage::delete($surat->file_surat);
            }
            $path = $request->file('file_surat')->store('surat-internal', 'public');
            $surat->file_surat = $path;
        }

        $surat->nomor_surat = $request->nomor_surat;
        $surat->perihal = $request->perihal;
        $surat->tanggal_surat = $request->tanggal_surat;
        $surat->save();

        $satkerIds = array_filter($request->tujuan_satker_ids, 'is_numeric');
        $surat->penerimaInternal()->sync($satkerIds);

        return redirect()->route('satker.surat-keluar.internal')->with('success', 'Surat berhasil diperbarui.');
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
}