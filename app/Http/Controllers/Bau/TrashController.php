<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\SuratKeluar;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    /**
     * Menampilkan daftar data yang telah dihapus (Soft Deleted).
     */
public function index()
{
    // Mengambil surat masuk yang dihapus
    $suratMasukTrash = Surat::onlyTrashed()
        ->select('id', 'nomor_surat', 'perihal', 'surat_dari', 'deleted_at', 'tipe_surat')
        ->latest('deleted_at')
        ->get();

    // PERBAIKAN: Ambil kolom 'tujuan_luar' (atau kolom lain yang menyimpan nama tujuan)
    $suratKeluarTrash = SuratKeluar::onlyTrashed()
        ->select('id', 'nomor_surat', 'perihal', 'tujuan_luar', 'deleted_at', 'tipe_kirim')
        ->latest('deleted_at')
        ->get();

    return view('bau.trash.index', compact('suratMasukTrash', 'suratKeluarTrash'));
}

    /**
     * Mengembalikan data Surat Masuk yang dihapus.
     */
    public function restoreSuratMasuk($id)
    {
        try {
            $surat = Surat::withTrashed()->findOrFail($id);
            $surat->restore();

            return redirect()->back()->with('success', 'Surat Masuk berhasil dikembalikan ke sistem.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengembalikan data: ' . $e->getMessage());
        }
    }

    /**
     * Mengembalikan data Surat Keluar yang dihapus.
     */
    public function restoreSuratKeluar($id)
    {
        try {
            $surat = SuratKeluar::withTrashed()->findOrFail($id);
            $surat->restore();

            return redirect()->back()->with('success', 'Surat Keluar berhasil dikembalikan ke sistem.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengembalikan data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data secara permanen (Opsional).
     * Gunakan ini jika ingin membersihkan database sepenuhnya.
     */
    public function forceDelete($id, $type)
    {
        try {
            if ($type == 'masuk') {
                $surat = Surat::withTrashed()->findOrFail($id);
            } else {
                $surat = SuratKeluar::withTrashed()->findOrFail($id);
            }

            $surat->forceDelete();

            return redirect()->back()->with('success', 'Data telah dihapus secara permanen.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus permanen: ' . $e->getMessage());
        }
    }
}