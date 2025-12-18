<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\SuratKeluar;
use App\Models\Satker;
use App\Models\User;
use App\Models\RiwayatSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

// --- IMPORT PENTING ---
use App\Services\WaService;
use Carbon\Carbon; // <--- INI YANG MENYEBABKAN ERROR SEBELUMNYA

class SuratController extends Controller
{
    /**
     * Menampilkan Surat Masuk EKSTERNAL
     */
    public function indexEksternal()
    {
        $semuaSurat = Surat::whereIn('status', ['baru_di_bau', 'di_admin_rektor'])
                            ->where('tipe_surat', 'eksternal') 
                            ->latest('diterima_tanggal')
                            ->get(); 
                            
        return view('bau.surat_index', compact('semuaSurat'));
    }

    /**
     * Menampilkan Surat Masuk INTERNAL (Router)
     */
    public function indexInternal()
    {
        $semuaSurat = Surat::whereIn('status', ['baru_di_bau', 'di_admin_rektor'])
                            ->where('tipe_surat', 'internal')
                            ->latest('diterima_tanggal')
                            ->get(); 
        
        return view('bau.surat_index', compact('semuaSurat'));
    }

    /**
     * Menampilkan Inbox Khusus BAU (Tujuan Akhir)
     */
public function indexUntukBau()
    {
        $user = Auth::user();
        $bauSatkerId = $user->satker_id;

        // 1. AMBIL SURAT EKSTERNAL (Tujuan: BAU)
        // Ini adalah surat manual yang diinput BAU atau Disposisi Rektor ke BAU
       $suratEksternal = Surat::where('tujuan_tipe', 'satker')
                                ->where('tujuan_satker_id', $bauSatkerId)
                                ->get()
                                ->map(function($item) {
                                    // PERBAIKAN DI SINI:
                                    // Cek kolom tipe_surat di DB. Jika 'internal', maka labelnya 'Internal'
                                    // ucfirst membuat 'internal' jadi 'Internal', 'eksternal' jadi 'Eksternal'
                                    $item->jenis_surat = ucfirst($item->tipe_surat); 
                                    
                                    $item->is_manual = true;
                                    $item->tgl_sort = $item->diterima_tanggal;
                                    return $item;
                                });

        // 2. AMBIL SURAT INTERNAL (Dari Satker Lain ke BAU)
        // Cek tabel surat_keluars yang tujuannya (pivot) adalah Satker BAU
        $suratInternal = SuratKeluar::where('tipe_kirim', 'internal')
                                ->whereHas('penerimaInternal', function($q) use ($bauSatkerId) {
                                    $q->where('satkers.id', $bauSatkerId);
                                })
                                ->get()
                                ->map(function($item) {
                                    $item->jenis_surat = 'Internal';
                                    $item->surat_dari = $item->user->satker->nama_satker ?? 'Satker Lain'; // Nama Pengirim
                                    $item->diterima_tanggal = $item->tanggal_surat; // Samakan field tanggal
                                    $item->is_manual = false; // Tidak bisa diedit (karena kiriman orang)
                                    $item->tgl_sort = $item->tanggal_surat;
                                    return $item;
                                });

        // 3. GABUNGKAN DAN URUTKAN
        $suratUntukBau = $suratEksternal->merge($suratInternal)->sortByDesc('tgl_sort');

        return view('bau.surat_untuk_bau_index', compact('suratUntukBau'));
    }

