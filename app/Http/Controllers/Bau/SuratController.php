<?php

namespace App\Http\Controllers\Bau;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\Satker;
use App\Models\User;
use App\Models\RiwayatSurat; // Pastikan Model RiwayatSurat diimport
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // Pastikan Auth diimport

class SuratController extends Controller
{
    public function index()
    {
        $semuaSurat = Surat::whereIn('status', ['baru_di_bau', 'di_admin_rektor'])
                            ->latest('diterima_tanggal')
                            ->get(); 
        return view('bau.surat_index', compact('semuaSurat'));
    }

    public function showDisposisi()
    {
        $suratDisposisi = Surat::with('disposisis.tujuanSatker', 'disposisis.klasifikasi')
                            ->whereIn('status', ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi']) // Sesuaikan dengan status disposisi Anda
                            ->latest('diterima_tanggal')
                            ->get(); 
        return view('bau.disposisi_index', compact('suratDisposisi'));
    }

    public function showRiwayat()
    {
        // PERBAIKAN DI SINI:
        // Menambahkan 'arsip_satker' ke dalam daftar status agar surat yang diarsip Satker muncul.
        $suratSelesai = Surat::with('disposisis.tujuanSatker')
                            ->whereIn('status', [
                                'selesai', 
                                'selesai_edaran', 
                                'diarsipkan', 
                                'disimpan',
                                'arsip_satker' // <--- DITAMBAHKAN: Status saat Satker melakukan arsip
                            ])
                            ->latest('diterima_tanggal')
                            ->get(); 
        return view('bau.riwayat_index', compact('suratSelesai'));
    }

    /**
     * MENAMPILKAN DETAIL RIWAYAT (JSON)
     * Method ini dipanggil oleh AJAX/Fetch di Modal Riwayat
     */
    public function showRiwayatDetail(Surat $surat)
    {
        // Eager load relasi riwayats dan user pembuat riwayat
        // Kita urutkan riwayat dari yang terbaru (opsional, tergantung kebutuhan timeline)
        $surat->load(['riwayats' => function($query) {
            $query->latest(); 
        }, 'riwayats.user']);

        return response()->json($surat);
    }

    public function create()
    {
        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')
                            ->with('satker')
                            ->orderBy('name', 'asc')
                            ->get();
        
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
            'tujuan_tipe' => 'required|in:rektor,satker,pegawai,edaran_semua_satker',
            'tujuan_satker_id' => 'required_if:tujuan_tipe,satker|nullable|exists:satkers,id',
            'tujuan_user_id' => 'required_if:tujuan_tipe,pegawai|nullable|exists:users,id',
        ]);

        $filePath = $request->file('file_surat')->store('surat', 'public');

        $status = 'baru_di_bau';
        $statusAksi = 'Surat diinput dan disimpan sebagai draft oleh BAU';
        $pesanRedirect = 'Surat berhasil disimpan sebagai draft.';
        $tujuan_satker_id = null;
        $tujuan_user_id = null;

