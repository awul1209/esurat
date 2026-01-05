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

    // ========================================================================
    // KASUS A: ADA TUJUAN DISPOSISI (Diteruskan ke Satker/Lainnya)
    // ========================================================================
    if (!empty($cleanSatkerIds) || !empty($disposisiLain)) {
        
        $tujuanNames = [];

        // 1. Simpan Tujuan Satker
        foreach ($cleanSatkerIds as $satkerId) {
            Disposisi::create([
                'surat_id'         => $surat->id,
                'tujuan_satker_id' => $satkerId,
                'user_id'          => $user->id,
                'klasifikasi_id'   => $klasifikasiId,
                'catatan_rektor'   => $catatanRektor,
                'tanggal_disposisi'=> now(),
            ]);
            
            $s = Satker::find($satkerId);
            if ($s) $tujuanNames[] = $s->nama_satker;
        }

        // 2. Simpan Tujuan Lain
        if (!empty($disposisiLain)) {
            Disposisi::create([
                'surat_id'         => $surat->id,
                'disposisi_lain'   => $disposisiLain,
                'user_id'          => $user->id,
                'klasifikasi_id'   => $klasifikasiId,
                'catatan_rektor'   => $catatanRektor,
                'tujuan_satker_id' => null,
                'tanggal_disposisi'=> now(),
            ]);
            $tujuanNames[] = $disposisiLain . ' (Eksternal)';
        }

        // 3. Update Status
        $surat->update(['status' => 'didisposisi']);

        // 4. Catat Riwayat
        $tujuanStr = implode(', ', $tujuanNames);
        
        RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Disposisi Rektor',
            'catatan'     => 'Rektor mendisposisikan surat ke: ' . $tujuanStr . '. (Menunggu BAU meneruskan).'
        ]);

        // 5. NOTIFIKASI WA (KE BAU - INFO DISPOSISI)
        try {
            $adminBaus = User::where('role', 'bau')->get();
            $nomorHpList = [];

            foreach ($adminBaus as $admin) {
                if ($admin->no_hp) {
                    $pecahan = explode(',', $admin->no_hp);
                    foreach($pecahan as $hp) $nomorHpList[] = trim($hp);
                }
            }

            $link = route('login'); 
            
            foreach ($nomorHpList as $hpTarget) {
                if(empty($hpTarget)) continue;

                $pesan = 
"📩 *Notifikasi Surat Telah Didisposisi*

Yth. Admin BAU,
Rektor telah melakukan disposisi pada surat berikut:

Asal Surat       : {$surat->surat_dari}
No. Agenda       : {$surat->no_agenda}
Perihal          : {$surat->perihal}
Tujuan Disposisi : {$tujuanStr}

Mohon segera LOGIN dan TERUSKAN surat fisik/digital ke Satker terkait.
Link: {$link}

Pesan otomatis Sistem e-Surat.";

                WaService::send($hpTarget, $pesan);
            }
        } catch (\Exception $e) {}

       if ($surat->tipe_surat == 'internal') {
            // Jika surat internal, arahkan ke route khusus internal
            return redirect()->route('adminrektor.suratmasuk.internal')
                ->with('success', 'Surat Internal telah didisposisi dan masuk Arsip.');
        } else {
            // Jika bukan internal (berarti eksternal), arahkan ke route index/eksternal
            return redirect()->route('adminrektor.suratmasuk.index')
                ->with('success', 'Surat Eksternal telah didisposisi dan masuk Arsip.');
        }
    }

    // ========================================================================
    // KASUS B: TIDAK ADA TUJUAN (LANGSUNG SELESAI/ARSIP)
    // ========================================================================
    else {
        $surat->update(['status' => 'selesai']);

        RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $user->id,
            'status_aksi' => 'Selesai (Arsip Rektor)',
            'catatan'     => 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).'
        ]);

        // ===============================================================
        // NOTIFIKASI WA (BARU: KE BAU - INFO ARSIP/SELESAI)
        // ===============================================================
        try {
            // A. Ambil semua Admin BAU
            $adminBaus = User::where('role', 'bau')->get();
            $nomorHpList = [];

            // B. Kumpulkan & Pecah Nomor HP
            foreach ($adminBaus as $admin) {
                if ($admin->no_hp) {
                    $pecahan = explode(',', $admin->no_hp);
                    foreach($pecahan as $hp) $nomorHpList[] = trim($hp);
                }
            }

            // C. Kirim Pesan (Konten berbeda: Memberitahu Arsip)
            $link = route('login'); 
            
            foreach ($nomorHpList as $hpTarget) {
                if(empty($hpTarget)) continue;

                $pesan = 
"📂 *Notifikasi Surat Diarsipkan (Selesai)*

Yth. Admin BAU,
Rektor telah meninjau surat berikut dan memutuskan untuk **DIARSIPKAN (SELESAI)**:

Asal Surat : {$surat->surat_dari}
No. Agenda : {$surat->no_agenda}
Perihal    : {$surat->perihal}

Surat ini **TIDAK PERLU** diteruskan/didisposisikan ke unit lain.
Status sistem: Selesai.

Link: {$link}
Pesan otomatis Sistem e-Surat.";

                WaService::send($hpTarget, $pesan);
            }

        } catch (\Exception $e) {}
        // ===============================================================

        if ($surat->tipe_surat == 'Internal') {
            // Jika surat internal, arahkan ke route khusus internal
            return redirect()->route('adminrektor.suratmasuk.internal')
                ->with('success', 'Surat Internal telah didisposisi dan masuk Arsip.');
        } else {
            // Jika bukan internal (berarti eksternal), arahkan ke route index/eksternal
            return redirect()->route('adminrektor.suratmasuk.index')
                ->with('success', 'Surat Eksternal telah didisposisi dan masuk Arsip.');
        }
    }
}

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
                    'selesai_edaran'    // Edaran selesai
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

public function showRiwayatDetail(Surat $surat)
    {
        // Load relasi riwayats (urutkan terbaru) dan user pelakunya
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }
}