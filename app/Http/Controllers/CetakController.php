<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\Klasifikasi; // Pastikan Model Klasifikasi di-import
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use App\Models\RiwayatSurat; // Tambahkan ini
class CetakController extends Controller
{
  // JANGAN LUPA: Tambahkan 'use setasign\Fpdi\Fpdi;' di paling atas file controller

//   cetak disposisi dari rektor
    public function cetakDisposisi(Surat $surat)
    {
        // 1. Keamanan
        if ($surat->status == 'baru_di_bau' || $surat->status == 'di_admin_rektor') {
            return redirect()->back()->with('error', 'Surat ini belum memiliki lembar disposisi untuk dicetak.');
        }

        // 2. Load Relasi
        $surat->load(['tujuanSatker', 'disposisis.tujuanSatker', 'klasifikasis']);
        $disposisis = $surat->disposisis;

        // Data Satker Tujuan
        $satkerTujuanList = $disposisis->pluck('tujuanSatker.nama_satker')->filter()->unique()->toArray();
        
        // Data Klasifikasi
        $selectedKlasifikasiIds = $surat->klasifikasis->pluck('id')->toArray();
        $daftarKlasifikasi = \App\Models\Klasifikasi::all(); 

        // Mapping String Lama (Legacy)
        $rawKlasifikasi = $surat->klasifikasis->pluck('nama_klasifikasi')->toArray();
        if ($surat->sifat) $rawKlasifikasi[] = $surat->sifat;
        
        $klasifikasiList = [];
        foreach ($rawKlasifikasi as $text) {
            $text = trim($text);
            $klasifikasiList[] = $text;
            // Mapping istilah lama
            if (stripos($text, 'Disposisi') !== false && stripos($text, 'Untuk') === false) $klasifikasiList[] = 'Untuk Disposisi';
            if (stripos($text, 'Tindak Lanjut') !== false && stripos($text, 'Mohon') === false) $klasifikasiList[] = 'Mohon Tindak Lanjut';
            if (stripos($text, 'Siapkan') !== false && stripos($text, 'Bahan') === false) $klasifikasiList[] = 'Siapkan Bahan';
            if (stripos($text, 'Sampaikan') !== false) $klasifikasiList[] = 'Sampaikan Kpd Ybs';
            if (stripos($text, 'Agar Hadir') !== false) $klasifikasiList[] = 'Agar Menghadap Saya';
        }
        $klasifikasiList = array_values(array_unique($klasifikasiList));
        
        // Data Lainnya
        $disposisiLain = $disposisis->pluck('disposisi_lain')->filter()->unique()->implode(', ');
        $disposisiRektorPertama = $disposisis->first();

        // ==========================================================
        // 3. PROSES CETAK & GABUNG FILE (FIXED)
        // ==========================================================
        
        // A. Generate PDF Lembar Disposisi (Halaman 1) pakai DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.lembar_disposisi', [ // Pastikan nama view benar
            'surat' => $surat,
            'disposisis' => $disposisis, 
            'satkerTujuanList' => $satkerTujuanList, 
            'daftarKlasifikasi' => $daftarKlasifikasi, 
            'selectedKlasifikasiIds' => $selectedKlasifikasiIds,
            'klasifikasiList' => $klasifikasiList, 
            'disposisiRektorPertama' => $disposisiRektorPertama, 
            'disposisiLain' => $disposisiLain, 
        ])->setPaper('a4', 'portrait');

        // B. Cek File Lampiran
        $path = storage_path('app/public/' . $surat->file_surat);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // KASUS 1: Jika Lampiran adalah PDF -> Gabungkan (Merge)
        if (file_exists($path) && $ext === 'pdf') {
            
            // 1. Simpan dulu Lembar Disposisi ke temporary file
            // Kita gunakan nama unik agar tidak bentrok
            $tempPath = storage_path('app/temp_disp_' . $surat->id . '_' . uniqid() . '.pdf');
            $pdf->save($tempPath);

            // 2. Inisialisasi FPDI
            // Pastikan Anda sudah menjalankan: composer require setasign/fpdi-fpdf
            $fpdi = new Fpdi();

            // 3. Masukkan Halaman Lembar Disposisi (Dari Temp)
            try {
                $countPage = $fpdi->setSourceFile($tempPath);
                for ($i = 1; $i <= $countPage; $i++) {
                    $template = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($template);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($template);
                }

                // 4. Masukkan Halaman Lampiran Asli
                $countPageLampiran = $fpdi->setSourceFile($path);
                for ($i = 1; $i <= $countPageLampiran; $i++) {
                    $template = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($template);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($template);
                }
            } catch (\Exception $e) {
                // Jika PDF corrupt atau version tidak support, kembalikan PDF Disposisi saja
                if (file_exists($tempPath)) unlink($tempPath);
                return $pdf->stream('Disposisi_' . $surat->nomor_surat . '.pdf');
            }

            // 5. Hapus file temp
            if (file_exists($tempPath)) unlink($tempPath);

            // 6. Output Final Gabungan
            return response($fpdi->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Disposisi_' . $surat->nomor_surat . '.pdf"'
            ]);
        }

        // KASUS 2: Jika Lampiran GAMBAR atau TIDAK ADA FILE
        return $pdf->stream('Disposisi - ' . $surat->perihal . '.pdf');
    }

