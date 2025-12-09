<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;

class SuratMasukController extends Controller
{
    /**
     * Menampilkan daftar surat yang perlu ditindaklanjuti (status 'di_admin_rektor')
     */
    public function index()
    {
        // Ambil SEMUA surat yang statusnya 'di_admin_rektor'
        $semuaSurat = Surat::where('status', 'di_admin_rektor')
                            ->latest('diterima_tanggal')
                            ->get();
        
        return view('admin_rektor.surat_masuk_index', compact('semuaSurat'));
    }
}