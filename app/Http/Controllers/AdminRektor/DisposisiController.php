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

class DisposisiController extends Controller
{
    /**
     * Menampilkan halaman detail surat dan form disposisi.
     */
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

    /**
     * Menyimpan keputusan Rektor.
     */
    public function store(Request $request, Surat $surat)
    {
        // 1. Validasi (tujuan_satker_id BOLEH KOSONG jika hanya untuk arsip Rektor)
        $validated = $request->validate([
            'tujuan_satker_id' => 'nullable|exists:satkers,id', 
            'klasifikasi_id' => 'nullable|exists:klasifikasis,id',
            'catatan_rektor' => 'nullable|string',
            'disposisi_lain' => 'nullable|string',
        ]);

        $user = Auth::user();

        // ========================================================================
        // KASUS A: ADA TUJUAN DISPOSISI (KEMBALI KE BAU)
        // ========================================================================
        if ($request->filled('tujuan_satker_id')) {
            
            // 1. Simpan Data Disposisi
            Disposisi::create([
                'surat_id' => $surat->id,
                'user_id' => $user->id,
                'tujuan_satker_id' => $validated['tujuan_satker_id'],
                'klasifikasi_id' => $validated['klasifikasi_id'],
                'catatan_rektor' => $validated['catatan_rektor'],
                'disposisi_lain' => $validated['disposisi_lain'],
                'tanggal_disposisi' => now(),
            ]);

            // 2. Update Status -> 'didisposisi' (Agar muncul di dashboard BAU untuk diteruskan)
            $surat->update(['status' => 'didisposisi']);

            // 3. Catat Riwayat
            $tujuan = Satker::find($validated['tujuan_satker_id']);
            $catatan_disposisi_lain = $validated['disposisi_lain'] ? ' | Lainnya: ' . $validated['disposisi_lain'] : '';

            RiwayatSurat::create([
                'surat_id' => $surat->id,
                'user_id' => $user->id,
                'status_aksi' => 'Disposisi Rektor (Proses)',
                'catatan' => 'Disposisi Rektor ke ' . $tujuan->nama_satker . '. Kembali ke BAU untuk diteruskan. Catatan: "' . ($validated['catatan_rektor'] ?? '-') . '"' . $catatan_disposisi_lain
            ]);

            return redirect()->route('home')->with('success', 'Disposisi disimpan. Surat dikembalikan ke BAU untuk diteruskan ke Satker.');
        }

        // ========================================================================
        // KASUS B: TIDAK ADA TUJUAN (LANGSUNG SELESAI/ARSIP)
        // ========================================================================
        else {
            // 1. Update Status -> 'selesai' (Arsip, tidak ke BAU)
            $surat->update(['status' => 'selesai']);

            // 2. Catat Riwayat
            RiwayatSurat::create([
                'surat_id' => $surat->id,
                'user_id' => $user->id,
                'status_aksi' => 'Selesai (Arsip Rektor)',
                'catatan' => 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).'
            ]);

            return redirect()->route('home')->with('success', 'Surat telah ditandai Selesai dan masuk Arsip (Tidak diteruskan ke manapun).');
        }
    }

    /**
     * FUNGSI RIWAYAT
     */
    public function riwayat()
    {
        // PERBAIKAN: Menambahkan status arsip_satker, diarsipkan, disimpan agar surat tidak hilang
        $suratSelesai = Surat::with('disposisis.tujuanSatker')
                            ->whereIn('status', [
                                'didisposisi', 
                                'selesai', 
                                'selesai_edaran',
                                'arsip_satker', // <-- PENTING: Status saat diarsip Satker
                                'diarsipkan',
                                'disimpan'
                            ])
                            ->latest('diterima_tanggal')
                            ->get();
        
        return view('admin_rektor.riwayat_disposisi_index', compact('suratSelesai'));
    }

    /**
     * FUNGSI DETAIL RIWAYAT UNTUK MODAL TIMELINE
     * Pastikan Anda menambahkan route untuk ini di web.php
     */
    public function showRiwayatDetail(Surat $surat)
    {
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }
}