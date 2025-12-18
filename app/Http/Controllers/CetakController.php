<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat;
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
        $surat->load('tujuanSatker', 'disposisis.tujuanSatker', 'disposisis.klasifikasi');
        $disposisis = $surat->disposisis;

        // ==========================================================
        // DATA SATKER TUJUAN
        // ==========================================================
        $satkerTujuanList = $disposisis->pluck('tujuanSatker.nama_satker')->filter()->unique()->toArray();
        
        // ==========================================================
        // DATA KLASIFIKASI / INSTRUKSI (PERBAIKAN UTAMA DISINI)
        // ==========================================================
        
        // 1. Ambil nama klasifikasi mentah dari Database
        $rawKlasifikasi = $disposisis->pluck('klasifikasi.nama_klasifikasi')->filter()->toArray();
        
        // 2. Tambahkan Sifat Surat (Segera, Rahasia, dll) jika ada
        if ($surat->sifat) {
            $rawKlasifikasi[] = $surat->sifat;
        }

        $klasifikasiList = [];

        // 3. LOGIKA MAPPING (PENYAMAAN ISTILAH DB -> PDF)
        foreach ($rawKlasifikasi as $item) {
            $text = trim($item);
            
            // Masukkan teks asli dulu (untuk jaga-jaga jika sama)
            $klasifikasiList[] = $text;

            // Cek dan terjemahkan istilah yang beda
            // Menggunakan stripos (case insensitive)
            
            // DB: Disposisi -> PDF: Untuk Disposisi
            if (stripos($text, 'Disposisi') !== false && stripos($text, 'Untuk') === false) {
                $klasifikasiList[] = 'Untuk Disposisi';
            }

            // DB: Tindak Lanjut -> PDF: Mohon Tindak Lanjut
            if (stripos($text, 'Tindak Lanjut') !== false && stripos($text, 'Mohon') === false) {
                $klasifikasiList[] = 'Mohon Tindak Lanjut';
            }

            // DB: Siapkan -> PDF: Siapkan Bahan
            if (stripos($text, 'Siapkan') !== false && stripos($text, 'Bahan') === false) {
                $klasifikasiList[] = 'Siapkan Bahan';
            }

            // DB: Sampaikan Ybs. -> PDF: Sampaikan Kpd Ybs
            if (stripos($text, 'Sampaikan') !== false) {
                $klasifikasiList[] = 'Sampaikan Kpd Ybs';
            }
            
            // DB: Agar Hadir -> PDF: Agar Menghadap Saya (Opsional, jika maksudnya sama)
            if (stripos($text, 'Agar Hadir') !== false) {
                $klasifikasiList[] = 'Agar Menghadap Saya';
            }
        }

        // 4. Bersihkan duplikat
        $klasifikasiList = array_values(array_unique($klasifikasiList));
        
        // ==========================================================
        // DATA LAINNYA
        // ==========================================================
        $disposisiLain = $disposisis->pluck('disposisi_lain')->filter()->unique()->implode(', ');
        $disposisiRektorPertama = $disposisis->first();

        // 5. Load View PDF
        $pdf = Pdf::loadView('pdf.lembar_disposisi', [
            'surat' => $surat,
            'disposisis' => $disposisis, 
            'satkerTujuanList' => $satkerTujuanList, 
            'klasifikasiList' => $klasifikasiList, // List ini sekarang sudah cocok dengan PDF
            'disposisiRektorPertama' => $disposisiRektorPertama, 
            'disposisiLain' => $disposisiLain, 
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Disposisi - ' . $surat->perihal . '.pdf');
    }
}