<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\Satker;
use App\Models\Klasifikasi;
use App\Models\Disposisi;
use App\Models\RiwayatSurat;
use App\Models\User; // Tambahkan ini
use Carbon\Carbon;
use App\Services\WaService; // Tambahkan ini

class DisposisiController extends Controller
{
    public function show(Surat $surat)
    {
        if ($surat->status != 'di_admin_rektor') {
            return redirect()->route('home')->with('error', 'Surat ini sudah diproses atau tidak berada di Admin Rektor.');
        }

        $daftarSatker = Satker::orderBy('nama_satker')->get();
        $daftarKlasifikasi = Klasifikasi::orderBy('kode_klasifikasi')->get();
        
        return view('admin_rektor.disposisi_show', compact(
            'surat', 
            'daftarSatker', 
            'daftarKlasifikasi'
        ));
    }

    public function store(Request $request, Surat $surat)
    {
        // 1. Validasi Input (HANYA KLASIFIKASI & TUJUAN)
        $request->validate([
            'tujuan_satker_ids' => 'nullable|array', 
            'disposisi_lain'    => 'nullable|string', 
            'catatan_rektor'    => 'nullable|string',
            'klasifikasi_id'    => 'nullable|exists:klasifikasis,id',
        ]);

        $user = Auth::user();
        $catatanRektor = $request->catatan_rektor;
        $disposisiLain = $request->disposisi_lain;
        $klasifikasiId = $request->klasifikasi_id;

        // Bersihkan input array dari value "lainnya"
        $inputSatkerIds = $request->input('tujuan_satker_ids', []);
        $cleanSatkerIds = array_filter($inputSatkerIds, function($value) {
            return $value !== 'lainnya';
        });

        // ========================================================================
        // KASUS A: ADA TUJUAN DISPOSISI (Satker Dropdown ATAU Manual Lainnya)
        // ========================================================================
        if (!empty($cleanSatkerIds) || !empty($disposisiLain)) {
            
            $tujuanNames = [];

            // 1. Simpan Tujuan Satker (Looping ID Satker)
            foreach ($cleanSatkerIds as $satkerId) {
                Disposisi::create([
                    'surat_id'         => $surat->id,
                    'tujuan_satker_id' => $satkerId,
                    'user_id'          => $user->id,
                    'klasifikasi_id'   => $klasifikasiId,
                    'catatan_rektor'   => $catatanRektor,
                    'tanggal_disposisi'=> now(),
                ]);
                
                $s = Satker::find($satkerId);
                if ($s) $tujuanNames[] = $s->nama_satker;
            }

            // 2. Simpan Tujuan Lain (Manual Input)
            if (!empty($disposisiLain)) {
                Disposisi::create([
                    'surat_id'         => $surat->id,
                    'disposisi_lain'   => $disposisiLain,
                    'user_id'          => $user->id,
                    'klasifikasi_id'   => $klasifikasiId,
                    'catatan_rektor'   => $catatanRektor,
                    'tujuan_satker_id' => null,
                    'tanggal_disposisi'=> now(),
                ]);
                $tujuanNames[] = $disposisiLain . ' (Eksternal)';
            }

            // 3. Update Status Surat -> 'didisposisi'
            $surat->update(['status' => 'didisposisi']);

            // 4. Catat Riwayat
            $tujuanStr = implode(', ', $tujuanNames);
            
            RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $user->id,
                'status_aksi' => 'Disposisi Rektor',
                'catatan'     => 'Rektor mendisposisikan surat ke: ' . $tujuanStr . '. (Menunggu BAU meneruskan).'
            ]);

            // ===============================================================
            // NOTIFIKASI WA KE ADMIN BAU (FITUR BARU)
            // ===============================================================
            try {
                $adminBau = User::where('role', 'bau')->first();
                
                if ($adminBau && $adminBau->no_hp) {
                    
                    $tglSurat = $surat->tanggal_surat->format('d-m-Y');
                    $link = route('login'); 

                    $pesan = 
"📩 *Notifikasi Surat Telah Didisposisi*

Yth. Admin BAU,
Rektor telah melakukan disposisi pada surat berikut:

Asal Surat    : {$surat->surat_dari}
No. Agenda    : {$surat->no_agenda}
Perihal       : {$surat->perihal}
Tujuan Disposisi : {$tujuanStr}

Mohon segera LOGIN dan TERUSKAN surat fisik/digital ke Satker terkait.
Link: {$link}

Pesan otomatis Sistem e-Surat.";

                    WaService::send($adminBau->no_hp, $pesan);
                }
            } catch (\Exception $e) {}
            // ===============================================================

            return redirect()->route('adminrektor.suratmasuk.index')->with('success', 'Disposisi berhasil disimpan & dikembalikan ke BAU.');
        }

        // ========================================================================
        // KASUS B: TIDAK ADA TUJUAN (LANGSUNG SELESAI/ARSIP)
        // ========================================================================
        else {
            $surat->update(['status' => 'selesai']);

            RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $user->id,
                'status_aksi' => 'Selesai (Arsip Rektor)',
                'catatan'     => 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).'
            ]);

            return redirect()->route('adminrektor.suratmasuk.index')->with('success', 'Surat telah ditandai Selesai dan masuk Arsip (Tidak diteruskan ke manapun).');
        }
    }

    public function riwayat()
    {
        $suratSelesai = Surat::with('disposisis.tujuanSatker')
                            ->whereIn('status', [
                                'didisposisi', 
                                'di_satker', 
                                'selesai', 
                                'selesai_edaran', 
                                'arsip_satker', 
                                'diarsipkan', 
                                'disimpan'
                            ])
                            // --- FILTER PERBAIKAN ---
                            // Hanya tampilkan surat yang Tipe Tujuannya 'rektor' atau 'universitas'
                            // Surat inputan manual satker (tipe: 'satker') otomatis TIDAK AKAN MUNCUL
                            ->whereIn('tujuan_tipe', ['rektor', 'universitas'])
                            // ------------------------
                            ->latest('diterima_tanggal')
                            ->get();
        
        return view('admin_rektor.riwayat_disposisi_index', compact('suratSelesai'));
    }

    public function showRiwayatDetail(Surat $surat)
    {
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }
}