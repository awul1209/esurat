<?php

namespace App\Http\Controllers\Bau;
use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArsipSuratRektorController extends Controller
{
    public function eksternal(Request $request)
    {
        $query = SuratKeluar::with('user')
            ->whereHas('user', function($q) {
                $q->where('role', 'admin_rektor');
            })
            ->where('tipe_kirim', 'eksternal')
            ->where('status', 'selesai'); // Khusus yang sudah selesai

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        $arsip = $query->latest()->get();

        return view('bau.arsip_rektor.eksternal', compact('arsip'));
    }

    public function exportEksternal(Request $request)
    {
        $query = SuratKeluar::where('tipe_kirim', 'eksternal')->where('status', 'selesai');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        $data = $query->latest()->get();
        $fileName = 'Arsip_Surat_Rektor_Eksternal_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            fputcsv($file, ['No', 'Nomor Surat', 'Perihal', 'Tujuan', 'Via','Tgl Surat', 'Tgl Selesai']);

            foreach ($data as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->nomor_surat,
                    $row->perihal,
                    $row->tujuan_luar,
                    $row->via,
                    $row->tanggal_surat->format('d-m-Y'),
                    $row->updated_at->format('d-m-Y H:i')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

   

    /**
     * Tampilkan Daftar Arsip Internal (Status: Selesai)
     * (Untuk tahap selanjutnya setelah fitur Internal aktif)
     */
    public function internal(Request $request)
    {
        $query = SuratKeluar::with(['user', 'penerimaInternal'])
            ->whereHas('user', function($q) {
                $q->where('role', 'admin_rektor');
            })
            ->where('tipe_kirim', 'internal')
            ->where('status', 'selesai'); // Filter hanya yang sudah selesai/diteruskan

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_terusan', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

       $arsip = $query->orderByRaw('COALESCE(tanggal_terusan, created_at) DESC')->get();

        return view('bau.arsip_rektor.internal', compact('arsip'));
    }

    public function exportInternal(Request $request)
{
    // 1. Ambil Data dengan Filter
    $query = \App\Models\SuratKeluar::with(['penerimaInternal'])
        ->where('tipe_kirim', 'internal')
        ->where('status', 'selesai');

    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tanggal_terusan', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
    }

    $data = $query->latest('tanggal_terusan')->get();

    // 2. Persiapan Header File CSV (Agar Excel bisa baca formatnya dengan benar)
    $fileName = 'Arsip_Internal_Rektor_' . now()->format('Ymd_His') . '.csv';
    
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // 3. Proses Streaming Data
    $callback = function() use($data) {
        $file = fopen('php://output', 'w');
        
        // Tambahkan BOM agar karakter spesial/simbol terbaca benar di Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Kolom
        fputcsv($file, [
            'No', 
            'Nomor Surat', 
            'Tanggal Surat', 
            'Perihal', 
            'Tujuan Satker', 
            'Waktu Masuk (Rektor)', 
            'Waktu Selesai (BAU)'
        ]);

        // Isi Data
        foreach ($data as $key => $row) {
            // Gabungkan Nama Satker menjadi satu string dipisah koma
            $tujuan = $row->penerimaInternal->pluck('nama_satker')->implode(', ');

            fputcsv($file, [
                $key + 1,
                $row->nomor_surat,
                \Carbon\Carbon::parse($row->tanggal_surat)->format('d/m/Y'),
                $row->perihal,
                $tujuan,
                $row->created_at->format('d/m/Y H:i'),
                $row->tanggal_terusan ? \Carbon\Carbon::parse($row->tanggal_terusan)->format('d/m/Y H:i') : '-'
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * Export Excel Arsip Internal
     */
    // public function exportInternal(Request $request)
    // {
    //     $query = SuratKeluar::where('tipe_kirim', 'internal')
    //         ->where('status', 'selesai');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    //     }

    //     $data = $query->latest('updated_at')->get();
    //     $fileName = 'Arsip_Surat_Rektor_Internal_' . date('Y-m-d_H-i') . '.csv';

    //     return $this->generateCsv($data, $fileName, 'internal');
    // }

    /**
     * Helper Function untuk Stream CSV
     */
    private function generateCsv($data, $fileName, $tipe)
    {
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($data, $tipe) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 

            // Header Header Kolom
            if($tipe == 'eksternal') {
                fputcsv($file, ['No', 'Nomor Surat', 'Tanggal Surat', 'Perihal', 'Tujuan Eksternal', 'Via', 'Selesai Pada']);
            } else {
                fputcsv($file, ['No', 'Nomor Surat', 'Tanggal Surat', 'Perihal', 'Tujuan Satker', 'Selesai Pada']);
            }

            foreach ($data as $index => $row) {
                if($tipe == 'eksternal') {
                    fputcsv($file, [
                        $index + 1,
                        $row->nomor_surat,
                        Carbon::parse($row->tanggal_surat)->format('d-m-Y'),
                        $row->perihal,
                        $row->tujuan_luar,
                        $row->via,
                        $row->updated_at->format('d-m-Y H:i')
                    ]);
                } else {
                    // Logic Internal (menyesuaikan kolom tujuan satker Anda)
                    fputcsv($file, [
                        $index + 1,
                        $row->nomor_surat,
                        Carbon::parse($row->tanggal_surat)->format('d-m-Y'),
                        $row->perihal,
                        $row->tujuan_satker_names ?? '-', // Sesuaikan dengan field/accessor Anda
                        $row->updated_at->format('d-m-Y H:i')
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // log eksternal
    /**
 * Log Activity khusus untuk Arsip Rektor eksternal
 */
public function getLog($id)
{
    $surat = \App\Models\SuratKeluar::with('user')->findOrFail($id);
    $history = [];

    // 1. TAHAP PENGAJUAN (Waktu asli saat rektor input)
    $history[] = [
        'status_aksi' => 'Permohonan Masuk',
        'catatan'     => "Surat dari Rektor menunggu verifikasi BAU. (Via: " . ($surat->via ?? '-') . ")",
        'tanggal_f'   => $surat->created_at->isoFormat('D MMMM Y [pukul] HH.mm') . ' WIB',
        'user_name'   => $surat->user->name ?? 'Admin Rektor'
    ];

    // 2. TAHAP PROSES & SELESAI
    if ($surat->status == 'selesai') {
        // Jika sudah selesai, kita ambil waktu "Selesai" dari kolom tanggal_terusan (yang baru dibuat)
        // Dan waktu "Proses" kita ambil dari updated_at (saat terakhir kali statusnya 'proses')
        
        // Logika Amankan Waktu:
        $waktuSelesai = $surat->tanggal_terusan ? \Carbon\Carbon::parse($surat->tanggal_terusan) : $surat->updated_at;
        
        // Agar logis, waktu proses kita set 1 menit setelah dibuat jika datanya tidak terekam khusus
        $waktuProses = $surat->created_at->addMinute(); 

        $history[] = [
            'status_aksi' => 'Diproses BAU',
            'catatan'     => 'BAU sedang menyiapkan dokumen dan pengiriman.',
            'tanggal_f'   => $waktuProses->isoFormat('D MMMM Y [pukul] HH.mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];

        $history[] = [
            'status_aksi' => 'Selesai / Diarsipkan',
            'catatan'     => 'Surat sudah dikirim ke tujuan dan resmi diarsipkan oleh BAU.',
            'tanggal_f'   => $waktuSelesai->isoFormat('D MMMM Y [pukul] HH.mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];
    } elseif ($surat->status == 'proses') {
        $history[] = [
            'status_aksi' => 'Diproses BAU',
            'catatan'     => 'BAU sedang menyiapkan dokumen dan pengiriman.',
            'tanggal_f'   => $surat->updated_at->isoFormat('D MMMM Y [pukul] HH.mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];
    }

    return response()->json(['riwayats' => $history]);
}


// internal log
public function getLogInternal($id)
{
    // Load relasi user (pengirim) dan penerimaInternal (satker tujuan + data pivot)
    $surat = \App\Models\SuratKeluar::with(['user', 'penerimaInternal'])->findOrFail($id);
    $history = [];

    // TAHAP 1: OLEH REKTOR
    $history[] = [
        'status_aksi' => 'Permohonan Masuk',
        'catatan'     => 'Surat internal diajukan oleh Admin Rektor dan masuk ke antrean verifikasi BAU.',
        'tanggal_f'   => $surat->created_at->isoFormat('D MMMM Y, HH:mm') . ' WIB',
        'user_name'   => $surat->user->name ?? 'Admin Rektor'
    ];

    // TAHAP 2: OLEH BAU (PENERUSAN)
    if ($surat->status == 'selesai') {
        $history[] = [
            'status_aksi' => 'Selesai & Diteruskan',
            'catatan'     => 'Admin BAU telah memverifikasi dokumen dan meneruskannya ke semua Satker tujuan.',
            'tanggal_f'   => $surat->tanggal_terusan 
                             ? \Carbon\Carbon::parse($surat->tanggal_terusan)->isoFormat('D MMMM Y, HH:mm') . ' WIB'
                             : $surat->updated_at->isoFormat('D MMMM Y, HH:mm') . ' WIB',
            'user_name'   => 'Admin BAU'
        ];
    }

    // TAHAP 3: OLEH SATKER TUJUAN (MEMBACA & MENGARSIPKAN)
    // Kita cek satu-persatu satker yang sudah berinteraksi
    foreach ($surat->penerimaInternal as $satker) {
        $pivot = $satker->pivot;
        
        // Cek jika Satker sudah mengarsipkan (is_read = 2)
        if ($pivot->is_read == 2) {
            $history[] = [
                'status_aksi' => 'Diarsipkan oleh Satker',
                'catatan'     => 'Surat resmi diterima dan diarsipkan oleh: ' . $satker->nama_satker,
                // Menggunakan updated_at di pivot sebagai waktu pengarsipan
                'tanggal_f'   => \Carbon\Carbon::parse($pivot->updated_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user_name'   => 'Admin ' . $satker->nama_satker
            ];
        } 
        // Jika baru sekedar dibaca (is_read = 1)
        elseif ($pivot->is_read == 1) {
            $history[] = [
                'status_aksi' => 'Dibaca oleh Satker',
                'catatan'     => 'Surat telah dibuka/dibaca oleh: ' . $satker->nama_satker,
                'tanggal_f'   => \Carbon\Carbon::parse($pivot->updated_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user_name'   => 'Admin ' . $satker->nama_satker
            ];
        }
    }

    return response()->json(['riwayats' => $history]);
}
}