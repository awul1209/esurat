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

    $querySurat = \App\Models\Surat::query()
        ->where(function($mainQuery) use ($user) {
            $mainQuery->whereHas('riwayats', function($q) use ($user) {
                $q->where('penerima_id', $user->id)
                  ->where('status_aksi', 'NOT LIKE', '%Informasi%');
            })
            ->orWhere('tujuan_user_id', $user->id);
        });

    $queryDelegasi = \App\Models\SuratKeluar::query()
       ->whereIn('status', ['Terkirim', 'Selesai'])
        ->whereHas('riwayats', function($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->where('status_aksi', 'NOT LIKE', '%Informasi%');
        });

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

    $suratUntukSaya = $dataDelegasi->concat($dataSurat)->map(function($item) use ($user) {
        $log = $item->riwayats->where('penerima_id', (int)$user->id)->first();
        
        $item->tipe_label = ($item instanceof \App\Models\Surat) ? ucfirst($item->tipe_surat) : 'Internal';
        $item->surat_dari_display = ($item instanceof \App\Models\Surat) ? $item->surat_dari : ($item->user->satker->nama_satker ?? 'Rektorat');
        
        $item->riwayat_id_fix = $log ? $log->id : null;
        $item->status_aksi_fix = $log ? $log->status_aksi : 'Riwayat Tidak Ditemukan';
        $item->is_read_fix = $log ? $log->is_read : 0;
        $item->tgl_tampil = $log ? $log->created_at : $item->tanggal_surat;

        // --- PERBAIKAN LOGIKA STATUS AKSI ---
        $item->is_perlu_terima = false;

        if ($log) {
            $status = $log->status_aksi;
            // Deteksi jika status mengandung kata kunci surat masuk langsung atau tanda tangan digital selesai
            if (stripos($status, 'Surat Masuk Langsung') !== false || 
                stripos($status, 'Selesai (Ditandatangani Digital)') !== false) {
                $item->is_perlu_terima = true;
            }
        } 
        
        if ($item instanceof \App\Models\Surat && $item->tujuan_user_id == $user->id) {
            $item->is_perlu_terima = true;
        }

        return $item;
    });

    $suratUntukSaya = $suratUntukSaya->unique('nomor_surat')->sortByDesc('tgl_tampil')->values();

    if ($tipe) {
        $suratUntukSaya = $suratUntukSaya->filter(fn($item) => strtolower($item->tipe_label) == strtolower($tipe));
    }

    return view('pegawai.surat_masuk_pribadi', compact('suratUntukSaya'));
}
    // TEMPEL KODE DD INI UNTUK DEBUG
    // dd([
    //     'user_id_login' => $user->id,
    //     'jumlah_data' => $suratUntukSaya->count(),
    //     'sampel_data_pertama' => $suratUntukSaya->first() ? [
    //         'id_surat' => $suratUntukSaya->first()->id,
    //         'nomor_surat' => $suratUntukSaya->first()->nomor_surat,
    //         'tipe_objek' => get_class($suratUntukSaya->first()),
    //         'isi_riwayats_relasi' => $suratUntukSaya->first()->riwayats, // Cek relasi Eloquent
    //         'id_konfirmasi_tempel' => $suratUntukSaya->first()->id_riwayat_fix ?? 'Belum ada',
    //         'is_perlu_terima' => $suratUntukSaya->first()->is_perlu_terima ?? 'Belum ada',
    //     ] : 'Data Kosong'
    // ]);


public function exportExcel(Request $request)
{
    $user = Auth::user();
    $from = $request->from;
    $to = $request->to;
    $tipe = $request->tipe;

    // 1. Query Jalur Eksternal
    $qEks = \App\Models\Surat::query()->where(function($mainQuery) use ($user) {
        $mainQuery->whereHas('riwayats', function($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->where('status_aksi', 'NOT LIKE', '%Informasi%');
        })
        ->orWhere('tujuan_user_id', $user->id);
    });

    // 2. Query Jalur Internal (Hanya yang sudah Terkirim/Final)
    $qInt = \App\Models\SuratKeluar::query()
        ->where('status', 'Terkirim')
        ->whereHas('riwayats', function($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->where('status_aksi', 'NOT LIKE', '%Informasi%');
        });

    // 3. Filter Tanggal (Wajib diterapkan di kedua query)
    if ($from && $to) {
        $qEks->whereBetween('tanggal_surat', [$from, $to]);
        $qInt->whereBetween('tanggal_surat', [$from, $to]);
    }

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

    $data = $dataEks->concat($dataInt)->unique('nomor');

    if($tipe) {
        $data = $data->filter(fn($item) => strtolower($item['tipe']) == strtolower($tipe));
    }

    // 6. Proses Download CSV
    $fileName = 'Laporan_Surat_Pribadi_' . now()->format('Ymd') . '.csv';
    $headers = [ 
        "Content-type" => "text/csv", 
        "Content-Disposition" => "attachment; filename=$fileName",
    ];

    $callback = function() use($data) {
        $file = fopen('php://output', 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($file, ['Tipe Surat', 'Asal Surat', 'Nomor Surat', 'Perihal', 'Tanggal']);
        foreach ($data as $row) { 
            fputcsv($file, [$row['tipe'], $row['asal'], $row['nomor'], $row['perihal'], $row['tanggal']]); 
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    

public function terimaSuratLangsung($riwayatId)
{
    try {
        $riwayat = \App\Models\RiwayatSurat::findOrFail($riwayatId);
        
        // 1. Update riwayat (sebagai log aktivitas)
        $riwayat->update([
            'is_read' => 2,
            'status_aksi' => 'Surat Diterima oleh Pegawai: ' . auth()->user()->name
        ]);

        // 2. UPDATE TABEL PIVOT (Kunci utama perbaikan status)
        // Kita update baris yang sesuai dengan surat ini dan satker si penerima
        \DB::table('surat_keluar_internal_penerima')
            ->where('surat_keluar_id', $riwayat->surat_keluar_id)
            ->where('satker_id', auth()->user()->satker_id)
            ->update([
                'is_read' => 2,
                'updated_at' => now()
            ]);

        return redirect()->back()->with('success', 'Surat berhasil diterima dan status diperbarui.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}
}