    public function storeInbox(Request $request)
    {
        $request->validate([
            'tipe_surat'  => 'required|in:internal,eksternal', // Validasi baru
            'nomor_surat' => 'required|string',
            'surat_dari'  => 'required|string',
            'perihal'     => 'required|string',
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat'  => 'required|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $user = Auth::user();
        $path = $request->file('file_surat')->store('surat-masuk-bau', 'public');

        Surat::create([
            'user_id'          => $user->id,
            
            // GUNAKAN INPUT DARI FORM (JANGAN HARDCODE 'eksternal' LAGI)
            'tipe_surat'       => $request->tipe_surat, 
            
            'nomor_surat'      => $request->nomor_surat,
            'surat_dari'       => $request->surat_dari,
            'perihal'          => $request->perihal,
            'tanggal_surat'    => $request->tanggal_surat,
            'diterima_tanggal' => $request->diterima_tanggal,
            'file_surat'       => $path,
            'sifat'            => 'Asli',
            'no_agenda'        => 'BAU-' . time(),
            'tujuan_tipe'      => 'satker',
            'tujuan_satker_id' => $user->satker_id,
            'status'           => 'di_satker',
        ]);

        return redirect()->back()->with('success', 'Surat berhasil dicatat.');
    }

    /**
     * UPDATE: Hanya untuk Surat Manual BAU
     */
    public function updateInbox(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);
        
        // Proteksi: Jangan edit surat disposisi rektor, hanya inputan sendiri
        if($surat->user_id != Auth::id()){
             return redirect()->back()->with('error', 'Tidak bisa mengedit surat kiriman/disposisi.');
        }

        $request->validate([
            'nomor_surat' => 'required',
            'surat_dari' => 'required',
            'perihal' => 'required',
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $data = $request->except(['file_surat', '_token', '_method']);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
            $data['file_surat'] = $request->file('file_surat')->store('surat-masuk-bau', 'public');
        }

        $surat->update($data);
        return redirect()->back()->with('success', 'Data surat diperbarui.');
    }

    /**
     * DESTROY: Hapus Surat Manual BAU
     */
    public function destroyInbox($id)
    {
        $surat = Surat::findOrFail($id);
        
        if($surat->user_id != Auth::id()){
             return redirect()->back()->with('error', 'Tidak bisa menghapus surat kiriman/disposisi.');
        }

        if ($surat->file_surat) Storage::disk('public')->delete($surat->file_surat);
        $surat->delete();

        return redirect()->back()->with('success', 'Surat dihapus.');
    }

    /**
     * Menampilkan Halaman Disposisi
     */
    public function showDisposisi()
    {
        $suratDisposisi = Surat::with('disposisis.tujuanSatker', 'disposisis.klasifikasi')
                            ->whereIn('status', ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'])
                            ->latest('diterima_tanggal')
                            ->get(); 
        return view('bau.disposisi_index', compact('suratDisposisi'));
    }

    /**
     * Menampilkan Riwayat
     */
    public function showRiwayat()
    {
        $user = Auth::user();
        $bauSatkerId = $user->satker_id;

        $suratSelesai = Surat::with(['disposisis.tujuanSatker', 'user'])
                            ->whereIn('status', [
                                'selesai', 
                                'selesai_edaran', 
                                'diarsipkan', 
                                'disimpan',
                                'arsip_satker',
                                'di_satker',
                                'didisposisi' // Tambahkan ini jaga-jaga statusnya masih di jalan
                            ])
                            // FILTER 1: Jangan tampilkan surat yang SEDANG di Inbox BAU (Tujuan = BAU)
                            ->where(function($q) use ($bauSatkerId) {
                                $q->where('tujuan_satker_id', '!=', $bauSatkerId)
                                  ->orWhereNull('tujuan_satker_id');
                            })
                            // [PERBAIKAN LOGIKA UTAMA]
                            ->where(function($q) use ($user) {
                                // 1. Surat yang diinput oleh BAU sendiri (Pasti tampil)
                                $q->where('user_id', $user->id)
                                
                                // 2. ATAU Surat dari Siapapun (termasuk Satker) yang TUJUANNYA ke REKTOR/UNIV
                                // (Karena surat ke Rektor pasti dikelola via BAU/Admin Rektor)
                                  ->orWhereIn('tujuan_tipe', ['rektor', 'universitas'])
                                  
                                // 3. ATAU Surat yang sudah ada jejak disposisi Rektornya
                                  ->orWhereHas('riwayats', function($subQ) {
                                      $subQ->where('status_aksi', 'Disposisi Rektor');
                                  });
                            })
                            ->latest('diterima_tanggal')
                            ->get(); 
                            
        return view('bau.riwayat_index', compact('suratSelesai'));
    }

    public function showRiwayatDetail(Surat $surat)
    {
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }

    // --- CRUD ---

    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')->with('satker')->orderBy('name', 'asc')->get();
        return view('bau.input_surat', compact('daftarSatker', 'daftarPegawai'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'surat_dari' => 'required|string|max:255',
            'tipe_surat' => 'required|in:eksternal,internal',
            'nomor_surat' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'perihal' => 'required|string',
            'no_agenda' => 'required|string|max:255|unique:surats,no_agenda',
            'diterima_tanggal' => 'required|date',
            'sifat' => 'required|string',
            'file_surat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'tujuan_tipe' => 'required|in:rektor,universitas,satker,pegawai,edaran_semua_satker',
            'tujuan_satker_id' => 'required_if:tujuan_tipe,satker|nullable|exists:satkers,id',
            'tujuan_user_id' => 'required_if:tujuan_tipe,pegawai|nullable|exists:users,id',
        ]);

        $filePath = $request->file('file_surat')->store('surat', 'public');

        $status = 'baru_di_bau'; 
        $tujuan_satker_id = null;
        $tujuan_user_id = null;
        $inputTipe = $request->input('tujuan_tipe');
        $statusAksi = 'Surat diinput (Draft)';

        // Logic Status
        if ($inputTipe == 'rektor' || $inputTipe == 'universitas') {
            $status = 'di_admin_rektor'; 
            $statusAksi = 'Surat diinput dan diteruskan ke Admin Rektor';
        } elseif ($inputTipe == 'satker') {
            $status = 'di_satker'; 
            $tujuan_satker_id = $validatedData['tujuan_satker_id'];
            $statusAksi = 'Surat dikirim langsung ke Satker';
        } elseif ($inputTipe == 'pegawai') {
            $status = 'di_satker'; 
            $tujuan_user_id = $validatedData['tujuan_user_id'];
            $statusAksi = 'Surat dikirim langsung ke Pegawai';
        } elseif ($inputTipe == 'edaran_semua_satker') {
            $status = 'di_satker';
            $statusAksi = 'Surat Edaran dikirim ke semua satker';
        }

        $surat = Surat::create([
            'surat_dari' => $validatedData['surat_dari'],
            'tipe_surat' => $validatedData['tipe_surat'],
            'nomor_surat' => $validatedData['nomor_surat'],
            'tanggal_surat' => $validatedData['tanggal_surat'],
            'perihal' => $validatedData['perihal'],
            'no_agenda' => $validatedData['no_agenda'],
            'diterima_tanggal' => $validatedData['diterima_tanggal'],
            'sifat' => $validatedData['sifat'],
            'file_surat' => $filePath,
            'status' => $status,
            'user_id' => Auth::id(),
            'tujuan_tipe' => $inputTipe,
            'tujuan_satker_id' => $tujuan_satker_id,
            'tujuan_user_id' => $tujuan_user_id,
        ]);

        RiwayatSurat::create([
            'surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Input Surat', 'catatan' => $statusAksi
        ]);

        if ($inputTipe == 'edaran_semua_satker') {
            $semuaSatkerId = Satker::pluck('id');
            $surat->satkerPenerima()->attach($semuaSatkerId, ['status' => 'terkirim']); 
        }

        // ====================================================================
        // NOTIFIKASI WA (BAU INPUT MANUAL)
        // ====================================================================
        $targets = []; // Array [hp, nama_tujuan]

        // 1. Jika Tujuan REKTOR
        if ($inputTipe == 'rektor' || $inputTipe == 'universitas') {
            $adminRektor = User::where('role', 'admin_rektor')->first();
            if ($adminRektor && $adminRektor->no_hp) {
                $targets[] = ['hp' => $adminRektor->no_hp, 'nama' => 'Rektor / Universitas'];
            }
        }
        // 2. Jika Tujuan SATKER (BAU -> Satker)
        elseif ($inputTipe == 'satker' && $tujuan_satker_id) {
            $adminSatker = User::where('role', 'satker')->where('satker_id', $tujuan_satker_id)->first();
            if ($adminSatker && $adminSatker->no_hp) {
                $targets[] = ['hp' => $adminSatker->no_hp, 'nama' => $adminSatker->satker->nama_satker ?? 'Satker'];
            }
        }
        // 3. Jika Tujuan PEGAWAI
        elseif ($inputTipe == 'pegawai' && $tujuan_user_id) {
            $pegawai = User::find($tujuan_user_id);
            if ($pegawai && $pegawai->no_hp) {
                $targets[] = ['hp' => $pegawai->no_hp, 'nama' => $pegawai->name];
            }
        }

        // KIRIM PESAN KE SEMUA TARGET
        foreach ($targets as $target) {
            try {
                $tglSurat = Carbon::parse($validatedData['tanggal_surat'])->format('d-m-Y'); // BUTUH USE CARBON
                $link = route('login'); 

                $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : {$target['nama']}
Tanggal Surat : {$tglSurat}
No. Surat     : {$validatedData['nomor_surat']}
Perihal       : {$validatedData['perihal']}
Pengirim      : {$validatedData['surat_dari']}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";
                
                WaService::send($target['hp'], $pesan);
            } catch (\Exception $e) {}
        }

        $route = ($validatedData['tipe_surat'] == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Surat berhasil disimpan.');
    }

    public function edit(Surat $surat)
    {
        $allowedStatuses = ['baru_di_bau', 'di_admin_rektor', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'Status surat tidak valid untuk diedit.');
        }
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')->with('satker')->orderBy('name', 'asc')->get();
        return view('bau.surat_edit', compact('surat', 'daftarSatker', 'daftarPegawai'));
    }

    public function update(Request $request, Surat $surat)
    {
        $allowedStatuses = ['baru_di_bau', 'di_admin_rektor', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->route('bau.surat.eksternal')->with('error', 'Status surat tidak valid.');
        }
        
        $validatedData = $request->validate([
            'surat_dari'  => 'required|string|max:255',
            'nomor_surat' => 'required|string|max:255',
            'perihal'     => 'required|string',
            'no_agenda'   => ['required', Rule::unique('surats')->ignore($surat->id)],
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
            'sifat' => 'required|string',
        ]);

        $surat->update($validatedData);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::exists($surat->file_surat)) {
                Storage::delete($surat->file_surat);
            }
            $path = $request->file('file_surat')->store('surat-masuk', 'public');
            $surat->update(['file_surat' => $path]);
        }

        $statusDisposisi = ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        if (in_array($surat->status, $statusDisposisi)) {
            return redirect()->route('bau.disposisi.index')->with('success', 'Data surat diperbarui.');
        }
        
        $route = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Data surat diperbarui.');
    }

public function destroy(Surat $surat)
    {
        // 1. Cek Validasi Status
        if (!in_array($surat->status, ['baru_di_bau', 'di_admin_rektor'])) {
            return redirect()->back()->with('error', 'Gagal hapus. Status surat tidak mengizinkan.');
        }

        try {
            // 2. Hapus File Fisik (Jika ada)
            if($surat->file_surat && Storage::disk('public')->exists($surat->file_surat)) {
                Storage::disk('public')->delete($surat->file_surat);
            }

            // 3. [SOLUSI UTAMA] HAPUS DATA RELASI DULU
            // Hapus semua riwayat yang terkait dengan surat ini
            $surat->riwayats()->delete(); 
            
            // Hapus semua disposisi yang terkait (jika ada) agar aman
            $surat->disposisis()->delete(); 

            // 4. Hapus Surat Utama
            $surat->delete();

            return redirect()->back()->with('success', 'Berhasil dihapus beserta riwayatnya.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus: ' . $e->getMessage());
        }
    }

