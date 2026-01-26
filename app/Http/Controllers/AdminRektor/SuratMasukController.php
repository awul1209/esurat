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
        // SURAT MASUK EKSTERNAL
        // Urut berdasarkan 'tanggal_surat' dari yang paling baru (descending)
        $semuaSurat = \App\Models\Surat::with(['tujuanSatker', 'tujuanUser'])
                                    ->where('status', 'di_admin_rektor')
                                    ->where('tipe_surat', 'eksternal') 
                                    ->orderBy('tanggal_surat', 'desc') // <-- PERBAIKAN DI SINI
                                    ->get();
        
        return view('admin_rektor.surat_masuk_index', compact('semuaSurat'));
    }

    public function indexInternal()
    {
        // SURAT MASUK INTERNAL
        // Urut berdasarkan 'tanggal_surat' dari yang paling baru (descending)
        $suratInternal = \App\Models\Surat::with(['tujuanSatker', 'tujuanUser'])
                                    ->where('status', 'di_admin_rektor')
                                    ->where('tipe_surat', 'internal')
                                    ->orderBy('tanggal_surat', 'desc') // <-- PERBAIKAN DI SINI
                                    ->get();

        return view('admin_rektor.surat_masuk_internal', compact('suratInternal'));
    }
}