public function cetakDisposisiSatker($id)
{
    // 1. Cari Riwayat berdasarkan user_id (karena pengirim_id tidak ada di SQL Anda)
    $myRiwayat = \App\Models\RiwayatSurat::where('user_id', \Illuminate\Support\Facades\Auth::id())
        ->where(function($q) use ($id) {
            $q->where('surat_id', $id)
              ->orWhere('surat_keluar_id', $id);
        })
        ->whereNotNull('status_aksi')
        ->with('penerima')
        ->latest()
        ->get();

    if ($myRiwayat->isEmpty()) {
        return "Gagal: Data riwayat delegasi tidak ditemukan. Pastikan Anda sudah mendisposisikan surat ini.";
    }

    $riwayatTerakhir = $myRiwayat->first();
    
    // 2. Tentukan Sumber Surat
    $surat = null;
    if ($riwayatTerakhir->surat_id) {
        // Mencari di tabel surats
        $surat = \App\Models\Surat::find($riwayatTerakhir->surat_id);
    } elseif ($riwayatTerakhir->surat_keluar_id) {
        // Mencari di tabel surat_keluars
        $surat = \App\Models\SuratKeluar::find($riwayatTerakhir->surat_keluar_id);
    }

    if (!$surat) {
        return "Gagal: Data induk surat tidak ditemukan di tabel surats maupun surat_keluars.";
    }

    // 3. Persiapkan Data untuk View
    $penerimaList = $myRiwayat->pluck('penerima.name')->unique()->filter()->toArray();

    $data = [
        'surat' => $surat,
        'riwayat' => $riwayatTerakhir,
        'penerimaList' => $penerimaList,
        'unitKerja' => \Illuminate\Support\Facades\Auth::user()->satker->nama_satker ?? 'Unit Kerja',
        'selectedKlasifikasi' => json_decode($riwayatTerakhir->status_aksi, true) ?? $riwayatTerakhir->status_aksi,
    ];

    // 4. Generate PDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.lembar_disposisi_satker', $data)
           ->setPaper('a4', 'portrait');

    // 5. Cek Lampiran (Kedua tabel menggunakan kolom file_surat)
    $fileSurat = $surat->file_surat; 
    $path = storage_path('app/public/' . $fileSurat);

    if ($fileSurat && file_exists($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
        try {
            $tempPath = storage_path('app/temp_satker_' . $id . '_' . uniqid() . '.pdf');
            $pdf->save($tempPath);

            $fpdi = new \setasign\Fpdi\Fpdi();
            
            // Gabungkan Lembar Disposisi
            $count = $fpdi->setSourceFile($tempPath);
            for ($i = 1; $i <= $count; $i++) {
                $template = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($template);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($template);
            }

            // Gabungkan Lampiran Asli
            $countL = $fpdi->setSourceFile($path);
            for ($i = 1; $i <= $countL; $i++) {
                $template = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($template);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($template);
            }

            if (file_exists($tempPath)) unlink($tempPath);
            return response($fpdi->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Disposisi_Satker_'.$id.'.pdf"'
            ]);
        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) unlink($tempPath);
            return $pdf->stream('Disposisi_Satker_'.$id.'.pdf');
        }
    }

    return $pdf->stream('Disposisi_Satker_' . $id . '.pdf');
}
    
}