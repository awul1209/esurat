<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\User;
use App\Models\RiwayatSurat;
use App\Models\Satker; // <-- (BARU) Pastikan ini ada
use Illuminate\Support\Facades\DB; // <-- (BARU) Pastikan ini ada

class SuratController extends Controller
{
    /**
     * (DIPERBARUI) Menampilkan halaman tabel Surat Masuk (Disposisi & Edaran).
     */
   public function indexMasukEksternal()
    {
        $user = Auth::user();
        $satkerId = $user->satker_id;

        $daftarPegawai = User::where('satker_id', $satkerId)
                            ->where('role', 'pegawai')
                            ->orderBy('name', 'asc')
                            ->get();
        
        // PERBAIKAN QUERY:
        // Kita ambil status 'selesai' (aktif) DAN 'arsip_satker' (sudah diarsipkan)
        $suratMasukSatker = Surat::whereIn('status', ['selesai', 'arsip_satker'])
            ->whereHas('disposisis', function ($query) use ($satkerId) {
                $query->where('tujuan_satker_id', $satkerId);
            })
            ->with('disposisis.tujuanSatker', 'tujuanUser')
            ->latest('diterima_tanggal')
            ->get();

        // (Kode Surat Edaran tetap sama...)
        $satker = Satker::find($satkerId);
        $suratEdaran = $satker->suratEdaran()->with('riwayats.user')->get();
        
        return view('satker.surat-masuk-eksternal', compact(
            'suratMasukSatker',
            'suratEdaran',
            'daftarPegawai'
        ));
    }

    /**
     * (FUNGSI BARU) Menandai surat sebagai Selesai/Arsip di level Satker.
     */
    public function arsipkan(Request $request, Surat $surat)
    {
        $user = Auth::user();

        // 1. Update status surat menjadi 'arsip_satker'
        $surat->update(['status' => 'arsip_satker']);

        // 2. Catat Riwayat
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diarsipkan/Selesai di Satker',
            'catatan' => 'Surat ditandai selesai oleh ' . $user->name . ' (Tidak didelegasikan).'
        ]);

        return redirect()->back()->with('success', 'Surat berhasil diarsipkan (Tandai Selesai).');
    }

    /**
     * Mendelegasikan (Pilihan A) surat DISPOSISI ke 1 pegawai.
     * (Nama fungsi diubah agar lebih jelas)
     */
    public function delegasiKePegawai(Request $request, Surat $surat)
    {
        // 1. Validasi
        $validated = $request->validate([
            'tujuan_user_id' => 'required|exists:users,id',
            'catatan_satker' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $pegawai = User::find($validated['tujuan_user_id']);

        // 2. Keamanan
        if (!$pegawai || $pegawai->satker_id != $user->satker_id) {
            return redirect()->back()->with('error', 'Pegawai tidak ditemukan di Satker Anda.');
        }

        // 3. Update Surat
        $surat->update([ 'tujuan_user_id' => $pegawai->id ]);

        // 4. Catat Riwayat
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Didelegasikan ke Pegawai', // Wording diubah
            'catatan' => 'Didelegasikan oleh Pimpinan Satker (' . $user->name . ') ke ' . $pegawai->name . '. Catatan: "' . ($validated['catatan_satker'] ?? '-') . '"'
        ]);

        return redirect()->route('satker.surat-masuk.eksternal')->with('success', 'Surat berhasil didelegasikan ke ' . $pegawai->name);
    }

    /**
     * (FUNGSI BARU) Meneruskan surat EDARAN ke semua pegawai internal.
     */
    public function broadcastInternal(Request $request, Surat $surat)
    {
        $user = Auth::user();
        
        // 1. Update status di tabel pivot
        DB::table('surat_edaran_satker')
            ->where('surat_id', $surat->id)
            ->where('satker_id', $user->satker_id)
            ->update(['status' => 'diteruskan_internal']);

        // 2. Catat Riwayat (Opsional, tapi bagus untuk pelacakan)
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diteruskan ke Internal Satker',
            'catatan' => 'Surat Edaran disebarkan ke semua pegawai di ' . $user->satker->nama_satker . ' oleh ' . $user->name
        ]);

        return redirect()->route('satker.surat-masuk.eksternal')->with('success', 'Surat Edaran berhasil disebarkan ke semua pegawai internal Anda.');
    }
}