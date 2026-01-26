<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuratKeluarInternalRektorController extends Controller
{
    public function index()
    {
        // Mengambil surat internal dari Rektor yang statusnya belum 'selesai'
        $surat = SuratKeluar::with(['user', 'penerimaInternal'])
            ->whereHas('user', function($q) {
                $q->where('role', 'admin_rektor');
            })
            ->where('tipe_kirim', 'internal')
            ->whereIn('status', ['pending', 'proses'])
            ->latest()
            ->get();

        return view('bau.surat_keluar_internal_rektor.index', compact('surat'));
    }

    public function proses($id)
    {
        $surat = SuratKeluar::findOrFail($id);
        $surat->update(['status' => 'proses']);

        return back()->with('success', 'Status surat berhasil diubah ke Proses.');
    }

public function teruskan($id)
{
    $surat = SuratKeluar::with('penerimaInternal')->findOrFail($id);
    
    // 1. Update status surat menjadi selesai
    $surat->update([
        'status' => 'selesai',
        'tanggal_terusan' => now(), 
    ]);

    // =========================================================
    // 2. NOTIFIKASI EMAIL KE SATKER-SATKER TUJUAN
    // =========================================================
    
    // Ambil ID semua Satker tujuan dari tabel pivot
    $satkerIds = $surat->penerimaInternal->pluck('id')->toArray();

    if (!empty($satkerIds)) {
        // Ambil ID User yang bertindak sebagai admin di Satker-satker tersebut
        $penerimaUserIds = \App\Models\User::whereIn('satker_id', $satkerIds)
            ->where('role', 'satker')
            ->pluck('id')
            ->toArray();

        if (!empty($penerimaUserIds)) {
            $link = route('login');

            $details = [
                'subject'    => '📩 Surat Rektorat Baru: ' . $surat->perihal,
                'greeting'   => 'Halo Bapak/Ibu di Satker Tujuan,',
                'body'       => "BAU telah memverifikasi dan meneruskan surat dari Rektorat untuk unit Anda.\n\n" .
                                "No. Surat: {$surat->nomor_surat}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Tanggal Terusan: " . now()->format('d-m-Y H:i') . " WIB\n\n" .
                                "Silakan login ke sistem e-Surat untuk membaca dan menindaklanjuti surat tersebut.",
                'actiontext' => 'Buka Inbox Satker',
                'actionurl'  => $link,
                'file_url'   => asset('storage/' . $surat->file_surat)
            ];

            // Kirim ke semua admin Satker (Email 1 & Email 2 otomatis)
            \App\Helpers\EmailHelper::kirimNotif($penerimaUserIds, $details);
        }
    }

    return back()->with('success', 'Surat berhasil diteruskan ke Satker dan notifikasi email telah dikirim.');
}
}