<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat;
// Import library PDF yang baru saja Anda install
use Barryvdh\DomPDF\Facade\Pdf;

class CetakController extends Controller
{
    /**
     * Membuat dan menampilkan file PDF Lembar Disposisi.
     */
    public function cetakDisposisi(Surat $surat)
    {
        // 1. Keamanan:
        // Jangan izinkan cetak lembar disposisi jika surat belum diproses Rektor
        if ($surat->status == 'baru_di_bau' || $surat->status == 'di_admin_rektor') {
            return redirect()->back()->with('error', 'Surat ini belum memiliki lembar disposisi untuk dicetak.');
        }

        // 2. Ambil data surat beserta semua relasi yang kita butuhkan
        $surat->load('tujuanSatker', 'disposisis.tujuanSatker', 'disposisis.klasifikasi');

        // 3. Ambil data disposisi TERAKHIR (yang paling relevan)
        // Kita asumsikan disposisi Rektor adalah yang terakhir dibuat.
        $disposisi = $surat->disposisis->last();

        // 4. Load View Template PDF
        // Kita akan buat file 'lembar_disposisi.blade.php' di langkah berikutnya.
        // Kita kirim data $surat dan $disposisi ke view tersebut.
        $pdf = Pdf::loadView('pdf.lembar_disposisi', [
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);

        // 5. Set orientasi kertas (sesuai gambar Anda, kertas A4 portrait)
        $pdf->setPaper('a4', 'portrait');

        // 6. Tampilkan PDF di browser (stream)
        // Nama file saat di-download: "Disposisi - [Perihal Surat].pdf"
        return $pdf->stream('Disposisi - ' . $surat->perihal . '.pdf');
    }
}