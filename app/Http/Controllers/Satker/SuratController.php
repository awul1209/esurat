<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\User;
use App\Models\RiwayatSurat;
use App\Models\Satker; 
use App\Models\Disposisi; // Pastikan import model Disposisi
use Illuminate\Support\Facades\DB; 

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuratController extends Controller
{
 public function indexMasukEksternal(Request $request) 
{
    $user = Auth::user();
    $satkerId = $user->satker_id;
    $myUserId = $user->id;

    $daftarPegawai = User::where('satker_id', $satkerId)
                        ->whereIn('role', ['pegawai', 'dosen'])
                        ->orderBy('name', 'asc')
                        ->get();
    
    $query = Surat::query() 
        ->where(function($query) use ($satkerId) {
            $query->whereHas('disposisis', function ($q) use ($satkerId) {
                $q->where('tujuan_satker_id', $satkerId);
            })
            ->orWhere('tujuan_satker_id', $satkerId);
        })
        ->where(function($q) {
            $q->where('tipe_surat', '!=', 'internal')
              ->orWhereNull('tipe_surat');
        })
        ->whereIn('status', ['di_satker', 'selesai', 'arsip_satker', 'didisposisi', 'terkirim']);

    // --- PERBAIKAN FILTER TANGGAL: MENGGUNAKAN TANGGAL SURAT ---
    if ($request->filled('start_date') && $request->filled('end_date')) {
        // Filter sekarang mengacu pada kolom tanggal_surat
        $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
    }

    $suratMasukSatker = $query->with(['disposisis.tujuanSatker', 'tujuanUser', 'tujuanSatker', 'delegasiPegawai', 'riwayats']) 
        ->latest('diterima_tanggal') // Tetap urutkan tampilan berdasarkan yang terbaru diterima
        ->get();

    // --- TRANSFORMASI DATA UNTUK BLADE ---
    $suratMasukSatker->transform(function ($surat) use ($myUserId) {
        $surat->isMyInput = ($surat->user_id == $myUserId);

        $myLogs = $surat->riwayats->where('user_id', $myUserId);
        
        $surat->isProcessed = $myLogs->filter(function($r) {
            $aksi = strtolower($r->status_aksi);
            return str_contains($aksi, 'disposisi') || 
                   str_contains($aksi, 'informasi umum') || 
                   str_contains($aksi, 'delegasi');
        })->isNotEmpty();

        return $surat;
    });

    $satker = Satker::find($satkerId);
    $suratEdaran = $satker->suratEdaran()->with('riwayats.user')->get();
    
    return view('satker.surat-masuk-eksternal', compact(
        'suratMasukSatker',
        'suratEdaran',
        'daftarPegawai'
    ));
}
//  public function indexMasukEksternal()
// {
//     $user = Auth::user();
//     $satkerId = $user->satker_id;
//     $myUserId = $user->id;

//     $daftarPegawai = User::where('satker_id', $satkerId)
//                         ->whereIn('role', ['pegawai', 'dosen'])
//                         ->orderBy('name', 'asc')
//                         ->get();
    
//     $suratMasukSatker = Surat::query()
//         ->where(function($query) use ($satkerId) {
//             $query->whereHas('disposisis', function ($q) use ($satkerId) {
//                 $q->where('tujuan_satker_id', $satkerId);
//             })
//             ->orWhere('tujuan_satker_id', $satkerId);
//         })
//         ->where(function($q) {
//             $q->where('tipe_surat', '!=', 'internal')
//               ->orWhereNull('tipe_surat');
//         })
//         ->whereIn('status', ['di_satker', 'selesai', 'arsip_satker', 'didisposisi', 'terkirim'])
//         ->with(['disposisis.tujuanSatker', 'tujuanUser', 'tujuanSatker', 'delegasiPegawai', 'riwayats']) 
//         ->latest('diterima_tanggal')
//         ->get();

//   // --- TRANSFORMASI DATA UNTUK BLADE ---
//     $suratMasukSatker->transform(function ($surat) use ($myUserId) {
//         $surat->isMyInput = ($surat->user_id == $myUserId);

//         // Ambil riwayat khusus yang dibuat oleh Admin ini
//         $myLogs = $surat->riwayats->where('user_id', $myUserId);
        
//         // PERBAIKAN FILTER: 
//         // Pastikan str_contains hanya mendeteksi tindakan distribusi pegawai.
//         // "Input Manual (Arsip Eksternal)" tidak mengandung kata di bawah, sehingga isProcessed tetap false.
//         $surat->isProcessed = $myLogs->filter(function($r) {
//             $aksi = strtolower($r->status_aksi);
//             // Menambah pengecekan 'delegasi' agar lebih aman
//             return str_contains($aksi, 'disposisi') || 
//                    str_contains($aksi, 'informasi umum') || 
//                    str_contains($aksi, 'delegasi');
//         })->isNotEmpty();

//         return $surat;
//     });

//     $satker = Satker::find($satkerId);
//     $suratEdaran = $satker->suratEdaran()->with('riwayats.user')->get();
    
//     return view('satker.surat-masuk-eksternal', compact(
//         'suratMasukSatker',
//         'suratEdaran',
//         'daftarPegawai'
//     ));
// }
 public function exportMasukEksternal(Request $request)
{
    $startDate = $request->start_date;
    $endDate   = $request->end_date;
    $user      = Auth::user();
    $satkerId  = $user->satker_id;

    $export = new class($startDate, $endDate, $user, $satkerId) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithStyles {
        
        protected $startDate;
        protected $endDate;
        protected $user;
        protected $satkerId;

        public function __construct($startDate, $endDate, $user, $satkerId)
        {
            $this->startDate = $startDate;
            $this->endDate   = $endDate;
            $this->user      = $user;
            $this->satkerId  = $satkerId;
        }

        public function collection()
        {
            // ==========================================
            // BAGIAN 1: SURAT MASUK (LOGIKA DARI INDEX)
            // ==========================================
            $query1 = \App\Models\Surat::query();

            $query1->where(function($masterQ) {
                $masterQ->whereHas('disposisis', function ($q) {
                    $q->where('tujuan_satker_id', $this->satkerId);
                })
                ->orWhere('tujuan_satker_id', $this->satkerId);
            });

            $query1->whereIn('status', ['di_satker', 'selesai', 'arsip_satker', 'didisposisi', 'terkirim']);

            $query1->where(function($q) {
                $q->where('tipe_surat', '!=', 'internal')
                  ->orWhereNull('tipe_surat');
            });

            // --- PERBAIKAN FILTER TANGGAL: MENGGUNAKAN TANGGAL SURAT ---
            if ($this->startDate && $this->endDate) {
                $query1->whereBetween('tanggal_surat', [$this->startDate, $this->endDate]);
            }
            
            $suratMasuk = $query1->get();

            // ==========================================
            // BAGIAN 2: SURAT EDARAN (LOGIKA DARI INDEX)
            // ==========================================
            $query2 = \App\Models\Surat::select('surats.*')
                    ->join('surat_edaran_satker', 'surats.id', '=', 'surat_edaran_satker.surat_id')
                    ->where('surat_edaran_satker.satker_id', $this->satkerId);
            
            // --- PERBAIKAN FILTER TANGGAL: MENGGUNAKAN TANGGAL SURAT ---
            if ($this->startDate && $this->endDate) {
                $query2->whereBetween('surats.tanggal_surat', [$this->startDate, $this->endDate]);
            }
            $suratEdaran = $query2->get();

            // ==========================================
            // BAGIAN 3: PENGGABUNGAN & SORTING
            // ==========================================
            return $suratMasuk->merge($suratEdaran)
                              ->unique('id')
                              ->sortByDesc('diterima_tanggal')
                              ->values(); // Reset index agar nomor urut di map() aman
        }

        public function headings(): array
        {
            return [
                'No', 'No Surat', 'Tanggal Surat', 'Diterima Tanggal', 'Perihal', 'Pengirim', 'Sifat', 'Link Surat'
            ];
        }

        public function map($surat): array
        {
            static $no = 0;
            $no++;

            $linkFile = $surat->file_surat ? url('storage/' . $surat->file_surat) : 'Tidak ada file';

            return [
                $no,
                $surat->nomor_surat,
                \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y'),
                \Carbon\Carbon::parse($surat->diterima_tanggal)->format('d-m-Y'),
                $surat->perihal,
                $surat->surat_dari,
                $surat->sifat ?? 'Biasa',
                $linkFile
            ];
        }

        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
        {
            return [ 1 => ['font' => ['bold' => true]] ];
        }
    };

    return \Maatwebsite\Excel\Facades\Excel::download($export, 'Laporan_Surat_Masuk_Eksternal_' . date('d-m-Y_H-i') . '.xlsx');
}

    // ... method index dll yang sudah ada ...

    // Method untuk menyimpan surat masuk eksternal inputan Satker sendiri
 public function store(Request $request)
{
    // 1. Validasi Input sesuai alur Eksternal
    $request->validate([
        'nomor_surat'      => 'required|string|max:255',
        'surat_dari'       => 'required|string|max:255',
        'perihal'          => 'required|string',
        'tanggal_surat'    => 'required|date',
        'diterima_tanggal' => 'required|date',
        'file_surat'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'target_tipe'      => 'required|in:arsip,pribadi,semua',
        // Delegasi wajib diisi jika targetnya adalah pribadi
        'delegasi_user_ids'=> 'required_if:target_tipe,pribadi|array',
        'catatan_delegasi' => 'nullable|string',
    ]);

    $user = Auth::user();
    // Simpan file ke folder khusus eksternal
    $path = $request->file('file_surat')->store('surat_masuk_eksternal_satker', 'public');

    try {
        DB::beginTransaction();

        // 2. Simpan Data Surat Utama
        $surat = \App\Models\Surat::create([
            'user_id'          => $user->id,
            'tipe_surat'       => 'eksternal',
            'nomor_surat'      => $request->nomor_surat,
            'surat_dari'       => $request->surat_dari,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'diterima_tanggal' => $request->diterima_tanggal,
            'file_surat'       => $path,
            'sifat'            => 'Asli',
            'no_agenda'        => 'ME-' . time(),
            'tujuan_tipe'      => 'satker',
            'tujuan_satker_id' => $user->satker_id,
            'status'           => 'arsip_satker', // Langsung dianggap diproses oleh admin
        ]);

        $penerimaNotifIds = [];
        $statusLog = '';

        // 3. LOGIKA DISTRIBUSI & EMAIL
        if ($request->target_tipe == 'pribadi') {
            // --- DISPOSISI PRIBADI ---
            $statusLog = 'Disposisi Eksternal';
            $penerimaNotifIds = $request->delegasi_user_ids;

            foreach ($penerimaNotifIds as $pId) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $pId,
                    'status_aksi' => 'Disposisi: ' . $statusLog,
                    'catatan'     => $request->catatan_delegasi ?? 'Segera tindak lanjuti surat eksternal ini.'
                ]);
            }
            // Hubungkan ke tabel pivot delegasi
            $surat->delegasiPegawai()->attach($penerimaNotifIds, [
                'catatan' => $request->catatan_delegasi,
                'created_at' => now(), 'updated_at' => now()
            ]);

        } elseif ($request->target_tipe == 'semua') {
            // --- SEBAR KE SEMUA PEGAWAI SATKER (Informasi Umum) ---
            $statusLog = 'Informasi Umum Eksternal';
            $pegawaiSatker = \App\Models\User::where('satker_id', $user->satker_id)
                                ->where('id', '!=', $user->id)
                                ->get();

            foreach ($pegawaiSatker as $p) {
                \App\Models\RiwayatSurat::create([
                    'surat_id'    => $surat->id,
                    'user_id'     => $user->id,
                    'penerima_id' => $p->id,
                    'status_aksi' => 'Informasi Umum: ' . $statusLog,
                    'catatan'     => $request->catatan_delegasi ?? 'Informasi eksternal untuk seluruh pegawai.'
                ]);
                $penerimaNotifIds[] = $p->id;
            }
            // Hubungkan ke tabel pivot delegasi
            $surat->delegasiPegawai()->attach($penerimaNotifIds, [
                'catatan' => $request->catatan_delegasi,
                'created_at' => now(), 'updated_at' => now()
            ]);

        } else {
            // --- HANYA SIMPAN SEBAGAI ARSIP ---
            \App\Models\RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $user->id,
                'status_aksi' => 'Input Manual (Arsip Eksternal)',
                'catatan'     => 'Surat eksternal dicatat dan diarsipkan oleh Admin Satker.'
            ]);
        }

        DB::commit();

        // 4. KIRIM NOTIFIKASI EMAIL (Menggunakan EmailHelper)
        if (!empty($penerimaNotifIds)) {
            $details = [
                'subject'    => '[SURAT EKSTERNAL]: ' . $surat->perihal,
                'greeting'   => 'Halo Pegawai,',
                'body'       => "Terdapat surat masuk dari instansi luar yang telah diproses oleh Admin Satker Anda.\n\n" .
                                "Nomor: {$surat->nomor_surat}\n" .
                                "Dari: {$surat->surat_dari}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Instruksi: " . ($request->catatan_delegasi ?? 'Silakan dipelajari.'),
                'actiontext' => 'Buka Dashboard',
                'actionurl'  => route('login'),
                'file_url'   => asset('storage/' . $surat->file_surat)
            ];

            \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
        }

        return redirect()->back()->with('success', 'Surat eksternal berhasil dicatat dan diproses.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
}

    // Update Surat (Hanya jika inputan sendiri)
