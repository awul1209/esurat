<?php
// MEMEPERBAIKI METOD EXPORT SURAT KELUAR INT/EKS DIGABUNG
namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SuratKeluar;
use App\Models\Surat;        
use App\Models\Satker;       
use App\Models\RiwayatSurat; 
use App\Models\User;         
use App\Services\WaService;
use Carbon\Carbon; // <--- PENTING UNTUK FORMAT TANGGAL

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;   
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SuratKeluarController extends Controller
{
   
public function indexInternal(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    
    // Ambil ID User yang sedang login (BAU)
    $userId = \Illuminate\Support\Facades\Auth::id(); 

    $query = SuratKeluar::where('tipe_kirim', 'internal')
        ->where('user_id', $userId) // <--- TAMBAHKAN INI (Filter Pengirim)
        ->with('penerimaInternal'); 

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $suratKeluars = $query->latest()->get();

    return view('bau.surat_keluar.index', compact('suratKeluars'));
}

 /**
     * Menampilkan Surat Keluar EKSTERNAL
     */
public function indexEksternal(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // AMBIL ID SATKER USER YANG LOGIN (BAU)
    $satkerIdSaya = Auth::user()->satker_id;

    // TAMBAHKAN FILTER 'whereHas'
    $query = SuratKeluar::where('tipe_kirim', 'eksternal')
        ->whereHas('user', function($q) use ($satkerIdSaya) {
            $q->where('satker_id', $satkerIdSaya);
        });

    // Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $suratKeluars = $query->latest()->get();

    return view('bau.surat_keluar.index', compact('suratKeluars'));
}
public function exportExcel(Request $request)
{
    $tipe = $request->input('type'); 
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // 1. Tambahkan eager loading 'riwayats.penerima' untuk mengambil nama pegawai
    $query = \App\Models\SuratKeluar::with(['penerimaInternal', 'riwayats.penerima'])
                ->where('tipe_kirim', $tipe);

    // 2. PERBAIKAN: Selalu filter berdasarkan user_id Auth (BAU) 
    // agar data eksternal tidak mengambil milik satker lain dan data internal tidak bocor.
    $query->where('user_id', \Illuminate\Support\Facades\Auth::id());

    // 3. Filter Tanggal
    if ($startDate && $endDate) {
        $query->whereBetween('tanggal_surat', [$startDate, $endDate]);
    }

    $data = $query->latest()->get();

    // 4. Generate CSV
    $filename = "Surat_Keluar_BAU_" . ucfirst($tipe) . "_" . date('Ymd_His') . ".csv";
    
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columns = ['No', 'Nomor Surat', 'Perihal', 'Tujuan', 'Tanggal Surat', 'Link File'];

    $callback = function() use($data, $columns, $tipe) {
        $file = fopen('php://output', 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM Fix agar terbaca rapi di Excel
        fputcsv($file, $columns); 

        foreach ($data as $index => $item) {
            
            // --- LOGIKA TUJUAN (PERBAIKAN) ---
            $tujuanText = '';

            if (strtolower($tipe) == 'internal') {
                // Prioritas 1: Jika ke Satker (Antar Satker)
                if ($item->penerimaInternal->count() > 0) {
                    $tujuanText = $item->penerimaInternal->pluck('nama_satker')->implode(', ');
                } 
                // Prioritas 2: Jika ke Pegawai Langsung (Cek Riwayat)
                elseif ($item->riwayats->whereNotNull('penerima_id')->count() > 0) {
                    $tujuanText = $item->riwayats->whereNotNull('penerima_id')
                                    ->pluck('penerima.name')
                                    ->unique()
                                    ->implode(', ');
                } 
                // Prioritas 3: Teks Manual
                else {
                    $tujuanText = $item->tujuan_surat ?? '-';
                }
            } else {
                // Untuk Eksternal: Ambil dari tujuan_luar atau tujuan_surat
                $tujuanText = $item->tujuan_luar ?? $item->tujuan_surat ?? '-';
            }

            $link = $item->file_surat ? asset('storage/' . $item->file_surat) : '-';
            
            fputcsv($file, [
                $index + 1,
                $item->nomor_surat,
                $item->perihal,
                $tujuanText,
                \Carbon\Carbon::parse($item->tanggal_surat)->format('d-m-Y'),
                $link
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * Form Buat Surat
     */
    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        return view('bau.surat_keluar.create', compact('daftarSatker'));
    }

    /**
     * Store surat keluar internal + notif email
     */
public function store(Request $request)
{
    // 1. Validasi
    $validator = Validator::make($request->all(), [
        'nomor_surat'   => 'required|unique:surat_keluars,nomor_surat',
        'perihal'       => 'required',
        'tanggal_surat' => 'required|date',
        'file_surat'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', 
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput(); 
    }

    $user = Auth::user();
    $path = $request->file('file_surat')->store('sk_bau_internaleks', 'public');
    
    $suratKeluar = null;

    DB::transaction(function() use ($request, $user, $path, &$suratKeluar) {
        
        // A. PILAH TUJUAN
        $satkerIds = [];     
        $targetRektor = [];  

        if ($request->tipe_kirim == 'internal') {
            $inputTujuan = $request->tujuan_satker_ids ?? [];
            foreach($inputTujuan as $val) {
                if ($val == 'rektor' || $val == 'universitas') {
                    $targetRektor[] = $val;
                } elseif (is_numeric($val)) {
                    $satkerIds[] = $val;
                }
            }
        }

        // B. SIMPAN SURAT KELUAR
        $displayTujuan = [];
        if (!empty($targetRektor)) $displayTujuan[] = implode(' & ', array_map('ucfirst', $targetRektor));
        if ($request->tipe_kirim == 'eksternal' && $request->tujuan_luar) $displayTujuan[] = $request->tujuan_luar;
        
        $suratKeluar = SuratKeluar::create([
            'user_id'          => $user->id,
            'tipe_kirim'       => $request->tipe_kirim, 
            'nomor_surat'      => $request->nomor_surat,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'tujuan_satker_id' => null, 
            'tujuan_surat'     => !empty($displayTujuan) ? implode(', ', $displayTujuan) : null,
            'file_surat'       => $path,
        ]);

        // Persiapan Data Notifikasi
        $tglSurat = \Carbon\Carbon::parse($request->tanggal_surat)->format('d-m-Y');
        $link = route('login');
        $namaPengirim = $user->satker->nama_satker ?? "Biro Administrasi Umum (BAU)"; 

        // =================================================================
        // SKENARIO 1: KIRIM KE SATKER LAIN (NOTIF EMAIL)
        // =================================================================
        if (!empty($satkerIds)) {
            $suratKeluar->penerimaInternal()->attach($satkerIds);

            // Ambil ID User yang bertugas di Satker-satker tujuan tersebut
            $penerimaUserIds = User::whereIn('satker_id', $satkerIds)
                                    ->whereIn('role', ['satker', 'bau', 'bapsi', 'admin'])
                                    ->pluck('id')
                                    ->toArray();

            if (!empty($penerimaUserIds)) {
                $details = [
                    'subject'    => '📩 Surat Masuk Baru: ' . $request->perihal,
                    'greeting'   => 'Halo Bapak/Ibu,',
                    'body'       => "Anda menerima surat internal baru yang dikirim melalui BAU.\n\n" .
                                    "No. Surat: {$request->nomor_surat}\n" .
                                    "Perihal: {$request->perihal}\n" .
                                    "Pengirim: {$namaPengirim}\n" .
                                    "Tanggal Surat: {$tglSurat}",
                    'actiontext' => 'Lihat Surat Masuk',
                    'actionurl'  => $link,
                    'file_url'   => asset('storage/' . $path)
                ];

                // Kirim Notifikasi Email via Helper
                \App\Helpers\EmailHelper::kirimNotif($penerimaUserIds, $details);
            }
        }
        // =================================================================
// SKENARIO 2: KIRIM KE PEGAWAI SPESIFIK (PERSONAL) - SESUAI STANDAR SATKER
// =================================================================
else if ($request->tujuan_tipe == 'pegawai' && $request->tujuan_user_id) {
    
    // A. Simpan ke tabel surats (sebagai Inbox Pegawai)
    // Field disamakan persis dengan mekanisme Satker ke Pegawai
    $suratMasuk = \App\Models\Surat::create([
        'surat_dari'       => 'BAU / Universitas', // Pemberi identitas asal
        'tipe_surat'       => 'internal',
        'nomor_surat'      => $request->nomor_surat,
        'tanggal_surat'    => $request->tanggal_surat,
        'perihal'          => $request->perihal,
        'no_agenda'        => 'PEGAWAI-' . strtoupper(uniqid()), // Generate No Agenda Unik
        'diterima_tanggal' => now(),
        'sifat'            => 'Asli',
        'file_surat'       => $path,
        'status'           => 'proses',
        'user_id'          => Auth::id(), // ID Admin BAU yang menginput
        'tujuan_tipe'      => 'pegawai',
        'tujuan_satker_id' => null,
        'tujuan_user_id'   => $request->tujuan_user_id, // Target Pegawai
    ]);

    // B. Simpan ke tabel riwayat_surats (Memicu tombol "Terima" di Dashboard)
    \App\Models\RiwayatSurat::create([
        'surat_id'         => $suratMasuk->id,
        'surat_keluar_id'  => $suratKeluar->id, // Hubungan ke arsip Surat Keluar BAU
        'user_id'      => Auth::id(),
        'penerima_id'      => $request->tujuan_user_id,
        'status_aksi'      => 'Personal (Surat Langsung dari BAU)',
         'catatan'          => 'Surat dikirim langsung ke Pegawai spesifik.',
        'is_read'          => 0, // Penting agar status di Dashboard adalah "Menunggu"
    ]);

    // C. Notifikasi Email (Tetap menggunakan Helper)
    $penerima = User::find($request->tujuan_user_id);
    if ($penerima) {
        $details = [
            'subject'    => '📩 Surat Personal Baru dari BAU: ' . $request->perihal,
            'greeting'   => 'Halo ' . $penerima->name . ',',
            'body'       => "Anda menerima surat langsung dari BAU.\n\n" .
                            "No. Surat: {$request->nomor_surat}\n" .
                            "Perihal: {$request->perihal}\n" .
                            "Silakan cek dashboard pegawai Anda untuk melakukan konfirmasi penerimaan.",
            'actiontext' => 'Buka Dashboard',
            'actionurl'  => route('pegawai.surat.pribadi'),
            'file_url'   => asset('storage/' . $path)
        ];
        \App\Helpers\EmailHelper::kirimNotif([$penerima->id], $details);
    }
}

        // =================================================================
        // SKENARIO 2: KIRIM KE REKTOR (NOTIF EMAIL)
        // =================================================================
        foreach ($targetRektor as $target) {
            $tujuanTipe = ($target == 'universitas') ? 'universitas' : 'rektor';
            $noAgenda = $request->no_agenda ?? null;

            $suratMasuk = Surat::create([
                'surat_dari'       => $namaPengirim, 
                'tipe_surat'       => 'internal', 
                'nomor_surat'      => $request->nomor_surat,
                'tanggal_surat'    => $request->tanggal_surat,
                'perihal'          => $request->perihal,
                'no_agenda'        => $noAgenda, 
                'diterima_tanggal' => now(),
                'sifat'            => 'Biasa',
                'file_surat'       => $path,
                'status'           => 'di_admin_rektor',
                'user_id'          => $user->id, 
                'tujuan_tipe'      => $tujuanTipe,
                'tujuan_satker_id' => null,
                'tujuan_user_id'   => null,
            ]);

            RiwayatSurat::create([
                'surat_id'    => $suratMasuk->id,
                'user_id'     => $user->id,
                'status_aksi' => 'Surat Masuk Internal (Dari BAU)',
                'catatan'     => 'Surat Internal dari BAU, langsung masuk ke Admin Rektor.'
            ]);

            // Ambil ID User Admin Rektor
            $adminRektorIds = User::where('role', 'admin_rektor')->pluck('id')->toArray();

            if (!empty($adminRektorIds)) {
                $detailsRektor = [
                    'subject'    => '📩 Surat Masuk Rektorat: ' . $request->perihal,
                    'greeting'   => 'Halo Admin Rektorat,',
                    'body'       => "Ada surat internal baru dari BAU untuk Rektor/Universitas.\n\n" .
                                    "No. Surat: {$request->nomor_surat}\n" .
                                    "Perihal: {$request->perihal}\n" .
                                    "Pengirim: {$namaPengirim}",
                    'actiontext' => 'Buka Dashboard Rektor',
                    'actionurl'  => $link,
                    'file_url'   => asset('storage/' . $path)
                ];

                \App\Helpers\EmailHelper::kirimNotif($adminRektorIds, $detailsRektor);
            }
        }
    });

    $routeTujuan = ($suratKeluar->tipe_kirim == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
    return redirect()->route($routeTujuan)->with('success', 'Data surat keluar berhasil disimpan dan notifikasi email telah dikirim.');
}



public function edit(SuratKeluar $suratKeluar)
{
    if ($suratKeluar->user_id != Auth::id()) {
        abort(403);
    }

    $daftarSatker = \App\Models\Satker::orderBy('nama_satker', 'asc')->get();

    // AMBIL ID SATKER DARI TABEL PIVOT
    // Kita ambil ID pertama karena dalam konteks ini tujuannya satu
    $currentSatkerId = $suratKeluar->penerimaInternal()->first()?->id;

    return view('bau.surat_keluar.edit', compact('suratKeluar', 'daftarSatker', 'currentSatkerId'));
}

public function update(Request $request, SuratKeluar $suratKeluar)
{
    if ($suratKeluar->user_id != Auth::id()) { abort(403); }

    $request->validate([
        'nomor_surat'     => ['required', 'string', \Illuminate\Validation\Rule::unique('surat_keluars')->ignore($suratKeluar->id)],
        'tanggal_surat'   => 'required|date',
        'tujuan_surat_id' => 'required_if:tipe_kirim,internal', // Untuk Pivot
        'tujuan_surat'    => 'required_if:tipe_kirim,eksternal', // Untuk kolom teks
        'perihal'         => 'required|string',
    ]);

    try {
        DB::beginTransaction();

        // 1. Update data dasar
        $suratKeluar->nomor_surat   = $request->nomor_surat;
        $suratKeluar->tanggal_surat = $request->tanggal_surat;
        $suratKeluar->perihal       = $request->perihal;

        // 2. LOGIKA PINTAR (Pemisahan Database)
        if ($suratKeluar->tipe_kirim == 'internal') {
            // Update Tabel Pivot
            $suratKeluar->penerimaInternal()->sync([$request->tujuan_surat_id]);
            
            // SESUAI PERMINTAAN: Kosongkan kolom tujuan_surat di tabel utama
            $suratKeluar->tujuan_surat = null; 
        } else {
            // Jika Eksternal: Isi kolom teks & pastikan pivot kosong
            $suratKeluar->tujuan_surat = $request->tujuan_surat;
            $suratKeluar->penerimaInternal()->detach(); 
        }

        // 3. Handle File
        if ($request->hasFile('file_surat')) {
            if ($suratKeluar->file_surat) Storage::disk('public')->delete($suratKeluar->file_surat);
            $suratKeluar->file_surat = $request->file('file_surat')->store('surat-keluar', 'public');
        }

        $suratKeluar->save();
        DB::commit();

        $route = ($suratKeluar->tipe_kirim == 'internal') ? 'bau.surat-keluar.internal' : 'bau.surat-keluar.eksternal';
        return redirect()->route($route)->with('success', 'Data berhasil diperbarui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
    }
}

  public function destroy($id)
{
    // 1. Cari Data Surat
    $surat = SuratKeluar::findOrFail($id);

    // 2. Proteksi (Opsional: Aktifkan jika hanya penginput yang boleh hapus)
    if ($surat->user_id != Auth::id()) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus data ini.');
    }

    // 3. Simpan Tipe untuk Redirect (sebelum masuk tempat sampah)
    $routeTujuan = ($surat->tipe_kirim == 'internal') 
        ? 'bau.surat-keluar.internal' 
        : 'bau.surat-keluar.eksternal';

    // 4. Eksekusi Soft Delete
    // CATATAN: Jangan hapus file di storage & jangan detach relasi agar bisa di-restore nanti
    $surat->delete(); 

    // 5. Redirect Kembali
    return redirect()->route($routeTujuan)->with('success', 'Arsip surat berhasil dipindahkan ke tempat sampah.');
}

    public function checkDuplicate(Request $request)
{
    $exists = \App\Models\SuratKeluar::where('nomor_surat', $request->value)->exists();
    return response()->json(['exists' => $exists]);
}

public function getRiwayatLog($id)
{
    try {
        // Load riwayat internal satker juga (penerima adalah pegawai)
        $suratKeluar = SuratKeluar::with(['penerimaInternal', 'riwayats.penerima', 'riwayats.user'])->findOrFail($id);
        $listRiwayat = [];

        $parseDate = function($val) {
            try {
                return $val ? \Carbon\Carbon::parse($val) : null;
            } catch (\Exception $e) { return null; }
        };

        // --- A. LOG AWAL: SURAT DIKIRIM OLEH BAU ---
        $tglKirim = $parseDate($suratKeluar->created_at);
        $listRiwayat[] = [
            'status_aksi' => 'Surat Dikirim',
            'catatan'     => 'Surat dibuat dan dikirim oleh BAU.',
            'created_at'  => $tglKirim ? $tglKirim->toISOString() : null,
            'tanggal_f'   => $tglKirim ? $tglKirim->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
            'user'        => ['name' => 'Admin BAU'] 
        ];

        // --- B. LOGIKA REKTOR ---
        $suratDiRektor = \App\Models\Surat::with(['riwayats.user'])
            ->where('nomor_surat', $suratKeluar->nomor_surat)
            ->first();

        if ($suratDiRektor && $suratDiRektor->riwayats->isNotEmpty()) {
            foreach ($suratDiRektor->riwayats as $raw) {
                $tglLog = $parseDate($raw->created_at);
                $listRiwayat[] = [
                    'status_aksi' => $raw->status_aksi,
                    'catatan'     => $raw->catatan,
                    'created_at'  => $tglLog ? $tglLog->toISOString() : null,
                    'tanggal_f'   => $tglLog ? $tglLog->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                    'user'        => ['name' => $raw->user->name ?? 'Admin Rektor']
                ];
            }
        }

        // --- C. LOGIKA PENERIMAAN OLEH SATKER (PIVOT) ---
        if ($suratKeluar->penerimaInternal->isNotEmpty()) {
            foreach ($suratKeluar->penerimaInternal as $penerima) {
                if (!$penerima->pivot) continue;

                $status = $penerima->pivot->is_read ?? 0;
                $waktuUpdate = $parseDate($penerima->pivot->updated_at);
                $namaSatker = $penerima->nama_satker ?? 'Satker Tujuan';

                // Jika status >= 1, artinya sudah pernah diterima/dibaca
                if ($status >= 1) {
                    $listRiwayat[] = [
                        'status_aksi' => 'Diterima Satker',
                        'catatan'     => 'Surat telah diterima dan masuk ke lemari digital ' . $namaSatker,
                        'created_at'  => $waktuUpdate ? $waktuUpdate->toISOString() : null,
                        'tanggal_f'   => $waktuUpdate ? $waktuUpdate->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                        'user'        => ['name' => 'Admin ' . $namaSatker]
                    ];
                }

                // Log khusus jika diarsipkan manual (tanpa delegasi)
                if ($status == 2) {
                    $listRiwayat[] = [
                        'status_aksi' => 'Diarsipkan',
                        'catatan'     => 'Surat telah selesai diproses dan diarsipkan oleh ' . $namaSatker,
                        'created_at'  => $waktuUpdate ? $waktuUpdate->toISOString() : null,
                        'tanggal_f'   => $waktuUpdate ? $waktuUpdate->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                        'user'        => ['name' => 'Admin ' . $namaSatker]
                    ];
                }
            }
        }

      // --- D. LOGIKA DELEGASI INTERNAL (TABEL RIWAYAT) ---
if ($suratKeluar->riwayats->isNotEmpty()) {
    foreach ($suratKeluar->riwayats as $riwayat) {
        $tglLog = $parseDate($riwayat->created_at);
        $namaPegawai = $riwayat->penerima->name ?? 'Pegawai';
        $namaAdminSatker = $riwayat->user->name ?? 'Admin Satker';

        // 1. Log Saat Didelegasikan
        if (stripos($riwayat->status_aksi, 'Delegasi') !== false) {
            $listRiwayat[] = [
                'status_aksi' => 'Didelegasikan ke: ' . $namaPegawai,
                'catatan'     => $riwayat->catatan,
                'created_at'  => $tglLog ? $tglLog->toISOString() : null,
                'tanggal_f'   => $tglLog ? $tglLog->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                'user'        => ['name' => $namaAdminSatker]
            ];

            // 2. Log Saat Pegawai Membaca (Jika ada data update_at di riwayat atau is_read_pegawai)
            // Asumsi: Jika riwayat memiliki kolom/status yang menandakan pegawai sudah baca
            if ($riwayat->is_read == 1 || $riwayat->status_aksi == 'Dibaca oleh Pegawai') {
                $tglBaca = $parseDate($riwayat->updated_at);
                $listRiwayat[] = [
                    'status_aksi' => 'Diterima oleh: ' . $namaPegawai,
                    'catatan'     => 'Surat telah dibuka dan dibaca oleh pegawai yang bersangkutan.',
                    'created_at'  => $tglBaca ? $tglBaca->toISOString() : null,
                    'tanggal_f'   => $tglBaca ? $tglBaca->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-',
                    'user'        => ['name' => $namaPegawai]
                ];
            }
        }
    }
}

        // --- E. URUTKAN TIMELINE ---
        usort($listRiwayat, function($a, $b) {
            $t1 = $a['created_at'] ? strtotime($a['created_at']) : 0;
            $t2 = $b['created_at'] ? strtotime($b['created_at']) : 0;
            return $t1 <=> $t2;
        });

        return response()->json([
            'status'      => 'success',
            'nomor_surat' => $suratKeluar->nomor_surat,
            'perihal'     => $suratKeluar->perihal,
            'riwayats'    => $listRiwayat
        ]);

    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

public function getPegawaiBySatker(Request $request)
{
    // Ambil user berdasarkan satker_id dan role yang diizinkan (pegawai/dosen)
    $pegawai = \App\Models\User::where('satker_id', $request->satker_id)
        ->whereIn('role', ['pegawai', 'dosen'])
        ->select('id', 'name', 'role')
        ->orderBy('name', 'asc')
        ->get();

    return response()->json($pegawai);
}
}