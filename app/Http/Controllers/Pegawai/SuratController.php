<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;

class SuratController extends Controller
{
    /**
     * Menampilkan halaman tabel "Surat Untuk Saya".
     * (Fungsi ini sudah benar, tidak berubah)
     */
    public function indexMasukEksternal()
    {
        $user = Auth::user();

        // Ambil data surat untuk tabel
        $suratUntukSaya = $user->suratUntukSaya()
                                ->with(['riwayats.user', 'disposisis']) // Muat relasi
                                ->latest('diterima_tanggal')
                                ->get();
        
        // Kirim data ke view tabel baru
        return view('pegawai.surat_index', compact(
            'suratUntukSaya'
        ));
    }

    /**
     * ====================================================
     * (FUNGSI YANG DIPERBAIKI)
     * ====================================================
     * Menampilkan halaman "Surat Umum" (Edaran).
     */
    public function indexSuratUmum()
    {
        $user = Auth::user();

        // 1. Pastikan pegawai terhubung ke Satker
        if (!$user->satker_id) {
            return view('pegawai.surat_umum', ['suratUmum' => collect()]);
        }

        // 2. Ambil model Satker-nya
        $satker = $user->satker;

        // 3. Ambil semua surat edaran yang statusnya
        //    sudah "diteruskan_internal" oleh Admin Satkernya.
        $suratUmum = $satker->suratEdaran()
                            
                            // KODE LAMA (SALAH):
                            // ->where('pivot_status', 'diteruskan_internal') 
                            
                            // KODE BARU (BENAR):
                            // Gunakan 'wherePivot' untuk memfilter kolom 'status' di tabel pivot
                            ->wherePivot('status', 'diteruskan_internal')
                            
                            ->with('riwayats.user') // Ambil riwayat untuk tahu pengirim (BAU)
                            ->get();
        
        // 4. Kirim data ke view baru
        return view('pegawai.surat_umum', compact(
            'suratUmum'
        ));
    }
}