public function update(Request $request, $id)
{
    $surat = \App\Models\Surat::findOrFail($id);
    $myUserId = Auth::id();

    // 1. PROTEKSI: Cek apakah ini inputan milik user yang sedang login
    if ($surat->user_id != $myUserId) {
        return redirect()->back()->with('error', 'Anda tidak berhak mengedit surat ini.');
    }

    // 2. PROTEKSI: Cek apakah surat sudah diproses (Delegasi/Sebar)
    // Ambil riwayat khusus yang dibuat oleh Admin ini
    $myLogs = $surat->riwayats->where('user_id', $myUserId);
    $isProcessed = $myLogs->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'disposisi') || str_contains($aksi, 'informasi');
    })->isNotEmpty();

    if ($isProcessed) {
        return redirect()->back()->with('error', 'Update gagal! Surat sudah didelegasikan atau disebarkan ke pegawai.');
    }

    // 3. VALIDASI INPUT
    $request->validate([
        'nomor_surat'      => 'required|string|max:255',
        'surat_dari'       => 'required|string|max:255',
        'perihal'          => 'required|string',
        'tanggal_surat'    => 'required|date',
        'diterima_tanggal' => 'required|date',
        'file_surat'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
    ]);

    try {
        DB::beginTransaction();

        $data = $request->except(['file_surat', '_token', '_method']);

        // 4. HANDLE FILE (Hapus lama, simpan baru)
        if ($request->hasFile('file_surat')) {
            // Gunakan path yang konsisten dengan method store
            if ($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('surat_masuk_eksternal_satker', 'public');
        }

        // 5. EKSEKUSI UPDATE
        $surat->update($data);

        // 6. CATAT AUDIT TRAIL
        \App\Models\RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $myUserId,
            'status_aksi' => 'Update Data (Eksternal)',
            'catatan'     => 'Admin Satker memperbarui informasi identitas surat eksternal manual.'
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Data surat manual berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

    // Hapus Surat (Hanya jika inputan sendiri)
   public function destroy($id)
{
    // 1. Ambil data surat
    $surat = \App\Models\Surat::findOrFail($id);
    $myUserId = Auth::id();

    // 2. Proteksi Hak Akses
    if ($surat->user_id != $myUserId) {
        return redirect()->back()->with('error', 'Anda tidak berhak menghapus surat ini.');
    }

    try {
        DB::beginTransaction();

        // 3. EKSEKUSI SOFT DELETE
        // JANGAN gunakan Storage::delete agar file tetap ada untuk keperluan Restore
        $surat->delete();

        // 4. Catat Riwayat Penghapusan
        \App\Models\RiwayatSurat::create([
            'surat_id'    => $surat->id,
            'user_id'     => $myUserId,
            'status_aksi' => 'Hapus ke Tempat Sampah',
            'catatan'     => 'Admin Satker memindahkan surat eksternal manual ke tempat sampah.'
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Surat berhasil dipindahkan ke tempat sampah.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
    }
}

    /**
     * Helper Private untuk menandai selesai secara aman
     * (Mencegah perubahan status global jika surat disposisi)
     */
   private function updateStatusLokal($surat, $satkerId)
    {
        // 1. Cari Disposisi yang spesifik untuk Satker ini
        $disposisi = Disposisi::where('surat_id', $surat->id)
                              ->where('tujuan_satker_id', $satkerId)
                              ->first();

        if ($disposisi) {
            // FORCE UPDATE status penerimaan
            $disposisi->status_penerimaan = 'selesai';
            $disposisi->save(); 
        } else {
            // Jika tidak ketemu di tabel disposisi (berarti surat langsung), 
            // update status global surat
            $surat->status = 'arsip_satker';
            $surat->save();
        }
    }

    // delegasi surat masuk eksternal ke pegawai
  public function delegasiKePegawai(Request $request, Surat $surat)
{
    // 1. Validasi (Menambahkan input 'klasifikasi')
    $validated = $request->validate([
        'target_tipe'      => 'required|in:pribadi,semua',
        'tujuan_user_ids'  => 'required_if:target_tipe,pribadi|array',
        'tujuan_user_ids.*' => 'exists:users,id',
        'klasifikasi'      => 'nullable|string',
        'catatan_satker'   => 'nullable|string|max:500',
    ]);

    try {
        \DB::beginTransaction();

        $admin = Auth::user();
        $penerimaNotifIds = []; 
        $delegatedNames = [];

        // 2. Identifikasi Daftar Pegawai Penerima
        if ($validated['target_tipe'] == 'semua') {
            $pegawaiPenerima = \App\Models\User::where('satker_id', $admin->satker_id)
                                ->where('role', 'pegawai')
                                ->get();
            $labelAksi = "Informasi Umum";
            $instruksiFinal = "Untuk diketahui dan dipelajari.";
        } else {
            $pegawaiPenerima = \App\Models\User::whereIn('id', $validated['tujuan_user_ids'])
                                ->where('satker_id', $admin->satker_id)
                                ->get();
            $labelAksi = "Delegasi: " . ($validated['klasifikasi'] ?? 'Tindak Lanjut');
            $instruksiFinal = $validated['catatan_satker'] ?? 'Segera tindak lanjuti.';
        }

        if ($pegawaiPenerima->isEmpty()) {
            throw new \Exception("Tidak ada pegawai yang ditemukan di satker Anda.");
        }

        // 3. Proses Loop Delegasi
        foreach ($pegawaiPenerima as $pegawai) {
            
            // Simpan ke Pivot (Halaman Dashboard Pegawai)
            $surat->delegasiPegawai()->syncWithoutDetaching([
                $pegawai->id => [
                    'status'     => 'belum_dibaca',
                    'catatan'    => $instruksiFinal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            // Catat ke Riwayat (Penting untuk audit Rektorat/BAU)
            \App\Models\RiwayatSurat::create([
                'surat_id'    => $surat->id,
                'user_id'     => $admin->id,
                'penerima_id' => $pegawai->id,
                'status_aksi' => $labelAksi,
                'catatan'     => $instruksiFinal,
            ]);

            $penerimaNotifIds[] = $pegawai->id;
            $delegatedNames[]   = $pegawai->name;
        }

        // 4. Update Status Global Surat (Agar tombol aksi di Admin Satker hilang)
        $surat->update(['status' => 'selesai']);

        \DB::commit();

        // 5. Kirim Notifikasi Email Dinamis
        if (!empty($penerimaNotifIds)) {
            $isSemua = ($validated['target_tipe'] == 'semua');
            
            $details = [
                'subject'    => ($isSemua ? '📢 Info Surat Eksternal: ' : '📩 Disposisi Surat: ') . $surat->perihal,
                'greeting'   => 'Halo Bapak/Ibu,',
                'body'       => ($isSemua 
                                ? "Admin Satker ({$admin->name}) membagikan informasi surat eksternal baru kepada seluruh pegawai."
                                : "Anda menerima disposisi surat eksternal khusus dari Admin Satker ({$admin->name}).") . "\n\n" .
                                "Nomor Surat: {$surat->nomor_surat}\n" .
                                "Perihal: {$surat->perihal}\n" .
                                "Instruksi/Catatan: " . ($isSemua ? $instruksiFinal : "({$validated['klasifikasi']}) - {$instruksiFinal}"),
                'actiontext' => 'Lihat Surat di Dashboard',
                'actionurl'  => route('login'),
                'file_url'   => $surat->file_surat ? asset('storage/' . $surat->file_surat) : null
            ];

            \App\Helpers\EmailHelper::kirimNotif($penerimaNotifIds, $details);
        }

        $pesan = ($validated['target_tipe'] == 'semua') 
                 ? 'Surat berhasil disebarkan ke SELURUH pegawai.' 
                 : 'Surat berhasil didisposisikan ke: ' . implode(', ', $delegatedNames);

        return redirect()->route('satker.surat-masuk.eksternal')->with('success', $pesan);

    } catch (\Exception $e) {
        \DB::rollBack();
        return redirect()->back()->with('error', 'Gagal delegasi: ' . $e->getMessage());
    }
}

    public function arsipkan(Request $request, Surat $surat)
    {
        $user = Auth::user();
        
        // Panggil helper update status
        $this->updateStatusLokal($surat, $user->satker_id);
        
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diarsipkan/Selesai di Satker',
            'catatan' => 'Surat ditandai selesai oleh ' . $user->name . ' (Tidak didelegasikan).'
        ]);
        $surat->update(['status' => 'selesai']);
        
        return redirect()->back()->with('success', 'Surat berhasil diarsipkan (Tandai Selesai).');
    }

   

    public function broadcastInternal(Request $request, Surat $surat)
    {
        $user = Auth::user();
        DB::table('surat_edaran_satker')
            ->where('surat_id', $surat->id)
            ->where('satker_id', $user->satker_id)
            ->update(['status' => 'diteruskan_internal']);

        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'status_aksi' => 'Diteruskan ke Internal Satker',
            'catatan' => 'Surat Edaran disebarkan ke semua pegawai di ' . $user->satker->nama_satker . ' oleh ' . $user->name
        ]);

        return redirect()->route('satker.surat-masuk.eksternal')->with('success', 'Surat Edaran berhasil disebarkan ke semua pegawai internal Anda.');
    }

    // function get log untuk surat masuk eksternal satker
    // File: app/Http/Controllers/Satker/SuratController.php

public function getRiwayatDisposisi($id)
{
    try {
        $surat = \App\Models\Surat::findOrFail($id);
        $satkerId = auth()->user()->satker_id;
        
        // 1. Ambil Riwayat Pusat (FILTER: Hanya BAU & REKTOR)
        $riwayats = $surat->riwayats()
            ->with('user')
            ->whereHas('user', function($q) {
                $q->whereIn('role', ['admin', 'bau', 'admin_rektor']);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedData = [];

        foreach ($riwayats as $item) {
            $formattedData[] = [
                'status_aksi' => $item->status_aksi,
                'catatan'     => $item->catatan,
                'tanggal_f'   => \Carbon\Carbon::parse($item->created_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user_name'   => $item->user ? $item->user->name : 'Sistem',
                'raw_date'    => $item->created_at // Untuk sortir
            ];
        }

        // 2. CEK AKTIVITAS SATKER (Hanya ambil status TERAKHIR saja)
        // Cek apakah ada delegasi
        $delegasiTerakhir = $surat->delegasiPegawai()
            ->where('satker_id', $satkerId)
            ->orderBy('surat_delegasi.created_at', 'desc')
            ->first();

        // Cek status disposisi (Arsip)
        $myDisposisi = $surat->disposisis()
            ->where('tujuan_satker_id', $satkerId)
            ->first();

        if ($delegasiTerakhir) {
            // Jika sudah didelegasikan (baik 1 orang atau sebar semua/Informasi Umum)
            $isBroadcast = str_contains(strtolower($delegasiTerakhir->pivot->catatan ?? ''), 'informasi umum');
            
            $formattedData[] = [
                'status_aksi' => 'Selesai Didelegasikan/Disebar',
                'catatan'     => 'Surat telah diteruskan kepada pegawai internal unit.',
                'tanggal_f'   => \Carbon\Carbon::parse($delegasiTerakhir->pivot->created_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user_name'   => auth()->user()->name,
                'raw_date'    => $delegasiTerakhir->pivot->created_at
            ];
        } elseif ($myDisposisi && $myDisposisi->status_penerimaan == 'selesai') {
            // Jika hanya diarsipkan tanpa delegasi
            $formattedData[] = [
                'status_aksi' => 'Selesai / Diarsipkan Satker',
                'catatan'     => 'Surat telah diterima dan diarsipkan oleh Satker.',
                'tanggal_f'   => \Carbon\Carbon::parse($myDisposisi->updated_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB',
                'user_name'   => auth()->user()->name,
                'raw_date'    => $myDisposisi->updated_at
            ];
        }

        // Urutkan berdasarkan raw_date agar akurat
        usort($formattedData, function($a, $b) {
            return strtotime($a['raw_date']) - strtotime($b['raw_date']);
        });

        return response()->json([
            'status'   => 'success',
            'perihal'  => $surat->perihal,
            'riwayats' => $formattedData
        ]);

    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
}