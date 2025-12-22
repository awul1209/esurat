<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\Klasifikasi; // Pastikan Model Klasifikasi di-import
use Barryvdh\DomPDF\Facade\Pdf;

class CetakController extends Controller
{
    public function cetakDisposisi(Surat $surat)
    {
        // 1. Keamanan
        if ($surat->status == 'baru_di_bau' || $surat->status == 'di_admin_rektor') {
            return redirect()->back()->with('error', 'Surat ini belum memiliki lembar disposisi untuk dicetak.');
        }

        // 2. Load Relasi
        $surat->load([
            'tujuanSatker', 
            'disposisis.tujuanSatker', 
            'klasifikasis' // Load relasi pivot
        ]);
        
        $disposisis = $surat->disposisis;

        // ==========================================================
        // DATA SATKER TUJUAN (Untuk Kolom Kiri - Manual String Match)
        // ==========================================================
        $satkerTujuanList = $disposisis->pluck('tujuanSatker.nama_satker')->filter()->unique()->toArray();
        
        // ==========================================================
        // DATA KLASIFIKASI (PERBAIKAN UTAMA)
        // ==========================================================
        
        // 1. Ambil Array ID (Wajib untuk View PDF baru yang pakai centangId)
        // Ini variabel yang HILANG di kode Anda sebelumnya
        $selectedKlasifikasiIds = $surat->klasifikasis->pluck('id')->toArray();

        // 2. Ambil Master Data (Untuk Looping Checklist di PDF)
        $daftarKlasifikasi = Klasifikasi::all();


        // (OPSIONAL) LOGIKA MAPPING STRING LAMA
        // Bagian ini sebenarnya bisa dihapus jika Kolom Kanan PDF sudah full looping ID.
        // Tapi dibiarkan saja tidak masalah, siapa tahu dipakai untuk keperluan lain.
        $rawKlasifikasi = $surat->klasifikasis->pluck('nama_klasifikasi')->toArray();
        if ($surat->sifat) {
            $rawKlasifikasi[] = $surat->sifat;
        }
        $klasifikasiList = [];
        foreach ($rawKlasifikasi as $text) {
            $text = trim($text);
            $klasifikasiList[] = $text;
            // Mapping istilah lama (Legacy support)
            if (stripos($text, 'Disposisi') !== false && stripos($text, 'Untuk') === false) $klasifikasiList[] = 'Untuk Disposisi';
            if (stripos($text, 'Tindak Lanjut') !== false && stripos($text, 'Mohon') === false) $klasifikasiList[] = 'Mohon Tindak Lanjut';
            if (stripos($text, 'Siapkan') !== false && stripos($text, 'Bahan') === false) $klasifikasiList[] = 'Siapkan Bahan';
            if (stripos($text, 'Sampaikan') !== false) $klasifikasiList[] = 'Sampaikan Kpd Ybs';
            if (stripos($text, 'Agar Hadir') !== false) $klasifikasiList[] = 'Agar Menghadap Saya';
        }
        $klasifikasiList = array_values(array_unique($klasifikasiList));
        

        // ==========================================================
        // DATA LAINNYA
        // ==========================================================
        $disposisiLain = $disposisis->pluck('disposisi_lain')->filter()->unique()->implode(', ');
        $disposisiRektorPertama = $disposisis->first();

        // 3. Load View PDF
        $pdf = Pdf::loadView('pdf.lembar_disposisi', [
            'surat' => $surat,
            'disposisis' => $disposisis, 
            'satkerTujuanList' => $satkerTujuanList, 
            
            // Variabel Penting untuk Multi Klasifikasi:
            'daftarKlasifikasi' => $daftarKlasifikasi,       // Master data untuk di-loop
            'selectedKlasifikasiIds' => $selectedKlasifikasiIds, // Array ID untuk dicocokkan
            'klasifikasiList' => $klasifikasiList,           // String list (Legacy/Sifat surat)

            'disposisiRektorPertama' => $disposisiRektorPertama, 
            'disposisiLain' => $disposisiLain, 
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Disposisi - ' . $surat->perihal . '.pdf');
    }
}