    // --- ACTIONS ---

    public function forwardToRektor(Request $request, Surat $surat)
    {
        if ($surat->status != 'baru_di_bau') return back()->with('error', 'Sudah diproses.');
        
        $surat->update(['status' => 'di_admin_rektor']);
        
        RiwayatSurat::create([
            'surat_id' => $surat->id, 
            'user_id' => Auth::id(), 
            'status_aksi' => 'Diteruskan ke Admin Rektor', 
            'catatan' => 'Diteruskan ke Admin Rektor.'
        ]);
        
        // WA KE REKTOR
        try {
            $adminRektor = User::where('role', 'admin_rektor')->first();
            if ($adminRektor && $adminRektor->no_hp) {
                
                $tglSurat = $surat->tanggal_surat->format('d-m-Y'); // BUTUH CARBON
                $link = route('login');

                $pesan = 
"📩 *Notifikasi Surat Masuk Baru*

Satker Tujuan : Rektor / Universitas
Tanggal Surat : {$tglSurat}
No. Surat     : {$surat->nomor_surat}
Perihal       : {$surat->perihal}
Pengirim      : {$surat->surat_dari}

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";

                WaService::send($adminRektor->no_hp, $pesan);
            }
        } catch (\Exception $e) {}

        $route = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
        return redirect()->route($route)->with('success', 'Berhasil diteruskan.');
    }

