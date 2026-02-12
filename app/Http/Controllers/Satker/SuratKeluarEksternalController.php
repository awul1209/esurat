<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SuratKeluar; // <--- PAKAI MODEL INI
use App\Models\RiwayatSurat; // Jika ingin mencatat log aktivitas user
use App\Models\User;      // Pastikan Model User diimport
use App\Models\Satker;

// === IMPORT LIBRARY EXCEL ===
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class SuratKeluarEksternalController extends Controller
{
   public function index(Request $request)
    {
        $userId = Auth::id();

        // 1. Query Builder
        $query = SuratKeluar::where('user_id', $userId)
                            ->where('tipe_kirim', 'eksternal');

        // 2. Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }

        // 3. Ambil Data
        $suratKeluar = $query->latest('tanggal_surat')->get();

        return view('satker.surat_keluar_eksternal.index', compact('suratKeluar'));
    }

    /**
     * Method Export Excel Eksternal
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $userId    = Auth::id();

        // Anonymous Class Export
        $export = new class($startDate, $endDate, $userId) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
            
            protected $startDate;
            protected $endDate;
            protected $userId;

            public function __construct($startDate, $endDate, $userId)
            {
                $this->startDate = $startDate;
                $this->endDate   = $endDate;
                $this->userId    = $userId;
            }

            public function collection()
            {
                $query = SuratKeluar::where('user_id', $this->userId)
                                    ->where('tipe_kirim', 'eksternal');

                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
                }

                return $query->latest('tanggal_surat')->get();
            }

            public function headings(): array
            {
                return [
                    'No',
                    'Tujuan Surat (Pihak Luar)',
                    'Nomor Surat',
                    'Perihal',
                    'Tanggal Surat',
                    'Link File'
                ];
            }

            public function map($surat): array
            {
                static $no = 0;
                $no++;

                $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

                return [
                    $no,
                    $surat->tujuan_luar, // Kolom khusus eksternal
                    $surat->nomor_surat,
                    $surat->perihal,
                    \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                    $linkFile
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        };

        return Excel::download($export, 'Laporan_Surat_Keluar_Eksternal_' . date('d-m-Y_H-i') . '.xlsx');
    }

public function create()
    {
        // 1. Ambil semua user dengan role 'pimpinan' untuk pilihan "Mengetahui"
        // Kita juga melakukan eager loading 'jabatan' agar tidak error saat memanggil $p->jabatan->nama_jabatan
        $pimpinans = User::with('jabatan')
            ->where('role', 'pimpinan')
            ->get();

        // 2. Ambil semua Satker untuk pilihan "Tembusan Tambahan"
        $satkers = Satker::all();

        // 3. Kirim kedua variabel tersebut ke view
        return view('satker.surat_keluar_eksternal.create', compact('pimpinans', 'satkers'));
    }

  public function store(Request $request)
{
    // 1. Validasi (Tetap sesuai kode Anda)
    $request->validate([
        'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat',
        'tujuan_luar'   => 'required|string|max:255',
        'perihal'       => 'required|string|max:255',
        'tanggal_surat' => 'required|date',
        'file_surat'    => 'required|file|mimes:pdf,jpg,png|max:10240',
    ], [
        'nomor_surat.unique' => 'Nomor surat ini sudah terdaftar. Mohon gunakan nomor lain.',
    ]);

    $user = Auth::user();
    $path = $request->file('file_surat')->store('surat_keluar_eksternal_satker', 'public');

    // 2. Simpan Data Utama (Simpan ke variabel $surat agar kita punya ID-nya)
    $surat = SuratKeluar::create([
        'user_id'       => $user->id,
        'nomor_surat'   => $request->nomor_surat,
        'perihal'       => $request->perihal,
        'tanggal_surat' => $request->tanggal_surat,
        'file_surat'    => $path,
        'tipe_kirim'    => 'eksternal',
        'tujuan_luar'   => $request->tujuan_luar,
        'status'        => 'Draft', // Beri status Draft dulu sebelum diatur barcodenya
        'qrcode_hash' => Str::random(40), // Pastikan ini diisi

    ]);

   // 3. Simpan Relasi Pimpinan (Mengetahui) jika ada
if ($request->has('pimpinan_ids')) {
    $pimpinanData = [];
    foreach ($request->pimpinan_ids as $pimpinanId) {
        $pimpinanData[] = [
            'pimpinan_id' => $pimpinanId,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }
    
    // Gunakan createMany karena relasinya adalah hasMany
    $surat->validasis()->createMany($pimpinanData);
}

// 4. LOGIKA TEMBUSAN (BAU OTOMATIS)
    // Ambil pilihan dari form (jika ada), kalau kosong buat array kosong
    $pilihanTembusan = $request->tembusan_ids ?? [];

    // Cari ID Satker BAU
    $bau = \App\Models\Satker::where('nama_satker', 'Biro Administrasi Umum (BAU)')->first();
    
    // Tambahkan BAU ke dalam daftar antrean simpan jika ditemukan
    if ($bau) {
        $idBauTag = 'satker_' . $bau->id;
        if (!in_array($idBauTag, $pilihanTembusan)) {
            $pilihanTembusan[] = $idBauTag;
        }
    }

    // Proses Simpan ke tabel surat_tembusans
    foreach ($pilihanTembusan as $val) {
        $dataTembusan = [
            'surat_keluar_id' => $surat->id,
            'user_id'         => null,
            'satker_id'       => null,
        ];

        if (str_starts_with($val, 'user_')) {
            $dataTembusan['user_id'] = str_replace('user_', '', $val);
        } elseif (str_starts_with($val, 'satker_')) {
            $dataTembusan['satker_id'] = str_replace('satker_', '', $val);
        }

        // Simpan hanya jika ada ID yang valid
        if ($dataTembusan['user_id'] || $dataTembusan['satker_id']) {
            \App\Models\SuratTembusan::create($dataTembusan);
        }
    }
// Di method store
return redirect()->route('satker.surat-keluar.eksternal.bubuhkan-ttd', $surat->id);
}

    public function edit(SuratKeluar $surat)
    {
        // Pastikan milik user sendiri dan tipe eksternal
        if ($surat->user_id != Auth::id() || $surat->tipe_kirim != 'eksternal') abort(403);
        
        return view('satker.surat_keluar_eksternal.edit', compact('surat'));
    }

    public function update(Request $request, SuratKeluar $surat)
    {
        if ($surat->user_id != Auth::id()) abort(403);

        $request->validate([
            // Unique: Abaikan ID surat ini agar tidak dianggap duplikat dengan dirinya sendiri
            'nomor_surat'   => 'required|string|max:255|unique:surat_keluars,nomor_surat,' . $surat->id,
            'tujuan_luar'   => 'required|string|max:255',
            'perihal'       => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'file_surat'    => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ], [
            'nomor_surat.unique' => 'Nomor surat sudah digunakan.'
        ]);

        $data = $request->only(['nomor_surat', 'tujuan_luar', 'perihal', 'tanggal_surat']);

        // Hapus file lama jika ada upload baru
        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('surat-keluar', 'public');
        }

        $surat->update($data);

        return redirect()->route('satker.surat-keluar.eksternal.index')
                         ->with('success', 'Data surat keluar eksternal berhasil diperbarui.');
    }

public function destroy(SuratKeluar $surat)
{
    // Pastikan hanya pemilik yang bisa menghapus
    if ($surat->user_id != Auth::id()) {
        abort(403, 'Anda tidak memiliki akses untuk menghapus surat ini.');
    }

    // JANGAN hapus file_surat di sini agar bisa di-restore nanti oleh BAU.
    // File hanya akan benar-benar dihapus jika dilakukan 'forceDelete' di TrashController.
    
    // Laravel otomatis merubah status menjadi soft delete karena Trait SoftDeletes sudah dipasang di Model
    $surat->delete(); 

    return redirect()->back()->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');
}

public function bubuhkanTtd($id)
{
    $surat = SuratKeluar::findOrFail($id);
    
    // Pastikan view diarahkan ke folder eksternal yang baru kita buat
    return view('satker.surat_keluar_eksternal.bubuhkan_ttd', compact('surat'));
}

public function processSignature(Request $request, $id)
{
    // 1. Inisialisasi Data
    $surat = SuratKeluar::findOrFail($id);
    $user = Auth::user();
    $satker = $user->satker;

    $positions = json_decode($request->positions, true);
    $canvasW = floatval($positions['width']);
    $canvasH = floatval($positions['height']);

    // 2. Siapkan Path File & Logo
    $sourceFile = storage_path('app/public/' . $surat->file_surat);
    $tempQrLegalPath = storage_path('app/public/temp_qr_legal_ext_' . $id . '.png');
    $tempQrTtdPath = storage_path('app/public/temp_qr_ttd_ext_' . $id . '.png');
    $logoPath = ($satker && $satker->logo_satker) ? storage_path('app/public/' . $satker->logo_satker) : null;

  // --- A. Generate QR Barcode ---

// Pastikan hash tersedia, jika null/kosong maka generate otomatis sekarang
if (empty($surat->qrcode_hash)) {
    $surat->qrcode_hash = \Illuminate\Support\Str::random(40);
    $surat->save();
}

// Sekarang variabel $surat->qrcode_hash dijamin sudah ada isinya (string)
\App\Helpers\BarcodeHelper::generateQrWithLogo(
    (string) $surat->qrcode_hash, 
    $logoPath, 
    $tempQrLegalPath, 
    true
);

$ttdData = "DOKUMEN DITANDATANGANI SECARA DIGITAL\nNama : " . $user->name . "\nNIP  : " . ($user->nip ?? '-');
\App\Helpers\BarcodeHelper::generateQrWithLogo($ttdData, $logoPath, $tempQrTtdPath, false);

    // 3. Inisialisasi FPDI & Proses Stamping
    $pdf = new \setasign\Fpdi\Fpdi();
    if (!file_exists($sourceFile)) return back()->with('error', 'File sumber tidak ditemukan.');
    
    $pageCount = $pdf->setSourceFile($sourceFile);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        $ratioX = $size['width'] / $canvasW;
        $ratioY = $size['height'] / $canvasH;

        if (isset($positions['pages'][$pageNo])) {
            $state = $positions['pages'][$pageNo];
            // Barcode TTD
            if (isset($state['ttd']) && $state['ttd']['show'] && file_exists($tempQrTtdPath)) {
                $pdf->Image($tempQrTtdPath, floatval($state['ttd']['x']) * $ratioX, floatval($state['ttd']['y']) * $ratioY, 25, 25);
            }
            // Barcode Keabsahan
            if (isset($state['qr']) && $state['qr']['show'] && file_exists($tempQrLegalPath)) {
                $pdf->Image($tempQrLegalPath, floatval($state['qr']['x']) * $ratioX, floatval($state['qr']['y']) * $ratioY, 25, 25);
            }
        }
    }

    // 4. Simpan File Final
    $fileName = 'FINAL_EXT_' . time() . '_' . basename($surat->file_surat);
    $savePath = 'surat_keluar_eksternal_satker/' . $fileName;
    $pdf->Output(storage_path('app/public/' . $savePath), 'F');

    // Hapus Temp
    if (file_exists($tempQrLegalPath)) unlink($tempQrLegalPath);
    if (file_exists($tempQrTtdPath)) unlink($tempQrTtdPath);

   // 5. Update Status & Cek Alur Validasi
    // Cek apakah ada data di tabel validasi untuk surat ini
    $hasPimpinan = $surat->validasis()->exists(); 
    
    // Jika ada pimpinan, status 'Menunggu Validasi'. Jika kosong, langsung 'Terkirim'.
    $statusBaru = $hasPimpinan ? 'Menunggu Validasi' : 'Terkirim';

    $surat->update([
        'file_surat' => $savePath,
        'status' => $statusBaru,
        'is_final' => 1
    ]);

    // 6. LOGIKA NOTIFIKASI & DISTRIBUSI
    if ($hasPimpinan) {
        // --- JIKA ADA PIMPINAN (MENGETAHUI) ---
        $pimpinanIds = $surat->validasis()->pluck('pimpinan_id')->toArray();
        
        \App\Helpers\EmailHelper::kirimNotif($pimpinanIds, [
            'subject' => 'Perlu Validasi: ' . $surat->perihal,
            'body' => "Surat eksternal dari " . ($satker->nama_satker ?? $user->name) . " memerlukan validasi Anda.",
            'actiontext' => 'Validasi Sekarang',
            'actionurl' => route('login'),
            'file_url' => asset('storage/' . $savePath)
        ]);
        
        $msg = 'Surat berhasil disimpan dan menunggu validasi pimpinan.';
    } else {
        // --- JIKA KOSONG (LANGSUNG KE BAU & TEMBUSAN LAIN) ---
        $this->distribusiKeTembusan($surat);
        $msg = 'Surat berhasil dikirim (Tembusan otomatis ke BAU).';
    }

return redirect()->route('satker.surat-keluar.eksternal.index')->with('success', $msg);
}


// api keabsahan
private function daftarkanKeabsahanKeAPI($surat)
{
    $apiUrl = 'https://docverify.wiraraja.ac.id/api/createv2.php';
    $apiKey = 'c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1c3a8f5d7e9b4c2a1';

    // Sesuaikan data berdasarkan model SuratKeluar Anda
    $postData = [
        'token_code'      => 'bapsi2026', 
        'document_number' => $surat->nomor_surat,
        'document_type'   => 'Surat Keluar Eksternal',
        'title'           => $surat->perihal,
        'issued_date'     => $surat->tanggal_surat,
        'issued_by'       => $surat->user->satker->nama_satker ?? 'Universitas Wiraraja',
        'owner_name'      => $surat->user->name,
        'pdf_path'        => 'storage/' . $surat->file_surat
    ];

    try {
        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept'    => 'application/json'
        ])->asForm()->post($apiUrl, $postData);

        if ($response->successful()) {
            $result = $response->json();
            // Simpan token verifikasi dari API ke database jika diperlukan
            $surat->update([
                'verifikasi_url' => $result['data']['verify_url'] ?? null
            ]);
            return true;
        }
        
        return false;
    } catch (\Exception $e) {
        \Log::error('API Keabsahan Error: ' . $e->getMessage());
        return false;
    }
}



/**
 * Method Helper untuk Distribusi ke Tembusan (BAU & Unit Lain)
 */
private function distribusiKeTembusan($surat)
{
    $user = Auth::user();
    $namaPengirim = $user->satker->nama_satker ?? $user->name;
    $penerimaUserIds = [];

    // 1. Ambil semua data dari satu tabel tembusans
    $semuaTembusan = $surat->tembusans;

    foreach ($semuaTembusan as $t) {
        if ($t->user_id) {
            // Jika tembusan langsung ke User
            $penerimaUserIds[] = $t->user_id;
        } elseif ($t->satker_id) {
            // Jika tembusan ke Satker, ambil User pertama dari satker tersebut
            $adminSatker = \App\Models\User::where('satker_id', $t->satker_id)->first();
            if ($adminSatker) {
                $penerimaUserIds[] = $adminSatker->id;
            }
        }
    }

    // 2. OTOMATIS: Tambahkan BAU (Wajib)
    $bau = \App\Models\Satker::where('nama_satker', 'BAU')->first();
    if ($bau) {
        $adminBau = \App\Models\User::where('satker_id', $bau->id)->first();
        if ($adminBau) {
            $penerimaUserIds[] = $adminBau->id;
        }
    }

    // 3. Bersihkan duplikat ID
    $finalUserIds = array_unique($penerimaUserIds);

    // 4. Proses simpan ke Inbox (Tabel Surat Masuk)
    foreach ($finalUserIds as $targetUid) {
        \App\Models\Surat::create([
            'surat_dari'      => $namaPengirim,
            'tipe_surat'      => 'eksternal',
            'nomor_surat'     => $surat->nomor_surat,
            'tanggal_surat'   => $surat->tanggal_surat,
            'perihal'         => $surat->perihal,
            'sifat'           => 'Biasa',
            'no_agenda'       => 'EXT-' . strtoupper(uniqid()),
            'diterima_tanggal'=> now(),
            'file_surat'      => $surat->file_surat,
            'status'          => 'proses',
            'user_id'         => $user->id,
            'tujuan_user_id'  => $targetUid,
        ]);
        
        // Kirim Email Notifikasi
        \App\Helpers\EmailHelper::kirimNotif([$targetUid], [
            'subject' => 'Tembusan Surat Eksternal: ' . $surat->perihal,
            'body'    => "Anda menerima tembusan surat dari " . $namaPengirim,
            'actiontext' => 'Buka Surat',
            'actionurl'  => route('login')
        ]);
    }
}
}