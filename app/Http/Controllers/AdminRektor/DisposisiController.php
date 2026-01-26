<?php

namespace App\Http\Controllers\AdminRektor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\Satker;
use App\Models\Klasifikasi;
use App\Models\Disposisi;
use App\Models\RiwayatSurat;
use App\Models\User; // Tambahkan ini
use Carbon\Carbon;
use App\Services\WaService; // Tambahkan ini

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use setasign\Fpdi\Fpdi;

class DisposisiController extends Controller
{
    public function show(Surat $surat)
    {
        if ($surat->status != 'di_admin_rektor') {
            return redirect()->route('home')->with('error', 'Surat ini sudah diproses atau tidak berada di Admin Rektor.');
        }

        $daftarSatker = Satker::orderBy('nama_satker')->get();
        $daftarKlasifikasi = Klasifikasi::orderBy('kode_klasifikasi')->get();
        
        return view('admin_rektor.disposisi_show', compact(
            'surat', 
            'daftarSatker', 
            'daftarKlasifikasi'
        ));
    }


    // disposisi rektor dan arsip
 public function store(Request $request, Surat $surat)
{
    // 1. Validasi Input
    $request->validate([
        'tujuan_satker_ids' => 'nullable|array', 
        'disposisi_lain'    => 'nullable|string', 
        'catatan_rektor'    => 'nullable|string',
        'klasifikasi_ids'   => 'required|array', 
        'klasifikasi_ids.*' => 'exists:klasifikasis,id', 
    ]);

    $user = Auth::user();
    $catatanRektor = $request->catatan_rektor;
    $disposisiLain = $request->disposisi_lain;
    $klasifikasiId = $request->klasifikasi_ids[0] ?? null;

    // 2. Simpan Klasifikasi
    $surat->klasifikasis()->sync($request->klasifikasi_ids);

    // Bersihkan input array
    $inputSatkerIds = $request->input('tujuan_satker_ids', []);
    $cleanSatkerIds = array_filter($inputSatkerIds, function($value) {
        return $value !== 'lainnya';
    });

    // Ambil semua ID Admin BAU untuk notifikasi
    $bauUserIds = \App\Models\User::where('role', 'bau')->pluck('id')->toArray();
    $link = route('login');

    // ========================================================================
    // KASUS A: ADA TUJUAN DISPOSISI
    // ========================================================================
    if (!empty($cleanSatkerIds) || !empty($disposisiLain)) {
        
        $tujuanNames = [];

        foreach ($cleanSatkerIds as $satkerId) {
            Disposisi::create([
                'surat_id'          => $surat->id,
                'tujuan_satker_id'  => $satkerId,
                'user_id'           => $user->id,
                'klasifikasi_id'    => $klasifikasiId,
                'catatan_rektor'    => $catatanRektor,
                'tanggal_disposisi' => now(),
            ]);
            
            $s = Satker::find($satkerId);
            if ($s) $tujuanNames[] = $s->nama_satker;
        }

        if (!empty($disposisiLain)) {
            Disposisi::create([
                'surat_id'          => $surat->id,
                'disposisi_lain'    => $disposisiLain,
                'user_id'           => $user->id,
                'klasifikasi_id'    => $klasifikasiId,
                'catatan_rektor'    => $catatanRektor,
                'tujuan_satker_id'  => null,
                'tanggal_disposisi' => now(),
            ]);
            $tujuanNames[] = $disposisiLain . ' (Eksternal)';
        }

        $surat->update(['status' => 'didisposisi']);
        $tujuanStr = implode(', ', $tujuanNames);
        
        RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Disposisi Rektor',
            'catatan'     => 'Rektor mendisposisikan surat ke: ' . $tujuanStr . '. (Menunggu BAU meneruskan).'
        ]);

