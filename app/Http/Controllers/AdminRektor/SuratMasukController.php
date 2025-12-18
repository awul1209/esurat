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
        // Filter tipe_surat = 'eksternal' agar tidak tercampur
        $semuaSurat = Surat::where('status', 'di_admin_rektor')
                            ->where('tipe_surat', 'eksternal') 
                            ->latest('diterima_tanggal')
                            ->get();
        
        return view('admin_rektor.surat_masuk_index', compact('semuaSurat'));
    }

    public function indexInternal()
    {
        // Filter tipe_surat = 'internal'
        $suratInternal = Surat::where('status', 'di_admin_rektor')
                            ->where('tipe_surat', 'internal')
                            ->latest('diterima_tanggal')
                            ->get();

        return view('admin_rektor.surat_masuk_internal', compact('suratInternal'));
    }
}