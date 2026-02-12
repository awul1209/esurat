<?php

namespace App\Http\Controllers\Satker;

use App\Http\Controllers\Controller;
use App\Models\SuratValidasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// --- TAMBAHKAN SEMUA MODEL INI ---
use App\Models\User;           // Solusi untuk error Class "User" not found
use App\Models\Surat;          // Agar tidak error saat membuat Inbox
use App\Models\RiwayatSurat;   // Agar tidak error saat join riwayat
use App\Models\SuratKeluar;    // Model surat utama


class PimpinanValidasiController extends Controller
{
    // index validasi
    public function index()
    {
        // Ambil data validasi pimpinan yang login dengan status pending
        $validasiSurat = SuratValidasi::with(['suratKeluar.user.satker'])
            ->where('pimpinan_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pimpinan.validasi.index', compact('validasiSurat'));
    }

public function update(Request $request, $id)
{
    // 1. Validasi Input (Tetap sama)
    $request->validate([
        'status' => 'required|in:approved,rejected',
        'catatan' => 'required_if:status,rejected|nullable|string'
    ]);

    $validasi = SuratValidasi::findOrFail($id);
    $surat = $validasi->suratKeluar;
    $user = Auth::user(); // Ini adalah Pimpinan yang sedang login
    $dbStatus = ($request->status === 'approved') ? 'setuju' : 'revisi';

    DB::transaction(function () use ($request, $validasi, $surat, $user, $dbStatus) {
        
        // 2. Update status pimpinan di tabel validasi (Tetap sama)
        $validasi->update([
            'status' => $dbStatus,
            'catatan' => $request->catatan,
            'updated_at' => now()
        ]);

        // --- PERBAIKAN 1: Update Riwayat Milik Pimpinan (Agar label Mengetahui tidak hilang) ---
        // Menggunakan update agar tidak menambah baris baru dan tetap berlabel 'Mengetahui'
        \App\Models\RiwayatSurat::where('surat_keluar_id', $surat->id)
            ->where('penerima_id', $user->id)
            ->update([
                'status_aksi' => ($dbStatus === 'setuju') ? 'Mengetahui (Disetujui)' : 'Mengetahui (Minta Revisi)',
                'catatan'     => $request->catatan,
                'updated_at'  => now()
            ]);

        if ($dbStatus === 'revisi') {
            // Jika ada revisi, kunci distribusi (Tetap sama)
            $surat->update(['status' => 'Revisi', 'is_final' => 0]);
        } else {
            // JIKA DISETUJUI: Cek pimpinan lain (Tetap sama)
            $masihAdaPending = SuratValidasi::where('surat_keluar_id', $surat->id)
                ->where('status', 'pending')
                ->exists();

            // 4. DISTRIBUSI FINAL (Hanya jika SEMUA pimpinan SETUJU)
            if (!$masihAdaPending) {
                $surat->update(['status' => 'Terkirim', 'is_final' => 1]);

                // --- A. Distribusi ke Dashboard Satker (Pivot) (Tetap sama) ---
                $satkerTujuanIds = RiwayatSurat::where('surat_keluar_id', $surat->id)
                    ->join('users', 'riwayat_surats.penerima_id', '=', 'users.id')
                    ->pluck('users.satker_id')
                    ->push($surat->tujuan_satker_id)
                    ->unique()
                    ->filter();



                // --- B. Distribusi ke Inbox Personal (Tabel surats) ---
                // PERBAIKAN 2: Kita hanya proses yang BUKAN pimpinan (Mengetahui)
                $riwayats = RiwayatSurat::where('surat_keluar_id', $surat->id)
                    ->whereNotNull('penerima_id')
                    ->where('status_aksi', 'NOT LIKE', '%Mengetahui%') // Filter pimpinan
                    ->get();

                $penerimaIds = [];

                foreach ($riwayats as $r) {
                    $sudahAda = Surat::where('nomor_surat', $surat->nomor_surat)
                        ->where('tujuan_user_id', $r->penerima_id)
                        ->exists();

                    if (!$sudahAda) {
                        $suratMasuk = Surat::create([
                            'surat_dari'       => $surat->user->satker->nama_satker ?? $surat->user->name,
                            'tipe_surat'       => 'internal',
                            'nomor_surat'      => $surat->nomor_surat,
                            'tanggal_surat'    => $surat->tanggal_surat,
                            'perihal'          => $surat->perihal,
                            'sifat'            => $surat->sifat ?? 'Biasa',
                            'no_agenda'        => 'INT-' . strtoupper(uniqid()),
                            'file_surat'       => $surat->file_surat,
                            'status'           => 'proses',
                            'tujuan_user_id'   => $r->penerima_id,
                            'user_id'          => $surat->user_id, // Tetap ID Pengirim Asli
                            'diterima_tanggal' => now(), 
                        ]);

                        // Update riwayat pegawai jadi 'Surat Masuk Langsung' (Agar tombol terima muncul)
                        $r->update(['surat_id' => $suratMasuk->id, 'status_aksi' => 'Surat Masuk Langsung']);
                        $penerimaIds[] = $r->penerima_id;
                    }
                }

                // --- C. KIRIM NOTIFIKASI EMAIL (Tetap sama) ---
                if (!empty($penerimaIds)) {
                    $details = [
                        'subject'    => '✉️ SURAT INTERNAL BARU: ' . $surat->perihal,
                        'greeting'   => 'Halo, Bapak/Ibu,',
                        'body'       => "Anda telah menerima SURAT INTERNAL BARU yang telah divalidasi oleh pimpinan:\n\n" .
                                        "Asal Surat: " . ($surat->user->satker->nama_satker ?? $surat->user->name) . "\n" .
                                        "No. Surat: {$surat->nomor_surat}\n" .
                                        "Perihal: {$surat->perihal}\n\n" .
                                        "Surat ini telah tersedia di kotak masuk aplikasi e-Surat Anda. Mohon segera ditindaklanjuti.",
                        'actiontext' => 'Lihat Surat Masuk',
                        'actionurl'  => route('login'),
                        'file_url'   => asset('storage/' . $surat->file_surat)
                    ];
                    \App\Helpers\EmailHelper::kirimNotif($penerimaIds, $details);
                }

                // --- PERBAIKAN 3: Log pengiriman akhir (Gunakan ID Satker Pengirim, bukan ID Pimpinan) ---
                RiwayatSurat::create([
                    'surat_keluar_id' => $surat->id,
                    'user_id'         => $surat->user_id, // Gunakan ID Satker asal (ID 11)
                    'status_aksi'     => 'Surat Terdistribusi',
                    'catatan'         => 'Validasi pimpinan lengkap. Surat telah dikirim ke semua penerima.',
                    'created_at'      => now()
                ]);
            }
        }
    });

    $statusMsg = ($dbStatus === 'setuju') ? 'Dokumen berhasil disetujui.' : 'Permintaan revisi telah dikirim.';
    return redirect()->route('pimpinan.validasi.index')->with('success', $statusMsg);
}



  public function indexTembusan()
{
    $user = Auth::user();

    // Ambil data tembusan yang ditujukan ke User ID ini atau Satker ID ini
    $tembusanSurat = \App\Models\SuratTembusan::with(['suratKeluar.user.satker', 'suratKeluar.validasis'])
        ->where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('satker_id', $user->satker_id);
        })
        ->whereHas('suratKeluar', function($q) {
            $q->where('is_final', 1)
              ->whereNotIn('status', ['Pending', 'Revisi'])
              // LOGIKA FILTER VALIDASI:
              ->where(function($queryUtama) {
                  $queryUtama->whereDoesntHave('validasis') // Kasus 1: Tanpa "Mengetahui" langsung tampil
                  ->orWhere(function($queryValid) {
                      // Kasus 2: Ada "Mengetahui", pastikan SEMUA sudah 'disetujui'
                      $queryValid->whereHas('validasis')
                                 ->whereDoesntHave('validasis', function($qv) {
                                     $qv->where('status', '!=', 'setuju');
                                 });
                  });
              });
        })
        ->orderBy('created_at', 'desc')
        ->get();

    return view('pimpinan.validasi.tembusan', compact('tembusanSurat'));
}
}