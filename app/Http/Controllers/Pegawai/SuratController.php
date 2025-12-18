<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;

class SuratController extends Controller
{
    /**
     * Menampilkan daftar surat masuk untuk pegawai yang sedang login.
     */
    public function indexMasukEksternal() 
    {
        $user = Auth::user();

        $suratUntukSaya = Surat::where(function($query) use ($user) {
                // 1. Cek tabel pivot (Fitur Utama Delegasi)
                // Ini akan mencari ID user di tabel 'surat_delegasi'
                $query->whereHas('delegasiPegawai', function($q) use ($user) {
                    $q->where('users.id', $user->id); 
                })
                
                // 2. Cek kolom legacy (Jaga-jaga jika ada surat lama format langsung)
                ->orWhere('tujuan_user_id', $user->id);
            })
            
            // -----------------------------------------------------------
            // [REVISI PENTING]: SAYA MENGHAPUS FILTER STATUS GLOBAL
            // -----------------------------------------------------------
            // Alasannya: Jika Admin Satker sudah mendelegasikan (masuk pivot),
            // maka pegawai WAJIB melihatnya, meskipun status global surat 
            // masih 'didisposisi' atau 'di_satker'.
            // 
            // HANYA filter jika surat benar-benar ditarik/dihapus (opsional).
            // -----------------------------------------------------------
            // ->whereIn('status', ['selesai', 'arsip_satker']) <--- INI BIANG KEROKNYA
            
            // Eager Load data pivot khusus user ini
            // Agar di view kita bisa ambil: $surat->delegasiPegawai->first()->pivot->catatan
            ->with(['riwayats.user', 'disposisis', 'delegasiPegawai' => function($q) use ($user) {
                $q->where('users.id', $user->id);
            }, 'tujuanSatker']) 
            
            ->latest('diterima_tanggal')
            ->get();

        return view('pegawai.surat_index', compact('suratUntukSaya'));
    }

    /**
     * Menandai surat sebagai 'Selesai' / 'Sudah Dibaca' oleh Pegawai
     */
    public function selesai(Request $request, Surat $surat)
    {
        $user = Auth::user();

        // 1. Cek Pivot
        $isDelegated = $surat->delegasiPegawai()->where('user_id', $user->id)->exists();

        if ($isDelegated) {
            // Update status baca PRIBADI pegawai di tabel pivot
            // Ini tidak akan mengganggu status pegawai lain atau status global
            $surat->delegasiPegawai()->updateExistingPivot($user->id, [
                'status' => 'selesai'
            ]);
        } 
        // 2. Fallback Legacy
        elseif ($surat->tujuan_user_id == $user->id) {
             // Jika surat legacy, mungkin perlu update global atau abaikan
             // $surat->update(['status' => 'dibaca']); // Opsional
        }

        return redirect()->back()->with('success', 'Surat telah ditandai selesai/dibaca.');
    }
    
    // ... method indexSuratUmum tetap sama ...
    public function indexSuratUmum()
    {
        $user = Auth::user();
        if (!$user->satker_id) {
            return view('pegawai.surat_umum', ['suratUmum' => collect()]);
        }
        $satker = $user->satker;
        $suratUmum = $satker->suratEdaran()
                            ->wherePivot('status', 'diteruskan_internal')
                            ->with('riwayats.user')
                            ->latest('diterima_tanggal') 
                            ->get();
        return view('pegawai.surat_umum', compact('suratUmum'));
    }
}