    public function forwardToSatker(Request $request, Surat $surat)
    {
        $validStatuses = ['baru_di_bau', 'didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];

        if (!in_array($surat->status, $validStatuses)) {
             return back()->with('error', 'Status surat tidak valid untuk diteruskan.');
        }

        $statusAkhir = ($surat->tujuan_tipe == 'edaran_semua_satker') ? 'di_satker' : 'di_satker';
        $surat->update(['status' => $statusAkhir]);

        $tujuanList = [];
        $usersToNotify = []; // Array [hp, nama_satker]
        
        // Logika Ambil Penerima (Disposisi / Langsung)
        if ($surat->disposisis->count() > 0) {
            foreach($surat->disposisis as $d) {
                if ($d->tujuanSatker) {
                    $tujuanList[] = $d->tujuanSatker->nama_satker;
                    $admin = User::where('role', 'satker')->where('satker_id', $d->tujuan_satker_id)->first();
                    if ($admin && $admin->no_hp) {
                        $usersToNotify[] = ['hp' => $admin->no_hp, 'nama' => $d->tujuanSatker->nama_satker];
                    }
                }
            }
        } elseif ($surat->tujuan_satker_id) {
            $tujuanList[] = $surat->tujuanSatker->nama_satker;
            $admin = User::where('role', 'satker')->where('satker_id', $surat->tujuan_satker_id)->first();
            if ($admin && $admin->no_hp) {
                $usersToNotify[] = ['hp' => $admin->no_hp, 'nama' => $surat->tujuanSatker->nama_satker];
            }
        }

        // KIRIM WA KE SATKER TUJUAN
        foreach ($usersToNotify as $target) {
            try {
                $tglSurat = $surat->tanggal_surat->format('d-m-Y'); // BUTUH CARBON
                $link = route('login');

                // Pesan Disposisi (Sedikit Beda)
                $pesan = 
"📩 *Notifikasi Disposisi Surat*

Satker Tujuan : {$target['nama']}
Tanggal Surat : {$tglSurat}
No. Surat     : {$surat->nomor_surat}
Perihal       : {$surat->perihal}
Pengirim      : {$surat->surat_dari}
Status        : Diteruskan oleh BAU

Silakan cek dan tindak lanjuti surat tersebut melalui sistem e-Surat.
Detail surat: {$link}

Pesan ini dikirim otomatis oleh Sistem e-Surat.";

                WaService::send($target['hp'], $pesan);
            } catch (\Exception $e) {}
        }

        $namaTujuanString = implode(', ', $tujuanList) ?: 'Tujuan';
        RiwayatSurat::create(['surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Dikirim ke Satker/Penerima', 'catatan' => 'Dikirim ke: ' . $namaTujuanString]);

        return redirect()->route('bau.disposisi.index')->with('success', 'Surat berhasil dikirim ke: ' . $namaTujuanString);
    }

    public function selesaikanLainnya(Request $request, Surat $surat)
    {
        $surat->update(['status' => 'diarsipkan']);
        RiwayatSurat::create(['surat_id' => $surat->id, 'user_id' => Auth::id(), 'status_aksi' => 'Selesai (Manual)', 'catatan' => 'Diarsipkan oleh BAU.']);
        return redirect()->route('bau.disposisi.index')->with('success', 'Berhasil ditandai selesai.');
    }
}