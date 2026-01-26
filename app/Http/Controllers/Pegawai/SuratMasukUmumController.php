<?php
namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Surat;
use App\Models\SuratKeluar;

class SuratMasukUmumController extends Controller
{
    public function index(Request $request)
{
    $user = Auth::user();
    $from = $request->from;
    $to = $request->to;
    $tipe = $request->tipe;

    // 1. Query Dasar untuk Tabel Surat (Eksternal/Univ)
    $querySurat = Surat::whereHas('riwayats', function($q) use ($user) {
        $q->where('penerima_id', $user->id)->where('status_aksi', 'LIKE', '%Informasi%');
    });

    // 2. Query Dasar untuk Tabel SuratKeluar (Internal Satker)
    $queryDelegasi = SuratKeluar::whereHas('riwayats', function($q) use ($user) {
        $q->where('penerima_id', $user->id)->where('status_aksi', 'LIKE', '%Informasi%');
    });

    // --- LOGIKA FILTER TANGGAL (Diterapkan pada Query) ---
    if ($from && $to) {
        $querySurat->whereBetween('tanggal_surat', [$from, $to]);
        $queryDelegasi->whereBetween('tanggal_surat', [$from, $to]);
    }

    $dataSurat = $querySurat->get();
    $dataDelegasi = $queryDelegasi->get();

    // 3. Gabungkan dan Transformasi Data
    $suratUmum = $dataSurat->concat($dataDelegasi)->map(function($item) use ($user) {
        // Tentukan Tipe Label
        $item->tipe_label = ($item instanceof Surat) ? ucfirst($item->tipe_surat) : 'Internal';
        
        // Tentukan Asal Surat
        $item->surat_dari_display = ($item instanceof Surat) 
            ? $item->surat_dari 
            : ($item->user->satker->nama_satker ?? 'Rektorat');
        
        // Ambil Tanggal Terima dari Riwayat
        $logRiwayat = $item->riwayats->where('penerima_id', $user->id)->first();
        $item->tgl_display = $logRiwayat ? $logRiwayat->created_at : $item->tanggal_surat;
        
        return $item;
    });

    // --- LOGIKA FILTER TIPE (Diterapkan pada Collection) ---
    if ($tipe) {
        $suratUmum = $suratUmum->filter(function($item) use ($tipe) {
            return strtolower($item->tipe_label) == strtolower($tipe);
        });
    }

    // Urutkan dan Hilangkan Duplikat
    $suratUmum = $suratUmum->unique('nomor_surat')->sortByDesc('tgl_display');

    return view('pegawai.surat_masuk_umum', compact('suratUmum'));
}
   public function exportExcel(Request $request)
{
    $user = Auth::user();
    $from = $request->from;
    $to = $request->to;
    $tipe = $request->tipe;

    // --- 1. Query Dasar Jalur Eksternal/Univ (surats) ---
    // Hanya mengambil yang status aksinya 'Informasi' (Sebar Semua)
    $qEks = \App\Models\Surat::query()->whereHas('riwayats', function($q) use ($user) {
        $q->where('penerima_id', $user->id)
          ->where('status_aksi', 'LIKE', '%Informasi%');
    });

    // --- 2. Query Dasar Jalur Internal Satker (surat_keluars) ---
    // Hanya mengambil yang status aksinya 'Informasi' (Sebar Semua)
    $qInt = \App\Models\SuratKeluar::query()->whereHas('riwayats', function($q) use ($user) {
        $q->where('penerima_id', $user->id)
          ->where('status_aksi', 'LIKE', '%Informasi%');
    });

    // --- 3. Filter Tanggal (Diterapkan langsung pada Query) ---
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

    // Urutkan berdasarkan tanggal terbaru
    $data = $data->sortByDesc(function($item) {
        return \Carbon\Carbon::parse($item['tanggal']);
    });

    // --- 6. Proses Download CSV ---
    $fileName = 'Laporan_Surat_Umum_' . now()->format('Ymd') . '.csv';
    $headers = [ 
        "Content-type" => "text/csv", 
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $callback = function() use($data) {
        $file = fopen('php://output', 'w');
        
        // Tambahkan UTF-8 BOM agar Excel Windows langsung rapi
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header kolom CSV
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
}