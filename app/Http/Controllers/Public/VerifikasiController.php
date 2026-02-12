<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    /**
     * Menampilkan detail verifikasi surat berdasarkan hash QR Code.
     */
    public function index($hash)
    {
        // Cari surat berdasarkan qrcode_hash
        // Kita gunakan with('user.satker') agar informasi pengirim lengkap
        $surat = SuratKeluar::with(['user.satker', 'user.jabatan'])
                    ->where('qrcode_hash', $hash)
                    ->first();

        // Jika surat tidak ditemukan (hash tidak valid)
        if (!$surat) {
            return view('public.verifikasi_failed');
        }

        // Tampilkan halaman sukses verifikasi
        return view('public.verifikasi_success', compact('surat'));
    }
}