        // NOTIFIKASI EMAIL KE BAU (INFO DISPOSISI)
        if (!empty($bauUserIds)) {
            $details = [
                'subject'    => '🔴 DISPOSISI REKTOR: ' . $surat->perihal,
                'greeting'   => 'Yth. Admin BAU,',
                'body'       => "Rektor telah memberikan DISPOSISI pada surat berikut:\n\n" .
                                "Asal Surat: {$surat->surat_dari}\n" .
                                "No. Agenda: {$surat->no_agenda}\n" .
                                "Tujuan Disposisi: {$tujuanStr}\n" .
                                "Catatan Rektor: " . ($catatanRektor ?? '-') . "\n\n" .
                                "Mohon segera login dan teruskan surat tersebut ke Satker terkait.",
                'actiontext' => 'Lihat Disposisi',
                'actionurl'  => $link,
                'file_url'   => asset('storage/' . $surat->file_surat)
            ];
            \App\Helpers\EmailHelper::kirimNotif($bauUserIds, $details);
        }

    } 
    // ========================================================================
    // KASUS B: TIDAK ADA TUJUAN (LANGSUNG SELESAI/ARSIP)
    // ========================================================================
    else {
        $surat->update(['status' => 'arsip rektor']);

        RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Selesai (Arsip Rektor)',
            'catatan'     => 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).'
        ]);

        // NOTIFIKASI EMAIL KE BAU (INFO ARSIP/SELESAI)
        if (!empty($bauUserIds)) {
            $details = [
                'subject'    => '🟢 SURAT SELESAI/ARSIP: ' . $surat->perihal,
                'greeting'   => 'Yth. Admin BAU,',
                'body'       => "Rektor telah meninjau surat berikut dan memutuskan untuk DIARSIPKAN (SELESAI):\n\n" .
                                "Asal Surat: {$surat->surat_dari}\n" .
                                "No. Agenda: {$surat->no_agenda}\n" .
                                "Perihal: {$surat->perihal}\n\n" .
                                "Surat ini TIDAK PERLU diteruskan ke unit lain. Status sistem otomatis diset menjadi Selesai.",
                'actiontext' => 'Lihat Arsip Surat',
                'actionurl'  => $link
            ];
            \App\Helpers\EmailHelper::kirimNotif($bauUserIds, $details);
        }
    }

    // Redirect Berdasarkan Tipe Surat
    $redirectMsg = ($surat->tipe_surat == 'internal') 
        ? 'Surat Internal telah diproses.' 
        : 'Surat Eksternal telah diproses.';
    
    $routeName = ($surat->tipe_surat == 'internal') 
        ? 'adminrektor.suratmasuk.internal' 
        : 'adminrektor.suratmasuk.index';

    return redirect()->route($routeName)->with('success', $redirectMsg);
}


