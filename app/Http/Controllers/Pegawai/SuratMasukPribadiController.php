<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\SuratKeluar;
use Carbon\Carbon;

class SuratMasukPribadiController extends Controller
{
public function indexPribadiIntEks(Request $request) 
{
    $user = Auth::user();
    $from = $request->from;
    $to = $request->to;
    $tipe = $request->tipe;

    // 1. Query Dasar Tabel Surat (Eksternal/Univ)
    $querySurat = \App\Models\Surat::query()
        ->where(function($mainQuery) use ($user) {
            $mainQuery->whereHas('riwayats', function($q) use ($user) {
                $q->where('penerima_id', $user->id)
                  ->where('status_aksi', 'NOT LIKE', '%Informasi%');
            })
            ->orWhere('tujuan_user_id', $user->id);
        });

    // 2. Query Dasar Tabel SuratKeluar (Internal Satker)
    $queryDelegasi = \App\Models\SuratKeluar::query()
        ->whereHas('riwayats', function($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->where('status_aksi', 'NOT LIKE', '%Informasi%');
        });

    // --- LOGIKA FILTER TANGGAL ---
    if ($from && $to) {
        $querySurat->whereBetween('tanggal_surat', [$from, $to]);
        $queryDelegasi->whereBetween('tanggal_surat', [$from, $to]);
    }

    $dataSurat = $querySurat->with(['riwayats' => function($q) use ($user) {
        $q->where('penerima_id', $user->id)->latest();
    }])->get();

    $dataDelegasi = $queryDelegasi->with(['riwayats' => function($q) use ($user) {
        $q->where('penerima_id', $user->id)->latest();
    }])->get();

    // 3. Gabungkan dan Transformasi Data
    $suratUntukSaya = $dataSurat->concat($dataDelegasi)->map(function($item) use ($user) {
        $log = $item->riwayats->where('penerima_id', $user->id)->first();
        
        // Tentukan Tipe Label
        $item->tipe_label = ($item instanceof \App\Models\Surat) ? ucfirst($item->tipe_surat) : 'Internal';
        $item->surat_dari_display = ($item instanceof \App\Models\Surat) ? $item->surat_dari : ($item->user->satker->nama_satker ?? 'Rektorat');
        
        $item->riwayat_id = $log ? $log->id : null;
        $item->status_penerimaan = $log ? $log->is_read : 0; 
        $item->tgl_display = $log ? $log->created_at : $item->tanggal_surat;

        // Logika Tombol Action
        $item->is_perlu_terima = false;
        if ($item->tujuan_user_id == $user->id || ($log && stripos($log->status_aksi, 'Personal') !== false)) {
            $item->is_perlu_terima = true;
        }

        return $item;
    });

    // --- LOGIKA FILTER TIPE SURAT ---
    if ($tipe) {
        $suratUntukSaya = $suratUntukSaya->filter(function($item) use ($tipe) {
            // strtolower agar filter 'internal' cocok dengan 'Internal'
            return strtolower($item->tipe_label) == strtolower($tipe);
        });
    }

    // Urutkan dan Hilangkan Duplikat
    $suratUntukSaya = $suratUntukSaya->unique('nomor_surat')->sortByDesc('tgl_display');

    return view('pegawai.surat_masuk_pribadi', compact('suratUntukSaya'));
}

public function exportExcel(Request $request)
{
    $user = Auth::user();
    $from = $request->from;
    $to = $request->to;
    $tipe = $request->tipe;

    // --- 1. Query Jalur Eksternal/Univ (surats) ---
    // Mencari yang ditujukan ke saya dan BUKAN surat umum (Informasi)
    $qEks = \App\Models\Surat::query()->where(function($mainQuery) use ($user) {
        $mainQuery->whereHas('riwayats', function($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->where('status_aksi', 'NOT LIKE', '%Informasi%');
        })
        ->orWhere('tujuan_user_id', $user->id);
    });

    // --- 2. Query Jalur Internal Satker (surat_keluars) ---
    // Mencari hasil delegasi/disposisi ke saya dan BUKAN surat umum
    $qInt = \App\Models\SuratKeluar::query()->whereHas('riwayats', function($q) use ($user) {
        $q->where('penerima_id', $user->id)
          ->where('status_aksi', 'NOT LIKE', '%Informasi%');
    });

    // --- 3. Filter Tanggal (Diterapkan di Query Database) ---
    if ($from && $to) {
        $qEks->whereBetween('tanggal_surat', [$from, $to]);
        $qInt->whereBetween('tanggal_surat', [$from, $to]);
    }

    // --- 4. Transformasi Data ---
    $dataEks = $qEks->get()->map(fn($s) => [
        'tipe' => ucfirst($s->tipe_surat), 
        'asal' => $s->surat_dari, 
        'nomor' => $s->nomor_surat, 
        'perihal' => $s->perihal, 
        'tanggal' => \Carbon\Carbon::parse($s->tanggal_surat)->format('d-m-Y')
    ]);

    $dataInt = $qInt->with('user.satker')->get()->map(fn($s) => [
        'tipe' => 'Internal', 
        'asal' => $s->user->satker->nama_satker ?? 'Rektorat', 
        'nomor' => $s->nomor_surat, 
        'perihal' => $s->perihal, 
        'tanggal' => \Carbon\Carbon::parse($s->tanggal_surat)->format('d-m-Y')
    ]);

    // Gabungkan data
    $data = $dataEks->concat($dataInt)->unique('nomor');

    // --- 5. Filter Tipe (Diterapkan pada Collection) ---
    if($tipe) {
        $data = $data->filter(function($item) use ($tipe) {
            return strtolower($item['tipe']) == strtolower($tipe);
        });
    }

    // --- 6. Proses Download CSV ---
    $fileName = 'Laporan_Surat_Pribadi_' . now()->format('Ymd') . '.csv';
    $headers = [ 
        "Content-type" => "text/csv", 
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $callback = function() use($data) {
        $file = fopen('php://output', 'w');
        // Tambahkan BOM untuk mendukung karakter khusus di Excel Windows
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, ['Tipe Surat', 'Asal Surat', 'Nomor Surat', 'Perihal', 'Tanggal']);
        
        foreach ($data as $row) { 
            fputcsv($file, [
                $row['tipe'], 
                $row['asal'], 
                $row['nomor'], 
                $row['perihal'], 
                $row['tanggal']
            ]); 
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    

public function terimaSuratLangsung($riwayatId)
{
    try {
        $riwayat = \App\Models\RiwayatSurat::findOrFail($riwayatId);
        
        // Update kolom is_read di tabel riwayat_surats
        $riwayat->update([
            'is_read' => 2, // Selesai
            'status_aksi' => 'Surat Diterima oleh Pegawai'
        ]);

        return redirect()->back()->with('success', 'Surat berhasil diterima.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}
}