        if ($request->input('tujuan_tipe') == 'rektor' || $request->input('tujuan_tipe') == 'satker') {
            $status = 'di_admin_rektor';
            $tujuan_satker_id = $validatedData['tujuan_satker_id'];
            $tujuanInfo = ($request->input('tujuan_tipe') == 'rektor') ? 'Rektor' : Satker::find($tujuan_satker_id)->nama_satker;
            $statusAksi = 'Surat diinput dan diteruskan ke Admin Rektor (Tujuan: ' . $tujuanInfo . ')';
            $pesanRedirect = 'Surat berhasil disimpan dan diteruskan ke Admin Rektor.';

        } elseif ($request->input('tujuan_tipe') == 'pegawai') {
            $status = 'selesai';
            $tujuan_user_id = $validatedData['tujuan_user_id'];
            $tujuanInfo = User::find($tujuan_user_id)->name;
            $statusAksi = 'Surat diinput dan diteruskan langsung ke Pegawai (Tujuan: ' . $tujuanInfo . ')';
            $pesanRedirect = 'Surat berhasil disimpan dan diteruskan langsung ke ' . $tujuanInfo . '.';
        
        } elseif ($request->input('tujuan_tipe') == 'edaran_semua_satker') {
            $status = 'selesai_edaran';
            $statusAksi = 'Surat diinput dan diedarkan ke SEMUA SATKER';
            $pesanRedirect = 'Surat Edaran berhasil disimpan dan disebarkan ke semua Satker.';
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
            'tujuan_tipe' => $validatedData['tujuan_tipe'],
            'tujuan_satker_id' => $tujuan_satker_id,
            'tujuan_user_id' => $tujuan_user_id,
        ]);

        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => Auth::id(),
            'status_aksi' => $statusAksi,
            'catatan' => $statusAksi
        ]);

        if ($request->input('tujuan_tipe') == 'edaran_semua_satker') {
            $semuaSatkerId = Satker::pluck('id');
            $surat->satkerPenerima()->attach($semuaSatkerId, ['status' => 'terkirim']);
        }

        return redirect()->route('bau.surat.index')->with('success', $pesanRedirect);
    }

    /**
     * Menampilkan form edit surat.
     */
    public function edit(Surat $surat)
    {
        $allowedStatuses = [
            'baru_di_bau', 
            'di_admin_rektor', 
            'didisposisi',            
            'menunggu_tindak_lanjut', 
            'selesai_disposisi',      
            'disposisi',              
            'disposisi_rektor',       
            'sudah_disposisi'         
        ];

        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'Maaf, surat dengan status "' . $surat->status . '" saat ini tidak dapat diedit. Silakan tambahkan status ini ke Controller.');
        }

        $daftarSatker = Satker::orderBy('nama_satker', 'asc')->get();
        $daftarPegawai = User::where('role', 'pegawai')->with('satker')->orderBy('name', 'asc')->get();
                            
        return view('bau.surat_edit', compact('surat', 'daftarSatker', 'daftarPegawai'));
    }

    /**
     * Memperbarui data surat di database.
     */
    public function update(Request $request, Surat $surat)
    {
        $allowedStatuses = [
            'baru_di_bau', 
            'di_admin_rektor', 
            'didisposisi',            
            'menunggu_tindak_lanjut', 
            'selesai_disposisi',
            'disposisi',
            'disposisi_rektor',
            'sudah_disposisi'
        ];

        if (!in_array($surat->status, $allowedStatuses)) {
            return redirect()->route('bau.surat.index')->with('error', 'Surat dengan status "' . $surat->status . '" tidak dapat diubah.');
        }

        $validatedData = $request->validate([
            'surat_dari'  => 'required|string|max:255',
            'nomor_surat' => 'required|string|max:255',
            'perihal'     => 'required|string',
            'no_agenda'   => ['required', Rule::unique('surats')->ignore($surat->id)],
            'tanggal_surat' => 'required|date',
            'diterima_tanggal' => 'required|date',
        ]);

        $surat->update([
            'surat_dari'       => $validatedData['surat_dari'],
            'nomor_surat'      => $validatedData['nomor_surat'],
            'perihal'          => $validatedData['perihal'],
            'no_agenda'        => $validatedData['no_agenda'],
            'tanggal_surat'    => $validatedData['tanggal_surat'],
            'diterima_tanggal' => $validatedData['diterima_tanggal'],
        ]);

        if ($request->hasFile('file_surat')) {
            if ($surat->file_surat && Storage::exists($surat->file_surat)) {
                Storage::delete($surat->file_surat);
            }
            $path = $request->file('file_surat')->store('surat-masuk', 'public');
            $surat->update(['file_surat' => $path]);
        }

        $statusDisposisi = ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];
        
        if (in_array($surat->status, $statusDisposisi)) {
            return redirect()->route('bau.disposisi.index')->with('success', 'Data surat disposisi berhasil diperbarui.');
        }

        return redirect()->route('bau.surat.index')->with('success', 'Data surat berhasil diperbarui.');
    }

    public function destroy(Surat $surat)
    {
        if (!in_array($surat->status, ['baru_di_bau', 'di_admin_rektor'])) {
            return redirect()->route('bau.surat.index')->with('error', 'Surat yang sudah diproses lebih lanjut tidak dapat dihapus.');
        }
        
        if($surat->file_surat) {
            Storage::disk('public')->delete($surat->file_surat);
        }
        
        $surat->delete();
        return redirect()->route('bau.surat.index')->with('success', 'Surat berhasil dihapus.');
    }

    public function forwardToRektor(Request $request, Surat $surat)
    {
        if ($surat->status != 'baru_di_bau') {
            return back()->with('error', 'Surat ini sudah diproses.');
        }
        $surat->update(['status' => 'di_admin_rektor']);
        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => Auth::id(),
            'status_aksi' => 'Diteruskan ke Admin Rektor',
            'catatan' => 'Surat diteruskan oleh BAU ke Admin Rektor untuk disposisi.'
        ]);
        return redirect()->route('bau.surat.index')->with('success', 'Surat berhasil diteruskan ke Admin Rektor.');
    }

    /**
     * Meneruskan surat disposisi ke Satker (Finalisasi)
     */
    public function forwardToSatker(Request $request, Surat $surat)
    {
        $validStatuses = ['didisposisi', 'menunggu_tindak_lanjut', 'selesai_disposisi', 'disposisi', 'disposisi_rektor', 'sudah_disposisi'];

        if (!in_array($surat->status, $validStatuses)) {
             return back()->with('error', 'Surat belum didisposisi oleh Rektor atau status tidak valid untuk diteruskan.');
        }

        $surat->update(['status' => 'selesai']);

        $disposisiTerakhir = $surat->disposisis()->latest()->first();
        $namaTujuan = 'Satker Tujuan';
        
        if ($disposisiTerakhir && $disposisiTerakhir->tujuanSatker) {
            $namaTujuan = $disposisiTerakhir->tujuanSatker->nama_satker;
        }

        RiwayatSurat::create([
            'surat_id' => $surat->id,
            'user_id' => Auth::id(), 
            'status_aksi' => 'Diteruskan ke Satker',
            'catatan' => 'Surat hasil disposisi Rektor telah diteruskan oleh BAU ke ' . $namaTujuan . '.'
        ]);

        return redirect()->route('bau.disposisi.index')->with('success', 'Surat berhasil diteruskan ke ' . $namaTujuan . ' (Status: Selesai).');
    }
}