// RIWAYAT DISPOSISI
   public function riwayat(Request $request)
    {
        // Query Dasar
        $query = Surat::with(['disposisis.tujuanSatker']) // Load relasi disposisi
            ->whereIn('tujuan_tipe', ['rektor', 'universitas']) // Filter Surat Tujuan Rektor
            
            // --- PERBAIKAN LOGIKA DISINI ---
            // Hanya ambil surat yang statusnya menunjukkan proses disposisi / diteruskan ke satker.
            // Kita KELUARKAN status: 'selesai', 'diarsipkan', 'disimpan' (karena ini masuk Arsip Rektor)
            ->whereIn('status', [
                'didisposisi',      // Sedang proses diteruskan ke satker
                'di_satker',        // Sudah diterima satker
                'arsip_satker',     // Sudah diselesaikan oleh satker
                'selesai_edaran',    // Edaran yang sudah disebar
                'diarsipkan',    // Edaran yang sudah disebar
                'selesai'
            ]);

        // --- FILTER TANGGAL ---
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // --- FILTER TIPE SURAT ---
        if ($request->filled('tipe_surat') && $request->tipe_surat != 'semua') {
            $query->where('tipe_surat', $request->tipe_surat);
        }

        // Ambil Data (Urutkan dari yang terbaru diterima)
        $suratSelesai = $query->latest('diterima_tanggal')->get();
        
        return view('admin_rektor.riwayat_disposisi_index', compact('suratSelesai'));
    }

   public function exportRiwayat(Request $request)
{
    // Tangkap Input Filter
    $startDate = $request->start_date;
    $endDate   = $request->end_date;
    $tipeSurat = $request->tipe_surat;

    // Buat Class Export Anonymous
    $export = new class($startDate, $endDate, $tipeSurat) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
        
        protected $startDate, $endDate, $tipeSurat;

        public function __construct($startDate, $endDate, $tipeSurat) {
            $this->startDate = $startDate;
            $this->endDate   = $endDate;
            $this->tipeSurat = $tipeSurat;
        }

        public function collection() {
            // QUERY DISESUAIKAN DENGAN VIEW (Hanya yang didisposisi/diteruskan)
            $query = Surat::with(['disposisis.tujuanSatker', 'tujuanSatker', 'tujuanUser']) // Load relasi untuk mapping
                ->whereIn('tujuan_tipe', ['rektor', 'universitas'])
                ->whereIn('status', [
                    'didisposisi',      // Proses jalan ke satker
                    'di_satker',        // Sampai di satker
                    'arsip_satker',     // Selesai di satker
                    'diarsipkan',     // BEM / ORMAWA / Lainnya
                    'selesai_edaran',    // Edaran selesai
                    'selesai'
                ]);
                // Status 'selesai', 'diarsipkan', 'disimpan' SUDAH DIHAPUS (Masuk Arsip Rektor)

            // Filter Tanggal
            if ($this->startDate && $this->endDate) {
                $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
            }

            // Filter Tipe
            if ($this->tipeSurat && $this->tipeSurat != 'semua') {
                $query->where('tipe_surat', $this->tipeSurat);
            }

            return $query->latest('diterima_tanggal')->get();
        }

        public function headings(): array {
            return [
                'No', 
                'No Agenda', 
                'No Surat', 
                'Tanggal Surat', 
                'Pengirim', 
                'Perihal', 
                'Status Terakhir',
                'Tujuan Akhir (Disposisi)', // Kolom Baru agar informatif
                'Link File'
            ];
        }

        public function map($surat): array {
            static $no = 0; $no++;
            
            // 1. Generate Link File
            $link = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

            // 2. Logika Tujuan Akhir (Mirip dengan View)
            $tujuanText = '-';
            $tipe = $surat->tujuan_tipe;

            if ($tipe == 'universitas' || $tipe == 'rektor') {
                // Ambil data dari relasi disposisi
                $targets = [];
                foreach($surat->disposisis as $d) {
                    if($d->tujuanSatker) {
                        $targets[] = $d->tujuanSatker->nama_satker;
                    } elseif($d->disposisi_lain) {
                        $targets[] = $d->disposisi_lain;
                    }
                }
                if(count($targets) > 0) {
                    $tujuanText = implode(', ', $targets);
                } else {
                    $tujuanText = 'Menunggu Disposisi';
                }
            } elseif ($tipe == 'satker') {
                $tujuanText = $surat->tujuanSatker->nama_satker ?? '-';
            } elseif ($tipe == 'pegawai') {
                $tujuanText = $surat->tujuanUser->name ?? '-';
            } elseif ($tipe == 'edaran_semua_satker') {
                $tujuanText = 'Semua Satker (Edaran)';
            }
            
            return [
                $no,
                $surat->no_agenda,
                $surat->nomor_surat,
                Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                $surat->surat_dari,
                $surat->perihal,
                $surat->status,     // Status (didisposisi, di_satker, dll)
                $tujuanText,        // Hasil logika tujuan
                $link
            ];
        }

        public function styles(Worksheet $sheet) {
            // Bold Header
            return [ 
                1 => ['font' => ['bold' => true]],
            ];
        }
    };

    return Excel::download($export, 'Riwayat_Disposisi_Rektor_' . date('d-m-Y_H-i') . '.xlsx');
}

 /**
     * Perhatikan: Nama function diganti jadi 'detail' 
     * agar sesuai dengan pesan error Laravel.
     */

//  LOG RIWAYAT DISPOSISI
  public function detail($id)
{
    try {
        $surat = \App\Models\Surat::findOrFail($id);

        // Ambil riwayat hanya dari Admin Pusat (BAU & Rektor)
        // Ini otomatis menghilangkan riwayat 'Informasi Umum' atau 'Delegasi' dari Satker
        $riwayats = $surat->riwayats()
            ->whereHas('user', function($q) {
                $q->whereIn('role', ['admin', 'bau', 'admin_rektor']);
            })
            ->latest()
            ->get();

        $formattedData = $riwayats->map(function($item) {
            return [
                'status_aksi' => $item->status_aksi, 
                'catatan'     => $item->catatan,
                'tanggal_f'   => \Carbon\Carbon::parse($item->created_at)->isoFormat('D MMMM Y, HH.mm') . ' WIB',
                'user_name'   => $item->user ? $item->user->name : 'Sistem'
            ];
        });

        return response()->json([
            'status'   => 'success',
            'perihal'  => $surat->perihal,
            'riwayats' => $formattedData
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}